<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\parents;

use axy\sourcemap\parsing\FormatChecker;
use axy\sourcemap\parsing\Context;
use axy\sourcemap\indexed\Sources;
use axy\sourcemap\indexed\Names;

/**
 * Partition of the SourceMap class (basic)
 */
abstract class Base
{
    /**
     * The constructor
     *
     * @param array $data [optional]
     *        the map data
     * @param string $filename [optional]
     *        the default file name of the map
     * @throws \axy\sourcemap\errors\InvalidFormat
     *         the map has an invalid format
     */
    public function __construct(array $data = null, $filename = null)
    {
        $data = FormatChecker::check($data);
        $this->context = new Context($data);
        $this->sources = new Sources($this->context);
        $this->names = new Names($this->context);
        $this->outFileName = $filename;
        if ((!empty($data['sourcesContent'])) && (!empty($data['sources']))) {
            $contents = $data['sourcesContent'];
            foreach ($data['sources'] as $index => $fn) {
                if (isset($contents[$index])) {
                    $this->sources->setContent($fn, $contents[$index]);
                }
            }
        }
    }

    /**
     * Returns the data of the json file
     *
     * @return array
     */
    public function getData()
    {
        $data = $this->context->data;
        $result = [
            'version' => 3,
            'file' => $data['file'] ?: '',
            'sourceRoot' => $data['sourceRoot'] ?: '',
            'sources' => $this->sources->getNames(),
            'names' => $this->names->getNames(),
            'mappings' => $this->context->getMappings()->pack(),
        ];
        $contents = $this->sources->getContents();
        if (!empty($contents)) {
            $result['sourcesContent'] = $contents;
        }
        return $result;
    }

    public function __clone()
    {
        $this->context = clone $this->context;
        $this->sources = new Sources($this->context);
        $this->names = new Names($this->context);
    }

    /**
     * @var \axy\sourcemap\parsing\Context
     */
    protected $context;

    /**
     * @var \axy\sourcemap\indexed\Sources
     */
    protected $sources;

    /**
     * @var \axy\sourcemap\indexed\Names
     */
    protected $names;

    /**
     * @var string
     */
    protected $outFileName;
}
