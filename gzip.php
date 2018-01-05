<?php

//use Patchwork\JSqueeze;

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip
 *
 * @copyright   Copyright (C) 2005 - 2016 Inimov.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

spl_autoload_register(function ($name) {

    switch(strtolower($name)):

        case 'patchwork\jsqueeze':

            require __DIR__.'/lib/JSqueeze.php';
            break;

        case 'patchwork\cssmin':

            require __DIR__.'/lib/cssmin.php';
            break;

        case 'gzip\gziphelper':

            require __DIR__.'/helper.php';
            break;

        default:

            $file = __DIR__.'/lib/'.str_replace('\\', '/', $name).'.php';

            if(is_file($file)) {
                    
                require $file; 
            }

            break;

    endswitch;
});

class PlgSystemGzip extends JPlugin
{
    protected $options = [];
    protected $worker_id = '';

    public function onAfterRoute() {

        $document = JFactory::getDocument();

        if(JFactory::getApplication()->isSite() && $document->getType() == 'html') {

            if(!empty($this->options['debug'])) {

                $document->addScriptDeclaration('console.log(document.documentElement.dataset.prf);');
            }

            if(!empty($this->options['pwaenabled'])) {

                $document->addScriptDeclaration(str_replace(['{CACHE_NAME}', '{defaultStrategy}', '{scope}', '{debug}'], ['v_'.$this->worker_id, empty($this->options['pwa_network_strategy']) ? 'nf' : $this->options['pwa_network_strategy'], \JUri::root(true) . '/', $this->worker_id.(empty($this->options['debug']) ? '.min' : '')], file_get_contents(__DIR__.'/worker/dist/browser.min.js')));
            }
        }
    }

