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
use TBela\CSS\Compiler;
use TBela\CSS\Element;
use TBela\CSS\Element\Stylesheet;
use TBela\CSS\Parser;
use TBela\CSS\Renderer;
use TBela\CSS\Value;
use function file_get_contents;
use function file_put_contents;
use function getimagesize;
use function preg_replace_callback;

class CSSHelper
{

	/**
	 * @param string $html
	 * @param array $options
	 *
	 * @return string
	 *
	 * @throws Parser\SyntaxError
	 * @throws \Exception
	 * @since version
	 */
	public function processHTML($html, array $options = [])
	{

		$path = $options['css_path'];

		$fetch_remote = !empty($options['fetchcss']);

		$links = [];
//		$root = $options['uri_root'];
		$ignore = !empty($options['cssignore']) ? $options['cssignore'] : [];
		$remove = !empty($options['cssremove']) ? $options['cssremove'] : [];

		$async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

		$css_options = isset($options['css_options']) ? $options['css_options'] : [
			'compress' => !empty($options['minifycss'])
		];

		$hashFile = GZipHelper::getHashMethod($options);
		$cssParser = new Parser();
		$cssRenderer = new Renderer($css_options);
		$headStyle = new Stylesheet();

		$fetchFonts = function ($node) use($options, $headStyle) {

		if ((string) $node['name'] == 'font-face') {

			/**
			 * @var Element\AtRule $node
			 */
			$node->addDeclaration('font-display', $options['fontdisplay']);

			$query = $node->query('./[@name=src]');

			if ($query) {

				// copy only the src property of the font-face => the font will not block page loading
				// @font-face {
				//  src: url(...);
				// }
				$headStyle->append(end($query)->copy()->getRoot());
			}
		}
	};
		$parseUrls = function ($html) use($path) {

			return preg_replace_callback('~url\(([^)]+)\)~', function ($matches) use($path) {

				$url = preg_replace('~^(["\'])?([^\1]+)\1~', '$2', trim($matches[1]));

				if (strpos($matches[0], 'data:') !== false) {

					return $matches[0];
				}

				if (preg_match_all('~^(https?:)?//~', $url)) {

					$parts = explode('/', parse_url($url)['path']);
					$localName = $path . GZipHelper::shorten(crc32($url)) . '-' . end($parts);

					if (!is_file($localName)) {

						$data = Parser\Helper::fetchContent($url);

						if ($data !== false) {

							file_put_contents($localName, $data);
						}
					}

					if (is_file($localName)) {

						return 'url('.GZipHelper::url($localName).')';
					}
				}

				if (substr($url, 0, 1) != '/') {

					return 'url('.GZipHelper::url($url).')';
				}

				return $matches[0];
			}, $html);
		};

		$html = preg_replace_callback('#<('.(empty($options['parseinlinecss']) ? 'link' : '[^\s>]+').')([^>]*)>#', function ($matches) use ($css_options, $cssRenderer, $parseUrls, &$links, $ignore, $remove, $cssParser, $path, $fetch_remote, $options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if ($matches[1] != 'link') {

				if (!empty($options['parseinlinecss']) && !empty($attributes['style'])) {

					$attributes['style'] = str_replace("\n", '', trim(preg_replace('~^\.foo\s*\{([^}]+)\}~s', '$1', $cssRenderer->render((new Parser('.foo { '.preg_replace_callback('~url\(([^)]+)\)~', function ($matches) {

						$name = preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($matches[1]));

						if (strpos($matches[0], 'data:') !== false || !GZipHelper::isFile($name)) {

							return $matches[0];
						}

						return 'url('.GZipHelper::url($name).')';

					}, $attributes['style']).' }', $css_options))->parse()))));

					$result = '<'.$matches[1].' ';

					foreach ($attributes as $key => $value) {

						$result .= $key.'="'.$value.'" ';
					}

					return rtrim($result).'>';
				}

				return $matches[0];
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

							return $matches[0];
						}
					}

					if ($fetch_remote && preg_match('#^((https?:)?)//#', $name)) {

						$remote = $attributes['href'];

						if (strpos($name, '//') === 0) {

							$remote = $options['scheme'] . ':' . $name;
						}

						$parts = parse_url($remote);
						$local = $path . GZipHelper::shorten(crc32($remote)) . '-' . basename($parts['path']) . '.css';

						if (!is_file($local)) {

							$clone = clone $cssParser;
							$clone->load($remote);

							file_put_contents($local, $parseUrls($clone->parse()));
						}

						if (is_file($local)) {

							$name = $local;
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

		if (!empty($options['mergecss'])) {

			foreach ($links as $position => $blob) {

				$hash = '';

				if (!empty($blob['links'])) {

					foreach ($blob['links'] as $attr) {

						$hash .= $hashFile($attr['href']);
						$media = isset($attr['media']) && $attr['media'] != 'all' ? $attr['media'] : '';

						$hash .= $media;
					}

					$file = $path . GZipHelper::shorten(crc32($hash)) . '-main' . (!empty($css_options['compress']) ? '.min' : '') . '.css';

					if (!is_file($file)) {

						foreach ($blob['links'] as $key => $attr) {

							$cssParser->append($attr['href']);
						}

						file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse()->traverse($fetchFonts, 'enter')))));
					}

					$links[$position]['links'] = [
						[
							'href' => $file,
							'rel' => 'stylesheet'
						]
					];
				}
			}
		}

		if (empty($options['mergecss']) && !empty($css_options['compress'])) {

			foreach ($links as $position => $blob) {

				if (!empty($blob['links'])) {

					$file = '';

					foreach ($blob['links'] as $attr) {

						$hash = $hashFile($attr['href']) . (isset($attr['media']) && $attr['media'] != 'all' ? $attr['media'] : '');

						$file = $path . GZipHelper::shorten(crc32($hash)) . '-' .
							pathinfo($attr['href'], PATHINFO_BASENAME) .
							(!empty($css_options['compress']) ? '.min' : '') . '.css';

						if (!is_file($file)) {

							$cssParser->load($attr['href']);
							file_put_contents($file, $parseUrls($cssRenderer->render($this->parseBackgroundImages($cssParser->parse()->traverse($fetchFonts, 'enter'), $options))));
						}
					}

					$links[$position] = [
						[
							'href' => $file
						]
					];
				}
			}
		}

		$cssParser->setContent('');

		$css_hash = '';
		$stylesheets = [];

		foreach ($links as $values) {

			foreach ($values['links'] as $value) {

				if (isset($value['href']) && GZipHelper::isFile($value['href']) && isset($value['rel']) && $value['rel'] == 'stylesheet') {

					$media = !empty($link['media']) && $link['media'] != 'all' ? $link['media'] : '';
					$css_hash .= $value['href'].($media ? '-'.$media : '');
					$stylesheets[] = [$value['href'], $media];
				}
			}
		}

		$html = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use (&$links, $css_options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[1], $attr)) {

				foreach ($attr[2] as $k => $att) {

					$attributes[$att] = $attr[6][$k];
				}
			}

			if (isset($attributes['type']) && $attributes['type'] != 'text/css') {

				return $matches[0];
			}

			$position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';
			$links[$position]['style'][] = !empty($css_options['compress']) ? (new Compiler($css_options))->setContent($matches[2])->compile() : $matches[2];

			return '';
		}, $html);

		$parseCritical = !empty($options['criticalcssenabled']);
		$parseCssResize = !empty($options['imagecssresize']) && !empty($options['css_sizes']);
		$parseWebFonts = !empty($options['fontpreload']) || !isset($options);

		$criticalCss = null;
		$cssResize = null;
		$webFont = null;

		if ($parseWebFonts || $parseCritical || $parseCssResize) {

			if ($parseWebFonts) {

				$css_hash .= '|parseWebFonts';
			}

			if ($parseCritical) {

				$css_hash .= '|parseCritical'.$options['criticalcssclass'].$options['criticalcssclass'];
			}

			if ($parseCssResize) {

				$css_hash .= '|parseCssResize'.json_encode($options['css_sizes']);
			}

			$css_hash = $path .GZipHelper::shorten(crc32($css_hash)).(empty($options['compress']) ? '' : '.min').'.css';

			if (!is_file($css_hash) && !empty($stylesheets)) {

				foreach ($stylesheets as $stylesheet) {

					$cssParser->append($stylesheet[0], $stylesheet[1]);
				}

				$cssRoot = $cssParser->parse();

				if ($parseCritical && !empty($options['criticalcssclass'])) {

					$headStyle->appendCss($options['criticalcssclass']);
				}

				$query = [];

				if ($parseWebFonts) {

					// all fonts with an src attribute
//					$query[] = '@font-face/src/..';
				}

				if ($parseCritical && !empty($options['criticalcss'])) {

					$query[] = 'html, body';
					$query[] = implode(',', preg_split('#\n#s', $options['criticalcss'], -1, PREG_SPLIT_NO_EMPTY));
				}

				$nodes = $cssRoot->query(implode('|', $query));

				if (!empty($nodes)) {

					foreach ($nodes as $node) {

						$headStyle->append($node->copy()->getRoot());
					}
				}

//				if ($parseCssResize) {

//					$img_path = $options['img_path'];
//					$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
//					$const = constant('\Image\Image::'.$method);
//					$short_name = strtolower(str_replace('CROP_', '', $method));

					// all fonts with an src attribute
//					$this->parseBackgroundImages($headStyle, $options);
//				}

				$headStyle->deduplicate();
				file_put_contents($css_hash, $parseUrls($cssRenderer->render($headStyle)));
			}

			if (is_file($css_hash)) {

				if(!isset($links['head']['style'])) {

					$links['head']['style'] = [];
				}

				array_unshift($links['head']['style'], file_get_contents($css_hash));
			}
		}

		$headStyle->removeChildren();

		foreach ($links as $key => $blob) {

			if (!empty($blob['style'])) {

				$headStyle->appendCss(implode('', $blob['style']));
				unset($links[$key]['style']);
			}
		}

		$search = [];
		$replace = [];

		$head_string = '';
		$body_string = '';
		$noscript = '';

		if ($headStyle->hasChildren()) {

			$headStyle->deduplicate();
			$head_string .= '<style>' . $parseUrls($cssRenderer->render($headStyle)) . '</style>';
		}

		foreach ($links as $position => $blob) {

			if (isset($blob['links'])) {

				foreach ($blob['links'] as $key => $link) {

					if ($async) {

						$link['data-media'] = isset($link['media']) ? $link['media'] : 'all';
						$link['media'] = 'print';
					}

					//
					$css = '<link';

					reset($link);

					foreach ($link as $attr => $value) {

						$css .= ' ' . $attr . '="' . $value . '"';
					}

					$css .= '>';

					if ($async) {

						if (isset($link['media'])) {

							$noscript .= str_replace([' media="print"', 'data-media'], ['', 'media'], $css);
						} else {

							$noscript .= $css;
						}
					}

					$links[$position]['links'][$key] = $css;
				}

				${$position . '_string'} .= implode('', $links[$position]['links']);
			}

			if (!empty($blob['style'])) {

				$style = trim(implode('', $blob['style']));

				if ($style !== '') {

					${$position . '_string'} .= '<style>' . $style . '</style>';
				}
			}
		}

		if ($head_string !== '' || $noscript != '') {

			if ($noscript != '') {

				$head_string .= '<noscript>' . $noscript . '</noscript>';
			}

			$search[] = '</head>';
			$replace[] = $head_string . '</head>';
		}

		if ($body_string !== '') {

			$search[] = '</body>';
			$replace[] = $body_string . '</body>';
		}

		if (!empty($search)) {

			$html = str_replace($search, $replace, $html);
		}

		if (!empty($options['imagecssresize'])) {

			$html = preg_replace_callback('#<html([>]*)>#', function ($matches) {

				preg_match_all(GZipHelper::regexAttr, $matches[1], $attr);

				$attributes = [];

				foreach ($attr[2] as $key => $at) {

					$attributes[$at] = $attr[6][$key];
				}

				$attributes['class'] = isset($attributes['class']) ? $attributes['class'] . ' ' : '';
				$attributes['class'] .= 'resize-css-images';

				$result = '<html';

				foreach ($attributes as $key => $value) {

					$result .= ' ' . $key . '="' . $value . '"';
				}

				return $result . '>';

			}, $html, 1);
		}

		return $html;
	}

	public function parseBackgroundImages(Element $headStyle, array $options = []) {

		if (empty($options['imagecssresize']) || empty($options['css_sizes'])) {

			return $headStyle;
		}

		$img_path = $options['img_path'];
		$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
		$const = constant('\Image\Image::'.$method);
		$short_name = strtolower(str_replace('CROP_', '', $method));

		$stylesheet = new Stylesheet();

		foreach ($headStyle->query('[@name="background"]|[@name="background-image"]') as $property) {

			$images = [];
			$property->getValue()->map(function ($value) use(&$images) {

				/**
				 * @var Value $value
				 */

				if ($value->type == 'css-url') {

					$name = GZipHelper::getName(preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($value->arguments->{0})));

					if (GZipHelper::isFile($name)) {

						if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['jpg', 'png', 'webp'])) {

							$images[] = [

								'file' => $name,
								'size' => getimagesize($name)
							];
						}

						return Value::getInstance((object) [
							'name' => 'url',
							'type' => 'css-url',
							'arguments' => new Value\Set([
								Value::getInstance((object) [
									'type' => 'css-string',
									'value' => GZipHelper::url($name)
								])
							])
						]);
					}
				}

				return $value;
			});

			// ignore multiple backgrounds
			if (count($images) == 1) {

				foreach ($images as $settings) {

					$img = null;
					$hash = GZipHelper::shorten(crc32($settings['file']));

					foreach ($options['css_sizes'] as $size) {

						if ($size < $settings['size'][0]) {

							$crop = $img_path.$hash.'-'. $short_name.'-'.$size.'-'.basename($settings['file']);

							if (!is_file($crop)) {

								if (is_null($img)) {

									$img = new Image($settings['file']);

									if ($img->getWidth() > 1200) {

										$img->setSize(1200);
									}
								}

								$img->resizeAndCrop($size, null, $const)->save($crop);
							}

							if (is_file($crop)) {

								$property = $property->copy();
								$property->setName('background-image');
								$property->setValue('url('.GZipHelper::url($crop).')');

								$stylesheet->addAtRule('media', '(max-width:'.$size.'px)')->append($property->getRoot());
							}
						}
					}
				}
			}
		}

		$headStyle->insert($stylesheet, 0);

		return $headStyle;
	}
}