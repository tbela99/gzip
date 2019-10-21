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

use function array_unshift;
use function base64_encode;
use function file_get_contents;
use function getimagesize;
use function str_replace;
use function strtolower;
use \DateTime as DateTime;
use \JProfiler as JProfiler;
use Patchwork\JSqueeze as JSqueeze;
use Patchwork\CSSmin as CSSMin;
use Sabberworm\CSS\Rule\Rule;
use \Sabberworm\CSS\RuleSet\AtRuleSet as AtRuleSet;
use \Sabberworm\CSS\CSSList\AtRuleBlockList as AtRuleBlockList;
use \Sabberworm\CSS\RuleSet\DeclarationBlock as DeclarationBlock;

define('WEBP', function_exists('imagewebp') && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false);

class GZipHelper {

    // match empty attributes <script async src="https://www.googletagmanager.com/gtag/js?id=UA-111790917-1" data-position="head">
    const regexAttr = '~([\r\n\t ])?([a-zA-Z0-9:-]+)((=(["\'])(.*?)\5)|([\r\n\t ]|$))?~m'; #s
    const regexUrl = '#url\(([^)]+)\)#';

	static $regReduce = '';
	
	static $route = '';

    // JURI::root(true);
    static $uri = '';

    // JURI::root();
	static $url = '';

    static $options = [];

    static $cssBackgrounds = [];
    static $hosts = [];

    static $static_types = [];

    static $images = array(
        "gif" => array('as' => 'image'),
        "jpg" => array('as' => 'image'),
    //    "jpeg" => array('as' => 'image'),
        "png" => array('as' => 'image'),
        "webp" => array('as' => 'image')
    );

