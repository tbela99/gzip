<?php

namespace TBela\CSS\Parser;

class Helper
{

    /**
     * @return string
     * @ignore
     * @ignore
     */
    public static function getCurrentDirectory()
    {

        if (isset($_SERVER['PWD'])) {

            // when executing viw the cli
            return $_SERVER['PWD'];
        }

        return dirname($_SERVER['PHP_SELF']);
    }

    /**
     * @param $file
     * @param string $path
     * @return string
     * @ignore
     */
    public static function resolvePath($file, $path = '')
    {

        if ($path !== '') {

            if (!preg_match('#^(https?:/)?/#', $file)) {

                if ($file[0] != '/' && $path !== '') {

                    if ($path[strlen($path) - 1] != '/') {

                        $path .= '/';
                    }
                }

                $file = $path . $file;
            }
        }

        if (strpos($file, '../') !== false) {

            $return = [];

            if (strpos($file, '/') === 0)
                $return[] = '/';

            foreach (explode('/', $file) as $p) {

                if ($p == '..') {

                    array_pop($return);
                    continue;

                } else if ($p == '.') {

                    continue;

                } else {

                    $return[] = $p;
                }
            }

            $file = implode('/', $return);
        } else {

            $file = preg_replace(['#/\./#', '#^\./#'], ['/', ''], $file);
        }

        return preg_replace('#^' . preg_quote(static::getCurrentDirectory() . '/', '#') . '#', '', $file);
    }

    /**
     * @param $url
     * @param array $options
     * @param array $curlOptions
     * @return bool|string
     * @ignore
     */
    public static function fetchContent($url, array $options = [], array $curlOptions = [])
    {

        if (strpos($url, '//') === 0) {

            $url = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 'http' : 'https') . ':' . $url;
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

            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:80.0) Gecko/20100101 Firefox/80.0'
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
}