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
use Image\Image;
// use JURI;
use Patchwork\CSSmin;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\AtRuleSet;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use function file_get_contents;
use function file_put_contents;
use function getimagesize;
use function preg_replace_callback;

class CSSHelper {

	public function processHTML ($html, array $options = []) {

		$path = $options['css_path'];

		$fetch_remote = !empty($options['fetchcss']);

		$links = [];
		$ignore = !empty($options['cssignore']) ? $options['cssignore'] : [];
		$remove = !empty($options['cssremove']) ? $options['cssremove'] : [];

		$async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

		$html = preg_replace_callback('#<link([^>]*)>#', function ($matches) use(&$links, $ignore, $remove, $fetch_remote, $path, $options) {

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

					if ($fetch_remote && preg_match('#^((https?:)?)//#', $name)) {

						$remote = $attributes['href'];

						if (strpos($name, '//') === 0) {

							$remote = $options['scheme'] . ':' . $name;
						}

						$local = $path . preg_replace(array('#([.-]min)|(\.css)#', '#[^a-z0-9]+#i'), array('', '-'), $remote) .(empty($options['minifycss']) ? '' : '.min'). '.css';

						if (!is_file($local)) {

							$content = GZipHelper::getContent($remote);

							if ($content != false) {

								file_put_contents($local, $this->expandCss($content, dirname($remote), $options));
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

							$content = $this->css($name, $options);

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

								$css .= $this->expandCss(file_get_contents($name), dirname($name), $options);

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

						$links[$position]['links'] = [
							[
								'href' => $css_file,
								'rel' => 'stylesheet'
							]
						];
					}
				}
			}
		}

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

		$parseCritical = !empty($options['criticalcssenabled']);
		$parseCssResize = !empty($options['imagecssresize']);

		if ($parseCritical || $parseCssResize) {

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

						$css_file = $name . '.css';
						$css_hash = $name . '.php';

						$css_bg_file = $name . '-bg.css';
						$css_bg_hash = $name . '-bg.php';

						$content = null;

						if ($parseCssResize) {

							if (!is_file($css_bg_file) || file_get_contents($css_bg_hash) != $hashValue) {

								$content = file_get_contents($fname);

								$oCssParser = new Parser($content);
								$oCssDocument = $oCssParser->parse();

								$css_background = '';

								foreach ($oCssDocument->getContents() as $block) {

									// extractCssBackground
									$css_background .= $this->extractCssBackground($block);
								}

								if (!empty($css_background)) {

									if (!empty($minifier)) {

										$css_background = $minifier->minify($css_background);
									}
								}

								$background_css_path .= $css_background;

								file_put_contents($css_bg_file, $css_background);
								file_put_contents($css_bg_hash, $hashValue);
							}

							else {

								$background_css_path .= file_get_contents($css_bg_file);
							}
						}

						if ($parseCritical) {

							if (!is_file($css_file) || file_get_contents($css_hash) != $hashValue) {

								if (is_null($content)) {

									$content = file_get_contents($fname);
								}

								if (!isset($oCssParser)) {

									$oCssParser = new Parser($content);
								}

								if (!isset($oCssDocument)) {

									$oCssDocument = $oCssParser->parse();
								}

								$local_css = '';
								$local_font_face = '';

								foreach ($oCssDocument->getContents() as $block) {

									$local_css .= $this->extractCssRules(clone $block, $regexp);
									$local_font_face .= $this->extractFontFace(clone $block, $options);
								}

								$local_css = $local_font_face.$local_css;

								if (!empty($local_css)) {

									if (!empty($minifier)) {

										$local_css = $minifier->minify($local_css);
									}

									$local_css = $this->expandCss($local_css, dirname($css_file));
								}

								file_put_contents($css_file, $local_css);
								file_put_contents($css_hash, $hashValue);
							}

							else {

								$critical_path .= file_get_contents($css_file);
							}
						}
					}
				}
			}

			if ($background_css_path !== '') {

				$hash = crc32($background_css_path);

				$background_css_file = $path . $hash . '-build.css';
				$background_css_hash = $path . $hash . '-build.php';

				if (!is_file($background_css_file) || file_get_contents($background_css_hash) != $hash) {

					file_put_contents($background_css_file, $this->buildCssBackground($background_css_path, $options));
					file_put_contents($background_css_hash, $hash);
				}

				$background_css_path = file_get_contents($background_css_file);
			}