    static $pushed = array(
        "gif" => array('as' => 'image'),
        "jpg" => array('as' => 'image'),
        "jpeg" => array('as' => 'image'),
        "png" => array('as' => 'image'),
        "webp" => array('as' => 'image'),
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

	public static function setHeader($name, $value, $replace = false) {

		if ($replace && !empty(static::$headers[$name])) {

			if (!is_array(static::$headers[$name])) {

				static::$headers[$name] = [static::$headers[$name]];
			}

			static::$headers[$name][] = $value;
		}

		else {

			static::$headers[$name] = $value;
		}
	}

	public static function getHeaders() {

		return static::$headers;
	}

	public static function register ($callback) {

	//	if (!in_array($callback, static::$callbacks)) {

			static::$callbacks[] = [$callback, ucwords(str_replace(['Helpers', 'Helper', 'Gzip'], '', get_class($callback)))];
	//	}
	}

	public static function trigger ($event, $html, $options = [], $parseAttributes = false) {

		$profiler = JProfiler::getInstance('Application');

		if ($parseAttributes) {

			if (!empty (static::$callbacks)) {

				return preg_replace_callback('#<([a-zA-Z0-9:-]+)\s([^>]+)>#s', function ($matches) use($options, $event) {

					$tag = $matches[1];
					$attributes = [];

					if (preg_match_all(static::regexAttr, $matches[2],$attrib)) {

						foreach ($attrib[2] as $key => $value) {

							$attributes[$value] = $attrib[6][$key];
						}

						foreach (static::$callbacks as  $callback) {

							if (is_callable([$callback[0], $event])) {

								//	var_dump($callback[0]);
								$attributes = call_user_func_array([$callback[0], $event], [$attributes, $options, $tag]);
							}
						}
					}

					$result = '<'.$tag;

					foreach ($attributes as $key => $value) {

						$result .= ' '.$key.($value === '' ? '' : '="'.$value.'"');
					}

					return $result .'>';

				}, $html);

				$profiler->mark('after'.$callback[1].$event);
			}
		}

		else {

			foreach (static::$callbacks as $callback) {

				if (is_callable([$callback[0], $event])) {

					$html = call_user_func_array([$callback[0], $event], [$html, $options]);
					$profiler->mark('after'.$callback[1].$event);
				}
			}
		}

		return $html;
	}

    public static function getChecksum($file, callable $hashFile, $algo = 'sha256', $integrity = false) {

        $hash = $hashFile($file);
        $path = (isset(static::$options['ch_path']) ? static::$options['ch_path'] : 'cache/z/ch/'.$_SERVER['SERVER_NAME'].'/') . $hash . '-' . basename($file) . '.checksum.php';

	    $checksum = [];

        if (is_file($path)) {

        	// $checksum defined in $path;
            include $path;

            if (isset($checksum['hash']) && $checksum['hash'] == $hash && isset($checksum['algo']) && $checksum['algo'] == $algo) {

                return $checksum;
            }
        }

        $checksum = [
            'hash' => $hash, //
            //    'crossorigin' => 'anonymous',
            'algo' => $algo,
            'integrity' => empty($algo) || $algo == 'none' || empty($integrity) ? '' : $algo . "-" . base64_encode(hash_file($algo, $file, true))
        ];

        file_put_contents($path, '<?php $checksum = ' . var_export($checksum, true) . ';');
        return $checksum;
	}

    public static function accepted() {

        return static::$accepted;
    }

    public static function canPush($file, $ext = null) {
        /*

          @see https://www.w3.org/TR/preload/#link-element-interface-extensions
          <audio>, <video>	<link rel=preload as=media href=...>
          <script>, Worker's importScripts	<link rel=preload as=script href=...>
          <link rel=stylesheet>, CSS @import	<link rel=preload as=style href=...>
          CSS @font-face	<link rel=preload as=font href=...>
          <img>, <picture>, srcset, imageset	<link rel=preload as=image href=...>
          SVG's <image>, CSS *-image	<link rel=preload as=image href=...>
          XHR, fetch	<link rel=preload href=...>
          Worker, SharedWorker	<link rel=preload as=worker href=...>
          <embed>	<link rel=preload as=embed href=...>
          <object>	<link rel=preload as=object href=...>
          <iframe>, <frame>	<link rel=preload as=document href=...>
         */
        $name = static::getName($file);

        /*
          if(!static::isFile($name)) {

          return false;
          }
         */

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

	/**
	 * @param array $options
	 *
	 * @return \Closure
	 *
	 * @since 1.0
	 */
    public static function getHashMethod($options = []) {

        $scheme = \JUri::getInstance()->getScheme();

		static $hash;

        if (is_null($hash)) {

			$salt = empty(static::$hosts) ? '' : json_encode(static::$hosts);
			$salt.= static::$route;

            $hash = !(isset($options['hashfiles']) && $options['hashfiles'] == 'content') ? function ($file) use($scheme, $salt) {

                if (!static::isFile($file)) {

                    return static::shorten(crc32($scheme. $salt. $file));
                }

                return static::shorten(crc32($scheme. $salt. filemtime($file)));

            } : function ($file) use($scheme, $salt) {

                if (!static::isFile($file)) {

                    return static::shorten(crc32($scheme. $salt . $file));
                }

                return static::shorten(crc32($scheme. $salt . hash_file('crc32b', $file)));
            };
        }

        return $hash;
    }

    public static function getContent($url, $options = [], $curlOptions = []) {

        if (strpos($url, '//') === 0) {

            $url = \JUri::getInstance()->getScheme() . ':' . $url;
        }

        $ch = curl_init($url);

        if (strpos($url, 'https://') === 0) {

            // Turn on SSL certificate verfication
            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        }

        if (!empty($curlOptions)) {

            curl_setopt_array($ch, $curlOptions);
        }

        if (!empty($options)) {

            // Tell the curl instance to talk to the server using HTTP POST
            curl_setopt($ch, CURLOPT_POST, count($options));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options));
        }

        // 1 second for a connection timeout with curl
        //    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        // Try using this instead of the php set_time_limit function call
        //    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        // Causes curl to return the result on success which should help us avoid using the writeback option
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        //    if(curl_errno($ch)) {
        //    }

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {

            error_log('curl error :: ' . $url . ' #' . curl_errno($ch) . ' :: ' . curl_error($ch));
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        return $result;
    }

	/**
	 * minify css files
	 */
    public static function js($file, $remote_service = true) {

        static $jsShrink;

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

        if (is_null($jsShrink)) {

            $jsShrink = new JSqueeze;
        }

        return trim($jsShrink->squeeze($content, false, false), ';');
    }

	/**
	 * minify css
	 */
    public static function css($file, $remote_service = true, $path = null) {

        static $minifier;

        $content = '';

        if (preg_match('#^(https?:)?//#', $file)) {

            if (strpos($file, '//') === 0) {

                $file = 'http:' . $file;
            }

            $content = static::getContent($file);

            if ($content === false) {

                return false;
            }
        }

        else if (is_file($file)) {

            $content = static::expandCss(file_get_contents($file), dirname($file));
        }

        else {

            return false;
        }

        if (is_null($minifier)) {

            $minifier = new CSSMin;
        }

        return $minifier->minify($content);
    }

    public static function url($file) {

		$hash = preg_split('~([#?])~', $file, 2, PREG_SPLIT_NO_EMPTY);
		$hash = isset($hash[2]) ? $hash[1].$hash[2]: '';

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

                    return static::getHost(\JURI::root(true).'/'.static::$route.static::$pwa_network_strategy . $hashFile($name) . '/' . $file.$hash);
                }
            }

