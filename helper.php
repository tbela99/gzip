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

use Patchwork\JSqueeze as JSqueeze;
use Patchwork\CSSmin as CSSMin;
use \Sabberworm\CSS\RuleSet\AtRuleSet as AtRuleSet;
use \Sabberworm\CSS\CSSList\AtRuleBlockList as AtRuleBlockList;
use \Sabberworm\CSS\RuleSet\DeclarationBlock as DeclarationBlock;

define('WEBP', function_exists('imagewebp') && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false);

class GZipHelper {

    // match empty attributes <script async src="https://www.googletagmanager.com/gtag/js?id=UA-111790917-1" data-position="head">
    const regexAttr = '~([\r\n\t ])?([a-zA-Z0-9:-]+)((=(["\'])(.*?)\5)|([\r\n\t ]|$))?~m'; #s
    const regexUrl = '#url\(([^)]+)\)#';
    static $options = [];
    static $regReduce;

    static $cssBackgrounds = [];

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
        "txt" => [],
        "js" => array('as' => 'script'),
        "css" => array('as' => 'style'),
        "xml" => [],
        "pdf" => [],
        "eot" => array('as' => 'font'),
        "otf" => array('as' => 'font'),
        "ttf" => array('as' => 'font'),
        "woff" => array('as' => 'font'),
        "woff2" => array('as' => 'font'),
        "svg" => array('as' => 'image')
    );

    // can use http cache / url rewriting
    static $accepted = array(
        "gif" => "image/gif",
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "webp" => "image/webp",
        "swf" => "application/x-shockwave-flash",
        "ico" => "image/x-icon",
        "txt" => "text/plain",
        "js" => "text/javascript",
        "css" => "text/css",
        "xml" => "text/xml",
        "pdf" => "application/pdf",
        "eot" => "application/vnd.ms-fontobject",
        "otf" => "application/x-font-otf",
        "ttf" => "application/x-font-ttf",
        "woff" => "application/x-font-woff",
        "woff2" => "application/font-woff2",
        "svg" => "image/svg+xml",
        'mp3' => 'audio/mpeg'
    );

    static $pwa_network_strategy = '';

    public static function getChecksum($file, callable $hashFile, $algo = 'sha256', $integrity = false) {

        $hash = $hashFile($file);

        $path = (isset(static::$options['ch_path']) ? static::$options['ch_path'] : 'cache/z/ch/'.$_SERVER['SERVER_NAME'].'/') . $hash . '-' . basename($file) . '.checksum.php';

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

    public static function getHashMethod($options = []) {

        $scheme = \JUri::getInstance()->getScheme();

        static $hash;

        if (is_null($hash)) {

            $hash = !(isset($options['hashfiles']) && $options['hashfiles'] == 'content') ? function ($file) use($scheme) {

                if (!static::isFile($file)) {

                    return static::shorten(crc32($scheme . $file));
                }

                return static::shorten(crc32($scheme . filemtime($file)));
            } : function ($file) use($scheme) {

                if (!static::isFile($file)) {

                    return static::shorten(crc32($scheme . $file));
                }

                return static::shorten(crc32($scheme . hash_file('crc32b', $file)));
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

    public static function parseURLs($body, array $options = []) {

        if (empty($options['cachefiles'])) {

            return $body;
        }

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $accepted = static::accepted();
        $hashFile = static::getHashMethod($options);

        $replace = [];
        $body = preg_replace_callback('#<!--.*?-->#s', function ($matches) use(&$replace) {

            $hash = '--ht' . crc32($matches[0]) . 'ht--';
            $replace[$hash] = $matches[0];

            return $hash;

        }, $body);

        $body = preg_replace_callback('#(<script(\s[^>]*)?>)(.*?)</script>#s', function ($matches) use(&$replace) {

            $hash = '--ht' . crc32($matches[3]) . 'ht--';
            $replace[$hash] = $matches[3];

            return $matches[1].$hash.'</script>';
        }, $body);

        $body = preg_replace_callback('#(<style(\s[^>]*)?>)(.*?)</style>#s', function ($matches) use(&$replace) {

            $hash = '--ht' . crc32($matches[3]) . 'ht--';
            $replace[$hash] = $matches[3];

            return $matches[1].$hash.'</style>';
        }, $body);

        // TODO: parse url() in styles
        $pushed = [];
        $types = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && isset($options['h2push']) ? array_flip($options['h2push']) : [];

        $base = \JUri::root(true) . '/';

        $hashmap = array(
            'style' => 0,
            'font' => 1,
            'script' => 2
        );

        $checksum = !empty($options['checksum']) ? $options['checksum'] : false;

        $domains = [];

        $body = preg_replace_callback('#<([a-zA-Z0-9:-]+)\s([^>]+)>#s', function ($matches) use($checksum, $hashFile, $accepted, &$domains, &$pushed, $types, $hashmap, $base, $options) {

            $tag = $matches[1];

            return '<'.$matches[1].' '.preg_replace_callback(static::regexAttr, function ($matches) use($tag, $checksum, $hashFile, $accepted, &$domains, &$pushed, $types, $hashmap, $base, $options) {

                $attr = strtolower($matches[2]);

                if ($attr == 'srcset' || $attr == 'data-srcset') {

                    $return = [];

                    foreach (explode(',', $matches[6]) as $chunk) {

                        $parts = explode(' ', $chunk);

                        $name = trim($parts[0]);

                        $return[] = (static::isFile($name) ? static::url($name) : $name).' '.$parts[1];
                    }

                    return ' '.$attr.'="'.implode(',', $return).'"';
                }

                if (isset($options['parse_url_attr'][$attr])) {

                        $file = static::getName($matches[6]);

                        if (preg_match('#^(https?:)?(//[^/]+)#', $file, $domain)) {

                            if (empty($domain[1])) {

                                $domain[1] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'ON' ? 'https:' : 'http:';
                            }

                            $domains[$domain[1].$domain[2]] = $domain[1].$domain[2];
                        }

                        if (static::isFile($file)) {

                            $name = preg_replace('~[#?].*$~', '', $file);

                                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                                $push_data = empty($types) ? false : static::canPush($name, $ext);

                                if (!empty($push_data)) {

                                    if (!isset($types['all']) && (empty($push_data['as']) || empty($types[$push_data['as']]))) {

                                        unset($push_data);
                                    } else {

                                        if (isset($push_data['as']) && isset($hashmap[$push_data['as']])) {

                                            $push_data['score'] = $hashmap[$push_data['as']];
                                        } else {

                                            $push_data['score'] = count($hashmap);
                                        }

                                        $push_data['href'] = $file;
                                        $pushed[$base . $file] = $push_data;
                                    }
                                }

                                if (isset($accepted[$ext])) {

                                    unset($pushed[$base . $file]);

                                    $checkSumData = static::getChecksum($name, $hashFile, $checksum, $tag == 'script' || ($tag == 'link' && $ext == 'css'));

                                    $file = \JURI::root(true).'/media/z/'.static::$pwa_network_strategy . $checkSumData['hash'] . '/' . $file;

                                    if (!empty($push_data)) {

                                        $push_data['href'] = $file;
                                        $pushed[$base . $file] = $push_data;
                                    }

                                //    static::$pwacache[] = $base . $file;

                                    $result = ' ' . $matches[2] . '="' . $file . '" ';

                                    if(!empty($checksum) && $checksum != 'none') {

                                        if ($tag == 'script' || ($tag == 'link' && $ext == 'css')) {

                                            $result .= 'integrity="' . $checkSumData['integrity'] . '" crossorigin="anonymous" ';
                                        }
                                    }

                                    return $result;
                                }
                        //    }
                        }

                    //    static::$pwacache[] = \JRoute::_($file, false);
                    //    break;
                }

                return $matches[0];

            }, $matches[2]).'>';

        }, $body);

    //    $profiler->mark('end parse urls');

    //    $profiler->mark('push urls');

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

            unset($domains[(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'ON' ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME']]);
            unset($domains['http://get.adobe.com']);

            if (!empty($domains)) {
                    
                $replace['<head>'] = '<head><link rel="preconnect" crossorigin href="'.implode('"><link rel="preconnect" crossorigin href="', $domains).'">';
            }
        }

        if (!empty($replace)) {

            return str_replace(array_keys($replace), array_values($replace), $body);
        }

        return $body;
    }

    public static function url($file) {

        $name = preg_replace('~[#?].*$~', '', static::getName($file));

        if (strpos($name, 'data:') === 0) {

            return $file;
        }

        if (static::isFile($file)) {

            if (is_file($name) && strpos($name, 'media/z/') !== 0) {

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $accepted = static::accepted();

                if (isset($accepted[$ext])) {

                    $hashFile = static::getHashMethod();

                    return \JURI::root(true) . '/media/z/'.static::$pwa_network_strategy . $hashFile($name) . '/' . $file;
                }
            }

            return \JURI::root(true) . '/' . $file;
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

            if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                $file = static::resolvePath($path . trim(str_replace(array("'", '"'), "", $matches[1])));
            }

            else {

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
            }

            return // "\n" . ' /* url ' . $matches[1] .
                // -> uncomment to debug
                // ' isFile: '.(static::isFile($file) ? 'true '.preg_replace('~[#?].*$~', '', static::getName($file)).' -> '.static::url($file) : 'false').
              //  ' */ ' . "\n" . 
                "url(" . (static::isFile($file) ? static::url($file) : $file) . ")";
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

            //    $o = $file . ' ' . var_export([static::isFile($file), preg_match('#^(/|((https?:)?//))#i', $file)], true);

                return "\n" . '/* @ import ' . $file . ' ' . dirname($file) . ' */' . "\n" . static::expandCss($isFile ? file_get_contents($file) : static::getContent($file), dirname($file), $path);
            }, preg_replace(['#/\*.*?\*/#s', '#@charset [^;]+;#si'], '', $css))
        );

        return $css;
    }

    public static function parseCss($body, array $options = []) {

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $path = isset($options['css_path']) ? $options['css_path'] : 'cache/z/'.static::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/css/';

        $fetch_remote = !empty($options['fetchcss']);
        $remote_service = !empty($options['minifycssservice']);

        $links = [];
        $ignore = isset($options['cssignore']) ? $options['cssignore'] : [];
        $remove = isset($options['cssremove']) ? $options['cssremove'] : [];

        $async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

        $body = preg_replace_callback('#<link([^>]*)>#', function ($matches) use(&$links, $ignore, $remove, $fetch_remote, $path) {

            $attributes = [];

            if(preg_match_all(static::regexAttr, $matches[1], $attr)) {

                foreach ($attr[2] as $k => $att) {

                    $attributes[$att] = $attr[6][$k];
                }
            }

            if (!empty($attributes)) {

                if (isset($attributes['rel']) && $attributes['rel'] == 'stylesheet' && isset($attributes['href'])) {

                    $name = static::getName($attributes['href']);

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

                            $content = static::getContent($remote);

                            if ($content != false) {

                                file_put_contents($local, static::expandCss($content, dirname($remote), $path));
                            }
                        }

                        if (is_file($local)) {

                            $name = $local;
                        } else {

                            return '';
                        }
                    }

                    if (static::isFile($name)) {

                        $attributes['href'] = $name;
                        $links[$position]['links'][$name] = $attributes;
                        return '';
                    }
                }
            }

            return $matches[0];
        }, $body);

        $profiler = \JProfiler::getInstance('Application');
        $profiler->mark('afterParseLinks');

        
    //    $profiler->mark("done parse <link>");
        $hashFile = static::getHashMethod($options);

        $minify = !empty($options['minifycss']);

        if ($minify) {

            foreach ($links as $position => $blob) {

                if (!empty($blob['links'])) {
                        
                    foreach ($blob['links'] as $key => $attr) {

                        $name = static::getName($attr['href']);

                        if (!static::isFile($name)) {

                            continue;
                        }

                        $hash = $hashFile($name) . '-min';

                        $cname = str_replace(['cache', 'css', 'min', 'z/cn/', 'z/no/', 'z/cf/', 'z/nf/', 'z/co/', 'z/'], '', $attr['href']);
                        $cname = preg_replace('#[^a-z0-9]+#i', '-', $cname);

                        $css_file = $path . $cname . '-min.css';
                        $hash_file = $path . $cname . '.php';

                        if (!is_file($css_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash) {

                            $content = static::css($name, $remote_service, $path);

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

                                        $name = static::getName($attr['href']);

                                        if (!static::isFile($name)) {

                                            return '';
                                        }

                                        return $hashFile($name) . '.' . $name;
                                    }, $blob['links'])));

                    $hash = $path . static::shorten($hash);

                    $css_file = $hash . '.css';
                    $css_hash = $hash . '.php';

                    if (!is_file($css_file) || !is_file($css_hash) || file_get_contents($css_hash) != $hash) {

                        $content = '';

                        foreach ($blob['links'] as $attr) {

                            $name = static::getName($attr['href']);

                            if (!static::isFile($name)) {

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

                                $css .= static::expandCss(file_get_contents($name), dirname($name), $path);

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

        $body = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use(&$links, $minifier) {

            $attributes = [];

            if(preg_match_all(static::regexAttr, $matches[1], $attr)) {

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
		}, $body);
		
		
		$profiler->mark('afterParseStyles');
		
		$parseCritical = !empty($options['criticalcssenabled']);
		$parseCssResize = !empty($options['imagecssresize']);

        if ($parseCritical || $parseCssResize) {

       //     $profiler->mark("critical path css lookup");

            $critical_path = isset($options['criticalcssclass']) ? $options['criticalcssclass'] : '';
            $background_css_path = '';

            $styles = ['html', 'body'];

            if (!empty($options['criticalcss'])) {

                $styles = array_filter(array_map('trim', array_merge($styles, preg_split('#\n#s', $options['criticalcss'], -1, PREG_SPLIT_NO_EMPTY))));
            }

            // really needed?
            preg_match('#<((body)|(html))(\s.*?)? class=(["\'])(.*?)\5>#si', $body, $match);

            if (!empty($match[6])) {

                $styles = array_unique(array_merge($styles, explode(' ', $match[6])));
            }

            foreach ($styles as &$style) {

                $style = preg_quote(preg_replace('#\s+([>+\[:,{])\s+#s', '$1', $style), '#');
                unset($style);
            }

            # '#((html)|(body))#si'
            $regexp = '#(^|[>\s,~+},])((' . implode(')|(', $styles) . '))([\s:,~+\[{>,]|$)#si';

            foreach($links as $blob) {

                if (!empty($blob['links'])) {

                    foreach ($blob['links'] as $link) {

                        $fname = static::getName($link['href']);

                        if (!static::isFile($fname)) {

                            continue;
                        }

                        $hash = crc32($hashFile($fname) . '.' . $regexp . '.' . $fname);

                        $info = pathinfo($fname);

                        $name = $info['dirname'] . '/' . $info['filename'] . '-crit';
                        $bgname = $info['dirname'] . '/' . $info['filename'] . '-bg';

                        $css_file = $name . '.css';
                        $css_hash = $name . '.php';

                        $css_bg_file = $name . '-bg.css';
                        $css_bg_hash = $name . '-bg.php';

						$content = null;
						
						if ($parseCssResize) {
								
							if (!is_file($css_bg_file) || file_get_contents($css_bg_hash) != $hash) {

								$content = file_get_contents($fname);

								$oCssParser = new \Sabberworm\CSS\Parser($content);
								$oCssDocument = $oCssParser->parse();

								$css_background = '';

								foreach ($oCssDocument->getContents() as $block) {

									// extractCssBackground
									$css_background .= static::extractCssBackground($block);
								}

								if (!empty($css_background)) {

									if (!empty($minifier)) {

										$css_background = $minifier->minify($css_background);
									}
								}

							//	$css_background = static::expandCss($css_background, dirname($css_bg_file));
                            	$background_css_path .= $css_background;

								\file_put_contents($css_bg_file, $css_background);
								\file_put_contents($css_bg_hash, $hash);
							}

							else {

								$background_css_path .= \file_get_contents($css_bg_file);
							}
						}

						if ($parseCritical) {
								
							if (!is_file($css_file) || file_get_contents($css_hash) != $hash) {

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

									$local_css .= static::extractCssRules(clone $block, $regexp);
									$local_font_face .= static::extractFontFace(clone $block);
								}

								$local_css = $local_font_face.$local_css;

								if (!empty($local_css)) {

									if (!empty($minifier)) {

										$local_css = $minifier->minify($local_css);
									}

									$local_css = static::expandCss($local_css, dirname($css_file));

									\file_put_contents($css_file, $local_css);
									\file_put_contents($css_hash, $hash);
								}
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

                $background_css_file = $name . '-build.css';
                $background_css_hash = $name . '-build.php';
                                
                if (!is_file($background_css_file) || file_get_contents($background_css_hash) != $hash) {

                    \file_put_contents($background_css_file, static::buildCssBackground($background_css_path, $options));
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

                if(isset(static::$accepted[$ext]) && strpos(static::$accepted[$ext], 'font') !== false) {

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

        if ($async) {

            $head_string .= '<script data-ignore="true">'.file_get_contents(__DIR__.'/cssloader.min.js').'</script>';
        }

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

                        $link['onload'] = '_l(this)';

                        if (isset($link['media'])) {

                            $link['data-media'] = $link['media'];
                        }

                        $link['media'] = 'none';
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

                            $noscript .= str_replace(['data-media', ' onload="_l(this)"', ' media="none"'], ['media', ''], $css);
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

            $body = str_replace($search, $replace, $body);
        }

    //    $profiler->mark("done links & styles");

	//    static::$marks = array_merge(static::$marks, $profiler->getMarks());
	
	
		if(!empty($options['imagecssresize'])) {

			$body = preg_replace_callback('#<html([>]*)>#', function ($matches) {

			preg_match_all(static::regexAttr, $matches[1], $attr);

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

			}, $body, 1);
		}
	
        return $body;
    }

    public static function getName($name) {

        return preg_replace(static::$regReduce, '', $name);
    }

    public static function shorten($id, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@') {

        $base = strlen($alphabet);
        $short = '';
        $id = sprintf('%u', $id);

        while ($id) {
            $id = ($id - ($r = $id % $base)) / $base;
            $short = $alphabet{$r} . $short;
        }

        return $short;
    }

    public static function isFile($name) {

        $name = static::getName($name);

        if (preg_match('#^(https?:)?//#i', $name)) {

            return false;
        }

        $name = preg_replace('~(#|\?).*$~', '', $name);

        return is_file($name) || is_file(utf8_decode($name));
    }

    protected static function extractFontFace($block) {

        $content = '';

        if ($block instanceof AtRuleBlockList || $block instanceof AtRuleSet) {

            $atRuleName = $block->atRuleName();

            switch($atRuleName) {

                case 'media':

                    $result = '';

                    foreach ($block->getContents() as $b) {

                        $result .= static::extractFontFace($b);
                    }

                    if($result !== '') {

                        $content .= '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . $result . '}';
                    }

                    break;

                case 'font-face':

                    $content = '@' . $atRuleName . ' ' . $block->atRuleArgs() . '{' . implode('', $block->getRules()) . '}';

                   break;
            }
        }

        return $content;
    }

    protected static function extractCssRules($block, $regexp) {

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

    protected static function buildCssBackground($css, array $options = []) {

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

    protected static function extractCssBackground($block, $matchSize = null, $prefix = null) {

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

                            if (strpos($fileName, 'media/z/') === 0) {

                                $fileName = preg_replace('#^media/z/(((nf)|(cf)|(cn)|(no)|(co))/)?[^/]+/(1/)?#', '', $fileName);
                            }

                            $images[] = $fileName;

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

    public static function parseScripts($body, array $options = []) {

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $path = isset($options['js_path']) ? $options['js_path'] : 'cache/z/'.static::$pwa_network_strategy.$_SERVER['SERVER_NAME'].'/js/';

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $comments = [];

    //    $profiler = \JProfiler::getInstance('Application');

        $body = preg_replace_callback('#<!--.*?-->#s', function ($matches) use(&$comments) {

            $hash = '--***c-' . crc32($matches[0]) . '-c***--';
            $comments[$hash] = $matches[0];

            return $hash;
        }, $body);


    //    $files = [];
        $replace = [];
        $scripts = [];
    //    $js = [];
    //    $ignored = [];
        $ignore = isset($options['jsignore']) ? $options['jsignore'] : [];
        $remove = isset($options['jsremove']) ? $options['jsremove'] : [];

        // scripts
        // files
        // inline
        // ignored
        $sources = [];

    //    $inline_js = [];

        $fetch_remote = !empty($options['fetchjs']);
        $remote_service = !empty($options['minifyjsservice']);

    //    $profiler->mark('parse <script>');

        // parse scripts
        $body = preg_replace_callback('#<script([^>]*)>(.*?)</script>#si', function ($matches) use(&$sources, $path, $fetch_remote, $ignore, $remove) {

            $attributes = [];

            if(preg_match_all(static::regexAttr, $matches[1], $attr)) {

                foreach ($attr[2] as $k => $att) {

                    $attributes[$att] = $attr[6][$k];
                }
            }

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

            $position = isset($attributes['data-position']) && $attributes['data-position'] == 'head' ? 'head' : 'body';
            
            //else {

            //    $matches[1] = str_replace($match[0], '', $matches[1]);
           // }

           unset($attributes['type']);

            if (!empty($matches[2])) {

                $sources['inline'][$position][] = $matches[2];                
                return '';
            }

            // ignore custom type
            if (isset($attributes['src'])) {

                $name = static::getName($attributes['src']);

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

                        $content = static::getContent($remote);

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

        }, $body);

  //      $profiler->mark('done parse <script>');

        $hashFile = static::getHashMethod($options);

        // merge all js into a single file
        $content = '';

        $replace = [];

   //     $profiler->mark('minify <script>');

        if (!empty($options['minifyjs'])) {

            // compress all js files
            $replace = [];

            if (!empty($sources['files'])) {

                foreach($sources['files'] as $position => $fileList) {

                    foreach ($fileList as $key => $file) {

                        if (!static::isFile($file)) {

                            continue;
                        }

                        $name = preg_replace(array('#(^-)|([.-]min)|(cache/|-js/|-)|(\.?js)#', '#[^a-z0-9]+#i'), array('', '-'), $file);

                        $js_file = $path . $name . '-min.js';
                        $hash_file = $path . $name . '.php';

                        $hash = $hashFile($file);

                        if (!is_file($js_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash) {

                            $gzip = static::js($file, $remote_service);

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

   //     $profiler->mark('done minify <script>');
   //     $profiler->mark('merge <script>');

        if (!empty($options['mergejs'])) {

            if (!empty($sources['files'])) {

            }

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

                $name = $path . static::shorten($hash);
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

    //    $profiler->mark('done merge <script>');

        if (!empty($options['minifyjs'])) {

            if (!empty($sources['inline'])) {
                    
                foreach($sources['inline'] as $position => $js) {
                        
                    foreach($js as $key => $data) {

                        if (!empty($data)) {

                            
                            $jSqueeze = new JSqueeze();                  
                            $sources['inline'][$position][$key] = trim($jSqueeze->squeeze($data), ';');
                            
                        }
                    }
                }
            }
        }

        $script = [
    
            'head' => '',
            'body' => ''
        ];

    //    $profiler->mark('replace <script>');

        $async = false;

        if (!empty($sources['ignored'])) {

            foreach ($sources['ignored'] as $position => $fileList) {

            //    $script[$position] .=  implode('', $content);

                
                $attr = '';
                $hasScript = !empty($sources['inline'][$position]) && empty($files[$position]);

                if ($hasScript) {

                    $async = true;
                    $attr = ' onload="il(\''.$position.'\')"';
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

                        //  && empty($inline_js[$position])

                        $attr = '';
                        $hasScript = !empty($sources['inline'][$position]);

                        if ($hasScript) {

                            $async = true;
                            $attr = ' onload="il(\''.$position.'\')"';
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

            $script['head'] = '<script>'.file_get_contents(__DIR__.'/loader.min.js').'</script>'.$script['head'];
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
                
            $body = str_replace($strings, $replace, $body);
        }

    //    $profiler->mark('done replace <script>');

        if (!empty($comments)) {

            $body = str_replace(array_keys($comments), array_values($comments), $body);
        }

    //    static::$pwacache = array_merge(static::$pwacache, $files);
    //    static::$marks = array_merge(static::$marks, $profiler->getMarks());

        return $body;
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
                            
                                continue;
                        }
                    }
                
                   $attributes .= ' '.$value.'="'.static::parseSVGAttribute($attrib[6][$key], $value, $matches[2]).'"';
               }
            }            
           
           return '<'.$matches[2].$attributes.$matches[4].'>';
       },  $svg);

       return str_replace(array_keys($cdata), array_values($cdata), $svg);
    }

    public static function generateSVGPlaceHolder($image, $file, $sizes, $maxwidth, array $options, $path, $hash, $method, $short_name) {

        if (!empty($options['imagesvgplaceholder'])) {

            $short_name = strtolower(str_replace('CROP_', '', $method));

            $svg = $path.$hash.'-'. $short_name.'-'.pathinfo($file, PATHINFO_FILENAME).'.svg';

            if (!is_file($svg)) {
                
            //    if (!empty($images)) {
                    
                    $clone = clone $image;
                    file_put_contents($svg, 'data:image/svg+xml;base64,'.base64_encode(static::minifySVG($clone->load($file)->resizeAndCrop(min($clone->getWidth(), 1024), null, $method)->setSize(200)->toSvg())));
            //    }
            }

            if (is_file($svg)) {
                
            //    $style = !empty($attributes['style']) ? $attributes['style'].';' : '';
                return file_get_contents($svg);
            }
        }

        return '';
    }

    public static function parseImages($body, array $options = []) {

        if (empty($options['imageenabled'])) {

            return $body;
        }

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $path = $options['img_path'];

        // parse scripts
        $body = preg_replace_callback('#<img([^>]*)>#si', function ($matches) use($path, $options) {

            $attributes = [];
            
            if(preg_match_all(static::regexAttr, $matches[1], $attrib)) {

                foreach($attrib[2] as $key => $attr) {

                    $attributes[$attr] = $attrib[6][$key];
                }
            }

            $file = null;
            $pathinfo = null;

            // ignore custom type
            if (isset($attributes['src'])) {

                $name = static::getName($attributes['src']);
                $file = preg_replace('/(#|\?).*$/', '', $name);

                $basename = preg_replace('/(#|\?).*$/', '', basename($name));
                $pathinfo = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

                if (!empty($options['imageremote']) && preg_match('#^(https?:)?//#', $name)) {

                    if (isset(static::$accepted[$pathinfo]) && strpos(static::$accepted[$pathinfo], 'image/') !== false) {

                        if (strpos($name, '//') === 0) {

                            $name = \JURI::getInstance()->getScheme() . ':' . $name;
                        }

                        $local = $path . sha1($name) . '.' . $pathinfo;

                        if (!is_file($local)) {

                            $content = static::getContent($name);

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

                if (static::isFile($file)) {
                
                    $sizes = \getimagesize($file);

	                $maxwidth = $sizes[0];
	                $img = null;

	                // end fetch remote files
                    if(isset($options['imagedimensions'])) {

                        if (!isset($attributes['width']) && !isset($attributes['height'])) {

                            $attributes['width'] = $sizes[0];
                            $attributes['height'] = $sizes[1];
                        }
                    }

                    if (!empty($options['imageconvert']) &&  WEBP && $pathinfo != 'webp') {
                        
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
                    $const = constant('\Image\Image::'.$method);
                    $hash = sha1($file);
                    $short_name = strtolower(str_replace('CROP_', '', $method));
                    $crop =  $path.$hash.'-'. $short_name.'-'.basename($file);

                    $image = new \Image\Image();

					// generate svg placeholder for faster image preview
					if (!empty($options['imagesvgplaceholder'])) {
							
						$class = !empty($attributes['class']) ? $attributes['class'].' ' : '';
						$attributes['class'] = $class.'image-placeholder';
					}

                    $src = static::generateSVGPlaceHolder($image, $file, $sizes, $maxwidth, $options, $path, $hash, $method, $short_name);

                    if ($src !== '') {
                            
						$attributes['src'] = $src;
						$attributes['data-src'] = $file;
                    }

                    // responsive images?
                    if (!empty($options['imageresize']) && !empty($options['sizes']) && empty($attributes['srcset'])) {

                        // build mq based on actual image size
                        $mq = array_filter ($options['sizes'], function ($size) use($maxwidth) {

                            return $size < $maxwidth;								
                        });

                        if (!empty($mq)) {

                        //    $image = null;
                            $resource = null;
                            
                            $images = array_map(function ($size) use($file, $hash, $short_name, $path) {

                                return $path.$hash.'-'.$short_name.'-'.$size.'-'.basename($file);

                            }, $mq);

                            $srcset = [];

                            foreach ($images as $k => $img) {

                                if (!\is_file($img)) {

                                        if (!\is_file($crop)) {

											if (\is_null($image->getResource())) {

												$image->load($file);
																	
											    if ($maxwidth > 1200) {

											        $image->setSize(1200);
											    }												
											}
                                                
                                            $clone = clone $image;                                                                                     

                                            // resize image to use less memory
                                            $clone->resizeAndCrop($mq[$k], null, $method)->save($crop);
                                        //    }
                                        }
                                }

                                $srcset[] = $img.' '.$mq[$k].'w';
                            }

                            $srcset[] = $file.' '.$sizes[0].'w';
                            $maxwidth = end($mq) + 1;

                            $mq = array_map(function ($size) {

                                return '(max-width: '.$size.'px)';
                            });

                            $mq[] = '(min-width: '.$maxwidth.'px)';

                            $attributes['data-srcset'] = implode(',', $srcset);
                            $attributes['sizes'] = implode(',', $mq);
                        }						
					}
                }

                if (!isset($attributes['alt'])) {

                    $attributes['alt'] = '';
                }

                return '<img '.implode(' ', array_map(function ($value, $key) {

                    return $key .= '="'.$value.'"';

                }, 
                $attributes, array_keys($attributes))).'>';
            }

            return $matches[0];
        }, $body);

        return $body;
    }
}
