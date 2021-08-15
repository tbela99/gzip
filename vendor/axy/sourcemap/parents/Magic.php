<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parents;

use axy\sourcemap\errors\UnsupportedVersion;
use axy\errors\FieldNotExist;
use axy\errors\ContainerReadOnly;
use axy\errors\PropertyReadOnly;

/**
 * Partition of the SourceMap class (magic methods)
 *
 * @property int $version
 *           the version of the file format
 * @property string $file
 *           the "file" section
 * @property string $sourceRoot
 *           the "sourceRoot" section
 * @property string $outFileName
 *           the default file name of the map
 * @property-read \axy\sourcemap\indexed\Sources $sources
 *                the "sources" section (wrapper)
 * @property-read \axy\sourcemap\indexed\Names $names
 *                the "names" section (wrapper)
 */
abstract class Magic extends Base implements \Countable
{
    /**
     * Magic isset()
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return in_array($key, ['version', 'file', 'sourceRoot', 'sources', 'names', 'mappings', 'outFileName']);
    }

    /**
     * Magic get
     *
     * @param string $key
     * @return mixed
     * @throws \axy\errors\FieldNotExist
     */
    public function __get($key)
    {
        switch ($key) {
            case 'version':
                return 3;
            case 'file':
            case 'sourceRoot':
                return $this->context->data[$key];
            case 'sources':
                return $this->sources;
            case 'names':
                return $this->names;
            case 'outFileName':
                return $this->outFileName;
        }
        throw new FieldNotExist($key, $this, null, $this);
    }

    /**
     * Magic set
     *
     * @param string $key
     * @param mixed $value
     * @throws \axy\errors\FieldNotExist
     * @throws \axy\errors\PropertyReadOnly
     */
    public function __set($key, $value)
    {
        switch ($key) {
            case 'version':
                if (($value === 3) || ($value === '3')) {
                    return;
                }
                throw new UnsupportedVersion($value);
            case 'file':
            case 'sourceRoot':
                $this->context->data[$key] = $value;
                break;
            case 'sources':
            case 'names':
                throw new PropertyReadOnly($this, $key, null, $this);
            case 'outFileName':
                $this->outFileName = $value;
                break;
            default:
                throw new FieldNotExist($key, $this, null, $this);
        }
    }

    /**
     * Magic unset
     *
     * @param string $key
     * @throws \axy\errors\ContainerReadOnly
     */
    public function __unset($key)
    {
        throw new ContainerReadOnly($this, null, $this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '[Source Map]';
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->getData());
    }
}
