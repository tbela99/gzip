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

require __DIR__.'/autoload.php';

class PlgSystemGzip extends JPlugin
{
    protected $options = [];
    protected $worker_id = '';
    protected $manifest_id = '';

    public function onInstallerAfterInstaller($model, $package, $installer, $result) {

        if ($result && (string) $installer->manifest->name == 'plg_system_gzip') {
                
            $this->cleanCache();
        }
    }

    public function onContentPrepareForm(JForm $form, $data) {

        switch ($form->getName()) {

            case 'com_content.article':
                
                $document = JFactory::getDocument();
                
                $document->addStylesheet(JURI::root(true).'/plugins/system/gzip/push/css/form.css');
                $document->addScript(JURI::root(true).'/plugins/system/gzip/js/dist/fetch.js');
                $document->addScript(JURI::root(true).'/plugins/system/gzip/js/lib/lib.js');
                $document->addScript(JURI::root(true).'/plugins/system/gzip/js/lib/lib.ready.js');
                $document->addScript(JURI::root(true).'/plugins/system/gzip/push/js/form.js');

                JFactory::getLanguage()->load('plg_system_gzip', __DIR__);

                JFormHelper::addFieldPath(__DIR__.'/push/fields');

                $root = dom_import_simplexml($form->getXml());

                $testUser = $this->params->get('gzip.onesignal.web_push_test_user');

                foreach(simplexml_load_file(__DIR__.'/push/forms/com_content/article.xml')->children() as $child) {

                    if (!empty($testUser)) {

                        foreach($child->xpath('//fields[@name="push"]') as $field) {

                            foreach($field->xpath('./field[@name="sendtest"]') as $node) {

                                $option = $node->addChild('option');

                                $option['value'] = $testUser;
                                $option['text'] = $testUser;
                            }

                            foreach($field->xpath('./field[@name="web_push_test_title"]') as $node) {

                                $node['default'] = $data->title;
                            }

                            foreach($field->xpath('./field[@name="web_push_test_content"]') as $node) {

                                $node['default'] = strip_tags($data->introtext);
                            }
                        }
                    }

                    $root->insertBefore($root->ownerDocument->importNode(dom_import_simplexml($child), true), $root->firstChild);
                }

            break;

            case 'com_plugins.plugin':

                $object = $data;

                if (is_array($data)) {

                    $object = new \Joomla\Registry\Registry($data);
                }

                if (is_callable([$data, 'getProperties'])) {

                    $object = new \Joomla\Registry\Registry($data->getProperties());
                }
                
                if ($object->get('type') == 'plugin' && $object->get('element') == 'gzip' && $object->get('folder') == 'system') {
                
                    foreach ($form->getXml()->xpath('//field[@name="admin_secret"]') as $field) {

                        $field['description'] = JText::sprintf('PLG_GZIP_FIELD_ADMIN_SECRET_DESCRIPTION', \JURI::root());
                        break;
                    }
                }

                break;
        }
    }

    public function onAfterRoute() {

        if(JFactory::getApplication()->isSite()) {
            
            $document = JFactory::getDocument();

            if($document->getType() == 'html') {
                    
                if(!empty($this->options['debug'])) {

                    $document->addScriptDeclaration('console.log(document.documentElement.dataset.prf);');
                }

                $script = '';

                if(!empty($this->options['pwaenabled'])) {                    

                    $script = str_replace(['{CACHE_NAME}', '{defaultStrategy}', '{scope}', '{debug}'], ['v_'.$this->worker_id, empty($this->options['pwa_network_strategy']) ? 'nf' : $this->options['pwa_network_strategy'], \JUri::root(true) . '/', $this->worker_id.(empty($this->options['debug_pwa']) ? '.min' : '')], file_get_contents(__DIR__.'/worker/dist/browser.min.js'));

                    $onesignal = (array) $this->options['onesignal'];
                    if(!empty($onesignal['enabled']) && !empty($onesignal['web_push_app_id'])) {

                        $script .= str_replace(['{APP_ID}'], [$onesignal['web_push_app_id']], file_get_contents(__DIR__.'/worker/dist/onesignal.min.js'));
                    }
                }

                else {
                    
                    $script = str_replace(['{CACHE_NAME}', '{defaultStrategy}', '{scope}', '{debug}'], ['v_'.$this->worker_id, empty($this->options['pwa_network_strategy']) ? 'nf' : $this->options['pwa_network_strategy'], \JUri::root(true) . '/', $this->worker_id.(empty($this->options['debug_pwa']) ? '.min' : '')], file_get_contents(__DIR__.'/worker/dist/browser.uninstall.min.js'));

                }

                $document->addScriptDeclaration( $script);
            }
        }
    }
    
