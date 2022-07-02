<?php

/**
 * Serve files with cache headers.
 *
 * - supports range requests
 * - can send CORS headers
 * - user can extend supported mimetypes by editing the plugin settings
 * - precompress files using brotli or gzip
 *
 * @package     GZip Plugin
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

/**
 *
 * @var $app ;
 */

namespace Gzip\Helpers;

use DateTime;
use Gzip\GZipHelper;

class Responder
{

	/**
	 * @throws \SodiumException
	 * @since 3.0
	 */
	public function afterInitialise(array $options)
	{

		// url rewrite
		if (strpos($options['request_uri'], $options['webroot'] . $options['route']) === 0) {

			$this->respond($options);
			exit;
		}

		// prevent accessing the domain through the cdn address
		$domain = !empty($options['cdn_redirect']) ? preg_replace('#^([a-z]+:)?//#', '', $options['cdn_redirect']) : '';

		if ($domain !== '' && strpos($options['request_uri'], '/' . $options['route']) === 0) {

			foreach ($options['cdn'] as $key => $option) {

				if (preg_replace('#^([a-z]+:)?//#', '', $options['cdn'][$key]) == $_SERVER['SERVER_NAME']) {

					header('Location: //' . $domain . $options['request_uri'], true, 301);
					exit;
				}
			}
		}

		$dirname = dirname($_SERVER['SCRIPT_NAME']);

		if ($dirname != '/') {

			$dirname .= '/';
		}

		// fetch worker.js
		if (preg_match('#^' . $dirname . 'worker([a-z0-9.]+)?\.js#i', $options['request_uri'])) {

			$debug = !empty($options['debug_pwa']) ? '' : '.min';

			$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/serviceworker' . $debug . '.js';

			$this->enable_compression();

			header('Cache-Control: max-age=86400');
			header('Content-Type: text/javascript;charset=utf-8');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));


