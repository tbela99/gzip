<?php

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */

namespace Gzip;

defined('JPATH_PLATFORM') or die;

use Exception;
use JProfiler as JProfiler;
use JUri;
//use Patchwork\JSqueeze as JSqueeze;
use \Peast\Peast;
use \Peast\Formatter\Compact;
use \Peast\Formatter\PrettyPrint;
use \Peast\Renderer;
use function base64_encode;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function curl_setopt_array;
use function error_log;
use function file_get_contents;
use function json_encode;
use function pathinfo;
use function str_replace;
use function strtolower;
use function ucwords;

define('AVIF', function_exists('imageavif') && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/avif') !== false);
define('WEBP', function_exists('imagewebp') && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false);

class GZipHelper
{

	// match empty attributes like <script async src="https://www.googletagmanager.com/gtag/js?id=UA-111790917-1" data-position="head">
	const regexAttr = '~([\r\n\t ])?([a-zA-Z0-9:-]+)((=(["\'])(.*?)\5)|([\r\n\t ]|$))?~m'; #s
	const regexUrl = '#url\(([^)]+)\)#';

	const CRITICAL_PATH_URL = '/gzip-critical-path';

	/**
	 * @var string regex
	 * @since version
	 */
	static $regReduce = '';

	/**
	 * @var string route prefix
	 * @since version
	 */
	static $route = '';

	static $options = [];

	static $hosts = [];

	static $static_types = [];

	static $pushed = array(
		"gif" => array('as' => 'image'),
		"jpg" => array('as' => 'image'),
		"jpeg" => array('as' => 'image'),
		"png" => array('as' => 'image'),
		"webp" => array('as' => 'image'),
		"avif" => array('as' => 'image'),
		"swf" => array('as' => 'object'),
		"ico" => array('as' => 'image'),
		"svg" => array('as' => 'image'),
		"txt" => [],
		"js" => array('as' => 'script'),
		"css" => array('as' => 'style'),
		"xml" => [],
		"pdf" => [],
		"eot" => array('as' => 'font'),
		"otf" => array('as' => 'font'),
		"ttf" => array('as' => 'font'),
		"woff" => array('as' => 'font'),
		"woff2" => array('as' => 'font')
	);

	// can use http cache / url rewriting
	static $accepted = array(
		"js" => "text/javascript",
		"json" => "application/json",
		"map" => "application/json",
		"css" => "text/css",
		"eot" => "application/vnd.ms-fontobject",
		"otf" => "application/x-font-otf",
		"ttf" => "application/x-font-ttf",
		"woff" => "application/x-font-woff",
		"woff2" => "application/font-woff2",
		"ico" => "image/x-icon",
		"gif" => "image/gif",
		"jpg" => "image/jpeg",
		"jpeg" => "image/jpeg",
		"png" => "image/png",
		"webp" => "image/webp",
		"avif" => "image/avif",
		"svg" => "image/svg+xml",
		"swf" => "application/x-shockwave-flash",
		"txt" => "text/plain",
		"xml" => "text/xml",
		"pdf" => "application/pdf",
		'mp3' => 'audio/mpeg',
		'htm' => 'text/html',
		'html' => 'text/html'
	);

	static $pwa_network_strategy = '';

	protected static $callbacks = [];

	protected static $headers = [];

	public static function sanitizeFileName($string) {

		if (is_callable(' \Transliterator::transliterate')) {

			$string = \Transliterator::transliterate($string);
		}

		return str_replace(' ', '-', $string);
	}

	/**
	 * @param $file
	 * @param array $sizes
	 * @param array $options
	 *
	 * @return array
	 *
	 * @since 2.9.0
	 */
	protected function generateSrcSet($file, $sizes = [], array $options = [])
	{

		if (empty($sizes)) {

			return [];
		}

		$srcset = [];
		$file = GZipHelper::getName($file);

		if (!GZipHelper::isFile($file)) {

			$file = static::fetchRemoteImage($file, $options);
		}

		if (GZipHelper::isFile($file)) {

			$dim = getimagesize($file);

			if ($dim === false) {

				return [];
			}

			$width = $dim[0];

			$sizes = array_filter($sizes, function ($size) use ($width) {

				return $width > $size;
			});

			if (empty($sizes)) {

				return [];
			}


			$file = $this->convert($file, $options);


			$path = $options['img_path'];
			$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
			//    $const = constant('\Image\Image::'.$method);
			$hash = substr(md5($file), 0, 4);

			$root = $path . GZipHelper::sanitizeFileName(pathinfo($file, PATHINFO_FILENAME)) . '-%s-' . $hash . '.' . pathinfo($file, PATHINFO_EXTENSION);

			$img = null;
			$image = null;

			foreach ($sizes as $size) {

				$img = sprintf($root, $size);

				if (!is_file($img)) {

					if (is_null($image)) {

						$image = $this->initImage($file, $size);
					}

					(clone $image)->resizeAndCrop($size, null, $method)->save($img);
				}

				$srcset[$size] = $img;
			}

			if ($dim[0] > $sizes[0]) {

				// cache file
				// looking for invalid file name
				$srcset[$sizes[0]] = str_replace(' ', '%20', GZipHelper::url($file));
				krsort($srcset, SORT_NUMERIC);
			}
		}

		return $srcset;
	}

	public static function setHeader($name, $value, $replace = false)
	{

		if ($replace && !empty(static::$headers[$name])) {

			if (!is_array(static::$headers[$name])) {

				static::$headers[$name] = [static::$headers[$name]];
			}

			static::$headers[$name][] = $value;
		} else {

			static::$headers[$name] = $value;
		}
	}

	public static function getHeaders()
	{

		return static::$headers;
	}

	public static function setTimingHeaders($options = [])
	{

		if (!empty($options['servertiming'])) {

			$data = static::getTimingData();
			$header = [];

			foreach ($data['marks'] as $k => $mark) {

				$header[] = substr('0' . ($k + 1), -2) . '-' . preg_replace('#[^A-Za-z0-9]#', '', $mark->tip) . ';desc="' . $mark->tip . '";dur=' . floatval($mark->time); //.';memory='.$mark->memory;
			}

			$header[] = 'total;dur=' . $data['totalTime'];

			static::setHeader('Server-Timing', implode(',', $header));
		}
	}

	/**
	 * Display profile information.
	 *
	 * @return array
	 *
	 * @since   2.5
	 */
	public static function getTimingData()
	{
		$totalTime = 0;
		$marks = [];
		foreach (JProfiler::getInstance('Application')->getMarks() as $mark) {
			$totalTime += $mark->time;
			$marks[] = (object)array(
				'time' => $mark->time,
				'tip' => $mark->label
			);
		}

		return [
			'totalTime' => $totalTime,
			'marks' => $marks
		];
	}

	public static function register($callback)
	{

		static::$callbacks[] = [$callback, ucwords(str_replace(['Helpers', 'Helper', 'Gzip', '\\'], '', get_class($callback)))];
	}

	public static function trigger($event, $options = [], $html = '', $escape = false)
	{

		$replace = [];

		if ($escape) {

			$tags = ['noscript', 'script', 'style', 'pre'];

			$html = preg_replace_callback('#(<((' . implode(')|(', $tags) . '))[^>]*>)(.*?)</\2>#si', function ($matches) use (&$replace, $tags) {

				$match = $matches[count($tags) + 3];
				$hash = '--***-' . crc32($match) . '-***--';
				$replace[$hash] = $match;
				return $matches[1] . $hash . '</' . $matches[2] . '>';

			}, $html);
		}

		$profiler = JProfiler::getInstance('Application');

		foreach (static::$callbacks as $callback) {

			if (is_callable([$callback[0], $event])) {

				$profiler->mark($callback[1] . ucwords($event));
				$html = call_user_func_array([$callback[0], $event], [$options, $html]);
			}
		}

		if (!empty($replace)) {

			return str_replace(array_keys($replace), array_values($replace), $html);
		}

		return $html;
	}

	public static function getChecksum($file, callable $hashFile, $algo = 'sha256', $integrity = false)
	{

		$hash = $hashFile($file);
		$path = (isset(static::$options['ch_path']) ? static::$options['ch_path'] : 'cache/z/ch/' . $_SERVER['SERVER_NAME'] . '/') . $hash . '-' . basename($file) . '.checksum.php';

		$checksum = [];

		if (is_file($path)) {

			// $checksum defined in $path;
			include $path;

			if (isset($checksum['hash']) && $checksum['hash'] == $hash && isset($checksum['algo']) && $checksum['algo'] == $algo) {

				return $checksum;
			}
		}

		$checksum = [
			'hash' => $hash,
			'algo' => $algo,
			'integrity' => empty($algo) || $algo == 'none' || empty($integrity) ? '' : $algo . "-" . base64_encode(hash_file($algo, $file, true))
		];

		file_put_contents($path, '<?php $checksum = ' . var_export($checksum, true) . ';');
		return $checksum;
	}

	public static function canPush($file, $ext = null)
	{

		$name = static::getName($file);

		if (is_null($ext)) {

			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		}

		if (isset(static::$pushed[$ext])) {

			$push = static::$pushed[$ext];

			$push['rel'] = 'preload';

			return $push;
		}

		return false;
	}

	public static function getName($name)
	{

		return preg_replace(static::$regReduce, '', preg_replace('~(#|\?).*$~', '', $name));
	}

	/**
	 * minify js files
	 *
	 * @param string $file
	 * @param bool $remote_service
	 * @return bool|string
	 */
	public static function js($file, $remote_service = true)
	{

		static $parser;

		$content = '';

		if (preg_match('#^(https?:)?//#', $file)) {

			if (strpos($file, '//') === 0) {

				$file = 'http:' . $file;
			}

			$content = static::getContent($file);

			if ($content === false) {

				return false;
			}

		} else if (is_file($file)) {

			$content = file_get_contents($file);
		} else {

			return false;
		}

		if (is_null($parser)) {

			$parser = (new Renderer)->setFormatter(static::$options['minifyjs'] ? new Compact() : new PrettyPrint());
		}

		try {

			return trim($parser->render(Peast::latest($content)->parse(), false, false), ';');
		}

		catch (Exception $e) {

//			error_log($e->getTraceAsString());
		}

		return $content;
	}

	/**
	 * @param $url
	 * @param array $options
	 * @param array $curlOptions
	 * @return bool|string
	 */
	public static function getContent($url, $options = [], $curlOptions = [])
	{

		if (strpos($url, '//') === 0) {

			$url = JUri::getInstance()->getScheme() . ':' . $url;
		}

		$ch = curl_init($url);

		//   if (strpos($url, 'https://') === 0) {

		// Turn on SSL certificate verfication
		curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
		//    }

		if (!empty($curlOptions)) {

			curl_setopt_array($ch, $curlOptions);
		}

		if (!empty($options)) {

			// Tell the curl instance to talk to the server using HTTP POST
			curl_setopt($ch, CURLOPT_POST, count($options));
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options));
		}

		// Causes curl to return the result on success which should help us avoid using the writeback option
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// enable compression
		curl_setopt($ch, CURLOPT_ENCODING, '');

		$result = curl_exec($ch);

		if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {

			$ex = new Exception('curl error :: ' . $url . ' #' . curl_errno($ch) . ' :: ' . curl_error($ch));
			error_log($ex . "\n" . $ex->getTraceAsString());

			curl_close($ch);
			return false;
		}

		curl_close($ch);

		return $result;
	}

	/**
	 * @param $file
	 * @return string
	 */
	public static function url($file)
	{

		if (empty(static::$options['cachefiles'])) {

			if ($file[0] == '/' || preg_match('#^(https?:)?//#', $file)) {

				return $file;
			}

			return static::$options['webroot'] . $file;
		}

		$hash = preg_split('~([#?])~', $file, 2, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$hash = isset($hash[2]) ? $hash[1] . $hash[2] : '';

		$name = static::getName($file);

		if (strpos($name, 'data:') === 0) {

			return $file;
		}

		if (static::isFile($name)) {

			if (strpos($name, static::$route) !== 0) {

				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				$accepted = static::accepted();

				if (isset($accepted[$ext])) {

					$hashFile = static::getHashMethod();

					return static::getHost(static::$options['webroot'] . static::$route . static::$pwa_network_strategy . $hashFile($name) . '/' . $name . $hash);
				}
			}

			return preg_match('~^(https?:)?//~', $name) ? $name . $hash : static::getHost('/' . $name . $hash);
		}

		return $file;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public static function isFile($name)
	{

		$name = static::getName($name);

		if (preg_match('#^(https?:)?//#i', $name)) {

			return false;
		}

		return is_file($name) || is_file(utf8_decode($name));
	}

	public static function accepted()
	{

		return static::$accepted;
	}

	/**
	 * Convert file size to int. Ex '1Mb' -> 1 * 1024 * 1024
	 * @param $value
	 *
	 * @return int
	 *
	 * @since version
	 */
	public static function file_size($value)
	{

		return +preg_replace_callback('#(\d+)(.*+)#', function ($matches) {

			switch ($matches[2]) {

				case 'Kb':

					return $matches[1] * 1024;
				case 'Mb':

					return $matches[1] * 1024 * 1024;
				case 'Gb':

					return $matches[1] * 1024 * 1024 * 1024;
			}

			return $matches[1];

		}, $value);
	}

	/**
	 * @param array $options
	 *
	 * @return \Closure
	 *
	 * @since 1.0
	 */
	public static function getHashMethod($options = [])
	{

		$scheme = JUri::getInstance()->getScheme();

		static $hash, $cache = [];

		if (is_null($hash)) {

			$salt = empty(static::$hosts) ? '' : json_encode(static::$hosts);
			$salt .= static::$route;
			$salt .= json_encode(static::$options);

			$hash = !(isset($options['hashfiles']) && $options['hashfiles'] == 'content') ? function ($file) use ($scheme, $salt, $cache) {

				if (isset($cache[$file])) {

					return $cache[$file];
				}

				if (!static::isFile($file)) {

					$cache[$file] = static::shorten(crc32($scheme . $salt . $file));
				} else {
					$cache[$file] = static::shorten(crc32($scheme . $salt . filemtime(static::getName($file))));
				}

				return $cache[$file];

			} : function ($file) use ($scheme, $salt, $cache) {

				if (!static::isFile($file)) {

					$cache[$file] = static::shorten(crc32($scheme . $salt . $file));
				} else {

					$cache[$file] = static::shorten(crc32($scheme . $salt . hash_file('crc32b', static::getName($file))));
				}

				return $cache[$file];
			};
		}

		return $hash;
	}

	public static function shorten($value, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@')
	{

		$base = strlen($alphabet);
		$short = '';
		$id = sprintf('%u', $value);

		while ($id != 0) {
			$id = ($id - ($r = $id % $base)) / $base;
			$short = $alphabet[$r] . $short;
		}

		$response = ltrim($short, '0');

		if ($response === '' && $value !== '') {

			return '0';
		}

		return $response;
	}

	public static function getHost($file)
	{

		if (preg_match('#^([a-z]+:)?//#i', $file)) {

			return $file;
		}

		$count = count(static::$hosts);

		if ($count > 0) {

			$ext = pathinfo(static::getName($file), PATHINFO_EXTENSION);

			if (isset(static::$static_types[strtolower($ext)])) {

				if ($count == 1) {

					return static::$hosts[0] . $file;
				}

				$host = crc32($file) % $count;

				if ($host < 0) {

					$host += $count;
				}

				return static::$hosts[$host] . $file;
			}
		}

		return $file;
	}

	public static function parseUrl($url)
	{

		$result = parse_url($url);

		// match data: blob: etc ...
		if (preg_match('#^([^:]+\:)[^/]#', $url, $matches)) {

			return $matches[1];
		}

		$name = '';

		if (!isset($result['host'])) {

			$name = "'self'";
		} else {

			if (!isset($result['scheme'])) {

				$name = $result['scheme'] . '://' . $result['host'];
			} else {

				$name = $result['scheme'] . '://' . $result['host'];
			}
		}

		return $name;
	}
}
