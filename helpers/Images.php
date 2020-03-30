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

class ImagesHelper {

	public function postProcessHTML ($html, array $options = []) {

		return preg_replace_callback('#<img (.*?)/?>#si', function ($matches) use ($options) {

			$attributes = [];

			if (preg_match_all(GZipHelper::regexAttr, $matches[1],$attrib)) {

				foreach ($attrib[2] as $key => $value) {

					$attributes[$value] = $attrib[6][$key];
				}
			}

			$result = '<img';

			foreach ($this->processHTMLAttributes($attributes, $options) as $key => $value) {

				$result .= ' '.$key.'="'.$value.'"';
			}

			return $result.'>';

		}, $html);
	}

	public function processHTMLAttributes ($attributes, array $options = []) {

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

						$name = $options['scheme'] . ':' . $name;
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

				$sizes = getimagesize($file);

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

								$img = imagecreatefromgif($file);
								break;

							case 'png':

								$img = imagecreatefrompng($file);
								break;

							case 'jpg':

								$img = imagecreatefromjpeg($file);
								break;
						}
					}

					if ($img) {

						imagewebp($img, $newFile);
					}

					if (is_file($newFile)) {

						$attributes['src'] = $options['webroot'].$newFile;
						$file = $newFile;
					}
				}

				$method = empty($options['imagesresizestrategy']) ? 'CROP_FACE' : $options['imagesresizestrategy'];
				//    $const = constant('\Image\Image::'.$method);
				$hash = sha1($file);
				$short_name = strtolower(str_replace('CROP_', '', $method));
				//   $crop =  $path.$hash.'-'. $short_name.'-'.basename($file);

				$image = $sizes === false ? null : new Image($file);

				$src = '';

				// generate svg placeholder for faster image preview
				if ($sizes !== false && !empty($options['imagesvgplaceholder'])) {

					switch ($options['imagesvgplaceholder']) {

						case 'lqip':

							$src = $this->generateLQIP(clone $image, $file, $options, $path, $hash, $method);
							break;

						case 'svg':
						default:

							$src = $this->generateSVGPlaceHolder(clone $image, $file, $options, $path, $hash, $method);
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

						/** @var string[] $images */
						$images = array_map(function ($size) use($file, $hash, $short_name, $path) {

							return $path.$hash.'-'.$short_name.'-'.$size.'-'.basename($file);

						}, $mq);

						$srcset = [];

						if ($maxwidth > 1200) {

							$image->setSize(1200);
						}

						foreach ($images as $k => $img) {

							if (!is_file($img)) {

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

	/**
	 * @param Image $image
	 * @param string $file
	 * @param array $options
	 * @param string  $path
	 * @param string $hash
	 * @param string $method
	 *
	 * @return bool|string
	 *
	 * @since 2.4.1
	 */
    public function generateLQIP($image, $file, array $options, $path, $hash, $method) {

        if (!empty($options['imagesvgplaceholder'])) {

        	if ($image->getWidth() <= 80) {

        		return '';
	        }

            $short_name = strtolower(str_replace('CROP_', '', $method));

        	$extension = WEBP ? 'webp' : $image->getExtension();
            $img = $path.$hash.'-lqip-'. $short_name.'-'.pathinfo($file, PATHINFO_FILENAME).'.'.$extension;

            if (!is_file($img)) {

                clone $image->setSize(80)->save($img, 1);
            }

            if (is_file($img)) {
                
                return 'data:image/'.$extension.';base64,'.base64_encode(file_get_contents($img));
            }
        }

        return '';
    }

	/**
	 * @param Image $image
	 * @param string $file
	 * @param array $options
	 * @param string  $path
	 * @param string $hash
	 * @param string $method
	 *
	 * @return bool|string
	 *
	 * @since 2.4.0
	 */
	public function generateSVGPlaceHolder($image, $file, array $options, $path, $hash, $method) {

		if (!empty($options['imagesvgplaceholder'])) {

			$short_name = strtolower(str_replace('CROP_', '', $method));

			$svg = $path.$hash.'-svg-'. $short_name.'-'.pathinfo($file, PATHINFO_FILENAME).'.svg';

			if (!is_file($svg)) {

				$clone = clone $image;
				file_put_contents($svg, 'data:image/svg+xml;base64,'.base64_encode($this->minifySVG($clone->load($file)->resizeAndCrop(min($clone->getWidth(), 1024), null, $method)->setSize(200)->toSvg())));
			}

			if (is_file($svg)) {

				return file_get_contents($svg);
			}
		}

		return '';
	}

    protected function parseSVGAttribute($value, $attr, $tag) {
	 
        // remove unit
        if ($tag == 'svg' && ($attr == 'width' || $attr == 'height')) {
           
           $value = (float) $value;
        }
        
	 // shrink color
	 if ($attr == 'fill') {
	
		if (preg_match('#rgb\s*\(([^)]+)\)#i', $value, $matches)) {
			
			$matches = explode(',', $matches[1]);
			$value = sprintf('#%02x%02x%02x', +$matches[0], +$matches[1], +$matches[2]);
		}
		 
		if(strpos($value, '#') === 0) {
			
			if (
				$value[1] == $value[2] &&
				$value[3] == $value[4] &&
				$value[5] == $value[6]) {

				return '#'.$value[1].$value[3].$value[5];
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
               
            
               return '.'.$matches[2];
            }
            
           return $matches[1].'.'.$matches[2];
           
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

    public function minifySVG ($svg /*, $options = [] */) {

        // remove comments & stuff
        $svg = preg_replace([
        
            '#<\?xml .*?>#',
            '#<!DOCTYPE .*?>#si',
            '#<!--.*?-->#s',
            '#<metadata>.*?</metadata>#s'
        ], '', $svg);
        
        // remove extra space
        $svg = preg_replace(['#([\r\n\t ]+)<#s', '#>([\r\n\t ]+)#s'],  ['<', '>'], $svg);
            
        $cdata = [];
        
        $svg = preg_replace_callback('#\s*<!\[CDATA\[(.*?)\]\]>#s', function ($matches) use(&$cdata) {
            
            $key = '--cdata'.crc32($matches[0]).'--';
            
            $cdata[$key] = '<![CDATA['."\n".preg_replace(['#^([\r\n\t ]+)#ms', '#([\r\n\t ]+)$#sm'], '', $matches[1]).']]>';
            
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
                
                   $attributes .= ' '.$value.'="'.$this->parseSVGAttribute($attrib[6][$key], $value, $matches[2]).'"';
               }
            }            
           
           return '<'.$matches[2].$attributes.$matches[4].'>';
       },  $svg);

       return str_replace(array_keys($cdata), array_values($cdata), $svg);
    }

}