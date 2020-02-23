<?php

defined('_JEXEC') or die;

if (!ini_get('zlib.output_compression')) {

	ob_end_clean();
	ob_start('ob_gzhandler');
}

	/**
	 * Serve files with cache headers.
	 *
	 * - supports range requests
	 * - can send CORS headers
	 * - user can extend supported mimetypes by editing the plugin settings
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
 * @var $app;
 */

	use \Gzip\GZipHelper;

	$cdn_access_control_origin = isset($this->options['cdn_access_control_origin']) ? $this->options['cdn_access_control_origin'] : '*';

	// cookieless domain?
	if (!empty(GZipHelper::$hosts)) {

		foreach (GZipHelper::$hosts as $host) {

			if (preg_replace('#(https?:)?//([^/]+).*#', '$2', $host) == $_SERVER['SERVER_NAME']) {

				$cdn_access_control_origin = JURI::getScheme().'://'.$_SERVER['SERVER_NAME'];

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

	if (!empty($this->options['cdn_cors'])) {
		
		header('Access-Control-Allow-Origin: '.$cdn_access_control_origin);
		header('Access-Control-Expose-Headers: Date');
	}

	$uri = $_SERVER['REQUEST_URI'];

	$matches = preg_split('#/'.$this->route.'(((nf)|(cf)|(cn)|(no)|(co))/)?#', $uri, -1, PREG_SPLIT_NO_EMPTY);

	$uri = end($matches);

	$useEtag = strpos($uri, '1/') === 0;

	$uri = explode('/', $uri, $useEtag ? 3 : 2);

	$file = preg_replace('~[#|?].*$~', '', end($uri));
	$file = urldecode($file);

	$encrypted = preg_match('#\/e\/([^/]+)\/([^/]+)#', $_SERVER['REQUEST_URI'], $matches);
	$encrypted_data = null;

	if ($encrypted && !empty($this->options['expiring_links']['secret']) && !empty($matches)) {

		$raw_message = hex2bin($matches[1]);
		$nonce2 = substr($raw_message, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encrypted_message2 = substr($raw_message, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$decrypted_message = sodium_crypto_secretbox_open($encrypted_message2, $nonce2, hex2bin($this->options['expiring_links']['secret']));

		$encrypted_data = json_decode($decrypted_message, JSON_OBJECT_AS_ARRAY);

		$badRequest = !is_array($encrypted_data) ||
			!isset($encrypted_data['path']) ||
			!isset($encrypted_data['duration']) ||
			!isset($encrypted_data['method']);

		if (!$badRequest && !empty($encrypted_data['method'])) {

			$badRequest = $_SERVER['REQUEST_METHOD'] != $encrypted_data['method'];
		}

		if($badRequest) {

			header("HTTP/1.1 400 Bad Request");
			$app->close();
		}

		if (+$encrypted_data['duration'] < time()) {

			header("HTTP/1.1 410 Gone");
			$app->close();
		}

		if (!GZipHelper::isFile($encrypted_data['path'])) {

			header("HTTP/1.1 404 Not Found");
			$app->close();
		}

		$file = $encrypted_data['path'];
	}

	$utf8_file = utf8_decode($file);

	if (GZipHelper::isFile($utf8_file)) {

		$file = $utf8_file;
	}

	else if(!GZipHelper::isFile($file)) {

		header("HTTP/1.1 404 Not Found");
		exit;
	}

	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	$accepted = array_merge(GZipHelper::accepted(), GZipHelper::$static_types);

	if(!isset($accepted[$ext])) {

		header('HTTP/1.1 403 Forbidden');
		exit;
	}

	$mtime = filemtime($file);
	$range = [];
	$size = 0;

	if(empty($useEtag) && isset($_SERVER['HTTP_RANGE'])) {

		$useEtag = 1;
	}

	if(isset($_SERVER['HTTP_RANGE'])) {

		$size = filesize($file);
		$ranges = preg_split('#(^bytes=)|[\s,]#', $_SERVER['HTTP_RANGE'], -1, PREG_SPLIT_NO_EMPTY);

		foreach ($ranges as $range) {

			$range = explode('-', $range);

			if(
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

		if($range[0] === '') {

			$range[0] = 0;
		}

		if($range[1] === '') {

			$range[1] = $size - 1;
		}

		header('HTTP/1.1 206 Partial Content');
		header('Content-Length:'.($range[1] - $range[0] + 1));
		header('Content-Range: bytes '.$range[0].'-'.$range[1].'/'.$size);
	}

	header('X-Content-Type-Options: nosniff');

	if($useEtag) {

		$etag = Gzip\GZipHelper::shorten(hash_file('crc32b', $file));

		if(!empty($range)) {

			$etag .= '-'.implode('R', $range);
		}

		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {

			header('HTTP/1.1 304 Not Modified');
			exit;
		}

		header('ETag: ' . $etag);
	}

	else if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {

		header('HTTP/1.1 304 Not Modified');
		exit;
	}

	header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $mtime));
	header('Date: ' . gmdate('D, d M Y H:i:s T'));

	if(preg_match('#(text)|(xml)#', $accepted[$ext])) {

		header('Content-Type: '.$accepted[$ext].';charset=utf-8');
	}
	else {

		header('Content-Type: '.$accepted[$ext]);
	}

	$dt = new DateTime();

	$now = $dt->getTimestamp();

	$maxage = 0;

	if (!empty($encrypted_data['duration'])) {

		$maxage = $encrypted_data['duration'] - time();
	}

	else {

		$maxage = $this->params->get('gzip.pwa_cache.'.$ext);

		// -1 - ignore
		//  0 - unset
		if (intval($maxage) <= 0) {

			$maxage = $this->params->get('gzip.maxage', '2months');
		}

		$dt->modify('+'.$maxage);
		$maxage = $dt->getTimestamp() - $now;
	}


	header('Accept-Ranges: bytes');
	header('Cache-Control: public, max-age='.$maxage.', stale-while-revalidate='.(2 * $maxage).', immutable');

	if(!empty($range) && ($range[0] > 0 || $range[1] < $size -1)) {

		$cur = $range[0];
		$end = $range[1];

		$handle = fopen($file, 'rb');

		if($cur > 0) {

			fseek($handle, $cur);
		}

		//1024 * 16 = 16384
		while(!feof($handle) && $cur <= $end && (connection_status() == 0))
		{
			print fread($handle, min(16384, ($end - $cur) + 1));
			$cur += 16384;
		}

		fclose($handle);
		exit;
	}

	readfile($file);
	exit;