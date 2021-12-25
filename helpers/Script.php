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
use Peast\Formatter\Compact;
use Peast\Peast;
use Peast\Renderer;

class ScriptHelper {

	public function processHTML (array $options, $html) {

		static $parser;
		$path = $options['js_path'];

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

		/**
		 * capture scripts marked as nomodule/module
		 */
		$modules = [];

		// parse scripts
		$html = preg_replace_callback('#<script([^>]*)>(.*?)</script>#si', function ($matches) use(&$sources, &$modules, $path, $fetch_remote, $ignore, $remove) {

			$attributes = [];

			if(preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';

			if (array_key_exists('nomodule', $attributes)) {

				$modules[] = $matches[0];
				return '';
			}

			// ignore custom type
			//   preg_match('#\btype=(["\'])(.*?)\1#', $matches[1], $match);
			if (isset($attributes['type']) && stripos($attributes['type'], 'javascript') === false) {

				if($attributes['type'] == 'module') {

					$modules[] = $matches[0];
					return '';
				}

				return $matches[0];
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

						$attributes['data-ignore'] = 'true';
						break;
					}
				}
			}

			if (isset($attributes['data-ignore'])) {

				unset($attributes['data-ignore']);
				unset($attributes['data-position']);

				$script = "\n".'<script';

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
				$sources['scripts'][$position][$name] = "\n".'<script' . $matches[1] . '></script>';
			}

			return '';

		}, $html);

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

					file_put_contents($js_file, implode(";\n", $content));
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

					$data = implode("\n", $js);

					if (!empty($data)) {

						if (is_null($parser)) {

							$parser = (new Renderer)->setFormatter(new Compact());
						}

						try {

							$sources['inline'][$position] = [trim($parser->render(Peast::latest($data)->parse(), false, false), ';')];
						}

						catch (\Exception $e) {

							error_log($e->getMessage()."\n".$e->getTraceAsString());

							$sources['inline'][$position] = [trim($data, ';')];
						}
					}
				}
			}
		}

		$script = [

			'head' => '',
			'body' => ''
		];

		$async = false;

		if (!empty($sources['files'])) {

			foreach($sources['files'] as $position => $fileList) {

				if (!empty($fileList)) {

					if (count($fileList) == 1) {

						$attr = '';
						$hasScript = !empty($sources['inline'][$position]);

						if ($hasScript) {

							$async = true;
						}

						$script[$position] .= "\n".'<script data-async defer src="' . array_shift($fileList) . '"'.$attr.'></script>'."\n";

						if ($hasScript) {

							$script[$position] .= "\n".'<script type="text/script">' . trim(implode(';', $sources['inline'][$position]), ';') . '</script>'."\n";
							unset($sources['inline'][$position]);
						}
					}

					else {

						$script[$position] = "\n".'<script src="' . implode('"></script>'."\n".'<script src="', $fileList) . '"></script>'."\n";
					}
				}
			}
		}

		if (!empty($sources['inline'])) {

			foreach($sources['inline'] as $position => $content) {

				if (!empty($content)) {

					$script[$position] .= "\n".'<script>' . trim(implode(';'."\n", $content), ';') . '</script>'."\n";
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

		if (!empty($modules)) {

			if (isset($script['body'])) {

				$script['body'] .= '<template data-type="module">'.implode("\n", $modules).'</template>';
			}

			else {

				$script['body'] = '<template data-type="module">'.implode("\n", $modules).'</template>';
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