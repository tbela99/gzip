<?php

namespace TBela\CSS\Property;

Config::load(dirname(__DIR__).'/config.json');

/**
 * Property configuration manager class
 * @package TBela\CSS\Property
 * @ignore
 */
final class Config {

    /**
     * @var array
     * @ignore
     */
    protected static $config = [
        'properties' => [],
        'alias' => []
    ];

    /**
     * load properties configuration from a JSON file
     * @param string $file
     */
    public static function load($file) {

        static::$config = json_decode(file_get_contents($file), true);
    }

    /**
     * test the property existence
     * @param string $path
     * @return bool
     */
    public static function exists($path) {

        $found = true;
        $item = static::$config['alias'];

        foreach (explode('.', $path) as $p) {

            if (isset($item[$p])) {

                $item = $item[$p];
                continue;
            }

            $found = false;
            break;
        }

        if (!$found) {

            $found = true;
            $item = static::$config['properties'];

            foreach (explode('.', $path) as $p) {

                if (isset($item[$p])) {

                    $item = $item[$p];
                    continue;
                }

                $found = false;
            }
        }

        return $found;
    }

    /**
     * return property
     * @param string $path
     * @param mixed|null $default
     * @return mixed|null
     * @ignore
     */
    public static function getPath($path, $default = null) {

        $data = static::$config;

        foreach (explode('.', $path) as $item) {

            if (!isset($data[$item])) {

                return $default;
            }

            $data = $data[$item];
        }

        return $data;
    }

    /**
     * get property if it exists, return $default if it does not exist
     * @param string|null $name
     * @param mixed|null $default
     * @return array|mixed|null
     */
    public static function getProperty ($name = null, $default = null) {

        if (is_null($name)) {

            return static::$config;
        }

        if (isset(static::$config['properties'][$name])) {

            return static::$config['properties'][$name];
        }

        if (strpos($name, '.') > 0) {

            return static::getPath('properties.'.$name, $default);
        }

        return $default;
    }

    /**
     * Add a configuration entry
     * @param $shorthand
     * @param $pattern
     * @param $properties
     * @param bool $separator allow multiple values
     * @ignore
     *
     * @return array
     */
    public static function addSet ($shorthand, $pattern, $properties, $separator = null) {

        $config = [];

        $config[$shorthand] = [

            'shorthand' => $shorthand,
            'pattern' => $pattern,
            'value_map' => []
        ];

        if (!is_null($separator)) {

            $config[$shorthand]['separator'] = $separator;
        }

        $value_map_keys = [];

        // build value map
        foreach ($properties as $property => $data) {

            if (strpos($property, '.') !== false) {

                continue;
            }

            $value_map_keys[$data['type']][] = $property;
        }

        foreach ($properties as $property => $data) {

            if (strpos($property, '.') !== false) {

                $config[$shorthand][preg_replace('#^[^.]+\.#', '', $property)] = $data;
                continue;
            }

            $config[$shorthand]['properties'][] = $property;

            if (isset($data['value_map'])) {

                $map_keys = $value_map_keys[$properties[$property]['type']];

                $config[$shorthand]['value_map'][$property] = array_map(function ($value) use ($map_keys) {

                    return array_search($value, $map_keys, true);

                }, $data['value_map']);

                unset($data['value_map']);
            }

            $data['shorthand'] = $shorthand;
            $config[$property] = $data;
        }

        if (isset($config[$shorthand]['value_map'])) {

            $config[$shorthand]['value_map'] = array_reverse($config[$shorthand]['value_map']);
        }

        static::$config['properties'] = isset(static::$config['properties']) ? array_merge(static::$config['properties'], $config) : $config;
        return $config;
    }
}

