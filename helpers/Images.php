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
use function getimagesize;
use function imagecreatefromgif;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagewebp;
use function is_file;

class ImagesHelper
{

	/**
	 * @since 2.9.0
	 * supported extensions
	 */
	const EXTENSIONS = ['png', 'gif', 'jpg', 'jpeg', 'webp', 'avif'];
	const CONVERT_TO = ['avif', 'webp'];

	/**
	 * @param array $options
	 * @param string $html
	 * @return string
	 * @since
	 */
	public function postProcessHTML(array $options, $html)
	{

		return preg_replace_callback('#<([^\s>]+) (.*?)/?>#si', function ($matches) use ($options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[2], $attrib)) {

				foreach ($attrib[2] as $key => $value) {

					$attributes[$value] = $attrib[6][$key];
				}
			}

			if (!empty($options['inlineimageconvert']) && !empty($attributes['style'])) {

				$attributes['style'] = preg_replace_callback('~url\(([^)]+)\)~', function ($matches) use ($options, &$attributes) {

					$name = preg_replace('~^(["\']?)([^\1]+)\1$~', '$2', trim($matches[1]));

					$file = GZipHelper::getName($name);

					if (!GZipHelper::isFile($file)) {

						$file = static::fetchRemoteImage($file, $options);
					}

					if (GZipHelper::isFile($file)) {

						$file = $this->convert($file, $options);

						//
						if (!empty($options['css_sizes'])) {

							$srcSet = $this->generateSrcSet($file, $options['css_sizes'], $options);

							if (!empty($srcSet)) {

								$attributes['data-bg-style'] = htmlentities(json_encode(array_map(GZipHelper::class.'::url', $srcSet)));
								return 'url(' . GZipHelper::url(end($srcSet)) . ')';
							}
						}

						return 'url(' . GZipHelper::url($file) . ')';
					}

					return $matches[0];

				}, $attributes['style']);
			}

			if ($matches[1] != 'img') {

				if (isset($attributes['style'])) {

					$result = '<' . $matches[1];

					foreach ($attributes as $key => $value) {

						$result .= ' ' . $key . '="' . $value . '"';
					}

					return $result . '>';
				}

				return $matches[0];
			}

			$result = '<img';

			foreach ($this->processHTMLAttributes($attributes, $options) as $key => $value) {

				$result .= ' ' . $key . '="' . $value . '"';
			}

			return $result . '>';

		}, $html);
	}

	public function processHTMLAttributes(array $attributes, array $options = [])
	{

		$path = $options['img_path'];
		$ignored_image = !empty($options['imageignore']) ? $options['imageignore'] : [];

		$file = null;
		$pathinfo = null;

		// ignore custom type
		if (isset($attributes['src'])) {

			$name = GZipHelper::getName($attributes['src']);

			if (!empty($ignored_image)) {

				foreach ($ignored_image as $pattern) {

					if (strpos($name, $pattern) !== false) {

						return $attributes;
					}
				}
			}

			$file = static::fetchRemoteImage($name, $options);

			if (GZipHelper::isFile($file)) {

				$file = GZipHelper::getName($file);
				$attributes['src'] = $file;

				if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), static::EXTENSIONS)) {

					return $attributes;
				}

				$sizes = getimagesize($file);
				$maxwidth = $sizes[0];
				$img = null;

				// end fetch remote files
				if (isset($options['imagedimensions'])) {

					if ($sizes !== false && !isset($attributes['width']) && !isset($attributes['height'])) {

						$attributes['width'] = $sizes[0];
						$attributes['height'] = $sizes[1];
					}
				}

				$file = $this->convert($file, $options);


				$attributes['src'] = $file;

				$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];

				$hash = sha1($file);
				$image = null; // $sizes === false ? null : new Image($file);
				$src = '';
				$imagesize = getimagesize($file);

				// generate svg placeholder for faster image preview
				if ($sizes !== false && !empty($options['imagesvgplaceholder'])) {

					$short_name = strtolower(str_replace('CROP_', '', $method));

					$extension = $options['imagesvgplaceholder'] != 'lqip' ? 'svg' : (WEBP ? 'webp' : pathinfo($file, PATHINFO_EXTENSION));
					$img = $path . $hash . '-' . $options['imagesvgplaceholder'] . '-' . $short_name . '-' . pathinfo($file, PATHINFO_FILENAME) . '.' . $extension;

					switch ($options['imagesvgplaceholder']) {

						case 'lqip':

							$src = '';

							if ($imagesize[0] > 320) {

								if (!is_file($img)) {

									if (is_null($image)) {

										$image = $this->initImage($file);
									}

									(clone $image)->setSize(80)->save($img, 1);
								}

								if (is_file($img)) {

									$src = 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($img));
								}
							}

							break;

						case 'svg':
						default:

							if (!is_file($img)) {

								if (is_null($image)) {

									$image = $this->initImage($file);
								}

								file_put_contents($img, 'data:image/svg+xml;base64,' . base64_encode($this->minifySVG((clone $image)->resizeAndCrop(min($imagesize[0], 1024), null, $method)->setSize(200)->toSvg())));
							}

							if (is_file($img)) {

								$src = file_get_contents($img);
							}

							break;
					}
				}

				if ($src !== '') {

					$class = !empty($attributes['class']) ? $attributes['class'] . ' ' : '';
					$attributes['class'] = $class . 'image-placeholder image-placeholder-' . strtolower($options['imagesvgplaceholder']);

					$attributes['src'] = $src;
					$attributes['data-src'] = $file;
				}

				if (!empty($options['imagesvgplaceholder'])) {

					$attributes['loading'] = 'lazy';
				}

				// responsive images?
				if ($sizes !== false && !empty($options['imageresize']) && !empty($options['sizes']) && empty($attributes['srcset'])) {

					// build mq based on actual image size
					$mq = [];

					foreach ($options['sizes'] as $size) {

						if ($size <= $maxwidth) {

							$mq[] = $size;
						}
					}

					$set = $this->generateSrcSet($file, $mq, $options);

					if (!empty($set)) {

						$srcset = [];

						foreach ($set as $size => $img) {

							$srcset[] = $img . ' ' . $size . 'w';
						}

						if (!empty($set)) {

							$attributes['data-src'] = end($set);
						}

						$mq = array_keys($set);
						$j = count($mq);

						for ($i = 0; $i < $j; $i++) {

							if ($i < $j - 1) {

								$mq[$i] = '(min-width: ' . ($mq[$i + 1] + 1) . 'px) ' . $mq[$i] . 'px';
							} else {

								$mq[$i] = '(min-width: 0) ' . $mq[$i] . 'px';
							}
						}

						$attributes['srcset'] = implode(', ', $srcset);
						$attributes['sizes'] = implode(',', $mq);
					}
				}
			}

			if (!isset($attributes['alt'])) {

				$attributes['alt'] = '';
			}
		}

		return $attributes;
	}

	public static function convert($file, array $options = [])
	{

		$basename = GZipHelper::sanitizeFileName(preg_replace('/(#|\?).*$/', '', pathinfo($file, PATHINFO_FILENAME)));
		$pathinfo = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if (in_array($pathinfo, static::CONVERT_TO)) {

			return $file;
		}

		if (!empty($options['imageconvert']) && !((AVIF && $pathinfo == 'avif') || (WEBP && $pathinfo == 'webp'))) {

			$ext = AVIF ? 'avif' : 'webp';

			$img = null;
			$path = $options['img_path'];
			$newFile = $path . $basename.substr(md5($file), 6) . '.'.$ext;

			if (!is_file($newFile)) {

				switch ($pathinfo) {

					case 'gif':

						$img = imagecreatefromgif($file);
						break;

					case 'png':

						$img = imagecreatefrompng($file);
						break;

					case 'jpg':

						$img = imagecreatefromjpeg($file);
						break;

					case 'webp':

						$img = imagecreatefromwebp($file);
						break;
				}

				if ($img) {

					imagepalettetotruecolor($img);

					if (AVIF) {

						imageavif($img, $newFile);
					}
					else {

						imagewebp($img, $newFile);
					}
				}
			}

			if (is_file($newFile)) {

				return $newFile;
			}
		}

		return $file;
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
	public static function generateSrcSet($file, array $sizes = [], array $options = [])
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

			// getimagesize does not yet support avif https://github.com/php/php-src/pull/5127
			if ($width == 0 && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'avif') {

				$width = (new Image($file))->getWidth();
			}

			$sizes = array_values(array_filter($sizes, function ($size) use ($width) {

				return $width > $size;
			}));

			if (empty($sizes)) {

				return [];
			}

			$file = static::convert($file, $options);

			$path = $options['img_path'];
			$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];

			$hash = substr(md5($file), 0, 4);
			$root = $path . GZipHelper::sanitizeFileName(pathinfo($file, PATHINFO_FILENAME)) . '-%s-' . $hash . '.' . pathinfo($file, PATHINFO_EXTENSION);

			$img = null;
			$image = null;

			foreach ($sizes as $size) {

				$img = sprintf($root, $size);

				if (!is_file($img)) {

					if (is_null($image)) {

						$image = static::initImage($file, $size);
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

	protected function parseSVGAttribute($value, $attr, $tag)
	{

		// remove unit
		if ($tag == 'svg' && ($attr == 'width' || $attr == 'height')) {

			$value = (float)$value;
		}

		// shrink color
		if ($attr == 'fill') {

			if (preg_match('#rgb\s*\(([^)]+)\)#i', $value, $matches)) {

				$matches = explode(',', $matches[1]);
				$value = sprintf('#%02x%02x%02x', +$matches[0], +$matches[1], +$matches[2]);
			}

			if (strpos($value, '#') === 0) {

				if (
					$value[1] == $value[2] &&
					$value[3] == $value[4] &&
					$value[5] == $value[6]) {

					return '#' . $value[1] . $value[3] . $value[5];
				}
			}
		}

		//trim float numbers to precision 1
		$value = preg_replace_callback('#(\d+)\.(\d)(\d+)#', function ($matches) {

			if ($matches[2] == 0) {

				return $matches[1];
			}

			if ($matches[1] == 0) {

				if ($matches[2] == 0) {

					return 0;
				}


				return '.' . $matches[2];
			}

			return $matches[1] . '.' . $matches[2];

		}, $value);

		if ($tag == 'path' && $attr == 'd') {

			// trim commands
			$value = str_replace(',', ' ', $value);
			$value = preg_replace('#([\r\n\t ]+)?([A-Z-])([\r\n\t ]+)?#si', '$2', $value);
		}

		// remove extra space
		$value = preg_replace('#[\r\t\n ]+#', ' ', $value);
		return trim($value);
	}

	public function minifySVG($svg)
	{

		// remove comments & stuff
		$svg = preg_replace([

			'#<\?xml .*?>#',
			'#<!DOCTYPE .*?>#si',
			'#<!--.*?-->#s',
			'#<metadata>.*?</metadata>#s'
		], '', $svg);

		// remove extra space
		$svg = preg_replace(['#([\r\n\t ]+)<#s', '#>([\r\n\t ]+)#s'], ['<', '>'], $svg);

		$cdata = [];

		$svg = preg_replace_callback('#\s*<!\[CDATA\[(.*?)\]\]>#s', function ($matches) use (&$cdata) {

			$key = '--cdata' . crc32($matches[0]) . '--';

			$cdata[$key] = '<![CDATA[' . "\n" . preg_replace(['#^([\r\n\t ]+)#ms', '#([\r\n\t ]+)$#sm'], '', $matches[1]) . ']]>';

			return $key;

		}, $svg);

		$svg = preg_replace_callback('#([\r\n\t ])?<([a-zA-Z0-9:-]+)([^>]*?)(\/?)>#s', function ($matches) {

			$attributes = '';

			if (preg_match_all(GZipHelper::regexAttr, $matches[3], $attrib)) {

				foreach ($attrib[2] as $key => $value) {

					if (
						//$value == 'id' ||
						// $value == 'viewBox' ||
						$value == 'preserveAspectRatio' ||
						$value == 'version') {

						continue;
					}

					if ($value == 'svg') {

						switch ($attrib[6][$key]) {

							case 'width':
							case 'height':
							case 'xmlns':

								break;

							default:

								continue 2;
						}
					}

					$attributes .= ' ' . $value . '="' . $this->parseSVGAttribute($attrib[6][$key], $value, $matches[2]) . '"';
				}
			}

			return '<' . $matches[2] . $attributes . $matches[4] . '>';
		}, $svg);

		return str_replace(array_keys($cdata), array_values($cdata), $svg);
	}

	public static function fetchRemoteImage($name, array $options = [])
	{

		$path = $options['img_path'];
		$file = preg_replace('/(#|\?).*$/', '', $name);
		$basename = preg_replace('/(#|\?).*$/', '', basename($name));
		$pathinfo = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

		if (!empty($options['imageremote']) && preg_match('#^(https?:)?//#', $name)) {

			if (isset(GZipHelper::$accepted[$pathinfo]) && strpos(GZipHelper::$accepted[$pathinfo], 'image/') !== false) {

				if (strpos($name, '//') === 0) {

					$name = $options['scheme'] . ':' . $name;
				}

				$local = $path . sha1($name) . '.' . GZipHelper::sanitizeFileName($pathinfo);

				if (!is_file($local)) {

					$content = GZipHelper::getContent($name);

					if ($content !== false) {

						file_put_contents($local, $content);
					}
				}

				if (is_file($local)) {

					return $local;
				}
			}
		}

		return $file;
	}

	/**
	 * @param $file
	 * @param int $size
	 * @return Image
	 *
	 * @since version
	 */
	private static function initImage($file, $size = 1200)
	{

		$image = new Image($file);

		if ($image->getWidth() > $size) {

			$image->setSize($size);
		}

		return $image;
	}
}