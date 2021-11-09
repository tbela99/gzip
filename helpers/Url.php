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

namespace Gzip\Helpers;

use Gzip\GZipHelper;
use JUri;

class UrlHelper {

	/**
	 * perform url rewriting, distribute resources across cdn domains, generate HTTP push headers, replace rel="_blank" with rel="noopener noreferrer"
	 * @param array $options
	 * @param string $html
	 * @return string
	 * @since 1.0
	 */
	public function postProcessHTML (array $options, $html) {

		$accepted = GZipHelper::accepted();
		$hashFile = GZipHelper::getHashMethod($options);

		$replace = [];

		$pushed = [];
		$types = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && isset($options['h2push']) ? array_flip($options['h2push']) : [];

		$base = JUri::root(true) . '/';

		$hashmap = array(
			'style' => 0,
			'font' => 1,
			'script' => 2
		);

		$checksum = !empty($options['checksum']) ? $options['checksum'] : false;

		if ($checksum == 'none') {

			$checksum = false;
		}

		$domains = [];

		$html = preg_replace_callback('#<([a-zA-Z0-9:-]+)\s([^>]+)>(?!["\'])#s', function ($matches) use($checksum, $hashFile, $accepted, &$domains, &$pushed, $types, $hashmap, $base, $options) {

			$tag = $matches[1];
			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2],$attrib)) {

				foreach ($attrib[2] as $key => $value) {

					$attributes[$value] = $attrib[6][$key];
				}

				$url_attr = isset($options['parse_url_attr']) ? array_keys($options['parse_url_attr']) : ['href', 'src', 'srcset'];

				foreach ($url_attr as $attr) {

					if (isset($attributes[$attr]) && ($attr == 'srcset' || $attr == 'data-srcset')) {

						$return = [];

						foreach (explode(',', $attributes[$attr]) as $chunk) {

							$parts = explode(' ', $chunk, 2);

							$name = trim($parts[0]);

							$return[] = (GZipHelper::isFile($name) ? GZipHelper::url($name) : $name).' '.$parts[1];
						}

						$attributes[$attr] = implode(',', $return);
					}

					// fix target=_blank #
					if (!empty($options['link_rel']) && strtolower($tag) == 'a') {

						if (isset($attributes['target']) && $attributes['target'] == '_blank') {

							if (!isset($attributes['rel'])) {

								$attributes['rel'] = implode(' ', $options['link_rel']);
							}

							else {

								$values = array_merge($options['link_rel'], explode(' ', $attributes['rel']));
								$attributes['rel'] = implode(' ', array_unique($values));
							}
						}
					}

					if ((!empty($options['cachefiles']) || $checksum) && isset($attributes[$attr]) && isset($options['parse_url_attr'][$attr])) {

						$file = GZipHelper::getName($attributes[$attr]);

						if (GZipHelper::isFile($file)) {

							$name = preg_replace('~[#?].*$~', '', $file);
							$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

							if (isset(GZipHelper::$static_types[$ext]) && empty($attributes['crossorigin'])) {

								// crossorigin="anonymous"
								$attributes['crossorigin'] = '';
							}

							$push_data = empty($types) ? false : GZipHelper::canPush($name, $ext);

							if (!empty($push_data)) {

								if (!isset($types['all']) && (empty($push_data['as']) || empty($types[$push_data['as']]))) {

									unset($push_data);
								}

								else {

									if (isset($push_data['as']) && isset($hashmap[$push_data['as']])) {

										$push_data['score'] = $hashmap[$push_data['as']];
									} else {

										$push_data['score'] = count($hashmap);
									}

									$push_data['href'] = GZipHelper::getHost($file);
									$pushed[$base . $file] = $push_data;
								}
							}

							if ($checksum) {

								$checkSumData = GZipHelper::getChecksum($name, $hashFile, $checksum, $tag == 'script' || ($tag == 'link' && $ext == 'css'));

								if ($tag == 'script' || ($tag == 'link' && $ext == 'css')) {

									$attributes['integrity'] = $checkSumData['integrity'];
									// crossorigin="anonymous"
									$attributes['crossorigin'] = '';
								}
							}

							if (isset($accepted[$ext])) {

								unset($pushed[$base . $file]);

								if (!empty($options['cachefiles'])) {

									$file = GZipHelper::getHost(JURI::root(true).'/'.GZipHelper::$route.GZipHelper::$pwa_network_strategy . (isset($checkSumData['hash']) ? $checkSumData['hash'] : $hashFile($file)) . '/' . $file);
								}

								if (!empty($push_data)) {

									$push_data['href'] = $file;
									$pushed[$file] = $push_data;
								}

								$attributes[$attr] = $file;
							}
						}

						if (preg_match('#^(https?:)?(//[^/]+)#', $file, $domain)) {

							if (empty($domain[1])) {

								$domain[1] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https:' : 'http:';
							}

							$domains[$domain[1].$domain[2]] = $domain[1].$domain[2];
						}
					}
				}
			}

			$result = '<'.$tag;

			foreach ($attributes as $key => $value) {

				$result .= ' '.$key.($value === '' ? '' : '="'.$value.'"');
			}

			return $result .'>';

		}, $html);

		if (!empty($pushed)) {

			usort($pushed, function ($a, $b) {

				if ($a['score'] != $b['score']) {

					return $a['score'] - $b['score'];
				}

				return $a['href'] < $b['href'] ? -1 : 1;

			});

			foreach ($pushed as $push) {

				$header = '';

				$file = $push['href'];

				unset($push['href']);
				unset($push['score']);

				foreach ($push as $key => $var) {

					$header .= '; ' . $key . '=' . $var;
				}

				// or use html <link rel=preload> -> remove header
				header('Link: <' . $file . '> ' . $header, false);
			}
		}

		if (!empty($domains)) {

			unset($domains[(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME']]);
			unset($domains['http://get.adobe.com']);

			if (!empty($domains)) {

				$replace['<head>'] = '<head><link rel="preconnect" crossorigin href="'.implode('"><link rel="preconnect" crossorigin href="', $domains).'">'."\n";
			}
		}

		if (!empty($replace)) {

			return str_replace(array_keys($replace), array_values($replace), $html);
		}

		return $html;
	}
}