			readfile($file);
			exit;
		}

		// fetch sync.fallback.js
		// plugins/system/gzip/worker/dist/sync.fallback'.$debug.'.js
		if (preg_match('#^' . $dirname . 'sync-fallback([a-z0-9.]+)?\.js#i', $options['request_uri'])) {

			$debug = $options['debug_pwa'] ? '' : '.min';

			$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/sync.fallback' . $debug . '.js';

			$this->enable_compression();

			header('Cache-Control: max-age=86400');
			header('Content-Type: text/javascript; charset=utf-8');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

			readfile($file);
			exit;
		}


		// fetch worker.js
		if (!empty($options['pwa_app_manifest']) && $options['pwaenabled'] == 1) {

			$file = JPATH_SITE . '/cache/z/app/' . $_SERVER['SERVER_NAME'] . '/manifest.json';

			if (preg_match('#^' . $dirname . 'manifest([a-z0-9.]+)?\.json#i', $_SERVER['REQUEST_URI'])) {

				$this->enable_compression();

				header('Cache-Control: max-age=86400');
				header('Content-Type: application/manifest+json;charset=utf-8');
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($file)));

				readfile($file);
				exit;
			}
		}
	}

	/**
	 * @param array $options
	 * @throws \SodiumException
	 * @since 3.0
	 *
	 */
	public function respond(array $options)
	{

		$cdn_access_control_origin = isset($options['cdn_access_control_origin']) ? $options['cdn_access_control_origin'] : '*';

		// cookieless domain?
		if (!empty(GZipHelper::$hosts)) {

			foreach (GZipHelper::$hosts as $host) {

				if (preg_replace('#(https?:)?//([^/]+).*#', '$2', $host) == $_SERVER['SERVER_NAME']) {

					$cdn_access_control_origin = JURI::getScheme() . '://' . $_SERVER['SERVER_NAME'];

					// delete cookies
					if (isset($_SERVER['HTTP_COOKIE'])) {

						$cookies = explode(';', $_SERVER['HTTP_COOKIE']);

						$expiry = time() - 1000;

						foreach ($cookies as $cookie) {

							$parts = explode('=', $cookie);

							$name = trim($parts[0]);
							setcookie($name, '', $expiry);
							setcookie($name, '', $expiry, '/');
						}
					}

					break;
				}
			}
		}

		if (!empty($options['cdn_cors'])) {

			header('Access-Control-Allow-Origin: ' . $cdn_access_control_origin);
			header('Access-Control-Expose-Headers: Date');
		}

		$uri = $options['request_uri'];

		$matches = preg_split('#/' . $options['route'] . '(((nf)|(cf)|(cn)|(no)|(co))/)?#', $uri, -1, PREG_SPLIT_NO_EMPTY);

		$uri = end($matches);
		$file = $uri;

		$useEtag = strpos($uri, '1/') === 0;

		if (!empty($options['cachefiles'])) {

			$uri = explode('/', $uri, $useEtag ? 3 : 2);
			$file = end($uri);
		}

		$file = urldecode(preg_replace('~[#|?].*$~', '', $file));

		$encrypted = preg_match('#\/e\/([^/]+)\/([^/]+)#', $options['request_uri'], $matches);
		$encrypted_data = null;

		if ($encrypted && !empty($options['expiring_links']['secret']) && !empty($matches)) {

			$raw_message = hex2bin($matches[1]);
			$nonce2 = substr($raw_message, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$encrypted_message2 = substr($raw_message, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
			$decrypted_message = sodium_crypto_secretbox_open($encrypted_message2, $nonce2, hex2bin($options['expiring_links']['secret']));

			$encrypted_data = json_decode($decrypted_message, JSON_OBJECT_AS_ARRAY);

			$badRequest = !is_array($encrypted_data) ||
				!isset($encrypted_data['path']) ||
				!isset($encrypted_data['duration']) ||
				!isset($encrypted_data['method']);

			if (!$badRequest && !empty($encrypted_data['method'])) {

				$badRequest = $options['request_method'] != $encrypted_data['method'];
			}

			if ($badRequest) {

				header("HTTP/1.1 400 Bad Request");
				exit;
			}

			if (+$encrypted_data['duration'] < time()) {

				header("HTTP/1.1 410 Gone");
				exit;
			}

			if (!GZipHelper::isFile($encrypted_data['path'])) {

				header("HTTP/1.1 404 Not Found");
				exit;
			}

			$file = $encrypted_data['path'];
		}

		$utf8_file = utf8_decode($file);

		if (GZipHelper::isFile($utf8_file)) {

			$file = $utf8_file;
		} else if (!GZipHelper::isFile($file)) {

			header("HTTP/1.1 404 Not Found");
			exit;
		}

		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		$accepted = array_merge(GZipHelper::accepted(), GZipHelper::$static_types);

		if (!isset($accepted[$ext])) {

			header('HTTP/1.1 403 Forbidden');
			exit;
		}

		$mtime = filemtime($file);
		$range = [];
		$size = 0;

		if (empty($useEtag) && isset($_SERVER['HTTP_RANGE'])) {

			$useEtag = 1;
		}

		if (isset($_SERVER['HTTP_RANGE'])) {

			$size = filesize($file);
			$ranges = preg_split('#(^bytes=)|[\s,]#', $_SERVER['HTTP_RANGE'], -1, PREG_SPLIT_NO_EMPTY);

			foreach ($ranges as $range) {

				$range = explode('-', $range);

				if (
					count($range) != 2 ||
					($range[0] === '' && $range[1] === '') ||
					$range[0] > $size - 1 ||
					$range[1] > $size - 1 ||
					($range[1] !== '' && $range[1] < $range[0])
				) {

					header('HTTP/1.1 416 Range Not Satisfiable');
					exit;
				}
			}

			$range = explode('-', array_shift($ranges));

			if ($range[0] === '') {

				$range[0] = 0;
			}

			if ($range[1] === '') {

				$range[1] = $size - 1;
			}

			header('HTTP/1.1 206 Partial Content');
			header('Content-Length:' . ($range[1] - $range[0] + 1));
			header('Content-Range: bytes ' . $range[0] . '-' . $range[1] . '/' . $size);
		}

		header('X-Content-Type-Options: nosniff');

		if ($useEtag) {

			$etag = GZipHelper::shorten(hash_file('crc32b', $file));

			if (!empty($range)) {

				$etag .= '-' . implode('R', $range);
			}

			if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {

				header('HTTP/1.1 304 Not Modified');
				exit;
			}

			header('ETag: ' . $etag);
		} else if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {

			header('HTTP/1.1 304 Not Modified');
			exit;
		}

		header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $mtime));
		header('Date: ' . gmdate('D, d M Y H:i:s T'));

		if (preg_match('#(text)|(xml)#', $accepted[$ext])) {

			header('Content-Type: ' . $accepted[$ext] . ';charset=utf-8');
		} else {

			header('Content-Type: ' . $accepted[$ext]);
		}

		$dt = new DateTime();

		$now = $dt->getTimestamp();

		$maxage = 0;

		if (!empty($encrypted_data['duration'])) {

			$maxage = $encrypted_data['duration'] - time();
		} else {

			$maxage = !empty($options['pwa_cache'][$ext]) ? $options['pwa_cache'][$ext] : '2months';

			// -1 - ignore
			//  0 - unset
			if (intval($maxage) <= 0) {

				$maxage = !empty($options['maxage']) ?$options['maxage']  : '2months';
			}

			$dt->modify('+' . $maxage);
			$maxage = $dt->getTimestamp() - $now;
		}


		header('Accept-Ranges: bytes');
		header('Cache-Control: public, max-age=' . $maxage . ', stale-while-revalidate=' . (2 * $maxage) . ', immutable');

		if (!empty($range) && ($range[0] > 0 || $range[1] < $size - 1)) {

			$this->enable_compression();

			$cur = $range[0];
			$end = $range[1];

			$handle = fopen($file, 'rb');

			if ($cur > 0) {

				fseek($handle, $cur);
			}

			//1024 * 16 = 16384
			while (!feof($handle) && $cur <= $end && (connection_status() == 0)) {
				print fread($handle, min(16384, ($end - $cur) + 1));
				$cur += 16384;
			}

			fclose($handle);
			exit;
		}

		$validFileSize = true;
		$precompress = !isset($options['precompressfiles']) || !empty($options['precompressfiles']);

		if (!empty($options['precompressfiles_min_file_size']) || !empty($options['precompressfiles_max_file_size'])) {

			$minFileSize = GZipHelper::file_size($options['precompressfiles_min_file_size']);
			$maxFileSize = GZipHelper::file_size($options['precompressfiles_max_file_size']);

			$filesize = filesize($file);

			if ($minFileSize > 0) {

				if ($filesize >= $minFileSize) {

					if ($maxFileSize > 0) {

						$validFileSize = $filesize <= $maxFileSize;
					}
				} else {

					$validFileSize = false;
				}
			} else if ($maxFileSize > 0) {

				$validFileSize = $filesize <= $maxFileSize;
			}
		}

// text file only?
		if ($precompress && $validFileSize && preg_match('#(text)|(xml)|(font)#', $accepted[$ext]) && isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {

			$encoding = preg_split('#[,\s]+#', $_SERVER['HTTP_ACCEPT_ENCODING'], -1, PREG_SPLIT_NO_EMPTY);

			$cb = GZipHelper::getHashMethod();

			$name = '';
			$hash = $cb ($file);
			$last_hash = '';
			$basename = $options['c_path'] . sha1($file) . '-' . pathinfo($file, PATHINFO_FILENAME);

			$data = file_get_contents($file);

			// store file hash
			$cache = $basename . '.' . $ext . '.php';

			if (!is_file($cache)) {

				file_put_contents($cache, '<?php' . "\n" . 'defined(\'_JEXEC\') or die;' . "\n" . '$last_hash = ' . json_encode($hash) . ';');
			} else {

				require $cache;

				if ($hash != $last_hash) {

					file_put_contents($cache, '<?php' . "\n" . 'defined(\'_JEXEC\') or die;' . "\n" . '$last_hash = ' . json_encode($hash) . ';');
				}
			}

			if (function_exists('brotli_compress') && in_array('br', $encoding)) {

				$name = $basename . '.' . $ext . '.br';

				header('Content-Encoding: br');

				if (!file_exists($name) || $last_hash != $hash) {

					$data = brotli_compress($data);
				}
			} else if (function_exists('gzencode') && in_array('gzip', $encoding)) {

				$name = $basename . '.' . $ext . '.gz';

				header('Content-Encoding: gzip');

				if (!file_exists($name) || $last_hash != $hash) {

					$data = gzencode($data);
				}
			}

			if ($name !== '') {

				if (!file_exists($name) || $last_hash != $hash) {

					file_put_contents($name, $data);
				}

				if (ob_get_length()) {

					ob_end_clean();
				}

				ini_set('zlib.output_compression', 'Off');
				ini_set('brotli.output_compression', 'Off');

				readfile($name);
				exit;
			}
		}
//else {

		$this->enable_compression();
//}

		readfile($file);
		exit;
	}

	/**
	 * @since 3.0
	 */
	public function enable_compression()
	{

		if (ob_get_level()) {

			ob_end_clean();
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {

			$encoding = preg_split('#[,\s]+#', $_SERVER['HTTP_ACCEPT_ENCODING'], -1, PREG_SPLIT_NO_EMPTY);

			if (extension_loaded('brotli') && in_array('br', $encoding)) {

				ini_set('brotli.output_compression', 'On');
			} else {

				ini_set('zlib.output_compression', 'On');
			}
		}
	}
}