			$critical_path = $background_css_path.$critical_path;

			if (!empty($critical_path)) {

				//    array_unshift($css, $critical_path);                
				$links['head']['critical'] = empty($minifier) ? $critical_path : $minifier->minify($critical_path);
			}
		}

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
	
    public function extractFontFace($block, $options = []) {

        $content = '';

        if ($block instanceof AtRuleBlockList || $block instanceof AtRuleSet) {

            $atRuleName = $block->atRuleName();

            switch($atRuleName) {

                case 'media':

                    $result = '';

                    foreach ($block->getContents() as $b) {

                        $result .= $this->extractFontFace($b, $options);
                    }

                    if($result !== '') {

                        $content .= '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $result . '}';
                    }

                    break;

                case 'font-face':

                	if (!empty($options['fontdisplay']) && !empty($block->getRules('src'))) {

                		$rule = new Rule('font-display');

                		$rule->setValue($options['fontdisplay']);
                		$block->addRule($rule);
	                }

                    $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . implode('', $block->getRules()) . '}';

                   break;
            }
        }

        return $content;
    }

    public function extractCssRules($block, $regexp) {

        if ($block instanceof DeclarationBlock) {

            $matches = [];

            foreach ($block->getSelectors() as $selector) {

                if (preg_match($regexp, $selector)) {

                    $matches[] = $selector;
                }
            }

            if (!empty($matches)) {

                $block->createShorthands();
                return implode(', ', $matches) . '{' . implode('', $block->getRules()) . '}';
            }

            return '';

        } else if ($block instanceof AtRuleBlockList) {

            $atRuleName = $block->atRuleName();

            switch($atRuleName) {

                case 'media':

                    $content = '';

                    foreach ($block->getContents() as $b) {

                        $content .= $this->extractCssRules($b, $regexp);
                    }

                    if ($content !== '') {

                        $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $content . '}';
                    }

                    return $content;
            }
        }

        return '';
    }

    public function buildCssBackground($css, array $options = []) {

		$result = '';

        if (!empty($options['css_sizes'])) {

            if (preg_match_all(GZipHelper::regexUrl, $css, $matches)) {

                $files = [];

                foreach($matches[1] as $file) {

                	$name = GZipHelper::getName($file);

                    if (GZipHelper::isFile($name) && preg_match('#\.(png)|(jpg)#i', $name)) {

                        $size = getimagesize($name);

                        reset($options['css_sizes']);

                        foreach ($options['css_sizes'] as $s) {

                            if ($size[0] > $s) {

                                $files[$s][] = ['file' => $name, 'width' => $s];
                            }
                        }
                    }
                }

                $image = new Image();
                
                $path = $options['img_path'];
                $method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
                $const = constant('\Image\Image::'.$method);
                $short_name = strtolower(str_replace('CROP_', '', $method));

                foreach ($files as $size => $data) {

                    $replace = [];

                    foreach ($data as $d) {
                            
                        $file = $d['file'];
                        
                        // generate resized file & replace in css
                        $hash = sha1($file);
                        $crop = $path.$hash.'-'. $short_name.'-'.$size.'-'.basename($file);

                        if (!is_file($crop)) {

                            $image->load($file);

                            if ($d['width'] > 1200) {

                                $image->setSize(1200);
                            }
                            
                            $image->resizeAndCrop($size, null, $const)->save($crop);
                        }

                        $replace[$file] = GZipHelper::url($crop, $options);
                    }

                    if (!empty($replace)) {

                        $oCssParser = new Parser(str_replace(array_keys($replace), array_values($replace), $css));
                        $oCssDocument = $oCssParser->parse();

                        $css_background = '';

                        foreach ($oCssDocument->getContents() as $block) {

                            // extractCssBackground
                            $css_background .= $this->extractCssBackground($block, $size, '.resize-css-images ');
                        }

                        if (!empty($css_background)) {

                            $result .= '@media (max-width: '.$size.'px) {'.$css_background. '}';
                        }
                    }
                }
            }
		}
		
        if ($result !== '') {

            $cachefiles = !empty($options['cachefiles']);
        
            return preg_replace_callback('#url\(([^)]+)\)#s', function ($matches) use($cachefiles, $options) {

                $file = $matches[1];

                if ($cachefiles) {
                    
                    $file = GZipHelper::url($file);
                }

                else {

					$name = GZipHelper::getName($file);

					if (GZipHelper::isFile($name)) {

						$file = $options['webroot'].$name;
					}
                }

                return 'url('.$file.')';

            }, $result);
        }

        return $result;
    }

    public function extractCssBackground($block, $matchSize = null, $prefix = null) {

        if ($block instanceof DeclarationBlock) {

            $selectors = implode(',', $block->getSelectors());
            $rules = [];

            $block->createShorthands();

            foreach ($block->getRulesAssoc() as $rule) {

                $name = $rule->getRule();

                if ($name == 'background' || $name == 'background-image') {
                            
                    // extract rules and replace with @media (max-width: witdh) { selector { background-image: url() [, url(), url(), ...]}}
                    if(preg_match_all(GZipHelper::regexUrl, $rule->getValue(), $matches)) {

                        $images = [];

                        $isValid = false;

                        foreach ($matches[1] as $file) {

                            $file = preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($file));

                            $fileName = GZipHelper::getName($file);

                            if (strpos($fileName, GZipHelper::$route) === 0) {

                                $fileName = preg_replace('#^'.GZipHelper::$route.'(((nf)|(cf)|(cn)|(no)|(co))/)?[^/]+/(1/)?#', '', $fileName);
                            }

                            $images[] = GZipHelper::url($fileName);

                            if (GZipHelper::isFile($fileName) && preg_match('#\.(png)|(jpg)#i', $fileName)) {

                                $isValid = true;

                                if (!is_null($matchSize)) {

                                    $size = getimagesize($fileName);

                                    $isValid = $size[0] == $matchSize;
                                }
                            }
                        }

                        if ($isValid) {

                            $rules[] = $prefix.$selectors.'{background-image: url('.implode('),url(', $images).');}';
                        }
                    }
                }
            }

            if (!empty($rules)) {

                return implode('', $rules);
            }

            return '';

        } else if ($block instanceof AtRuleBlockList) {

            $atRuleName = $block->atRuleName();

            switch($atRuleName) {

                case 'media':

                    $content = '';

                    foreach ($block->getContents() as $b) {

                        $content .= $this->extractCssBackground($b, $matchSize, $prefix);
                    }

                    if ($content !== '') {

                        $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $content . '}';
                    }

                    return $content;
            }
        }

        return '';
    }

	/**
	 * @param string $path
	 * @param string|null $reference
	 *
	 *  /a, /b => /a
	 * /a/b/c, /a -> b/c
	 * images/gold/plates.png, images/ -> gold/plates.png
	 *
	 * @return string
	 * @since 2.8.0
	 */
	public function relativePath($path, $reference) {

		$ref0 = substr($reference, 0, 1);

		if ($reference == '/') {

			if ($path[0] == '/') {

				return substr($path, 1);
			}
		}

		if ($path[0] == '/' && $ref0 != '/') {

			return $path;
		}

		$result = [];

		$paths = explode('/', rtrim($path, '/'));
		$refs = explode('/', rtrim($reference, '/'));

		$i = count($refs);

		while($i--) {

			if ($refs[$i] == '.') {

				array_splice($refs, $i, 1);
			}

			else if ($refs[$i] == '..') {

				if ($i > 0) {

					array_splice($refs, $i - 1, 2);
				}
				else {

					// $refs[$i] = dirname current working directory?
					// unknown edge case
					array_splice($refs, $i, 1);
				}
			}
		}

		while ($refs) {

			if (empty($paths)) {

				break;
			}

			$r = array_shift($refs);

			$v = current($paths);

			if ($v !== $r) {

				$result[] = '..';

				while ($refs) {

					$result[] = '..';
					array_shift($refs);
				}
			}

			else if ($v === $r) {

				array_shift($paths);
			}
		}

		array_splice($result, count($result), 0, $paths);

		$i = $j = count($result);

		while ($i--) {

			if ($result[$i] == '.') {

				array_splice($result, $i, 1);
			}

			else if ($result[$i] == '..' && $i < $j && $result[$i + 1] != '..') {

				if ($i == 0 && $ref0 == '/' && $path[0] == '/') {

					$result[$i] = '';
				}
			}
		}

		return implode ('/', $result);
	}

    public function resolvePath($path) {

        if (strpos($path, '../') !== false) {

            $return = [];

            if (strpos($path, '/') === 0)
                $return[] = '/';

            foreach (explode('/', $path) as $p) {

                if ($p == '..') {

                    array_pop($return);
                } else {

                    $return[] = $p;
                }
            }

            return str_replace('/./', '', implode('/', $return));
        }

        return $path;
    }

    // parse @import
    public function expandCss($css, $path = null, array $options = []) {

        if (!is_null($path)) {

            if (!preg_match('#/$#', $path)) {

                $path .= '/';
            }
		}
		
        $css = preg_replace_callback('#url\(([^)]+)\)#', function ($matches) use($path, $options) {

            $file = trim(str_replace(array("'", '"'), "", $matches[1]));

            if (strpos($file, 'data:') === 0) {

                return $matches[0];
			}

            if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                $file = $this->resolvePath($path . trim(str_replace(array("'", '"'), "", $matches[1])));
            }

        //    else {

				preg_match('~(.*?)([#?].*)?$~', $file, $match);

            	$local = $options['css_path'] . GZipHelper::shorten(crc32($file)) . '-' . preg_replace('~[?#].*$~', '', basename($file));

            //	var_dump(['$loc' => $local]);

            	if (!is_file($local)) {

					$content = false;

					if (substr($file, 0, 2) == '//') {

						$file = $options['scheme'].$file;
					}

					if (preg_match('#^(https?:)?//#', $file)) {

						$content = GZipHelper::getContent($file);
					}

					else if (preg_match('#^([a-z]+:)?//#', $path)) {

						$content = GZipHelper::getContent($path . ($file[0] == '/' ? substr($file, 1) : $file));
					}

					if ($content !== false) {

						if (!is_file($local)) {

							file_put_contents($local, $content);
						}
					}
				}

            	if (is_file($local)) {

					if (isset($match[2])) {

						$local .= $match[2];
					}

					// basename instead of GZipHelper::url()?
					return 'url(' . basename($local) . ')';
				}

			return 'url(' . GZipHelper::url($file) . ')';
        },
            //resolve import directive, note import directive in imported css will NOT be processed
            preg_replace_callback('#@import([^;]+);#s', function ($matches) use($path, $options) {

                $file = trim($matches[1]);

                if (preg_match('#url\(([^)]+)\)#', $file, $m)) {

                    $file = $m[1];
                }

                $file = trim(str_replace(array("'", '"'), "", $file));

                if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                    $file = $this->resolvePath($path . GZipHelper::getName($file));
                }

                $isFile = GZipHelper::isFile($file);

                return "\n" .
	            //    '/* @ import ' . $file . ' ' . dirname($file) . ' */' .
	            //    "\n" .
	                $this->expandCss($isFile ? file_get_contents($file) : GZipHelper::getContent($file), dirname($file), $options);
            }, preg_replace(['#/\*.*?\*/#s', '#@charset [^;]+;#si'], '', $css))
        );

        return $css;
	}

	/**
	 * minify css
	 * @param string $file
	 * @param array $options
	 *
	 * @return bool|string
	 *
	 * @since 0.1
	 */
    public function css($file, array $options = []) {

        static $minifier;

        if (preg_match('#^(https?:)?//#', $file)) {

            if (strpos($file, '//') === 0) {

                $file = 'http:' . $file;
            }

            $content = GZipHelper::getContent($file);

            if ($content === false) {

                return false;
            }
        }

        else if (is_file($file)) {

            $content = $this->expandCss(file_get_contents($file), dirname($file), $options);
        }

        else {

            return false;
        }

        if (is_null($minifier)) {

            $minifier = new CSSMin;
        }

        return $minifier->minify($content);
    }
}