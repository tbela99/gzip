<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\helpers;

use axy\sourcemap\errors\IOError;
use axy\sourcemap\errors\InvalidJSON;

/**
 * Helpers for I/O file operations
 */
class IO
{
    /**
     * Loads a content from a file
     *
     * @param string $filename
     * @return string
     * @throws \axy\sourcemap\errors\IOError
     */
    public static function load($filename)
    {
        if (!is_readable($filename)) {
            throw new IOError($filename, 'File not found or file is not readable');
        }
        $content = @file_get_contents($filename);
        if ($content === false) {
            self::throwNativeError($filename);
        }
        return $content;
    }

    /**
     * Saves a content to a file
     *
     * @param string $filename
     * @param string $content
     * @throws \axy\sourcemap\errors\IOError
     */
    public static function save($filename, $content)
    {
        if (!@file_put_contents($filename, $content)) {
            self::throwNativeError($filename);
        }
    }

    /**
     * Loads a data from a JSON file
     *
     * @param string $filename
     * @return string
     * @throws \axy\sourcemap\errors\IOError
     * @throws \axy\sourcemap\errors\InvalidJSON
     */
    public static function loadJSON($filename)
    {
        $content = self::load($filename);
        $data = json_decode($content, true);
        if ($data === null) {
            throw new InvalidJSON();
        }
        return $data;
    }

    /**
     * Saves a data to a JSON file
     *
     * @param mixed $data
     * @param string $filename
     * @param int $jsonFlag [optional]
     * @return string
     * @throws \axy\sourcemap\errors\IOError
     */
    public static function saveJSON($data, $filename, $jsonFlag = 0)
    {
        $content = json_encode($data, $jsonFlag);
        self::save($filename, $content);
    }

    /**
     * @param string $filename
     * @throws \axy\sourcemap\errors\IOError
     */
    private static function throwNativeError($filename)
    {
        $error = error_get_last();
        if (isset($error['message'])) {
            $message = $error['message'];
        } else {
            $message = null;
        }
        throw new IOError($filename, $message);
    }
}