    public function onExtensionBeforeSave($context, $table, $isNew, $data = []) {

		//  pattern="^([a-zA-Z0-9_-]*)$"
        if ($context == 'com_plugins.plugin' && !empty($data) && $data['type'] == 'plugin' && $data['element'] == 'gzip') {

            $options = $data['params']['gzip'];
			
			if (isset($options['admin_secret'])) {
				
				if (!preg_match('#^([a-zA-Z0-9_-]*)$#', $options['admin_secret'])) {
					
					throw new \Exception('Invalid admin secret. You can only use numbers, letters, "_" and "-"', 400);
				}
			}
        }

        return true;
    }

    public function onExtensionAfterSave($context, $table, $isNew, $data = []) {

        if ($context == 'com_plugins.plugin' && !empty($data) && $data['type'] == 'plugin' && $data['element'] == 'gzip') {

            $options = $data['params']['gzip'];

            $this->cleanCache();

            $this->updateManifest($options);
            $this->updateServiceWorker($options);
        }

        return true;
    }

    public function onAfterInitialise() {

        $app = JFactory::getApplication();

        if($app->isSite()) {

            $options = $this->params->get('gzip');

            if(!empty($options)) {

                $this->options = (array) $options;
            }

            $this->options['parse_url_attr'] = empty($this->options['parse_url_attr']) ? [] : array_flip(array_map('strtolower', preg_split('#[\s,]#', $this->options['parse_url_attr'], -1, PREG_SPLIT_NO_EMPTY)));
            $this->options['parse_url_attr']['href'] = '';
            $this->options['parse_url_attr']['src'] = '';
            
            // do not render blank js file when service worker is disabled
        //    if(!empty($this->options['pwaenabled'])) {

                $file = JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/worker_version';

                if (!is_file($file)) {

                    $this->updateServiceWorker($this->options);
                }

                $this->worker_id = file_get_contents(JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/worker_version');
                $this->manifest_id = file_get_contents(JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/manifest_version');
        //    }

            $dirname = dirname($_SERVER['SCRIPT_NAME']);

            if($dirname != '/') {

                $dirname .= '/';
            }

            // fetch worker.js
            if(preg_match('#^'.$dirname.'worker([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

                $debug = $this->params->get('gzip.debug_pwa') ? '' : '.min';

                $file = JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/serviceworker'.$debug.'.js';

                if (!is_file($file)) {

                    $this->updateManifest($this->options);
                }

                header('Cache-Control: max-age=86400');
                header('Content-Type: text/javascript;charset=utf-8');
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

                readfile($file);
                exit;
            }
            
            $document = JFactory::getDocument();

            // fetch worker.js
            if(!empty($this->options['pwa_app_manifest'])) {
            
                $file = JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/manifest.json';

                if (!is_file($file)) {

                    $this->updateManifest($this->options);
                }

                if(preg_match('#^'.$dirname.'manifest([a-z0-9.]+)?\.json#i', $_SERVER['REQUEST_URI'])) {

                    $debug = '';

                    header('Cache-Control: max-age=86400');
                    header('Content-Type: application/manifest+json;charset=utf-8');
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));
                    
                    readfile($file);
                    exit;
                }

                if(method_exists($document, 'addHeadLink')) {

                    $document->addHeadLink(JURI::root(true).'/manifest'.$this->manifest_id.'.json', 'manifest');
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

                'id' => preg_replace('#.*?(com\.[a-z0-9.]+).*#', '$1', $options['pwa_app_native_android'])
            ];
        }

        if(!empty($options['pwa_app_native_ios'])) {

            $native_apps[] = [

                'platform' => 'itunes',
                'url' => $options['pwa_app_native_ios'],
                'id' => preg_replace('#.*?/id(\d+).*#', '$1', $options['pwa_app_native_ios'])
            */

            if(method_exists($document, 'addHeadLink')) {

            //    $name = $this->options['pwa_app_name'] === '' ? $config->get('sitename') : $this->options['pwa_app_name'];
                
                if(!empty($this->options['pwa_app_native_android'])) {

                    $url = $this->options['pwa_app_native_android'];

                    $document->addHeadLink($url, 'external', 'rel', ['data-app' => 'android']);
                //    $id = preg_replace('#.*?(com\.[a-z0-9.]+).*#', '$1', $this->options['pwa_app_native_android']);
                }

                if(!empty($this->options['pwa_app_native_ios'])) {

                    $url = $this->options['pwa_app_native_ios'];

                    $document->addHeadLink($url, 'external', 'rel', ['data-app' => 'ios']);
                    //$id = preg_replace('#.*?/id(\d+).*#', '$1', $this->options['pwa_app_native_ios']);
                }
            }

            // "start_url": "./?utm_source=web_app_manifest",
            // manifeste url
        }

        else if ($app->isAdmin()) {

            $secret = $this->params->get('gzip.admin_secret');

        //    var_export($secret);die;

            if (!is_null($secret) && $_SERVER['REQUEST_METHOD'] == 'GET' && JFactory::getUser()->get('id') == 0 && !array_key_exists($secret, $_GET)) {

                $app->redirect(JURI::root(true).'/');
            }
            
            if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task']) && strpos($_POST['task'], 'gzip.') === 0) {

                $token = JSession::getFormToken();

                if (isset($_POST[$token]) && $_POST[$token] == 1) {
                    
                    $data = $app->input->post->get('jform', [], 'array');
                    
                    $router = JApplicationSite::getRouter();
                    
                    $uri = $router->build('index.php?option=com_content&view=article&id='.$data['id'].'&catid='.$data['catid']);

                    $result = OneSignal\OneSignal::sendArticlePushNotification(
                            $this->params->get('gzip.onesignal.web_push_app_id'), 
                            $this->params->get('gzip.onesignal.web_push_api_key'), 
                            !empty($data['push']['sendtest']) ? $data['push']['sendtest'] : null,
                            $data['title'], 
                            JUri::root().str_replace(JUri::base(true).'/', '', $uri->toString()), 
                            $data['id'], 
                            $data['catid']
                        );

                    header('Content-Type: application/json; charset=utf8');
                    echo json_encode($result);
                }

                exit;
            }
        }
    }

    public function onAfterDispatch() {

        $document = JFactory::getDocument();

        $generator = $this->params->get('gzip.metagenerator');

        if(!is_null($generator)) {

            $document->setGenerator($generator);
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

        if(!empty($options['cssignore'])) {

            $options['cssignore'] = preg_split('#\s+#s', $options['cssignore'], -1, PREG_SPLIT_NO_EMPTY);
        }

        if(!empty($options['cssremove'])) {

            $options['cssremove'] = preg_split('#\s+#s', $options['cssremove'], -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach (['js', 'css', 'img', 'ch'] as $key) {

            $path = $_SERVER['SERVER_NAME'].'/'.$key.'/';

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

        $profiler = JProfiler::getInstance('Application');

        Gzip\GZipHelper::$options = $options;

        $profiler->mark('beforeParseImages');
        $body = Gzip\GZipHelper::parseImages($body, $options);

        $profiler->mark('afterParseImages');
        $body = Gzip\GZipHelper::parseCss($body, $options);

        $profiler->mark('afterParseCss');
        $body = Gzip\GZipHelper::parseScripts($body, $options);
        
        $profiler->mark('afterParseScripts');
        $body = Gzip\GZipHelper::parseURLs($body, $options);

        $profiler->mark('afterParseURLs');

        $app->setBody($body);
    }

    protected function updateManifest($options) {
        
        $path = JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/';

        if(!is_dir($path)) {

            $old_mask = umask();

            umask(022);
            mkdir($path, 0755, true);
            umask($old_mask);            
        }

        $config = JFactory::getConfig();

        $short_name = $options['pwa_app_short_name'] === '' ? $_SERVER['SERVER_NAME'] : $options['pwa_app_short_name'];
        $name = $options['pwa_app_name'] === '' ? $config->get('sitename') : $options['pwa_app_name'];
        $description = $options['pwa_app_description'] === '' ? $config->get('MetaDesc') : $options['pwa_app_description'];
        $start_url = $options['pwa_app_start_url'] === '' ? JURI::root(true).'/' : $options['pwa_app_start_url'];

        $start_url .= (strpos($start_url, '?') === false ? '?' : '&'). 'utm_source=web_app_manifest';

        $manifest = [
            'scope' => JURI::root(true).'/',
            'short_name' => substr($short_name, 0, 12),
            'name' => $name,
            'description' => $description,
            'start_url' => $start_url,
            'background_color' => $options['pwa_app_bg_color'],
            'theme_color' => $options['pwa_app_theme_color'],
            'display' => $options['pwa_app_display']
        ];

        if(!empty($options['onesignal'])) {

            $manifest['gcm_sender_id'] = '482941778795';
        }

        $native_apps = [];

        if(!empty($options['pwa_app_native_android'])) {

            $native_apps[] = [

                'platform' => 'play',
                'url' => $options['pwa_app_native_android'],
                'id' => preg_replace('#.*?(com\.[a-z0-9.]+).*#', '$1', $options['pwa_app_native_android'])
            ];
        }

        if(!empty($options['pwa_app_native_ios'])) {

            $native_apps[] = [

                'platform' => 'itunes',
                'url' => $options['pwa_app_native_ios'],
                'id' => preg_replace('#.*?/id(\d+).*#', '$1', $options['pwa_app_native_ios'])
            ];
        }

        if(!empty($native_apps)) {

            $manifest['prefer_related_applications'] = (bool) $options['pwa_app_native'];
            $manifest['related_applications'] = $native_apps;
        }

        if(!empty($options['pwa_app_icons_path'])) {

            $dir = JPATH_SITE.'/images/'.$options['pwa_app_icons_path'];

            if(is_dir($dir)) {

                foreach(new DirectoryIterator($dir) as $file) {

                    if($file->isFile() && preg_match('#\.((jpg)|(png)|(webp))$#i', $file, $match)) {

                        $size = getimagesize($file->getPathName());

                        $max = max($size[0], $size[1]);

                        $manifest['icons'][] = [

                            'src' => JUri::root(true).'/images/'.$options['pwa_app_icons_path'].'/'.$file,
                            'sizes' => $size[0].'x'.$size[1],
                            'type' => image_type_to_mime_type($size[2])
                        ];
                    }
                }
            }
        }

        file_put_contents($path.'manifest.json', json_encode(array_filter($manifest, function ($value) {

            if(is_array($value)) {

                $value = array_filter($value, function ($v) { return $v !== ''; });
            }

            return $value !== '' && !is_null($value) && count($value) != 0;
        })));
        
        file_put_contents($path.'manifest_version', hash_file('sha1', $path.'manifest.json'));
    }
    
    protected function updateServiceWorker($options) {

        $path = JPATH_SITE.'/cache/z/app/'.$_SERVER['SERVER_NAME'].'/';

        if(!is_dir($path)) {

            $old_mask = umask();

            umask(022);
            mkdir($path, 0755, true);
            umask($old_mask);            
        }

        $preloaded_urls = empty($options['pwa_app_cache_urls']) ? [] : preg_split('#\s#s', $options['pwa_app_cache_urls'], -1, PREG_SPLIT_NO_EMPTY);
        $exclude_urls = empty($options['pwa_app_cache_exclude_urls']) ? [] : preg_split('#\s#s', $options['pwa_app_cache_exclude_urls'], -1, PREG_SPLIT_NO_EMPTY);
                
        $exclude_urls[] = JUri::root(true).'/administrator';
        $exclude_urls = array_values(array_unique(array_filter($exclude_urls)));

        $import_scripts = '';
        $onesignal = (array) $options['onesignal'];

        if(!empty($onesignal['enabled'])) {

            $import_scripts .= 'importScripts("https://cdn.onesignal.com/sdks/OneSignalSDK.js")';
        }

        $hash = hash('sha1', json_encode($options).file_get_contents(__DIR__.'/worker_version'));

        $search = ['{CACHE_NAME}', '{defaultStrategy}', '{scope}', '"{exclude_urls}"', '"{preloaded_urls}"', '"{IMPORT_SCRIPTS}"'];
        $replace = ['v_'.$hash, empty($options['pwa_network_strategy']) ? 'nf' : $options['pwa_network_strategy'], \JUri::root(true), json_encode($exclude_urls), json_encode($preloaded_urls), $import_scripts];

        $data = str_replace($search, $replace, file_get_contents(__DIR__.'/worker/dist/serviceworker.min.js'));

        file_put_contents($path.'serviceworker.js', str_replace($search, $replace, file_get_contents(__DIR__.'/worker/dist/serviceworker.js')));
        file_put_contents($path.'serviceworker.min.js', $data);
        // => update the service worker whenever the manifest changes
        file_put_contents($path.'worker_version', hash('sha1', json_encode($options).$hash.$data));
    }

    protected function cleanCache() {
    
        //
        $path = JPATH_SITE.'/cache/z/app/';

        if (is_dir($path)) {

            $paths = [new DirectoryIterator($path)];

            while(count($parts) > 0) {

                $dir = array_shift($parts);

                foreach($dir as $file) {

                    if ($file->isDir() && !$file->isDot()) {

                        $parts[] = $file;

                        foreach(
                            [
                                "manifest.json", 
                                "manifest_version",
                                "serviceworker.js", 
                                "serviceworker.min.js", 
                                "worker_version" 
                            ] as $f) {

                            $f = $file->getPathName().'/'.$f;

                            if(is_file($f)) {

                                unlink($f);
                            }
                        }
                    }
                }
            }
        }        
    }
}
