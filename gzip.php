<?php

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

defined('_JEXEC') or die;

require __DIR__ . '/autoload.php';

use Gzip\GZipHelper as GZipHelper;
use Joomla\CMS\Factory as JFactory;
use Joomla\Registry\Registry as Registry;

class PlgSystemGzip extends JPlugin
{
	protected $options = [];
	protected $worker_id = '';
	protected $manifest_id = '';
	protected $route = '';

	public function onContentPrepareForm(JForm $form, $data)
	{

		switch ($form->getName()) {

			case 'com_content.article':

				$document = JFactory::getDocument();

				$document->addStylesheet(JURI::root(true) . '/plugins/system/gzip/push/css/form.css');
				$document->addScript(JURI::root(true) . '/plugins/system/gzip/js/dist/fetch.js');
				$document->addScript(JURI::root(true) . '/plugins/system/gzip/js/lib/lib.js');
				$document->addScript(JURI::root(true) . '/plugins/system/gzip/js/lib/lib.ready.js');
				$document->addScript(JURI::root(true) . '/plugins/system/gzip/push/js/form.js');

				JFactory::getLanguage()->load('plg_system_gzip', __DIR__);
				JFormHelper::addFieldPath(__DIR__ . '/push/fields');

				$root = dom_import_simplexml($form->getXml());

				$testUser = $this->params->get('gzip.onesignal.web_push_test_user');

				foreach (simplexml_load_file(__DIR__ . '/push/forms/com_content/article.xml')->children() as $child) {

					if (!empty($testUser)) {

						foreach ($child->xpath('//fields[@name="push"]') as $field) {

							foreach ($field->xpath('./field[@name="sendtest"]') as $node) {

								$option = $node->addChild('option');

								$option['value'] = $testUser;
								$option['text'] = $testUser;
							}

							foreach ($field->xpath('./field[@name="web_push_test_title"]') as $node) {

								$node['default'] = $data->title;
							}

							foreach ($field->xpath('./field[@name="web_push_test_content"]') as $node) {

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

					$object = new Registry($data);
				}

				if (is_callable([$data, 'getProperties'])) {

					$object = new Registry($data->getProperties());
				}

				if ($object->get('type') == 'plugin' && $object->get('element') == 'gzip' && $object->get('folder') == 'system') {

					$xml = $form->getXml();
					$keys = array_keys(GZipHelper::$accepted);

					foreach ($xml->xpath('//fieldset[@name="basic"]/fields[@name="gzip"]/fields[@name="expiring_links"]/field[@name="file_type"]|//fieldset[@name="cdn"]/fields[@name="gzip"]/field[@name="cdn_types"]') as $field) {

						//	reset($keys);

						foreach ($keys as $key) {

							$node = $field->addChild('option', strtoupper($key));
							$node['value'] = $key;
						}

						//     break;
					}

					foreach ($xml->xpath('//field[@name="admin_secret"]') as $field) {

						$field['description'] = JText::sprintf('PLG_GZIP_FIELD_ADMIN_SECRET_DESCRIPTION', JURI::root());
						break;
					}

					JFactory::getDocument()->addStyleDeclaration('.no-checkboxes>legend{display:none}');
				}

				break;
		}
	}

	public function onAfterRoute()
	{

		$app = JFactory::$application;

		$document = JFactory::getDocument();
		$docType = $document->getType();

		$debug = empty($this->options['debug']) ? '.min' : '';

		if ($app->isClient('administrator')) {

			$input = $app->input;

			if ($input->get('option') == 'com_plugins' &&
				$input->get('view') == 'plugin' &&
				$input->post->get('manifest_preview') == 1) {

				header('Cache-Control: max-age=0');

				$data = $app->input->post->get('jform', [], 'array');
				echo $this->buildManifest(isset($data['params']['gzip']) ? $data['params']['gzip'] : []);

				$app->close();
			}

			if ($docType == 'html') {

				$script = str_replace(
					[
						'{scope}',
						'{debug}'
					], [
					JUri::base(true) . '/',
					$this->worker_id . $debug
				], file_get_contents(__DIR__ . '/worker/dist/browser.administrator' . $debug . '.js'));
				$document->addScriptDeclaration($script);
			}
		}

		if ($app->isClient('site')) {

			if ($docType == 'html') {

				$script = '';

				if (!empty($this->options['pwaenabled'])) {

					if ($this->options['pwaenabled'] == 1) {

						$debug = empty($this->options['debug']) ? '.min' : '';
						$debug_pwa = (empty($this->options['debug_pwa']) ? '.min' : '');
						$data = file_get_contents(JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/browser' . $debug_pwa . '.js');

						if (empty($debug)) {

							// remove those multiline comments
							$data = preg_replace('#\/\*.*?\*\/#s', '', $data);
						}

						$script = $data;

						$onesignal = (array)$this->options['onesignal'];
						if (!empty($onesignal['enabled']) && !empty($onesignal['web_push_app_id'])) {

							$script .= str_replace(['{APP_ID}'], [$onesignal['web_push_app_id']], file_get_contents(__DIR__ . '/worker/dist/onesignal.min.js'));
						}

						$document->addStyleDeclaration(file_get_contents(__DIR__ . '/worker/css/pwa-app.css'));
					} // force service worker uninstall
					else if ($this->options['pwaenabled'] == -1) {

						$debug_pwa = empty($this->options['debug_pwa']) ? '.min' : '';
						$script = file_get_contents(JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/browser.uninstall' . $debug_pwa . '.js');
					}
				}

				if (!empty($script)) {

					$document->addScriptDeclaration($script);
				}
			}
		}
	}

	public function onExtensionBeforeSave($context, $table, $isNew, $data = [])
	{

		//  pattern="^([a-zA-Z0-9_-]*)$"
		if ($context == 'com_plugins.plugin' && !empty($data) && $data['type'] == 'plugin' && $data['element'] == 'gzip') {

			$options = $data['params']['gzip'];

			if (isset($options['admin_secret'])) {

				if (!preg_match('#^([a-zA-Z0-9_-]*)$#', $options['admin_secret'])) {

					throw new Exception('Invalid admin secret. You can only use numbers, letters, "_" and "-"', 400);
				}
			}
		}

		return true;
	}

	public function onExtensionAfterSave($context, $table, $isNew, $data = [])
	{

		if ($context == 'com_plugins.plugin' && !empty($data) && $data['type'] == 'plugin' && $data['element'] == 'gzip') {

			$shouldUpdate = false;

			if (empty($data['params']['gzip']['cache_key'])) {

				$shouldUpdate = true;
				$data['params']['gzip']['cache_key'] = substr(GZipHelper::shorten(filemtime(__FILE__)), 0, 3);
			}

			if (empty($data['params']['gzip']['expiring_links']['secret'])) {

				$shouldUpdate = true;
				$data['params']['gzip']['expiring_links']['secret'] = bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
			}

			if (
				!empty($data['params']['gzip']['pwa_share_target_enabled']) &&
				!empty($data['params']['gzip']['files_supported'])
			) {

				// enforce parameters when file sharing is ON
				$data['params']['gzip']['pwa_share_target_method'] = 'POST';
				$data['params']['gzip']['pwa_share_target_enctype'] = 'multipart/form-data';
			}

			if ($shouldUpdate) {

				$table->set('params', json_encode($data['params']));
				$table->store();
			}

			$options = json_decode(json_encode($data['params']['gzip']), JSON_OBJECT_AS_ARRAY);

			$php_config = [];
			$attributes = [];

			foreach ($options['instantloading'] as $key => $value) {

				switch ($key) {

					case 'filters':

						$attributes[] = $key . '="' . htmlspecialchars(json_encode(array_filter(preg_split('#\s+#s', $value, -1, PREG_SPLIT_NO_EMPTY), function ($value) {
								return $value !== '';
							})), ENT_QUOTES) . '"';
						break;

					case 'trigger':
					case 'intensity':
					case 'filter-type':
					case 'allow-query-string':
					case 'allow-external-links':

						if (!empty($value)) {

							$attributes[] = $key . '="' . $value . '"';
						}

						break;
				}
			}

			$php_config['instantloading'] = $attributes;
			$php_config['headers'] = $this->updateSecurityHeaders($options);

			$this->cleanCache();
			//	$this->updateSecurityHeaders($options);
			$this->updateManifest($options);
			$this->updateServiceWorker($options);


			$path = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/';

			if (!is_dir($path)) {

				$old_mask = umask();

				umask(022);
				mkdir($path, 0755, true);
				umask($old_mask);
			}

			file_put_contents($path . 'config.php', '<?php' . "\n" .
				"defined('JPATH_PLATFORM') or die;\n\n" .
				"\$php_config = " . var_export($php_config, true) . ';');

		}

		return true;
	}

	public function onAfterInitialise()
	{

		$app = JFactory::$application;

		$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/worker_version';

		$options = (array)$this->params->get('gzip');

		if (!empty($options)) {

			$this->options = json_decode(json_encode($options), JSON_OBJECT_AS_ARRAY);
		}

		if (!is_file($file)) {

			$this->updateServiceWorker($this->options);
		}

		if (is_file($file)) {

			$this->worker_id = file_get_contents(JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/worker_version');
		}

		$dirname = JURI::base(true) . '/';

		// fetch worker.js
		if (preg_match('#^' . $dirname . 'administrator/worker([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

			$debug = $this->params->get('gzip.debug_pwa') ? '' : '.min';

			$file = __DIR__ . '/worker/dist/serviceworker.administrator' . $debug . '.js';

			header('Cache-Control: max-age=86400');
			header('Content-Type: text/javascript;charset=utf-8');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

			readfile($file);
			$app->close();
		}

		if ($app->isClient('site')) {

			$this->route = $this->params->get('gzip.cache_key') . '/';

			GZipHelper::$route = $this->route;

			// segregate http and https cache
			$prefix = 'cache/z/' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'ssl/' : '');

			if (!empty($this->options['pwaenabled'])) {

				if (empty($this->options['pwa_network_strategy']) || $this->options['pwa_network_strategy'] == 'un') {

					$this->options['pwa_network_strategy'] = 'no';
				}

				$prefix .= $this->options['pwa_network_strategy'] . '/';
				GZipHelper::$pwa_network_strategy = $this->options['pwa_network_strategy'] . '/';
			}

			foreach (['js', 'css', 'img', 'ch', 'e', 'c'] as $key) {

				$path = $_SERVER['SERVER_NAME'] . '/' . $key . '/';

				if (isset($this->options['hashfiles']) && $this->options['hashfiles'] == 'content' && $key != 'e') {

					$path .= '1/';
				}

				if (!is_dir($prefix . $path)) {

					$old_mask = umask();

					umask(022);
					mkdir($prefix . $path, 0755, true);
					umask($old_mask);
				}

				$this->options[$key . '_path'] = $prefix . $path;
			}

			if (!empty($this->options['cdn'])) {

				$this->options['cdn'] = array_filter(array_values($this->options['cdn']));
			} else
				$this->options['cdn'] = [];

			$this->options['scheme'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';

			if (is_object($this->options['cdn'])) {

				$this->options['cdn'] = array_filter(array_values($this->options['cdn']));
			}

			if (empty($this->options['cdn'])) {

				$this->options['cdn'] = [];
			}

			GZipHelper::$regReduce = ['#^((' . implode(')|(', array_filter(array_merge(array_map(function ($host) {
					return $host . '/';
				}, $this->options['cdn']),
					[JUri::root(), JUri::root(true) . '/']))) . '))#', '#^(' . JUri::root(true) . '/)?' . $this->route . '(((nf)|(cf)|(cn)|(no)|(co))/)?[^/]+/#', '#(\?|\#).*$#'];

			if (!isset($this->options['cdn_types'])) {

				$this->options['cdn_types'] = array_keys(GZipHelper::$accepted);
			}

			$this->options['static_types'] = [];

			foreach ($this->options['cdn_types'] as $type) {

				if (isset(GZipHelper::$accepted[$type])) {

					$this->options['static_types'][$type] = GZipHelper::$accepted[$type];
				}
			}

			$types = '';

			if (!empty($this->options['cdntypes_custom'])) {

				$types = $this->options['cdntypes_custom'];
			}

			if (!empty($this->options['expiring_links']['mimetypes_expiring_links'])) {

				$types .= "\n".$this->options['expiring_links']['mimetypes_expiring_links'];
			}

			if (trim($types) !== '') {

				foreach (explode("\n", $types) as $option) {

					$option = trim($option);

					if ($option !== '') {

						$option = explode(' ', $option, 2);

						if (count($option) == 2) {

							$this->options['static_types'][$option[0]] = $option[1];
						}
					}
				}
			}

			foreach ($this->options['cdn'] as $key => $option) {

				$this->options['cdn'][$key] = (preg_match('#^([a-zA-z]+:)?//#', $option) ?: $this->options['scheme'] . '://') . $option;
			}

			GZipHelper::$hosts = empty($this->options['cnd_enabled']) ? [] : $this->options['cdn'];
			GZipHelper::$static_types = $this->options['static_types'];

			// do not render blank js file when service worker is disabled
			if (!empty($this->options['pwaenabled'])) {

				$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/worker_version';

				if (!is_file($file)) {

					$this->updateServiceWorker($this->options);
				}

				$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/manifest_version';

				if (!is_file($file)) {

					$this->updateManifest($this->options);
				}

				if (is_file($file)) {

					$this->manifest_id = file_get_contents($file);
				}
			}

			if (strpos($_SERVER['REQUEST_URI'], JURI::root(true) . '/' . $this->route) === 0) {

				require __DIR__ . '/responder.php';
				$app->close();
			}

			// prevent accessing the domain through the cdn address
			$domain = !empty($this->options['cdn_redirect']) ? preg_replace('#^([a-z]+:)?//#', '', $this->options['cdn_redirect']) : '';

			if ($domain !== '' && strpos($_SERVER['REQUEST_URI'], '/' . $this->route) === 0) {

				foreach ($this->options['cdn'] as $key => $option) {

					if (preg_replace('#^([a-z]+:)?//#', '', $this->options['cdn'][$key]) == $_SERVER['SERVER_NAME']) {

						header('Location: //' . $domain . $_SERVER['REQUEST_URI'], true, 301);
						$app->close();
					}
				}
			}

			$this->options['parse_url_attr'] = empty($this->options['parse_url_attr']) ? [] : array_flip(array_map('strtolower', preg_split('#[\s,]#', $this->options['parse_url_attr'], -1, PREG_SPLIT_NO_EMPTY)));
			$this->options['parse_url_attr']['href'] = '';
			$this->options['parse_url_attr']['src'] = '';
			$this->options['parse_url_attr']['srcset'] = '';
			$this->options['parse_url_attr']['data-src'] = '';
			$this->options['parse_url_attr']['data-srcset'] = '';

			$dirname = dirname($_SERVER['SCRIPT_NAME']);

			if ($dirname != '/') {

				$dirname .= '/';
			}

			// fetch worker.js
			if (preg_match('#^' . $dirname . 'worker([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

				$debug = $this->params->get('gzip.debug_pwa') ? '' : '.min';

				$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/serviceworker' . $debug . '.js';

				if (!is_file($file)) {

					$this->updateManifest($this->options);
				}

				header('Cache-Control: max-age=86400');
				header('Content-Type: text/javascript;charset=utf-8');
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

				readfile($file);
				$app->close();
			}

			// fetch sync.fallback.js
			// plugins/system/gzip/worker/dist/sync.fallback'.$debug.'.js
			if (preg_match('#^' . $dirname . 'sync-fallback([a-z0-9.]+)?\.js#i', $_SERVER['REQUEST_URI'])) {

				$debug = $this->params->get('gzip.debug_pwa') ? '' : '.min';

				$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/sync.fallback' . $debug . '.js';

				if (!is_file($file)) {

					$this->updateServiceWorker($this->options);
				}

				header('Cache-Control: max-age=86400');
				header('Content-Type: text/javascript; charset=utf-8');
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

				readfile($file);
				$app->close();
			}

			$document = JFactory::getDocument();

			// fetch worker.js
			if (!empty($this->options['pwa_app_manifest']) && $this->options['pwaenabled'] == 1) {

				$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/manifest.json';

				if (!is_file($file)) {

					$this->updateManifest($this->options);
				}

				if (preg_match('#^' . $dirname . 'manifest([a-z0-9.]+)?\.json#i', $_SERVER['REQUEST_URI'])) {

					header('Cache-Control: max-age=86400');
					header('Content-Type: application/manifest+json;charset=utf-8');
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

					readfile($file);
					exit;
				}

				if (method_exists($document, 'addHeadLink')) {

					$document->addHeadLink(JURI::root(true) . '/manifest' . $this->manifest_id . '.json', 'manifest');
				}

				if (!empty($this->options['pwa_app_theme_color'])) {

					// setMetaData
					$document->setMetaData('theme-color', $this->options['pwa_app_theme_color']);
				}
			}

			if (method_exists($document, 'addHeadLink')) {

				//    $name = $this->options['pwa_app_name'] === '' ? $config->get('sitename') : $this->options['pwa_app_name'];

				if (!empty($this->options['pwa_app_native_android'])) {

					$url = $this->options['pwa_app_native_android'];

					$document->addHeadLink($url, 'external', 'rel', ['data-app' => 'android']);
					//    $id = preg_replace('#.*?(com\.[a-z0-9.]+).*#', '$1', $this->options['pwa_app_native_android']);
				}

				if (!empty($this->options['pwa_app_native_ios'])) {

					$url = $this->options['pwa_app_native_ios'];

					$document->addHeadLink($url, 'external', 'rel', ['data-app' => 'ios']);
					//$id = preg_replace('#.*?/id(\d+).*#', '$1', $this->options['pwa_app_native_ios']);
				}
			}

			// "start_url": "./?utm_source=web_app_manifest",
			// manifeste url
		} else if ($app->isClient('admin')) {

			$secret = $this->params->get('gzip.admin_secret');

			if (!is_null($secret) && $_SERVER['REQUEST_METHOD'] == 'GET' && JFactory::getUser()->get('id') == 0 && !array_key_exists($secret, $_GET)) {

				$app->redirect(JURI::root(true) . '/');
			}

			if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task']) && strpos($_POST['task'], 'gzip.') === 0) {

				$token = JSession::getFormToken();

				if (isset($_POST[$token]) && $_POST[$token] == 1) {

					$data = $app->input->post->get('jform', [], 'array');

					$router = JApplicationSite::getRouter();

					$uri = $router->build('index.php?option=com_content&view=article&id=' . $data['id'] . '&catid=' . $data['catid']);

					$result = OneSignal\OneSignal::sendArticlePushNotification(
						$this->params->get('gzip.onesignal.web_push_app_id'),
						$this->params->get('gzip.onesignal.web_push_api_key'),
						!empty($data['push']['sendtest']) ? $data['push']['sendtest'] : null,
						$data['title'],
						JUri::root() . str_replace(JUri::base(true) . '/', '', $uri->toString()),
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

	public function onAfterDispatch()
	{

		$app = JFactory::$application;

		$document = JFactory::getDocument();

		$generator = $this->params->get('gzip.metagenerator');

		if (!is_null($generator)) {

			$document->setGenerator($generator);
		}
	}

	public function onAfterRender()
	{

		$app = JFactory::$application;

		if (!$app->isClient('site') || JFactory::getDocument()->getType() != 'html') {

			return;
		}

		$options = $this->options;

		if (!empty($options['jsignore'])) {

			$options['jsignore'] = preg_split('#\s+#s', $options['jsignore'], -1, PREG_SPLIT_NO_EMPTY);
		}

		if (!empty($options['imageignore'])) {

			$options['imageignore'] = preg_split('#\s+#s', $options['imageignore'], -1, PREG_SPLIT_NO_EMPTY);
		}

		if (!empty($options['jsremove'])) {

			$options['jsremove'] = preg_split('#\s+#s', $options['jsremove'], -1, PREG_SPLIT_NO_EMPTY);
		}

		if (empty($options['jsremove'])) {

			$options['jsremove'] = [];
		}

		if (!empty($options['cssignore'])) {

			$options['cssignore'] = preg_split('#\s+#s', $options['cssignore'], -1, PREG_SPLIT_NO_EMPTY);
		}

		if (empty($options['cssignore'])) {

			$options['cssignore'] = [];
		}

		if (!empty($options['cssremove'])) {

			$options['cssremove'] = preg_split('#\s+#s', $options['cssremove'], -1, PREG_SPLIT_NO_EMPTY);
		}

		if (empty($options['cssremove'])) {

			$options['cssremove'] = [];
		}

		// Save-Data header enforce some settings
		if (isset($_SERVER["HTTP_SAVE_DATA"]) && $this->params->get('gzip.savedata', true) && strtolower($_SERVER["HTTP_SAVE_DATA"]) === "on") {

			// optimize images
			$options['imageenabled'] = true;
			$options['imageconvert'] = true;
			$options['imagedimensions'] = true;

			// optimize css
			$options['asynccss'] = true;
			$options['minifycss'] = true;
			$options['fetchcss'] = true;
			$options['cssenabled'] = true;
			$options['mergecss'] = true;
			$options['imagecssresize'] = true;
			$options['criticalcssenabled'] = true;

			// optimize javascript
			$options['jsenabled'] = true;
			$options['fetchjs'] = true;
			$options['minifyjs'] = true;
			$options['mergejs'] = true;

			// minify html
			$options['minifyhtml'] = true;

			// enable service worker? no?
		}

		$body = $app->getBody();

		GZipHelper::$options = $options;

		if (!empty($options['imageenabled'])) {

			GZipHelper::register(new Gzip\Helpers\ImagesHelper());
		}

		if (!empty($options['cssenabled'])) {

			GZipHelper::register(new Gzip\Helpers\CSSHelper());
		}

		if (!empty($options['jsenabled'])) {

			GZipHelper::register(new Gzip\Helpers\ScriptHelper());
		}

		if (!empty($options['expiring_links_enabled']) && !empty($options['expiring_links']['file_type'])) {

			GZipHelper::register(new Gzip\Helpers\EncryptedLinksHelper());
		}

		if (!empty($options['cachefiles'])) {

			GZipHelper::register(new Gzip\Helpers\UrlHelper());
		}

		GZipHelper::register(new Gzip\Helpers\HTMLHelper());

	//	if (!empty($options['cspenabled'])) {

			GZipHelper::register(new Gzip\Helpers\SecureHeadersHelper());
	//	}

		$profiler = JProfiler::getInstance('Application');

		$profiler->mark('afterRenderStart');

		$body = GZipHelper::trigger('preprocessHTML', $body, $options);
		$body = GZipHelper::trigger('processHTML', $body, $options);
		$body = GZipHelper::trigger('postProcessHTML', $body, $options, true);

		GZipHelper::setTimingHeaders($options);

		foreach (GZipHelper::getHeaders() as $key => $rule) {

			if (is_array($rule)) {

				$app->setHeader($key, $rule[0], $rule[1]);
			} else {

				$app->setHeader($key, $rule, true);
			}
		}

		$app->setBody($body);
	}

	public function onInstallerAfterInstaller($model, $package, $installer, $result)
	{

		if ($result && (string)$installer->manifest->name == 'plg_system_gzip') {

			$this->cleanCache();
		}
	}

	protected function buildManifest($options)
	{

		$config = JFactory::getConfig();

		$options = json_decode(json_encode($options), JSON_OBJECT_AS_ARRAY);

		$short_name = $options['pwa_app_short_name'] === '' ? $_SERVER['SERVER_NAME'] : $options['pwa_app_short_name'];
		$name = $options['pwa_app_name'] === '' ? $config->get('sitename') : $options['pwa_app_name'];
		$description = $options['pwa_app_description'] === '' ? $config->get('MetaDesc') : $options['pwa_app_description'];
		$start_url = $options['pwa_app_start_url'] === '' ? JURI::root(true) . '/' : $options['pwa_app_start_url'];

		$start_url .= (strpos($start_url, '?') === false ? '?' : '&') . 'utm_source=web_app_manifest';

		$manifest = [
			'scope' => JURI::root(true) . '/',
			'short_name' => substr($short_name, 0, 12),
			'name' => $name,
			'description' => $description,
			'start_url' => $start_url,
			'background_color' => $options['pwa_app_bg_color'],
			'theme_color' => $options['pwa_app_theme_color'],
			'display' => $options['pwa_app_display']
		];

		if (!empty($options['pwa_share_target_enabled'])) {

			$manifest['share_target'] = [

				'action' => $options['pwa_share_target_action'],
				'method' => $options['pwa_share_target_method'],
				'enctype' => $options['pwa_share_target_enctype']
			];

			if (!empty($options['title_supported'])) {

				$manifest['share_target']['params']['title'] = !empty($options['pwa_share_target_params']['title']) ? $options['pwa_share_target_params']['title'] : 'title';
			}

			if (!empty($options['text_supported'])) {

				$manifest['share_target']['params']['text'] = !empty($options['pwa_share_target_params']['text']) ? $options['pwa_share_target_params']['text'] : 'text';
			}

			if (!empty($options['url_supported'])) {

				$manifest['share_target']['params']['url'] = !empty($options['pwa_share_target_params']['url']) ? $options['pwa_share_target_params']['url'] : 'url';
			}

			if (!empty($options['files_supported'])) {

				$manifest['share_target']['params']['files'] = !empty($options['pwa_share_target_params']['files']) ? json_decode($options['pwa_share_target_params']['files'], true) : [];
			}
		}

		if (!empty($options['onesignal'])) {

			$manifest['gcm_sender_id'] = '482941778795';
		}

		$native_apps = [];

		if (!empty($options['pwa_app_native_android'])) {

			$native_apps[] = [

				'platform' => 'play',
				'url' => $options['pwa_app_native_android'],
				'id' => preg_replace('#.*?(com\.[a-z0-9.]+).*#', '$1', $options['pwa_app_native_android'])
			];
		}

		if (!empty($options['pwa_app_native_ios'])) {

			$native_apps[] = [

				'platform' => 'itunes',
				'url' => $options['pwa_app_native_ios'],
				'id' => preg_replace('#.*?/id(\d+).*#', '$1', $options['pwa_app_native_ios'])
			];
		}

		if (!empty($native_apps)) {

			$manifest['prefer_related_applications'] = (bool)$options['pwa_app_native'];
			$manifest['related_applications'] = $native_apps;
		}

		if (!empty($options['pwa_app_icons_path'])) {

			$dir = JPATH_SITE . '/images/' . $options['pwa_app_icons_path'];

			if (is_dir($dir)) {

				foreach (new DirectoryIterator($dir) as $file) {

					if ($file->isFile() && preg_match('#\.((jpg)|(png)|(webp))$#i', $file, $match)) {

						$size = getimagesize($file->getPathName());

						//$max = max($size[0], $size[1]);

						$manifest['icons'][] = [

							'src' => JUri::root(true) . '/images/' . $options['pwa_app_icons_path'] . '/' . $file,
							'sizes' => $size[0] . 'x' . $size[1],
							'type' => image_type_to_mime_type($size[2])
						];
					}
				}
			}
		}

		$manifest = array_filter($manifest, function ($value) {

			if (is_array($value)) {

				$value = array_filter($value, function ($v) {
					return $v !== '';
				});
			}

			return $value !== '' && !is_null($value) && (!is_array($value) || count($value) != 0);
		});

		if (empty ($manifest)) {

			$manifest = [];
		}

		return json_encode($manifest);
	}

	protected function updateSecurityHeaders($options)
	{

		$headers = [];
		
		if (!empty($options['dns_prefetch'])) {

			$headers['X-DNS-Prefetch-Control'] = [$options['dns_prefetch'], true];
		}

		if (isset($options['hsts_maxage']) && intval($options['hsts_maxage']) > 0) {

			$dt = new DateTime();

			$now = $dt->getTimestamp();
			$dt->modify($options['hsts_maxage']);

			$headers['Strict-Transport-Security'] = 'max-age=' . ($dt->getTimestamp() - $now);

			if (!empty($options['hsts_subdomains'])) {

				$headers['Strict-Transport-Security'] .= '; includeSubDomains';
			}

			if (!empty($options['hsts_preload'])) {

				$headers['Strict-Transport-Security'] .= '; preload';
			}
		}

		if (!empty($options['frameoptions'])) {

			switch ($options['frameoptions']) {

				case 'allow-from':

					if (!empty($options['frameoptions_uri'])) {

						$headers['X-Frame-Options'] = [$options['frameoptions'] . ' ' . $options['frameoptions_uri'], true];
					}

					break;
				default:

					$headers['X-Frame-Options'] = [$options['frameoptions'], true];
					break;
			}
		}

		if (!empty($options['xcontenttype'])) {

			$headers['X-Content-Type-Options'] = [$options['xcontenttype'], true];
		}

		if (isset($options['xssprotection'])) {

			switch ($options['xssprotection']) {

				case '0':
				case '1':

					$headers['X-XSS-Protection'] = [$options['xssprotection'] . ' ' . $options['xss_uri'], true];
					break;

				case 'block':

					$headers['X-XSS-Protection'] = ['1; mode=block', true];
					break;

				case 'report':

					if (!empty($options['xss_uri'])) {

						$headers['X-XSS-Protection'] = ['1; report=' . $options['xss_uri'], true];
					}

					break;
				default:

					$headers['X-XSS-Protection'] = [$options['xssprotection'], true];
					break;
			}
		}

		return $headers;
	}

	protected function updateManifest($options)
	{

		if (empty($options['pwa_app_manifest'])) {

			return;
		}

		$path = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/';

		if (!is_dir($path)) {

			$old_mask = umask();

			umask(022);
			mkdir($path, 0755, true);
			umask($old_mask);
		}

		file_put_contents($path . 'manifest.json', $this->buildManifest($options));
		file_put_contents($path . 'manifest_version', hash_file('sha1', $path . 'manifest.json'));
	}

	protected function updateServiceWorker($options)
	{

		if (is_object($options)) {

			$options = json_decode(json_encode($options), JSON_OBJECT_AS_ARRAY);
		}

		if (empty($options['pwaenabled'])) {

			return;
		}

		$path = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/';

		if (!is_dir($path)) {

			$old_mask = umask();

			umask(022);
			mkdir($path, 0755, true);
			umask($old_mask);
		}

		$preloaded_urls = empty($options['pwa_app_cache_urls']) ? [] : preg_split('#\s#s', $options['pwa_app_cache_urls'], -1, PREG_SPLIT_NO_EMPTY);
		$exclude_urls = empty($options['pwa_app_cache_exclude_urls']) ? [] : preg_split('#\s#s', $options['pwa_app_cache_exclude_urls'], -1, PREG_SPLIT_NO_EMPTY);

		$exclude_urls[] = JUri::root(true) . '/administrator/';
		$exclude_urls = array_values(array_unique(array_filter($exclude_urls)));

		if (!empty($options['pwa_offline_page'])) {

			$preloaded_urls[] = $options['pwa_offline_page'];
		}

		$preloaded_urls = array_values(array_unique($preloaded_urls));

		$import_scripts = '';
		$onesignal = (array)$options['onesignal'];

		if (!empty($onesignal['enabled'])) {

			// one signal is blocked by adblockers and this kills the service worker. we need to catch the error here
			$import_scripts .= 'try{importScripts("https://cdn.onesignal.com/sdks/OneSignalSDK.js")}catch(e){console.error("cannot load OneSignalSDK.js 😭",e)}';
		}

		$cache_duration = !empty($options['pwa_cache_default']) ? $options['pwa_cache_default'] : $this->params->get('gzip.maxage', '2months');
		$defaultNetworkStrategy = empty($options['pwa_network_strategy']) ? 'nf' : $options['pwa_network_strategy'];

		if ($defaultNetworkStrategy == 'un') {

			$defaultNetworkStrategy = 'no';
		}

		// additional routing strategies
		$strategies = [];

		if (!isset($options['pwa_cache'])) {

			$options['pwa_cache'] = [];
		}

		$maxFileSize = GZipHelper::file_size($options['pwa_cache_max_file_size']);

		$cache_settings = [
			'caching' => (bool)$options['pwa_cache_enabled'],
			'strategy' => $defaultNetworkStrategy,
			'maxAge' => $cache_duration,
			// maximum number of files in the cache
			'limit' => +$options['pwa_cache_max_file_count'],
			// maximum cacheable file sze
			'maxFileSize' => $maxFileSize,
			'cacheName' => 'gzip_sw_worker_expiration_cache_default_private',
			'settings' => []
		];

		foreach ($options['pwa_network_strategies'] as $key => $value) {

			// use default settings
			if (empty($options['pwa_cache'][$key])) {

				continue;
			}

			if (empty($value)) {

				$value = $options['pwa_network_strategy'];
			}

			if ($value == 'un') {

				$value = 'no';
			}

			$strategies[$key]['mime'] = [];
			$strategies[$key]['network'] = [];

			foreach (GZipHelper::$accepted as $ext => $mime_type) {

				if ($ext == $key || strpos($mime_type, $key) !== false) {

					$strategies[$key]['network'][] = $ext;
					$strategies[$key]['mime'][] = $mime_type;
				}
			}

			// fallback to default pwa cache settings if not set
			$cache_duration_type = empty($options['pwa_cache'][$key]) ? $options['pwa_cache_default'] : $options['pwa_cache'][$key];

			if (intval($cache_duration_type) == -1 || empty($strategies[$key]['network'])) {

				unset($strategies[$key]);
				unset($options['pwa_network_strategies'][$key]);
				continue;
			}

			if (intval($cache_duration_type) == 0) {

				// fallback to the default http cache settings if none set
				$cache_duration_type = $this->params->get('gzip.maxage', '2months');
			}

			//	$strategies[$key]['value'] = $value;
			//	$strategies[$key]['cache'] = $dt->getTimestamp() - $now;
			//	$strategies[$key]['key'] = $key;

			//	$strategies[$key]['network'][] = $ext;
			//	$strategies[$key]['mime'][] = $mime_type;

			$cache_settings['settings'][$key] = [
				//	'type' => $key,
				//	'cacheName' => 'gzip_sw_worker_expiration_cache_'.$key.'_private',
				'strategy' => $value,
				'ext' => $strategies[$key]['network'],
				'mime' => $strategies[$key]['mime'],
				'maxAge' => $cache_duration_type,
				'maxFileSize' => $maxFileSize,
				'limit' => +$options['pwa_cache_max_file_count']
			];

			// delete defaults
			if ($value == $defaultNetworkStrategy &&
				$cache_settings['settings'][$key]['maxAge'] == $cache_duration) {

				unset($cache_settings['settings'][$key]);
			}
		}

		$cache_settings['settings'] = array_values($cache_settings['settings']);


		$worker_id = trim(file_get_contents(__DIR__ . '/worker_version'));
		$hash = hash('sha1', json_encode($options) . $worker_id);

		$hosts = [$_SERVER['SERVER_NAME']];

		if (!empty($options['cnd_enabled'])) {

			foreach ($options['cdn'] as $option) {

				$hosts[] = preg_replace('#^([a-zA-z]+:)?//#', '', $option);
			}
		}

		$search =
			[
				'"{pwa_cache_settings}"',
				'"{pwa_offline_page}"',
				'"{SYNC_API_TAG}"',
				'"{VERSION}"',
				'"{BACKGROUND_SYNC}"',
				'"{CDN_HOSTS}"',
				'"{STORES}"',
				'{CACHE_NAME}',
				'{ROUTE}',
				'{scope}',
				'"{exclude_urls}"',
				'"{preloaded_urls}"',
				'"{pwa_cache_max_file_count}"'
			];

		$replace = [];

		$debug = empty($this->params->get('gzip.debug_pwa')) ? '' : '.min';
		$sync_enabled = $this->params->get('gzip.pwa_sync_enabled', 'disabled');

		$json_debug = $debug ? JSON_PRETTY_PRINT : 0;

		//	$offline_data = [];

		$offline_data = [
			'enabled' => !empty($options['pwa_offline_enabled']),
			'url' => '',
			'methods' => []
		];

		$charset = 'utf-8';

		if (!empty($options['pwa_offline_enabled']) &&

			(
				!empty($options['pwa_offline_page']) ||
				!empty($options['pwa_offline_html_page'])
			)
		) {

			$offline_data['methods'] = empty($options['pwa_offline_method']) ? ['GET'] : $options['pwa_offline_method'];

			$offline_data['type'] = 'url';
			//	}

			//	else {

			if (isset($options['pwa_offline_pref']) && $options['pwa_offline_pref'] == 'html') {

				$html = (string)$options['pwa_offline_html_page'];

				if (preg_match('#<meta\s+charset=(["\'])?([^"\']+)\\1#', $html, $matches)) {

					$charset = $matches[2];
				}

				$offline_data['charset'] = $charset;
				$offline_data['type'] = 'response';
				$offline_data['body'] = $html;
				unset($options['pwa_offline_page']);
			} else {

				$offline_data['url'] = $options['pwa_offline_page'];
				unset($options['pwa_offline_html_page']);
			}
		}

		array_unshift($search, '"{offline_charset}"');
		array_unshift($replace, $charset);

		$replace = array_merge($replace, [
			json_encode($cache_settings, $json_debug),
			json_encode(
				$offline_data, $json_debug),
			'"gzip_sync_queue"',
			json_encode($worker_id),
			json_encode([
				'enabled' => $sync_enabled != 'disabled',
				'method' => $this->params->get('gzip.pwa_sync_method', ['GET']),
				'pattern' => $sync_enabled == 'enabled' ? [] : array_filter(preg_split('#\s+#', $this->params->get('gzip.pwa_sync_patterns', ''), PREG_SPLIT_NO_EMPTY))
			], $json_debug),
			json_encode(array_values(array_unique($hosts)), $json_debug),
			json_encode(array_merge(['gzip_sw_worker_expiration_cache_private'], array_map(function ($key) {
				return 'gzip_sw_worker_expiration_cache_private_' . $key;
			}, array_keys($strategies))), $json_debug),
			'v_' . $hash,
			$this->params->get('gzip.cache_key'),
			//	json_encode($cacheExpiryStrategy, $json_debug),
			//	json_decode($defaultNetworkStrategy, $json_debug),
			JUri::root(true) . '/',
			json_encode($exclude_urls, $json_debug),
			json_encode($preloaded_urls, $json_debug),
			+$options['pwa_cache_max_file_count']
		]);

		$data = str_replace($search, $replace, file_get_contents(__DIR__ . '/worker/dist/serviceworker.min.js'));

		file_put_contents($path . 'serviceworker.js', str_replace($search, $replace, $import_scripts . file_get_contents(__DIR__ . '/worker/dist/serviceworker.js')));
		file_put_contents($path . 'serviceworker.min.js', $import_scripts . $data);

		file_put_contents($path . 'sync.fallback.js', str_replace($search, $replace, file_get_contents(__DIR__ . '/worker/dist/sync.fallback.js')));
		file_put_contents($path . 'sync.fallback.min.js', str_replace($search, $replace, file_get_contents(__DIR__ . '/worker/dist/sync.fallback.min.js')));

		// => update the service worker whenever the manifest changes
		$worker_id = hash('sha1', json_encode($options) . $hash . $import_scripts . $data);
		file_put_contents($path . 'worker_version', $worker_id);

		$search[] = '{debug}';

		$replace_min = array_merge($replace, [$worker_id . '.min']);

		$replace[] = $worker_id;

		file_put_contents($path . 'browser.uninstall.js', str_replace($search, $replace, file_get_contents(__DIR__ . '/worker/dist/browser.uninstall.js')));
		file_put_contents($path . 'browser.uninstall.min.js', str_replace($search, $replace_min, file_get_contents(__DIR__ . '/worker/dist/browser.uninstall.min.js')));

		$data = file_get_contents(__DIR__ . '/worker/dist/browser.js');

		if ($sync_enabled != 'disabled') {

			$data .= file_get_contents(__DIR__ . '/worker/dist/browser.sync.js');
		}

		file_put_contents($path . 'browser.js', str_replace($search, $replace, $data));

		$data = file_get_contents(__DIR__ . '/worker/dist/browser.min.js');

		if ($sync_enabled) {

			$data .= file_get_contents(__DIR__ . '/worker/dist/browser.sync.min.js');
		}

		file_put_contents($path . 'browser.min.js', str_replace($search, $replace_min, $data));
	}

	protected function cleanCache()
	{

		//
		$path = JPATH_SITE . '/cache/z/app/';

		if (is_dir($path)) {

			foreach (new DirectoryIterator($path) as $file) {

				if ($file->isDir() && !$file->isDot()) {

					foreach (
						[
							//	"config.php",
							"headers.php",
							"manifest.json",
							"manifest_version",
							"serviceworker.js",
							"serviceworker.min.js",
							"worker_version"
						] as $f) {

						$f = $file->getPathName() . '/' . $f;

						if (is_file($f)) {

							unlink($f);
						}
					}
				}
			}
		}
	}
}