    public function onAfterInitialise() {

        if(JFactory::getApplication()->isSite()) {

            $options = $this->params->get('gzip');

            if(!empty($options)) {

                $this->options = (array) $options;
            }

            if(!empty($this->options['pwaenabled'])) {

                $this->worker_id = file_get_contents(__DIR__.'/worker_version');
            }

            $dirname = dirname($_SERVER['SCRIPT_NAME']);

            if($dirname != '/') {

                $dirname .= '/';
            }

            // fetch worker.js
            if(preg_match('#^'.$dirname.'worker([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

            //    $debug = ''; // $this->params->get('gzip.debug') ? '' : '.min';

                $file = __DIR__.'/worker/dist/serviceworker'.$debug.'.js';

                header('Cache-Control: max-age=86400');
                header('Content-Type: text/javascript;charset=utf-8');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

                $exclude_urls = empty($this->options['pwa_app_cache_exclude_urls']) ? [] : preg_split('#\s#s', $this->options['pwa_app_cache_exclude_urls'], -1, PREG_SPLIT_NO_EMPTY);
                
                $exclude_urls[] = JUri::root(true).'/administrator';
                $exclude_urls = array_values(array_unique(array_filter($exclude_urls)));

                echo str_replace(['{CACHE_NAME}', '{defaultStrategy}', '{scope}', '"{exclude_urls}"'], ['v_'.$this->worker_id, empty($this->options['pwa_network_strategy']) ? 'nf' : $this->options['pwa_network_strategy'], \JUri::root(true), json_encode($exclude_urls)], file_get_contents($file));
                exit;
            }
            
            $document = JFactory::getDocument();

            // fetch worker.js
            if(!empty($this->options['pwa_app_manifest'])) {
                
                if(preg_match('#^'.$dirname.'manifest([a-z0-9.]+)?\.json#i', $_SERVER['REQUEST_URI'])) {

                    $debug = ''; // $this->params->get('gzip.debug') ? '' : '.min';

                //    $file = __DIR__.'/worker/dist/serviceworker'.$debug.'.js';

                //    header('Cache-Control: max-age=86400');
                    header('Content-Type: application/json;charset=utf-8');
                //    header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

                    $config = JFactory::getConfig();

                    $short_name = $this->options['pwa_app_short_name'] === '' ? $_SERVER['SERVER_NAME'] : $this->options['pwa_app_short_name'];
                    $name = $this->options['pwa_app_name'] === '' ? $config->get('sitename') : $this->options['pwa_app_name'];
                    $description = $this->options['pwa_app_description'] === '' ? $config->get('MetaDesc') : $this->options['pwa_app_description'];
                    $start_url = $this->options['pwa_app_start_url'] === '' ? JURI::root(true).'/' : $this->options['pwa_app_start_url'];

                    $start_url .= (strpos($start_url, '?') === false ? '?' : '&'). 'utm_source=web_app_manifest';

                    $manifest = [
                        'scope' => JURI::root(true).'/',
                        'short_name' => substr($short_name, 0, 12),
                        'name' => $name,
                        'description' => $description,
                    //    'icons' => $this->options['pwa_app_icons'],
                        'start_url' => $start_url,
                        'background_color' => $this->options['pwa_app_bg_color'],
                        'theme_color' => $this->options['pwa_app_theme_color'],
                        'display' => $this->options['pwa_app_display']
                    ];

                    $native_apps = [];

                    if(!empty($this->options['pwa_app_native_android'])) {

                        $native_apps[] = [

                            'platform' => 'play',
                            'url' => $this->options['pwa_app_native_android']
                        ];
                    }

                    if(!empty($this->options['pwa_app_native_ios'])) {

                        $native_apps[] = [

                            'platform' => 'itunes',
                            'url' => $this->options['pwa_app_native_ios']
                        ];
                    }

                    if(!empty($native_apps)) {

                        $manifest['prefer_related_applications'] = (bool) $this->options['pwa_app_native'];
                        $manifest['related_applications'] = $native_apps;
                    }

                    for($i = 1; $i < 7; $i++) {

                        $name = 'pwa_app_icons_'.$i;

                        if(!empty($this->options[$name])) {

                            $file = $this->options[$name];

                            if(is_file($file)) {

                                $size = getimagesize($file);

                                $max = max($size[0], $size[1]);

                                $manifest['icons'][] = [

                                    'src' => Gzip\GZipHelper::url(JUri::root(true).'/'.$file),
                                    'sizes' => $max.'x'.$max,
                                    'type' => image_type_to_mime_type($size[2])
                                ];
                            }
                        }
                    }

                    echo json_encode(array_filter($manifest, function ($value) {

                        if(is_array($value)) {

                            $value = array_filter($value, function ($v) { return $v !== ''; });
                        }

                        return $value !== '' && !is_null($value) && count($value) != 0;
                    }));

                //    echo str_replace(['{CACHE_NAME}', '{defaultStrategy}', '{scope}'], ['v_'.$this->worker_id, empty($this->options['pwa_network_strategy']) ? 'nf' : $this->options['pwa_network_strategy'], \JUri::root(true)], file_get_contents($file));
                    exit;
                }

                if(method_exists($document, 'addHeadLink')) {

                    $document->addHeadLink(JURI::root(true).'/manifest.json', 'manifest');
                }

                if(!empty($this->options['pwa_app_theme_color'])) {
                        
                    // setMetaData
                    $document->setMetaData('theme-color', $this->options['pwa_app_theme_color']);
                }
            }
            /*
            <meta property=al:android:package content=com.hostedcloudvideo.android>
<meta property=al:android:app_name content="Hosted Cloud Video">
<meta property=al:android:url content=intent://secure-login#Intent;package=com.hostedcloudvideo.android;scheme=hosted-cloud-video;end;>
<link rel=external data-app=android href=//play.google.com/store/apps/details?id=com.hostedcloudvideo.android>
<meta property=al:ios:app_store_id content=1087088968><meta property=al:ios:app_name content="Hosted Cloud Video">
<meta property=al:ios:url content=hosted-cloud-video://secure-login><meta name=apple-itunes-app content="app-id=1087088968, app-argument=/secure-login">
<link rel=external data-app=ios href=//itunes.apple.com/us/app/hosted-cloud-video/id1087088968?mt=8>

            */

            if(method_exists($document, 'addHeadLink')) {

                $name = $this->options['pwa_app_name'] === '' ? $config->get('sitename') : $this->options['pwa_app_name'];
                
                if(!empty($this->options['pwa_app_native_android'])) {

                    $url = $this->options['pwa_app_native_android'];

                //    if(preg_match())

                    $document->addHeadLink('<link rel="external" data-app="android" href="'.$url.'">');
                }

                if(!empty($this->options['pwa_app_native_ios'])) {

                    $url = $this->options['pwa_app_native_ios'];

                    $document->addHeadLink('<link rel="external" data-app="ios" href="'.$url.'">');
                }
            }


            // "start_url": "./?utm_source=web_app_manifest",
            // manifeste url
        }
    }

    public function onAfterRender() {

        $app = JFactory::getApplication();

        if(!$app->isSite() || JFactory::getDocument()->getType() != 'html') {

            return;
        }

        $options = $this->options;
        $prefix = 'cache/z/';

        if(!empty($options['pwaenabled'])) {

            if(empty($options['pwa_network_strategy'])) {

                $options['pwa_network_strategy'] = 'un';
            }

            $prefix .= $options['pwa_network_strategy'].'/';
            Gzip\GZipHelper::$pwa_network_strategy = $options['pwa_network_strategy'].'/';
        }

        if(!empty($options['jsignore'])) {

            $options['jsignore'] = preg_split('#\s+#s', $options['jsignore'], -1, PREG_SPLIT_NO_EMPTY);
        }

        if(!empty($options['jsremove'])) {

            $options['jsremove'] = preg_split('#\s+#s', $options['jsremove'], -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach (['js', 'css', 'img', 'ch'] as $key) {

            $path = $key.'/';

            if (isset($options['hashfiles']) && $options['hashfiles'] == 'content') {

                $path .= '1/';
            }

            if(!is_dir($prefix.$path)) {

                $old_mask = umask();

                umask(022);
                mkdir($prefix.$path, 0755, true);
                umask($old_mask);
            }

            $options[$key.'_path'] = $prefix.$path;
        }

        $body = $app->getBody();

        $profiler = JProfiler::getInstance('_gzip_');

        Gzip\GZipHelper::$options = $options;

        $body = Gzip\GZipHelper::parseImages($body, $options);
        $body = Gzip\GZipHelper::parseCss($body, $options);
        $body = Gzip\GZipHelper::parseScripts($body, $options);
        $body = Gzip\GZipHelper::parseURLs($body, $options);
    //    $body = Gzip\GZipHelper::parsePWA($body, $options);

        $profiler->mark('done');

        if(!empty($options['debug'])) {

            $quote = empty($options['minifyhtml']) ? '"' : '';
            $body = preg_replace('#<html #', '<html data-prf='.$quote. htmlspecialchars(implode("\n", array_map(function ($mark) {

                $m = [

                  'time' => +$mark->time,
                  'totalTime' => $mark->totalTime,
                  'label' => $mark->label,
                  'memory' => +$mark->memory,
                  'totalMemory' => $mark->totalMemory
                ];

                return json_encode($m);

            }, array_merge(Gzip\GZipHelper::$marks, $profiler->getMarks()))), ENT_QUOTES).$quote.' ', $body, 1);
        }

        if(!empty($options['pwacachepages']) && !empty($options['pwacachelifetime'])) {

            $app->allowCache(true);

            $dt = gmdate('D, d M Y H:i:s', time()).' GMT';

            $app->setHeader('Date', $dt, true );
            $app->setHeader('Last-Modified', $dt, true );
            $app->setHeader('Cache-Control', /*'no-cache,no-store,'.*/ 'max-age='.(int) $options['pwacachelifetime'].',must-revalidate', true);
        }

        $app->setBody($body);
    }
}
