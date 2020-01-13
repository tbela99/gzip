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
use Patchwork\JSqueeze as JSqueeze;

class ScriptHelper {

	public function postProcessHTML ($html, array $options = []) {

		$path = isset($options['js_path']) ? $options['js_path'] : 'cache/z/'.GZipHelper::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/js/';

		$comments = [];

		$html = preg_replace_callback('#<!--.*?-->#s', function ($matches) use(&$comments) {

			$hash = '--***c-' . crc32($matches[0]) . '-c***--';
			$comments[$hash] = $matches[0];

			return $hash;
		}, $html);

		$ignore = !empty($options['jsignore']) ? $options['jsignore'] : [];
		$remove = !empty($options['jsremove']) ? $options['jsremove'] : [];

		// scripts
		// files
		// inline
		// ignored
		$sources = [];

		$fetch_remote = !empty($options['fetchjs']);
		$remote_service = !empty($options['minifyjsservice']);

		// parse scripts
		$html = preg_replace_callback('#<script([^>]*)>(.*?)</script>#si', function ($matches) use(&$sources, $path, $fetch_remote, $ignore, $remove) {

			$attributes = [];

			if(preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';

			// ignore custom type
			//   preg_match('#\btype=(["\'])(.*?)\1#', $matches[1], $match);
			if (isset($attributes['type']) && stripos($attributes['type'], 'javascript') === false) {

				return $matches[0];
			}

			if (isset($attributes['data-ignore'])) {

				unset($attributes['data-ignore']);
				unset($attributes['data-position']);

				$script = '<script';

				foreach($attributes as $name => $value) {

					$script .= ' '.$name.'="'.$value.'"';
				}

				return $script.'>'.$matches[2].'</script>';
			}

			unset($attributes['type']);

			if (!empty($matches[2])) {

				$sources['inline'][$position][] = $matches[2];
				return '';
			}

			// ignore custom type
			if (isset($attributes['src'])) {

				$name = GZipHelper::getName($attributes['src']);

				foreach ($remove as $r) {

					if (strpos($name, $r) !== false) {

						return '';
					}
				}

				foreach ($ignore as $i) {

					if (strpos($name, $i) !== false) {

						$sources['ignored'][$position][$name] = $attributes['src'];
						return '';
					}
				}

				if ($fetch_remote && preg_match('#^(https?:)?//#', $name)) {

					$remote = $name;

					if (strpos($name, '//') === 0) {

						$remote = 'http:' . $name;
					}

					$local = $path . preg_replace(array('#([.-]min)|(\.js)#', '#[^a-z0-9]+#i'), array('', '-'), $remote) . '.js';

					if (!is_file($local)) {

						$content = GZipHelper::getContent($remote);

						if ($content != false) {

							file_put_contents($local, $content);
						}
					}

					if (is_file($local)) {

						$name = $local;
						$matches[1] = str_replace($attributes['src'], $local, $matches[1]);
					}
				}

				$sources['files'][$position][$name] = $name;
				$sources['scripts'][$position][$name] = '<script' . $matches[1] . '></script>';
			}

			return '';

		}, $html);

		//      $profiler->mark('done parse <script>');

		$hashFile = GZipHelper::getHashMethod($options);

		if (!empty($options['minifyjs'])) {

			// compress all js files

			if (!empty($sources['files'])) {

				foreach($sources['files'] as $position => $fileList) {

					foreach ($fileList as $key => $file) {

						if (!GZipHelper::isFile($file)) {

							continue;
						}

						$name = preg_replace(array('#(^-)|([.-]min)|(cache/|-js/|-)|(\.?js)#', '#[^a-z0-9]+#i'), array('', '-'), $file);

						$js_file = $path . $name . '-min.js';
						$hash_file = $path . $name . '.php';

						$hash = $hashFile($file);

						if (!is_file($js_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash) {

							$gzip = GZipHelper::js($file, $remote_service);

							if ($gzip !== false) {

								file_put_contents($js_file, $gzip);
								file_put_contents($hash_file, $hash);
							}
						}

						if (is_file($js_file) && is_file($hash_file) && file_get_contents($hash_file) == $hash) {

							$sources['files'][$position][$key] = $js_file;
						}
					}
				}
			}
		}

		if (!empty($options['mergejs'])) {

			foreach($sources['files'] as $position => $filesList) {

				$hash = '';

				foreach ($filesList as $key => $file) {

					if (!isset($ignored[$key])) {

						$hash .= $hashFile($file) . '.' . $file;
					}
				}

				if (!empty($hash)) {

					$hash = crc32($hash);
				}

				$name = $path . GZipHelper::shorten($hash);
				$js_file = $name . '.js';
				$hash_file = $name . '.php';

				$createFile = !is_file($js_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash;

				$content = [];

				foreach ($filesList as $key => $file) {

					if ($createFile) {

						$content[] = trim(file_get_contents($file), ';');
					}

					unset($sources['files'][$position][$key]);
				}

				if (!empty($content)) {

					file_put_contents($js_file, implode(';', $content));
					file_put_contents($hash_file, $hash);
				}

				if (is_file($js_file) && is_file($hash_file) && file_get_contents($hash_file) == $hash) {

					$sources['files'][$position] = array_merge([$js_file => $js_file], $sources['files'][$position]);
				}
			}
		}

		if (!empty($options['minifyjs'])) {

			if (!empty($sources['inline'])) {

				foreach($sources['inline'] as $position => $js) {

					$data = implode(';', $js);

					//    foreach($js as $key => $data) {

					if (!empty($data)) {

						$jSqueeze = new JSqueeze();
						$sources['inline'][$position] = [trim($jSqueeze->squeeze($data), ';')];
					}
					//    }
				}
			}
		}

		$script = [

			'head' => '',
			'body' => ''
		];

		$async = false;

		if (!empty($sources['ignored'])) {

			foreach ($sources['ignored'] as $position => $fileList) {

				$attr = '';
				$hasScript = !empty($sources['inline'][$position]) && empty($files[$position]);

				if ($hasScript) {

					$async = true;
				}

				$script[$position] .= '<script async defer src="' . array_shift($fileList) . '"'.$attr.'></script>';

				if ($hasScript) {

					$script[$position] .= '<script type="text/foo">' . trim(implode(';', $sources['inline'][$position]), ';') . '</script>';
					unset($sources['inline'][$position]);
				}
			}
		}

		if (!empty($sources['files'])) {

			foreach($sources['files'] as $position => $fileList) {

				if (!empty($fileList)) {

					if (count($fileList) == 1) {

						$attr = '';
						$hasScript = !empty($sources['inline'][$position]);

						if ($hasScript) {

							$async = true;
						}

						$script[$position] .= '<script async defer src="' . array_shift($fileList) . '"'.$attr.'></script>';

						if ($hasScript) {

							$script[$position] .= '<script type="text/foo">' . trim(implode(';', $sources['inline'][$position]), ';') . '</script>';
							unset($sources['inline'][$position]);
						}
					}

					else {

						$script[$position] = '<script src="' . implode('"></script><script src="', $fileList) . '"></script>';
					}
				}
			}
		}

		if (!empty($sources['inline'])) {

			foreach($sources['inline'] as $position => $content) {

				if (!empty($content)) {

					$script[$position] .= '<script>' . trim(implode(';', $content), ';') . '</script>';
				}
			}
		}

		$strings = [];
		$replace = [];

		if ($async) {

			if (!isset($script['body'])) {

				$script['head'] = '';
			}
		}

		foreach ($script as $position => $content) {

			if (empty($content)) {

				continue;
			}

			$tag = '</'.$position.'>';
			$strings[] = $tag;
			$replace[] = $content.$tag;
		}

		if (!empty($strings)) {

			$html = str_replace($strings, $replace, $html);
		}

		if (!empty($comments)) {

			$html = str_replace(array_keys($comments), array_values($comments), $html);
		}

		return $html;
	}
}