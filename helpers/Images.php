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

class ImagesHelper {

	public function processHTMLAttributes ($attributes, array $options = [], $tag) {

		if (strtolower($tag) != 'img') {

			return $attributes;
		}

		$path = $options['img_path'];
		$ignored_image = !empty($options['imageignore']) ? $options['imageignore'] : [];

		$file = null;
		$pathinfo = null;

		// ignore custom type
		if (isset($attributes['src'])) {

			$name = GZipHelper::getName($attributes['src']);
			$file = preg_replace('/(#|\?).*$/', '', $name);

			if (!empty($ignored_image)) {

				foreach($ignored_image as $pattern) {

					if (strpos($name, $pattern) !== false) {

						return $attributes;
					}
				}
			}

			$basename = preg_replace('/(#|\?).*$/', '', basename($name));
			$pathinfo = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

			if (!empty($options['imageremote']) && preg_match('#^(https?:)?//#', $name)) {

				if (isset(GZipHelper::$accepted[$pathinfo]) && strpos(GZipHelper::$accepted[$pathinfo], 'image/') !== false) {

					if (strpos($name, '//') === 0) {

						$name = \JURI::getInstance()->getScheme() . ':' . $name;
					}

					$local = $path . sha1($name) . '.' . $pathinfo;

					if (!is_file($local)) {

						$content = GZipHelper::getContent($name);

						if ($content !== false) {

							file_put_contents($local, $content);
						}
					}

					if (is_file($local)) {

						$attributes['src'] = $local;
						$file = $local;
					}
				}
			}

			if (GZipHelper::isFile($file)) {

				$file = GZipHelper::getName($file);
				if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['png', 'gif', 'jpg', 'jpeg'])) {

					return $attributes;
				}

				$sizes = \getimagesize($file);

				$maxwidth = $sizes[0];
				$img = null;

				// end fetch remote files
				if(isset($options['imagedimensions'])) {

					if ($sizes !== false && !isset($attributes['width']) && !isset($attributes['height'])) {

						$attributes['width'] = $sizes[0];
						$attributes['height'] = $sizes[1];
					}
				}

				if (!empty($options['imageconvert']) && WEBP && $pathinfo != 'webp') {

					$newFile = $path.sha1($file).'-'.pathinfo($file, PATHINFO_FILENAME).'.webp';

					if (!is_file($newFile)) {

						switch ($pathinfo) {

							case 'gif':

								$img = \imagecreatefromgif($file);
								break;

							case 'png':

								$img = \imagecreatefrompng($file);
								break;

							case 'jpg':

								$img = \imagecreatefromjpeg($file);
								break;
						}
					}

					if ($img) {

						\imagewebp($img, $newFile);
					}

					if (\is_file($newFile)) {

						$attributes['src'] = $newFile;
						$file = $newFile;
					}
				}

				$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
				//    $const = constant('\Image\Image::'.$method);
				$hash = sha1($file);
				$short_name = strtolower(str_replace('CROP_', '', $method));
				//   $crop =  $path.$hash.'-'. $short_name.'-'.basename($file);

				$image = $sizes === false ? null : new \Image\Image($file);

				$src = '';

				// generate svg placeholder for faster image preview
				if ($sizes !== false && !empty($options['imagesvgplaceholder'])) {

					switch ($options['imagesvgplaceholder']) {

						case 'lqip':

							$src = GZipHelper::generateLQIP(clone $image, $file, $options, $path, $hash, $method);
							break;

						case 'svg':
						default:

							$src = GZipHelper::generateSVGPlaceHolder(clone $image, $file, $options, $path, $hash, $method);
							break;
					}
				}

				if ($src !== '') {

					$class = !empty($attributes['class']) ? $attributes['class'].' ' : '';
					$attributes['class'] = $class.'image-placeholder image-placeholder-'.strtolower($options['imagesvgplaceholder']);

					$attributes['src'] = $src;
					$attributes['data-src'] = $file;
				}

				if (!empty($options['imagesvgplaceholder'])) {

					$attributes['loading'] = 'lazy';
				}

				// responsive images?
				if ($sizes !== false && !empty($options['imageresize']) && !empty($options['sizes']) && empty($attributes['srcset'])) {

					// build mq based on actual image size
					$mq = array_filter ($options['sizes'], function ($size) use($maxwidth) {

						return $size <= $maxwidth;
					});

					if (!empty($mq)) {

						$mq = array_values($mq);

						//    $image = null;
						$resource = null;

						$images = array_map(function ($size) use($file, $hash, $short_name, $path) {

							return $path.$hash.'-'.$short_name.'-'.$size.'-'.basename($file);

						}, $mq);

						$srcset = [];

						if ($maxwidth > 1200) {

							$image->setSize(1200);
						}

						foreach ($images as $k => $img) {

							if (!\is_file($img)) {

								$cloneImg = clone $image;
								$cloneImg->resizeAndCrop($mq[$k], null, $method)->save($img);
							}

							$srcset[] = $img.' '.$mq[$k].'w';
						}

						if (!empty($images)) {

							$attributes['data-src'] = end($images);
						}

						if ($sizes[0] > $mq[0]) {

							array_unshift($srcset, $file.' '.$sizes[0].'w');
							array_unshift($mq, $sizes[0]);
						}

						//    $mq[] = '(min-width: '.$maxwidth.'px)';

						$j = count($mq);

						for ($i = 0; $i < $j; $i++) {

							if ($i < $j - 1) {

								$mq[$i] = '(min-width: '.($mq[$i + 1] + 1).'px) '.$mq[$i].'px';
							}
							else {

								$mq[$i] = '(min-width: 0) '.$mq[$i].'px';
							}
						}

						if (!empty($mq)) {

							$attributes['data-srcset'] = implode(',', array_map(function ($url) {

								$data = explode(' ', $url, 2);

								if (count($data) == 2) {

									return $data[0].' '.$data[1];
								}

								return $url;

							}, $srcset));
							$attributes['sizes'] = implode(',', $mq);
						}
					}
				}
			}

			if (!isset($attributes['alt'])) {

				$attributes['alt'] = '';
			}
		}


		return $attributes;
	}

}