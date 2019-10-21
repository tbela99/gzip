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
use Patchwork\CSSmin;

class CSSHelper {

	public function postProcessHTML ($html, array $options = []) {

		$path = isset($options['css_path']) ? $options['css_path'] : 'cache/z/'.GZipHelper::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/css/';

		$fetch_remote = !empty($options['fetchcss']);
		$remote_service = !empty($options['minifycssservice']);

		$links = [];
		$ignore = !empty($options['cssignore']) ? $options['cssignore'] : [];
		$remove = !empty($options['cssremove']) ? $options['cssremove'] : [];

		$async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

		$html = preg_replace_callback('#<link([^>]*)>#', function ($matches) use(&$links, $ignore, $remove, $fetch_remote, $path) {

			$attributes = [];

			if(preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if (!empty($attributes)) {

				if (isset($attributes['rel']) && $attributes['rel'] == 'stylesheet' && isset($attributes['href'])) {

					$name = GZipHelper::getName($attributes['href']);

					$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';

					unset($attributes['data-position']);

					foreach ($remove as $r) {

						if (strpos($name, $r) !== false) {

							return '';
						}
					}

					foreach ($ignore as $i) {

						if (strpos($name, $i) !== false) {

							$links[$position]['ignored'][$name] = $attributes;
							return '';
						}
					}

					if ($fetch_remote && preg_match('#^(https?:)?//#', $name)) {

						$remote = $name;

						if (strpos($name, '//') === 0) {

							$remote = \JURI::getInstance()->getScheme() . ':' . $name;
						}

						$local = $path . preg_replace(array('#([.-]min)|(\.css)#', '#[^a-z0-9]+#i'), array('', '-'), $remote) . '.css';

						if (!is_file($local)) {

							$content = GZipHelper::getContent($remote);

							if ($content != false) {

								file_put_contents($local, GZipHelper::expandCss($content, dirname($remote), $path));
							}
						}

						if (is_file($local)) {

							$name = $local;
						} else {

							return '';
						}
					}

					if (GZipHelper::isFile($name)) {

						$attributes['href'] = $name;
						$links[$position]['links'][$name] = $attributes;
						return '';
					}
				}
			}

			return $matches[0];
		}, $html);

		$profiler = \JProfiler::getInstance('Application');
		$profiler->mark('afterParseLinks');

		//    $profiler->mark("done parse <link>");
		$hashFile = GZipHelper::getHashMethod($options);

		$minify = !empty($options['minifycss']);

		if ($minify) {

			foreach ($links as $position => $blob) {

				if (!empty($blob['links'])) {

					foreach ($blob['links'] as $key => $attr) {

						$name = GZipHelper::getName($attr['href']);

						if (!GZipHelper::isFile($name)) {

							continue;
						}

						$hash = $hashFile($name) . '-min';

						$cname = str_replace(['cache', 'css', 'min', 'z/cn/', 'z/no/', 'z/cf/', 'z/nf/', 'z/co/', 'z/'], '', $attr['href']);
						$cname = preg_replace('#[^a-z0-9]+#i', '-', $cname);

						$css_file = $path . $cname . '-min.css';
						$hash_file = $path . $cname . '.php';

						if (!is_file($css_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash) {

							$content = GZipHelper::css($name, $remote_service, $path);

							if ($content != false) {

								file_put_contents($css_file, $content);
								file_put_contents($hash_file, $hash);
							}
						}

						if (is_file($css_file) && is_file($hash_file) && file_get_contents($hash_file) == $hash) {

							$links[$position]['links'][$key]['href'] = $css_file;
						}
					}
				}
			}
		}

		$profiler->mark('afterMinifyLinks');

		if (!empty($options['mergecss'])) {

			foreach ($links as $position => $blob) {

				if (!empty($blob['links'])) {

					$hash = crc32(implode('', array_map(function ($attr) use($hashFile) {

						$name = GZipHelper::getName($attr['href']);

						if (!GZipHelper::isFile($name)) {

							return '';
						}

						return $hashFile($name) . '.' . $name;
					}, $blob['links'])));

					$hash = $path . GZipHelper::shorten($hash);

					$css_file = $hash . '.css';
					$css_hash = $hash . '.php';

					if (!is_file($css_file) || !is_file($css_hash) || file_get_contents($css_hash) != $hash) {

						$content = '';

						foreach ($blob['links'] as $attr) {

							$name = GZipHelper::getName($attr['href']);

							if (!GZipHelper::isFile($name)) {

								continue;
							}

							$local = $path . $hashFile($name) . '-' . preg_replace(array('#([.-]min)|(\.css)#', '#[^a-z0-9]+#i'), array('', '-'), $name) . '-xp.css';

							if (!is_file($local)) {

								$css = !empty($options['debug']) ? "\n" . ' /* @@file ' . $name . ' */' . "\n" : '';

								$media = isset($attr['media']) && $attr['media'] != 'all' ? '@media ' . $attr['media'] . ' {' : null;

								if (!is_null($media)) {

									$css .= $media;
								}

								//    $profiler->mark("merge expand " . $name . " ");

								$css .= GZipHelper::expandCss(file_get_contents($name), dirname($name), $path);

								//     $profiler->mark("done merge expand " . $attr['href'] . " ");

								if (!is_null($media)) {

									$css .= '}';
								}

								file_put_contents($local, $css);
							}

							$content .= file_get_contents($local);
						}

						if (!empty($content)) {

							file_put_contents($css_file, $content);
							file_put_contents($css_hash, $hash);
						}
					}

					if (is_file($css_file) && is_file($css_hash) && file_get_contents($css_hash) == $hash) {

						$links[$position]['links'] = array(
							[
								'href' => $css_file,
								'rel' => 'stylesheet'
							]
						);
					}
				}
			}
		}

		$profiler->mark('afterMergeLinks');

		$minifier = null;

		if ($minify) {

			$minifier = new CSSmin;
		}

		$html = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use(&$links, $minifier) {

			$attributes = [];

			if(preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if (isset($attributes['type']) && $attributes['type'] != 'text/css') {

				return $matches[0];
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';

			$links[$position]['style'][] = empty($minifier) ? $matches[2] : $minifier->minify($matches[2]);

			return '';
		}, $html);


		$profiler->mark('afterParseStyles');

		$parseCritical = !empty($options['criticalcssenabled']);
		$parseCssResize = !empty($options['imagecssresize']);

		if ($parseCritical || $parseCssResize) {

			//     $profiler->mark("critical path css lookup");

			$critical_path = isset($options['criticalcssclass']) ? $options['criticalcssclass'] : '';
			$background_css_path = '';

			$styles = ['html', 'body'];

			if ($parseCritical) {

				if (!empty($options['criticalcss'])) {

					$styles = array_filter(array_map('trim', array_merge($styles, preg_split('#\n#s', $options['criticalcss'], -1, PREG_SPLIT_NO_EMPTY))));

					// really needed?
					preg_match('#<((body)|(html))(\s[^>]*?)? class=(["\'])([^>]*?)\5>#si', $html, $match);

					if (!empty($match[6])) {

						$styles = array_unique(array_merge($styles, explode(' ', $match[6])));
					}
				}

				foreach ($styles as &$style) {

					$style = preg_quote(preg_replace('#\s+([>+\[:,{])\s+#s', '$1', $style), '#');
					unset($style);
				}
			}

			# '#((html)|(body))#si'
			$regexp = '#(^|[>\s,~+},])((' . implode(')|(', $styles) . '))([\s:,~+\[{>,]|$)#si';

			foreach($links as $blob) {

				if (!empty($blob['links'])) {

					foreach ($blob['links'] as $k => $link) {

						$fname = GZipHelper::getName($link['href']);

						if (!GZipHelper::isFile($fname)) {

							continue;
						}

						$info = pathinfo($fname);

						$hash = base_convert (crc32($info['filename'] . '.' . $regexp . '.' . $fname), 10, 36);
						$hashValue = $hashFile($fname);

						$name = $info['dirname'] . '/' . $info['filename'] . '-'. $hash . '-crit';
						$bgname = $info['dirname'] . '/' . $info['filename'] . '-'. $hash . '-bg';

						$css_file = $name . '.css';
						$css_hash = $name . '.php';

						$css_bg_file = $name . '-bg.css';
						$css_bg_hash = $name . '-bg.php';

						$content = null;

						if ($parseCssResize) {

							if (!is_file($css_bg_file) || file_get_contents($css_bg_hash) != $hashValue) {

								$content = file_get_contents($fname);

								$oCssParser = new \Sabberworm\CSS\Parser($content);
								$oCssDocument = $oCssParser->parse();

								$css_background = '';

								foreach ($oCssDocument->getContents() as $block) {

									// extractCssBackground
									$css_background .= GZipHelper::extractCssBackground($block);
								}

								if (!empty($css_background)) {

									if (!empty($minifier)) {

										$css_background = $minifier->minify($css_background);
									}
								}

								//	$css_background = GZipHelper::expandCss($css_background, dirname($css_bg_file));
								$background_css_path .= $css_background;

								file_put_contents($css_bg_file, $css_background);
								file_put_contents($css_bg_hash, $hashValue);
							}

							else {

								$background_css_path .= file_get_contents($css_bg_file);
							}

							$profiler->mark('afterParseCssBGResize'.$k);
						}

						if ($parseCritical) {

							if (!is_file($css_file) || file_get_contents($css_hash) != $hashValue) {

								if (is_null($content)) {

									$content = file_get_contents($fname);
								}

								if (!isset($oCssParser)) {

									$oCssParser = new \Sabberworm\CSS\Parser($content);
								}

								if (!isset($oCssDocument)) {

									$oCssDocument = $oCssParser->parse();
								}

								$local_css = '';
								$local_font_face = '';

								foreach ($oCssDocument->getContents() as $block) {

									$local_css .= GZipHelper::extractCssRules(clone $block, $regexp);
									$local_font_face .= GZipHelper::extractFontFace(clone $block, $options);
								}

								$local_css = $local_font_face.$local_css;

								if (!empty($local_css)) {

									if (!empty($minifier)) {

										$local_css = $minifier->minify($local_css);
									}

									$local_css = GZipHelper::expandCss($local_css, dirname($css_file));

									\file_put_contents($css_file, $local_css);
									\file_put_contents($css_hash, $hashValue);
								}
							}

							else {

								$critical_path .= file_get_contents($css_file);
							}
						}
					}
				}

				$profiler->mark('afterParseCssBGResize');
			}

			if ($background_css_path !== '') {

				$hash = crc32($background_css_path);

				$background_css_file = $path . $hash . '-build.css';
				$background_css_hash = $path . $hash . '-build.php';

				if (!is_file($background_css_file) || file_get_contents($background_css_hash) != $hash) {

					\file_put_contents($background_css_file, GZipHelper::buildCssBackground($background_css_path, $options));
					\file_put_contents($background_css_hash, $hash);
				}

				$background_css_path = \file_get_contents($background_css_file);
			}

			$critical_path = $background_css_path.$critical_path;

			if (!empty($critical_path)) {

				//    array_unshift($css, $critical_path);                
				$links['head']['critical'] = empty($minifier) ? $critical_path : $minifier->minify($critical_path);
			}
		}

		$profiler->mark('afterParseCriticalCss');

		// extract web fonts
		//   $profiler->mark("extract web fonts");

		$css = '';
		$web_fonts = '';

		if (!empty($links['head']['critical'])) {

			$css .= $links['head']['critical'];
		}

		foreach($links as $blob) {

			if (!empty($blob['style'])) {

				$css .= implode('', $blob['style']);
			}
		}

		// font preloading - need to be fixed, an invalid url is returned
		if(preg_match_all('#url\(([^)]+)\)#', $css, $fonts)) {

			$web_fonts = implode("\n", array_unique(array_map(function ($url) use($path) {

				$url = preg_replace('#(^["\'])([^\1])\1#', '$2', trim($url));

				$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));

				if(isset(GZipHelper::$accepted[$ext]) && strpos(GZipHelper::$accepted[$ext], 'font') !== false) {

					//
					return '<!-- $path '.$path.' - $url '.$url.' --><link rel="preload" href="'.$url.'" as="font">';
				}

				return false;

			}, $fonts[1])));
		}

		if (!empty($web_fonts)) {

			$links['head']['webfonts'] = empty($minifier) ? $web_fonts : $minifier->minify($web_fonts);
		}

		$search = [];
		$replace = [];

		$head_string = '';
		$body_string = '';
		$noscript = '';

		if (isset($links['head']['webfonts'])) {

			$search[] = '<head>';
			$replace[] = '<head>'.$links['head']['webfonts'];
			unset($links['head']['webfonts']);
		}

		if (isset($links['head']['critical'])) {

			$head_string .= '<style>'.$links['head']['critical'].'</style>';
			unset($links['head']['critical']);
		}

		foreach ($links as $position => $blob) {

			if (isset($blob['links'])) {

				foreach ($blob['links'] as $key => $link) {

					if ($async) {

						//     $link['onload'] = '_l(this)'

						$link['data-media'] = isset($link['media']) ? $link['media'] : 'all';
						$link['media'] = 'print';
					}

					// 
					$css = '<link';

					reset($link);

					foreach ($link as $attr => $value) {

						$css .=' '.$attr.'="'.$value.'"';
					}

					$css .= '>';

					if ($async) {

						if (isset($link['media'])) {

							$noscript .= str_replace([' media="print"', 'data-media'], ['', 'media'], $css);
						}

						else {

							$noscript .= $css;
						}
					}

					$links[$position]['links'][$key] = $css;
				}

				${$position.'_string'} .= implode('', $links[$position]['links']);
			}

			if (!empty($blob['style'])) {

				$style = trim(implode('', $blob['style']));

				if ($style !== '') {

					${$position.'_string'} .= '<style>'.$style.'</style>';
				}
			}
		}

		if ($head_string !== '' || $noscript != '') {

			if ($noscript != '') {

				$head_string .= '<noscript>'.$noscript.'</noscript>';
			}

			$search[] = '</head>';
			$replace[] = $head_string.'</head>';
		}

		if ($body_string !== '') {

			$search[] = '</body>';
			$replace[] = $body_string.'</body>';
		}

		if (!empty($search)) {

			$html = str_replace($search, $replace, $html);
		}

		if(!empty($options['imagecssresize'])) {

			$html = preg_replace_callback('#<html([>]*)>#', function ($matches) {

				preg_match_all(GZipHelper::regexAttr, $matches[1], $attr);

				$attributes = [];

				foreach($attr[2] as $key => $at) {

					$attributes[$at] = $attr[6][$key];
				}

				$attributes['class'] = isset($attributes['class']) ? $attributes['class'].' ' : '';
				$attributes['class'] .= 'resize-css-images';

				$result = '<html';

				foreach ($attributes as $key => $value) {

					$result .= ' '.$key.'="'.$value.'"';
				}

				return $result .'>';

			}, $html, 1);
		}

		return $html;
	}
}