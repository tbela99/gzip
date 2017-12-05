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

class PlgSystemGzip extends JPlugin
{
    protected $options = [];

    public function onAfterRoute() {

        $options = $this->params->get('gzip');

        if(!empty($options)) {

            $this->options = (array) $options;
        }

        $document = JFactory::getDocument();

        if(JFactory::getApplication()->isSite() && $document->getType() == 'html') {


        //    $debug =  ;

            if(!empty($this->options['debug'])) {

                $document->addScriptDeclaration('console.log(document.documentElement.dataset.prf);');
            }

            if(!empty($this->options['pwaenabled'])) {

                    $document->addScriptDeclaration(str_replace(['{scope}', '{debug}'], [\JUri::root(true) . '/', empty($this->options['debug']) ? '.min' : ''], file_get_contents(__DIR__.'/worker/dist/browser.min.js')));
                }
            }
    }

    public function onAfterInitialise() {

        if(JFactory::getApplication()->isSite() && preg_match('#'.preg_quote(dirname($_SERVER['SCRIPT_NAME']).'/').'worker([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

        //    $debug = '';
            $debug = ''; // $this->params->get('gzip.debug') ? '' : '.min';
            $file = __DIR__.'/worker/dist/serviceworker'.$debug.'.js';

            header('Cache-Control: max-age=86400');
            header('Content-Type: text/javascript;charset=utf-8');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

            echo str_replace('{scope}', JURI::root(true) ,file_get_contents($file));
            exit;
        }
    }

    public function onAfterRender() {

        $app = JFactory::getApplication();

        if(!$app->isSite() || JFactory::getDocument()->getType() != 'html') {

            return;
        }

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

                    require __DIR__.'/lib/'.str_replace('\\', '/', $name).'.php';
                    break;

            endswitch;
        });

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
