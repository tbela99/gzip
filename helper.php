<?php

namespace Gzip;

use Patchwork\JSqueeze as JSqueeze;
use Patchwork\CSSmin as CSSMin;
use \Sabberworm\CSS\RuleSet\AtRuleSet as AtRuleSet;
use \Sabberworm\CSS\CSSList\AtRuleBlockList as AtRuleBlockList;
use \Sabberworm\CSS\RuleSet\DeclarationBlock as DeclarationBlock;
#use \Sabberworm\CSS\RuleSet\RuleSet as RuleSet;
#use \Sabberworm\CSS\Rule\Rule as Rule;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GZipHelper {

    static $regReduce;
    static $attr = '#(\S+)=(["\'])([^\2]*?)\2#si';
    static $pwacache = [];
    static $pushed = array(
        "gif" => array('as' => 'image'),
        "jpg" => array('as' => 'image'),
        "jpeg" => array('as' => 'image'),
        "png" => array('as' => 'image'),
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
    static $marks = [];
    static $accepted = array(
        "gif" => "image/gif",
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
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
        "svg" => "image/svg+xml"
    );
    static $encoded = array(
        "gif" => "image/gif",
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "ico" => "image/x-icon",
        "eot" => "application/vnd.ms-fontobject",
        "otf" => "application/x-font-otf",
        "ttf" => "application/x-font-ttf",
        "woff" => "application/x-font-woff",
        "woff2" => "application/font-woff2",
        "svg" => "image/svg+xml"
    );

    /*
      public static function integrigyChecksum($file, $algo = 'sha256') {

      return $algo."-". hash_file($algo, $file, true);;
      }
     */

    public static function getChecksum($file, callable $hashFile, $algo = 'sha256') {

        $hash = $hashFile($file);

        $path = 'cache/z/ch/' . $hash . '-' . basename($file) . '.checksum.php';

        if (is_file($path)) {

            include $path;

            if (isset($checksum['hash']) && $checksum['hash'] == $hash && isset($checksum['algo']) && $checksum['algo'] == $algo) {

                return $checksum;
            }
        }

        $checksum = [
            'hash' => $hash, // 
            //    'crossorigin' => 'anonymous',
            'algo' => $algo,
            'integrity' => empty($algo) || $algo == 'none' ? '' : $algo . "-" . base64_encode(hash_file($algo, $file, true))
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

        if ($remote_service) {

            $options = array('input' => $content);

            $response = static::getContent('https://javascript-minifier.com/raw', $options);

            if ($response === false) {

                return false;
            }

            if (preg_match('#^java\.net#s', $response)) {

                return false;
            }

            return $response;
        }

        if (is_null($jsShrink)) {

            $jsShrink = new JSqueeze;
        }

        return trim($jsShrink->squeeze($content, false, false), ';');
    }

    public static function css($file, $remote_service = true) {

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
        } else if (is_file($file)) {

            $content = static::expandCss(file_get_contents($file), dirname($file));
        } else {

            return false;
        }

        //    $content = static::expandCss($content, dirname($file));

        if ($remote_service) {

            $options = array('input' => $content);

            $response = static::getContent('https://cssminifier.com/raw', $options);

            if ($response !== false && strpos($response, 'Error:') === false) {

                return $response;
            }
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

        $profiler = \JProfiler::getInstance('_url_');

        $profiler->mark('parse urls');

        static::$pwacache = [];

        $body = preg_replace_callback('#<([^\s\\>]+)\s([^>]+)>#si', function ($matches) use($checksum, $hashFile, $accepted, &$pushed, $types, $hashmap, $base) {

            $tag = $matches[1];

            return preg_replace_callback('~([\r\n\t ])?([a-zA-Z0-9:]+)=(["\'])([^\s\3]+)\3([\r\n\t ])?~', function ( $matches) use($tag, $checksum, $hashFile, $accepted, &$pushed, $types, $hashmap, $base) {

                switch (strtolower($matches[2])) {

                    case 'src':
                //    case 'data':
                    case 'href':

                        $file = static::getName($matches[4]);

                        if (static::isFile($file)) {

                            $name = preg_replace('~[#?].*$~', '', $file);

                            if (is_file($name)) {

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

                                    $checkSumData = static::getChecksum($name, $hashFile, $checksum);

                                    $file = 'media/z/' . $checkSumData['hash'] . '/' . $file;

                                    if (!empty($push_data)) {

                                        $push_data['href'] = $file;
                                        $pushed[$base . $file] = $push_data;
                                    }

                                    static::$pwacache[] = $base . $file;

                                    $result = ' ' . $matches[2] . '="' . $file . '" ';

                                    if(!empty($checksum) && $checksum != 'none') {

                                        if ($tag == 'script' || ($tag == 'link' && $ext == 'css')) {

                                            $result .= 'integrity="' . $checkSumData['integrity'] . '" crossorigin="anonymous" ';
                                        }
                                    }

                                    return $result;
                                }
                            }
                        }

                        static::$pwacache[] = \JRoute::_($file, false);
                        break;
                }

                return $matches[0];
                
            }, $matches[0]);
            
        }, $body);

        $profiler->mark('end parse urls');

        $profiler->mark('push urls');

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

                // or use html <link rel=preload>
                header('Link: <' . $file . '> ' . $header, false);
            }
        }

        $profiler->mark('end push urls');

        if (!empty($replace)) {

            return str_replace(array_keys($replace), array_values($replace), $body);
        }

        static::$marks = array_merge(static::$marks, $profiler->getMarks());

        return $body;
    }

    public static function url($file) {

        $name = preg_replace('~[#?].*$~', '', static::getName($file));

        if (strpos($name, 'data:') === 0) {

            return $file;
        }

        if (static::isFile($file)) {

            if (is_file($name)) {

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                $accepted = static::accepted();

                if (isset($accepted[$ext])) {

                    $hashFile = static::getHashMethod();

                    return \JURI::root(true) . '/media/z/' . $hashFile($name) . '/' . $file;
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

            return str_replace('./', '', implode('/', $return));
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
            } else {

                if (preg_match('#^(https?:)?//#', $file)) {

                    $content = static::getContent($file);

                    if ($content !== false) {

                        preg_match('~(.*?)([#?].*)?$~', $file, $match);

                        $file = 'cache/z/css/' . static::shorten(crc32($file)) . '-' . basename($match[1]);

                        if (!is_file($file)) {

                            file_put_contents($file, $content);
                        }

                        if (isset($match[2])) {

                            $file .= $match[2];
                        }
                    }
                }
            }

            return "\n" . ' /* url ' . $matches[1] . ' */ ' . "\n" . "url(" . (static::isFile($file) ? static::url($file) : $file) . ")";
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

                    return "\n" . '/* @ import ' . $file . ' ' . dirname($file) . ' */' . "\n" . static::expandCss($isFile ? file_get_contents($file) : static::getContent($file), dirname($file));
                }, preg_replace(['#/\*.*?\*/#s', '#@charset [^;]+;#si'], '', $css))
        );

        return $css;
    }

    public static function parseCss($body, array $options = []) {

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $path = isset($options['css_path']) ? $options['css_path'] : 'cache/z/css/';

        $fetch_remote = !empty($options['fetchcss']);
        $remote_service = !empty($options['minifycssservice']);

        $links = [];

        $profiler = \JProfiler::getInstance('_css_');

        $profiler->mark("parse <links>");

        $body = preg_replace_callback('#<link([^>]*)>#', function ($matches) use(&$links, $fetch_remote, $profiler, $path) {

            if (preg_match_all(static::$attr, $matches[1], $match)) {

                $attr = array_flip($match[1]);

                foreach ($attr as $key => $value) {

                    $attr[$key] = $match[3][$value];
                }

                if (isset($attr['rel']) && $attr['rel'] == 'stylesheet' && isset($attr['href'])) {

                    $name = static::getName($attr['href']);

                    if ($fetch_remote && preg_match('#^(https?:)?//#', $name)) {

                        $remote = $name;

                        if (strpos($name, '//') === 0) {

                            $remote = \JURI::getInstance()->getScheme() . ':' . $name;
                        }

                        $local = $path . preg_replace(array('#([.-]min)|(\.css)#', '#[^a-z0-9]+#i'), array('', '-'), $remote) . '.css';

                        if (!is_file($local)) {

                            $content = static::getContent($remote);

                            if ($content != false) {

                                file_put_contents($local, static::expandCss($content, dirname($remote)));
                            }
                        }

                        if (is_file($local)) {

                            $name = $local;
                        } else {

                            return '';
                        }
                    }

                    if (static::isFile($name)) {

                        $attr['href'] = $name;
                        $links[] = $attr;
                        return '';
                    }
                }
            }

            return $matches[0];
        }, $body);

        $profiler->mark("done parse <link>");
        $hashFile = static::getHashMethod($options);

        $profiler->mark("minify <links>");

        if (!empty($options['minifycss'])) {

            foreach ($links as &$attr) {

                $name = static::getName($attr['href']);

                if (!static::isFile($name)) {

                    continue;
                }

                $hash = $hashFile($name) . '-min';

                $cname = str_replace(['cache', 'css', 'z/', 'min'], '', $attr['href']);
                $cname = preg_replace('#[^a-z0-9]+#i', '-', $cname);

                $css_file = $path . $cname . '-min.css';
                $hash_file = $path . $cname . '.php';

                if (!is_file($css_file) || !is_file($hash_file) || file_get_contents($hash_file) != $hash) {

                    $content = static::css($name, $remote_service);

                    if ($content != false) {

                        file_put_contents($css_file, $content);
                        file_put_contents($hash_file, $hash);
                    }
                }

                if (is_file($css_file) && is_file($hash_file) && file_get_contents($hash_file) == $hash) {

                    $attr['href'] = \JURI::root(true) . '/' . $css_file;
                }

                unset($attr);
            }
        }

        $profiler->mark("done minify <links>");

        $profiler->mark("merge css ");

        if (!empty($options['mergecss'])) {

            if (!empty($links)) {

                $hash = crc32(implode('', array_map(function ($attr) use($hashFile) {

                                    $name = static::getName($attr['href']);

                                    if (!static::isFile($name)) {

                                        return '';
                                    }

                                    return $hashFile($name) . '.' . $name;
                                }, $links)));

                $hash = $path . static::shorten($hash);

                $css_file = $hash . '.css';
                $css_hash = $hash . '.php';

                if (!is_file($css_file) || !is_file($css_hash) || file_get_contents($css_hash) != $hash) {

                    $content = '';

                    foreach ($links as $attr) {

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

                            $profiler->mark("merge expand " . $name . " ");

                            $css .= static::expandCss(file_get_contents($name), dirname($name));

                            $profiler->mark("done merge expand " . $attr['href'] . " ");

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

                    $links = array(
                            [
                            'href' => \JURI::root(true) . '/' . $css_file,
                            'rel' => 'stylesheet'
                        ]
                    );
                }
            }
        }

        $minifier = null;

        if (!empty($options['minifycss'])) {

            $minifier = new CSSmin;
        }

        $async = !empty($options['asynccss']) || !empty($options['criticalcssenabled']);

        $position = $async ? 'body' : 'head';

        if ($async) {

            $body = str_replace('</body>', implode('', array_map(function ($link) use($options) {

                                $css = '<link';

                                if (isset($link['media'])) {

                                    $link['onload'] = 'this.media=&apos;' . str_replace(['"', "'", "\n"], ['&quot;', "\&apos;", ' '], $link['media']) . '&apos;;this.removeAttribute(&apos;onload&apos;)';
                                } else {

                                    $link['onload'] = '[&apos;media&apos;,&apos;onload&apos;].forEach(function(p){this.removeAttribute(p)},this)';
                                }

                                $link['media'] = 'none';

                                foreach ($link as $key => $value) {

                                    $css .= ' ' . $key . '="' . $value . '"';
                                }

                                return $css . '>';
                            }, $links)) . '</body>', $body);

            $profiler->mark("done merge css");
        } else {

            $body = str_replace('</head>', implode('', array_map(function ($link) use($options) {

                                $css = '<link';

                                foreach ($link as $key => $value) {

                                    $css .= ' ' . $key . '="' . $value . '"';
                                }

                                return $css . '>';
                            }, $links)) . '</head>', $body);

            $profiler->mark("done merge css");
        }

        $profiler->mark("parse <style>");

        $css = [];

        $body = preg_replace_callback('#(<style[^>]*>)(.*?)</style>#si', function ($matches) use(&$css) {

            preg_match('#\btype\s*=\s*["\'](.*?)\1#', $matches[1], $match);

            if (empty($match) || $match[1] == 'text/css') {

                $css[] = $matches[2];

                return '';
            }

            return $matches[0];
        }, $body);

        if (!empty($options['criticalcssenabled'])) {

            $profiler->mark("critical path css lookup");

            $critical_path = '';

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

            # echo $regexp;die;

            foreach ($links as $link) {

                $fname = static::getName($link['href']);

                if (!static::isFile($fname)) {

                    continue;
                }

                $hash = crc32($hashFile($fname) . '.' . $regexp . '.' . $fname);

                $info = pathinfo($fname);

                $name = $info['dirname'] . '/' . $info['filename'] . '-crit';

                $css_file = $name . '.css';
                $css_hash = $name . '.php';

                if (!is_file($css_file) || file_get_contents($css_hash) != $hash) {

                    $oCssParser = new \Sabberworm\CSS\Parser(file_get_contents($fname));
                    $oCssDocument = $oCssParser->parse();

                    $local_css = '';
                    $local_font_face = '';

                    foreach ($oCssDocument->getContents() as $block) {

                        $local_css .= static::extractCssRules($block, $regexp);
                        $local_font_face .= static::extractFontFace($block);
                    }
                    
                    $local_css = $local_font_face.$local_css;
                    
                    if (!empty($local_css)) {

                        if (!empty($minifier)) {

                            $local_css = $minifier->minify($local_css);
                        }

                        file_put_contents($css_file, $local_css);
                        file_put_contents($css_hash, $hash);
                    }
                }

                if (is_file($css_file) && file_get_contents($css_hash) == $hash) {

                    $critical_path .= file_get_contents($css_file);
                }
            }
            
            # echo var_export($critical_path, true);die;

            if (!empty($critical_path)) {

                array_unshift($css, $critical_path);
            }

            if (!empty($options['criticalcssclass'])) {

                array_unshift($css, $options['criticalcssclass']);
            }

            $profiler->mark("done critical path css lookup");
        }

        if (!empty($css)) {

            $css = static::expandCss(implode('', $css));

            if (!empty($options['minifycss'])) {

                if (empty($minifier)) {

                    $minifier = new CSSmin;
                }

                $css = $minifier->minify($css);
            }

            $body = str_replace('</head>', '<style>' . $css . '</style></head>', $body);
        }

        $profiler->mark("done parse <style>");

        foreach ($links as $link) {

            static::$pwacache[$link['href']] = $link['href'];
        }

        static::$marks = array_merge(static::$marks, $profiler->getMarks());

        return $body;
    }

    public static function parsePWA($body, $options = []) {

        $scope = \JUri::root(true) . '/';

        static::$pwacache = array_filter(static::$pwacache, function ($entry) use($scope) {

            return strpos($entry, $scope) === 0;
        });

    //    if (!empty($options['pwacachepages'])) {

    //        array_unshift(static::$pwacache, $_SERVER['REQUEST_URI']);
    //    }

        if (empty($options['pwaenabled']) || empty(static::$pwacache)) {

            return $body;
        }


        $debug = !empty($options['debug']);
        $suffix = $debug ? '' : 'min.';

        $sw_hash = file_get_contents(__DIR__ . '/worker'.$suffix.'.checksum');
        
        $js = preg_replace_callback('#(["]?)\{worker-([^}]+)\}\1#', function ($matches) use($scope, $sw_hash, $options) {

            switch ($matches[2]) {

                case 'db':

                    return "'" . static::url('media/s/localforage-all.min.js') . "'";

                case 'integrity':

                    return '"'.file_get_contents(__DIR__.'/integrity.checksum').'"';

                case 'location':

                    return '"' . $scope . 'worker'. /*$sw_hash.*/ '.js' . '"';

                case 'hash':

                    return '"' . $sw_hash . '"';

                case 'scope':

                    return '"' . $scope . '"';

                case 'name':

                        return "'rs-worker'";

                case 'max-age':

                        if (!empty($options['pwacachepages']) && !empty($options['pwacachelifetime'])) {

                            // push current page
                            return (int) $options['pwacachelifetime'] * 1000;
                        }

                        return '0';

                case 'date':

                        if (!empty($options['pwacachepages']) && !empty($options['pwacachelifetime'])) {

                            // push current page
                            return '"'.str_replace(' ', 'T',date('Y-m-d H:i:sP')).'"'; //microtime(true);
                        }

                        return 'undef';

                case 'page':

                        if (!empty($options['pwacachepages']) && !empty($options['pwacachelifetime'])) {

                            // push current page
                            return json_encode($_SERVER['REQUEST_URI']);
                        }

                        return 'undef';

                case 'files':

                    $files = [];

                    foreach(static::$pwacache as $file) {

                        $name = preg_replace('#^.*?/media/z/(1/)?[^/]+#', '', $file);

                        $files[$name] = $file;
                    }
                    

                    return json_encode($files);
            }

            return $matches[0];
            
        }, file_get_contents(__DIR__ . '/load-worker.'.$suffix.'js'));


        if (!empty($options['minifyjs'])) {

            $min = new \Patchwork\JSqueeze;

            $js = trim($min->squeeze($js), ';');
        }

        return str_replace('</body>', '<script>' . $js . '</script></body>', $body);
    }

    public static function getName($name) {

        return preg_replace(static::$regReduce, '', $name);
    }

    public static function shorten($id, $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@+=,') {

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

        return is_file(preg_replace('~(#|\?).*$~', '', $name));
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

    public static function parseScripts($body, array $options = []) {

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $path = isset($options['js_path']) ? $options['js_path'] : 'cache/z/js/';

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        $comments = [];

        $profiler = \JProfiler::getInstance('_css_');

        $body = preg_replace_callback('#<!--.*?-->#s', function ($matches) use(&$comments) {

            $hash = '--***c-' . crc32($matches[0]) . '-c***--';
            $comments[$hash] = $matches[0];

            return $hash;
        }, $body);


        $files = [];
        $replace = [];
        $scripts = [];
        $js = [];
        $ignored = [];
        $ignore = isset($options['jsignore']) ? $options['jsignore'] : [];
        $remove = isset($options['jsremove']) ? $options['jsremove'] : [];

        $inline_js = [];

        $fetch_remote = !empty($options['fetchjs']);
        $remote_service = !empty($options['minifyjsservice']);

        $profiler->mark('parse <script>');

        // parse scripts
        $body = preg_replace_callback('#<script([^>]*)>(.*?)</script>#si', function ($matches) use(&$inline_js, &$js, &$files, &$scripts, &$ignored, $path, $fetch_remote, $ignore, $remove) {

            // ignore custom type
            preg_match('#\btype=(["\'])(.*?)\1#', $matches[1], $match);

            if (!empty($match) && stripos($match[2], 'javascript') === false) {

                return $matches[0];
            } else if (!empty($match)) {

                $matches[1] = str_replace($match[0], '', $matches[1]);
            }

            if (!empty($matches[2])) {

                $inline_js[] = $matches[2];
            }

            // ignore custom type
            if (preg_match('#\bsrc=(["\'])(.*?)\1#', $matches[1], $match)) {

                $name = static::getName($match[2]);

                foreach ($remove as $r) {

                    if (strpos($name, $r) !== false) {

                        return '';
                    }
                }

                foreach ($ignore as $i) {

                    if (strpos($name, $i) !== false) {

                        $ignored[$name] = 1;
                        break;
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
                        $matches[1] = str_replace($match[2], $local, $matches[1]);
                    }
                }

                $files[$name] = $name;
                $scripts[$name] = '<script' . $matches[1] . '></script>';
            }

            return '';
        }, $body);

        $profiler->mark('done parse <script>');

        $hashFile = static::getHashMethod($options);

        // merge all js into a single file
        $content = '';

        $replace = [];

        $profiler->mark('minify <script>');

        if (!empty($options['minifyjs'])) {

            // compress all js files
            $replace = [];

            foreach ($files as $key => $file) {

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

                    $files[$key] = $js_file;
                }
            }
        }

        $profiler->mark('done minify <script>');

        $profiler->mark('merge <script>');

        if (!empty($options['mergejs'])) {

            $hash = '';

            foreach ($files as $key => $file) {

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

            foreach ($files as $key => $file) {

                if (isset($ignored[$key]) || !static::isFile($file)) {

                    continue;
                }

                if ($createFile) {

                    $content[] = trim(file_get_contents($file), ';');
                }

                unset($files[$key]);
            }

            if (!empty($content)) {

                file_put_contents($js_file, implode(';', $content));
                file_put_contents($hash_file, $hash);
            }

            if (is_file($js_file) && is_file($hash_file) && file_get_contents($hash_file) == $hash) {

                $files = array_merge([$js_file => $js_file], $files);
            }
        }

        $profiler->mark('done merge <script>');

        if (!empty($options['minifyjs'])) {

            if (!empty($inline_js)) {

                $jSqueeze = new JSqueeze();
                $inline_js = [trim($jSqueeze->squeeze(implode(';', $inline_js)), ';')];
            }
        }

        $script = '';

        $profiler->mark('replace <script>');

        if (!empty($files)) {

            if (count($files) == 1 && empty($inline_js)) {

                $script = '<script async src="' . array_shift($files) . '"';

                /*
                  if(!empty($inline_js)) {

                  $script .= ' onload="var n=this,s=document.createElement(&apos;script&apos;);s.text=&apos;'.str_replace(["\n","'", '<', '>', '"'], ['\n', "\&apos;", '&lt;', '&gt;', '&quot;'], implode(';', $inline_js)).'&apos;;this.parentNode.appendChild(s);this.removeAttribute(&apos;onload&apos;)"';

                  unset($inline_js);
                  }
                 */

                $script .= '></script>';
            } else {

                $script = '<script src="' . implode('"></script><script src="', $files) . '"></script>';
            }
        }

        if (!empty($inline_js)) {

            $script .= '<script>' . trim(implode(';', $inline_js), ';') . '</script>';
        }

        $body = str_replace('</body>', $script . '</body>', $body);

        $profiler->mark('done replace <script>');

        if (!empty($comments)) {

            $body = str_replace(array_keys($comments), array_values($comments), $body);
        }

        static::$pwacache = array_merge(static::$pwacache, $files);
        static::$marks = array_merge(static::$marks, $profiler->getMarks());

        return $body;
    }

    public static function parseImages($body, array $options = []) {

        if (empty($options['imageenabled'])) {

            return $body;
        }

        if (!isset(static::$regReduce)) {

            static::$regReduce = '#^((' . \JUri::root() . ')|(' . \JURI::root(true) . '/))#';
        }

        if (!empty($options['imageremote'])) {

            $path = $options['img_path'];

            // parse scripts
            $body = preg_replace_callback('#<img([^>]*)>#si', function ($matches) use($path) {

                // ignore custom type
                if (preg_match('#\bsrc=(["\'])(.*?)\1#', $matches[1], $match)) {

                    $name = static::getName($match[2]);

                    if (preg_match('#^(https?:)?//#', $name)) {

                        $basename = preg_replace('/(#|\?).*$/', '', basename($name));

                        if (preg_match('#\.((svg)|(jpg)|(png)|(gif))$#i', $basename)) {

                            $pathinfo = pathinfo($basename, PATHINFO_EXTENSION);

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

                                return str_replace($match[0], ' src="' . $local . '" ', $matches[0]);
                            }
                        }
                    }
                }

                return $matches[0];
            }, $body);
        }

        return $body;
    }
}