            return preg_match('~^(https?:)?//~', $name) ? $name.$hash :  static::getHost('/' . $name.$hash);
        }

        return $file;
    }

    public static function resolvePath($path) {

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
    public static function expandCss($css, $path = null) {

        if (!is_null($path)) {

            if (!preg_match('#/$#', $path)) {

                $path .= '/';
            }
		}
		
        $css = preg_replace_callback('#url\(([^)]+)\)#', function ($matches) use($path) {

            $file = trim(str_replace(array("'", '"'), "", $matches[1]));

            if (strpos($file, 'data:') === 0) {

                return $matches[0];
			}
			
			$name = static::getName($file);

            if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                $file = static::resolvePath($path . trim(str_replace(array("'", '"'), "", $matches[1])));
            }

        //    else {

                if (preg_match('#^(https?:)?//#', $file)) {

                    $content = static::getContent($file);

                    if ($content !== false) {

                        preg_match('~(.*?)([#?].*)?$~', $file, $match);

                        $file = 'cache/z/'.static::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/css/'. static::shorten(crc32($file)) . '-' . basename($match[1]);

                        if (!is_file($file)) {

                            file_put_contents($file, $content);
                        }

                        if (isset($match[2])) {

                            $file .= $match[2];
                        }
                    }
				}
				
			//	else {

					if(preg_match('#^([a-z]+:)?//#', $path)) {

						$content = static::getContent($path.substr($file, 1));

						if ($content !== false) {

							preg_match('~(.*?)([#?].*)?$~', $file, $match);
	
							$file = 'cache/z/'.static::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/css/'. static::shorten(crc32($file)) . '-' . basename($match[1]);
	
							if (!is_file($file)) {
	
								file_put_contents($file, $content);
							}
	
							if (isset($match[2])) {
	
								$file .= $match[2];
							}
						}
					//	return $path.su
					}

					else {
							
						if ($file[0] == '/') {

							return 'url(' . static::getHost($file).')';
						}
					}

					return 'url(' . static::url($file).')';
        },
            //resolve import directive, note import directive in imported css will NOT be processed
            preg_replace_callback('#@import([^;]+);#s', function ($matches) use($path) {

                $file = trim($matches[1]);

                if (preg_match('#url\(([^)]+)\)#', $file, $m)) {

                    $file = $m[1];
                }

                $file = trim(str_replace(array("'", '"'), "", $file));

                if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                    $file = static::resolvePath($path . static::getName($file));
                }

                $isFile = static::isFile($file);

                return "\n" .
	            //    '/* @ import ' . $file . ' ' . dirname($file) . ' */' .
	            //    "\n" .
	                static::expandCss($isFile ? file_get_contents($file) : static::getContent($file), dirname($file), $path);
            }, preg_replace(['#/\*.*?\*/#s', '#@charset [^;]+;#si'], '', $css))
        );

        return $css;
    }

    public static function getHost($file) {

		if (preg_match('#^([a-z]+:)?//#i', $file)) {

			return $file;
		}

	    $count = count(static::$hosts);

	    if ($count > 0) {

		    $ext = \pathinfo(static::getName($file), PATHINFO_EXTENSION);

		    if (isset(static::$static_types[strtolower($ext)])) {

			    if ($count == 1) {

				    return static::$hosts[0].$file;
			    }

			    $host = crc32($file) % $count;

			    if ($host < 0) {

				    $host += $count;
			    }

			    return static::$hosts[$host].$file;
		    }
	    }

	    return $file;
	}

    public static function getName($name) {

        return preg_replace(static::$regReduce, '', preg_replace('~(#|\?).*$~', '', $name));
    }

    public static function shorten($value, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@') {

        $base = strlen($alphabet);
        $short = '';
		$id = sprintf('%u', $value);
		
        while ($id != 0) {
            $id = ($id - ($r = $id % $base)) / $base;
            $short = $alphabet{$r} . $short;
		}
		
		$response = ltrim($short, '0');

		if ($response === '' && $value !== '') {

			return '0';
		}

        return $response;
    }

    public static function isFile($name) {

        $name = static::getName($name);

        if (preg_match('#^(https?:)?//#i', $name)) {

            return false;
        }

        return is_file($name) || is_file(utf8_decode($name));
    }

    public static function extractFontFace($block, $options = []) {

        $content = '';

        if ($block instanceof AtRuleBlockList || $block instanceof AtRuleSet) {

            $atRuleName = $block->atRuleName();

            switch($atRuleName) {

                case 'media':

                    $result = '';

                    foreach ($block->getContents() as $b) {

                        $result .= static::extractFontFace($b, $options);
                    }

                    if($result !== '') {

                        $content .= '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $result . '}';
                    }

                    break;

                case 'font-face':

                	if (!empty($options['fontdisplay']) && !empty($block->getRules('src'))) {

                		$rule = new \Sabberworm\CSS\Rule\Rule('font-display');

                		$rule->setValue($options['fontdisplay']);
                		$block->addRule($rule);
	                }

                    $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . implode('', $block->getRules()) . '}';

                   break;
            }
        }

        return $content;
    }

    public static function extractCssRules($block, $regexp) {

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

                        $content .= static::extractCssRules($b, $regexp);
                    }

                    if ($content !== '') {

                        $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $content . '}';
                    }

                    return $content;
            }
        }

        return '';
    }

    public static function buildCssBackground($css, array $options = []) {

		$result = '';

        if (!empty($options['css_sizes'])) {

            if (preg_match_all(static::regexUrl, $css, $matches)) {

                $files = [];

                foreach($matches[1] as $file) {

                    if (static::isFile($file) && preg_match('#\.(png)|(jpg)#i', $file)) {

                        $size = \getimagesize($file);

                        reset($options['css_sizes']);

                        foreach ($options['css_sizes'] as $s) {

                            if ($size[0] > $s) {

                                $files[$s][] = ['file' => $file, 'width' => $s];
                            }
                        }
                    }
                }

                $image = new \Image\Image();
                
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
                        $crop =  $path.$hash.'-'. $short_name.'-'.$size.'-'.basename($file);

                        if (!is_file($crop)) {

                            $image->load($file);

                            if ($d['width'] > 1200) {

                                $image->setSize(1200);
                            }
                            
                            $image->resizeAndCrop($size, null, $method)->save($crop);
                        }

                        $replace[$file] = static::url($crop, $options);
                    }

                    if (!empty($replace)) {

                        $oCssParser = new \Sabberworm\CSS\Parser(str_replace(array_keys($replace), array_values($replace), $css));
                        $oCssDocument = $oCssParser->parse();

                        $css_background = '';

                        foreach ($oCssDocument->getContents() as $block) {

                            // extractCssBackground
                            $css_background .= static::extractCssBackground($block, $size, '.resize-css-images ');
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
        
            return \preg_replace_callback('#url\(([^)]+)\)#s', function ($matches) use($cachefiles) {

                $file = $matches[1];
                
                if ($cachefiles) {
                    
                    $file = static::url($file);
                }

                else {

                    if (static::isFile($file)) {

                        $file = \Juri::root(true).'/'.$file;
                    }
                }

                return 'url('.$file.')';

            }, $result);
        }

        return $result;
    }

    public static function extractCssBackground($block, $matchSize = null, $prefix = null) {

        if ($block instanceof DeclarationBlock) {

            $selectors = implode(',', $block->getSelectors());
            $rules = [];

            $block->createShorthands();

            foreach ($block->getRulesAssoc() as $rule) {

                $name = $rule->getRule();

                if ($name == 'background' || $name == 'background-image') {
                            
                    // extract rules and replace with @media (max-width: witdh) { selector { background-image: url() [, url(), url(), ...]}}
                    if(preg_match_all(static::regexUrl, $rule->getValue(), $matches)) {

                        $images = [];

                        $isValid = false;

                        foreach ($matches[1] as $file) {

                            $file = preg_replace('#(^["\'])([^\1]+)\1#', '$2', trim($file));

                            $fileName = static::getName($file);

                            if (strpos($fileName, static::$route) === 0) {

                                $fileName = preg_replace('#^'.static::$route.'(((nf)|(cf)|(cn)|(no)|(co))/)?[^/]+/(1/)?#', '', $fileName);
                            }

                            $images[] = static::url($fileName);

                            if (static::isFile($fileName) && preg_match('#\.(png)|(jpg)#i', $fileName)) {

                                $isValid = true;

                                if (!is_null($matchSize)) {

                                    $size = \getimagesize($fileName);

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

                        $content .= static::extractCssBackground($b, $matchSize, $prefix);
                    }

                    if ($content !== '') {

                        $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $content . '}';
                    }

                    return $content;
            }
        }

        return '';
    }

    public static function removeEmptyRulesets($block) {

        if ($block instanceof AtRuleBlockList) {

            $content = '';

            foreach ($block->getContents() as $b) {

                $content .= static::removeEmptyRulesets($b);
            }

            if ($content !== '') {

                $content = '@' . $block->atRuleName() . ' ' . $block->atRuleArgs() . '{' . $content . '}';
            }

            return $content;
        }

        else if ($block instanceof AtRuleSet) {

        //    $block->createShorthands();
            $rules = $block->getRules();

            if(empty($rules)) {

                return '';
            }

            return '@' . $block->atRuleName() . ' ' . $block->atRuleArgs() .'{' . implode('', $rules) . '}';
        }

        else if ($block instanceof DeclarationBlock) {

            $block->createShorthands();
            $rules = $block->getRules();

            if(empty($rules)) {

                return '';
            }

            return implode(', ', $block->getSelectors()) . '{' . implode('', $rules) . '}';
        }

        return '';
    }

    protected static function parseSVGAttribute($value, $attr, $tag) {
	 
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

    public static function minifySVG ($svg /*, $options = [] */) {

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
                        
            if (preg_match_all(static::regexAttr, $matches[3], $attrib)) {
               
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
                
                   $attributes .= ' '.$value.'="'.static::parseSVGAttribute($attrib[6][$key], $value, $matches[2]).'"';
               }
            }            
           
           return '<'.$matches[2].$attributes.$matches[4].'>';
       },  $svg);

       return str_replace(array_keys($cdata), array_values($cdata), $svg);
    }

	/**
	 * @param \Image\Image $image
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
    public static function generateLQIP($image, $file, array $options, $path, $hash, $method) {

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
	 * @param \Image\Image $image
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
	public static function generateSVGPlaceHolder($image, $file, array $options, $path, $hash, $method) {

		if (!empty($options['imagesvgplaceholder'])) {

			$short_name = strtolower(str_replace('CROP_', '', $method));

			$svg = $path.$hash.'-svg-'. $short_name.'-'.pathinfo($file, PATHINFO_FILENAME).'.svg';

			if (!is_file($svg)) {

				$clone = clone $image;
				file_put_contents($svg, 'data:image/svg+xml;base64,'.base64_encode(static::minifySVG($clone->load($file)->resizeAndCrop(min($clone->getWidth(), 1024), null, $method)->setSize(200)->toSvg())));
			}

			if (is_file($svg)) {

				return file_get_contents($svg);
			}
		}

		return '';
	}

	public static function parseUrl($url) {

		$result = parse_url($url);

		// match data: blob: etc ...
		if (preg_match('#^([^:]+\:)[^/]#', $url, $matches)) {

			return $matches[1];
		}

		$name = '';

		if (!isset($result['host'])) {

			$name = "'self'";
		}

		else {

			if (!isset($result['scheme'])) {

				$name = $result['scheme'].'://'.$result['host'];
			}

			else {

				$name = $result['scheme'].'://'.$result['host'];
			}
		}

		return $name;
	}
}
