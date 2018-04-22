<?php

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

	const JPATH_PLATFORM = 1;

	ob_start();

	chdir('../..');

	if (is_file('cache/z/app/config.php')) {

		//
		include 'cache/z/app/config.php';

		if (class_exists('GzipHelperConfig')) {

			// CORS enabled?
			if (!empty(GzipHelperConfig::CORS)) {

				header('Access-Control-Allow-Origin: *');
			}

			// cookieless domain?
			if (!empty(GzipHelperConfig::HOSTS) && in_array($_SERVER['SERVER_NAME'], GzipHelperConfig::HOSTS)) {

				header('Access-Control-Allow-Origin: *');

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
			}
		}

	}

	$uri = $_SERVER['REQUEST_URI'];

	$matches = preg_split('#/media/z/(((nf)|(cf)|(cn)|(no)|(co))/)?#', $uri, -1, PREG_SPLIT_NO_EMPTY);

	$uri = end($matches);

	$useEtag = strpos($uri, '1/') === 0;

	$uri = explode('/', $uri, $useEtag ? 3 : 2);

	$file = preg_replace('~[#|?].*$~', '', end($uri));
	$file = strpos($file, '%20') === false ? $file : urldecode($file);

	$utf8_file = utf8_decode($file);

	if (is_file($utf8_file)) {

		$file = $utf8_file;
	}

	else if(!is_file($file)) {

		header("HTTP/1.1 404 Not Found");
		exit;
	}

	require 'plugins/system/gzip/helper.php';

	$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	$accepted = defined('GzipHelperConfig::VALID_MIMETYPES') ? GzipHelperConfig::VALID_MIMETYPES : Gzip\GZipHelper::accepted();

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

	if(preg_match('#(text)|(xml)#', $accepted[$ext])) {

		header('Content-Type: '.$accepted[$ext].';charset=utf-8');
	}
	else {

		header('Content-Type: '.$accepted[$ext]);
	}

	header('Accept-Ranges: bytes');
//header('Content-Type: '.$accepted[$ext].';charset=utf-8');
	header('Cache-Control: public, max-age=31536000, immutable');

	$dt = new DateTime();

	$dt->modify('+2months');


	header('Expires: ' . gmdate('D, d M Y H:i:s T', $dt->getTimestamp()));

	if(!empty($range) && ($range[0] > 0 || $range[1] < $size -1)) {

		$cur = $range[0];
		$end = $range[1];

		$handle = fopen($file, 'rb');

		if($cur > 0) {

			fseek($handle, $cur);
		}

		while(!feof($handle) && $cur <= $end && (connection_status() == 0))
		{
			print fread($handle, min(1024 * 16, ($end - $cur) + 1));
			$cur += 1024 * 16;
		}

		fclose($handle);
		exit;
	}

	readfile($file);