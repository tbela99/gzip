<?php

namespace TBela\CSS\Parser;

use TBela\CSS\Exceptions\IOException;

/**
 * Class Helper
 * @package TBela\CSS\Parser
 */
class Helper
{

    /**
     * @var bool
     * @ignore
     */
    protected static $fixParseUrl;

    /**
     * fix parsing bug in parse_url for php < 8 : parse_url('/?#iefix') will not return the query string
     * @param string $url
     * @return array
     * @ignore
     */
    protected static function doParseUrl($url)
    {

        $data = parse_url($url);

        if (!isset(static::$fixParseUrl)) {

            static::$fixParseUrl = !array_key_exists('query', parse_url('/?#iefix'));
        }

        if (static::$fixParseUrl && !isset($data['query'])) {

            $match = preg_split('~([#?])~', $url, 2, PREG_SPLIT_DELIM_CAPTURE);

            if ((isset($match[1]) ? $match[1] : '') == '?') {

                $data['query'] = '';
            }
        }

        return $data;
    }

    /**
     * @return string
     * @ignore
     * @ignore
     */
    public static function getCurrentDirectory()
    {

        if (isset($_SERVER['PWD'])) {

            // when executing via the cli
            return $_SERVER['PWD'];
        }

        return dirname($_SERVER['PHP_SELF']);
    }

    /**
     * @param string $file
     * @param string $path
     * @return string
     * @ignore
     */
    public static function resolvePath($file, $path = '')
    {

        if ($path !== '') {

            if (!preg_match('#^(https?:/)?/#', $file)) {

                if ($file[0] != '/') {

                    if ($path[strlen($path) - 1] != '/') {

                        $path .= '/';
                    }
                }

                $file = $path . $file;
            }
        }

        if (strpos($file, '..') !== false) {

            $return = [];
            $fromRoot = strpos($file, '/') === 0;

            foreach (preg_split('#/#', $file, -1, PREG_SPLIT_NO_EMPTY) as $p) {

                if ($p == '..') {

                    array_pop($return);

                } else if ($p == '.') {

                    continue;

                } else {

                    $return[] = $p;
                }
            }

            $file = ($fromRoot ? '/' : '') . implode('/', $return);
        } else {

            $file = preg_replace(['#/\./#', '#^\./#'], ['/', ''], $file);
        }

        return $file;
    }

    /**
     * @param string $file
     * @param string $ref
     * @return string
     */
    public static function absolutePath($file, $ref)
    {

        // web server environment
        if (substr($ref, 0, 1) == '/' && php_sapi_name() != 'cli') {

            if (substr($file, 0, 1) == '/' &&
                substr($file, 1, 1) != '/') {

                return substr($file, 1);
            }

            return $file;
        }

        if (static::isAbsolute($file)) {

            $data = static:: doParseUrl($file);
            $data['path'] = static::resolvePath($data['path']);

            return static::toUrl($data);
        }

        if ($ref === '') {

            return $file;
        }

        $data = static:: doParseUrl(rtrim($ref, '/') . '/' . $file);

        if (isset($data['path'])) {

            $data['path'] = static::resolvePath($data['path']);
        }

        return static::toUrl($data);
    }

    /**
     * compute relative path
     * @param string $file
     * @param string $ref relative directory
     * @return string
     */
    public static function relativePath($file, $ref)
    {

        $isAbsolute = static::isAbsolute($file);

        if ($isAbsolute && !static::isAbsolute($ref)) {

            return $file;
        }

        $original = static::resolvePath($file);
        $fileUrl = static:: doParseUrl($file);
        $refUrl = static:: doParseUrl($ref);

        foreach (['scheme', 'host'] as $key) {

            if (isset($fileUrl[$key])) {

                if (!isset($refUrl[$key]) || $refUrl[$key] != $fileUrl[$key]) {

                    return $file;
                }

                unset($fileUrl[$key]);
            }
        }

        $basename = basename($file);

        $ref = preg_split('#[/]+#', rtrim($refUrl['path'], '/'), -1, PREG_SPLIT_NO_EMPTY);
        $file = preg_split('#/#', dirname($fileUrl['path']), -1, PREG_SPLIT_NO_EMPTY);

        $j = count($ref);

        while ($j--) {

            if ($ref[$j] == '.') {

                array_splice($ref, $j, 1);
                continue;
            }

            if ($ref[$j] == '..' && isset($ref[$j - 1]) && $ref[$j - 1] != '..') {

                array_splice($ref, $j - 1, 2);
                $j--;
            }
        }

        $j = count($file);

        while ($j--) {

            if ($file[$j] == '.') {

                array_splice($file, $j, 1);
                continue;
            }

            if ($file[$j] == '..' && isset($file[$j - 1]) && $file[$j - 1] != '..') {

                array_splice($file, $j - 1, 2);
            }
        }

        if (count($file) < count($ref)) {

            return static::toUrl($fileUrl);
        }

        while ($ref) {

            $r = $ref[0];

            if (!isset($file[0]) || $file[0] != $r) {

                break;
            }

            array_shift($file);
            array_shift($ref);
        }

        $result = implode('/', array_merge(array_fill(0, count($ref), '..'), $file));
        $result = ($result === '' ? '' : $result . '/') . $basename;

        return $isAbsolute && strlen($original) <= strlen($result) ? $original : $result;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isAbsolute($path)
    {

        return (bool)preg_match('#^(/|(https?:)?//)#', $path);
    }

    /**
     * @param array $data
     * @return string
     */
    protected static function toUrl(array $data)
    {

        $url = '';

        if (isset($data['scheme'])) {

            $url .= $data['scheme'] . ':';
        }

        if (isset($data['user'])) {

            $url .= $data['user'] . ':' . $data['pass'] . '@';
        }

        if (isset($data['host'])) {

            $url .= '//' . $data['host'];
        }

        if (isset($data['path'])) {

            $url .= $data['path'];
        }

        if (isset($data['query'])) {

            $url .= '?' . $data['query'];
        }

        if (isset($data['fragment'])) {

            $url .= '#' . $data['fragment'];
        }

        return $url;
    }

    /**
     * @param string $url
     * @param array $options
     * @param array $curlOptions
     * @return bool|string
     * @ignore
     */
    public static function fetchContent($url, array $options = [], array $curlOptions = [])
    {
        $userAgent = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/93.0';

        $proto = strpos($url, 'https:') === 0 ? 'https' : 'http';

        if (extension_loaded('curl')) {

            if (strpos($url, '//') === 0) {

                $url = $proto . ':' . $url;
            }

            $ch = curl_init($url);

            // Turn on SSL certificate verfication
            curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // enable compression
            curl_setopt($ch, CURLOPT_ENCODING, '');

            // google font sends a different response when this header is missing
            curl_setopt($ch, CURLOPT_HTTPHEADER, [

                $userAgent
            ]);

            if (!empty($curlOptions)) {

                curl_setopt_array($ch, $curlOptions);
            }

            if (!empty($options)) {

                // Tell the curl instance to talk to the server using HTTP POST
                curl_setopt($ch, CURLOPT_POST, count($options));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options));
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($ch);

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {

                curl_close($ch);
                return false;
            }

            curl_close($ch);
            return $result;
        }

        return @file_get_contents($url, false, stream_context_create([
            $proto => [
                'verify_peer' => true,
                'ignore_errors' => true,
                'follow_location' => true,
                'user_agent' => $userAgent,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'cafile' => __DIR__ . '/cacert.pem'
            ]
        ]));
    }
}