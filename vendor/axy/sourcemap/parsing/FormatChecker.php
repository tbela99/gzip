<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parsing;

use axy\sourcemap\errors\UnsupportedVersion;
use axy\sourcemap\errors\InvalidSection;

/**
 * Checking and normalization of source map data
 */
class FormatChecker
{
    /**
     * Checks and normalizes the source map data
     *
     * @param array $data [optional]
     *        the original data
     * @return array $data
     *         the normalized data
     * @throws \axy\sourcemap\errors\InvalidFormat
     *         the data format is invalid
     */
    public static function check(array $data = null)
    {
        if ($data === null) {
            return self::$defaults;
        }
        foreach (self::$defaults as $section => $default) {
            if (array_key_exists($section, $data)) {
                $value = $data[$section];
                if (is_array($default)) {
                    if (!is_array($value)) {
                        throw new InvalidSection($section, 'must be an array');
                    }
                    if (array_values($value) !== $value) {
                        throw new InvalidSection($section, 'must be a numeric array');
                    }
                } elseif (is_array($value)) {
                    throw new InvalidSection($section, 'must be a string');
                }
            } else {
                if (in_array($section, self::$required)) {
                    throw new InvalidSection($section, 'is required, but not specified');
                }
                $data[$section] = $default;
            }
        }
        if (!in_array($data['version'], [3, '3'], true)) {
            throw new UnsupportedVersion($data['version']);
        }
        if (count(array_diff_key($data['sourcesContent'], $data['sources'])) > 0) {
            throw new InvalidSection('sourcesContent', 'does not correspond to sources');
        }
        return $data;
    }

    /**
     * @var array
     */
    private static $defaults = [
        'version' => 3,
        'file' => null,
        'sourceRoot' => null,
        'sources' => [],
        'sourcesContent' => [],
        'names' => [],
        'mappings' => '',
    ];

    /**
     * @var string[]
     */
    private static $required = ['version', 'sources', 'mappings'];
}
