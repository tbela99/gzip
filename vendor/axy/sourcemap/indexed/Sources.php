<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\indexed;

/**
 * Wrapper for section "sources" (and "sourcesContent")
 */
final class Sources extends Base
{
    /**
     * Sets a content of a file
     *
     * @param string $fileName
     * @param string $content
     */
    public function setContent($fileName, $content)
    {
        $this->contents[$this->add($fileName)] = $content;
    }

    /**
     * Returns a data for the "sourcesContent" section
     *
     * @return string[]
     */
    public function getContents()
    {
        if (empty($this->contents)) {
            return [];
        }
        $result = [];
        $last = 0;
        foreach ($this->indexes as $index) {
            if (isset($this->contents[$index])) {
                $result[] = $this->contents[$index];
                $last = $index;
            } else {
                $result[] = null;
            }
        }
        return array_slice($result, 0, $last + 1);
    }

    /**
     * {@inheritdoc}
     */
    protected function onRename($index, $newName)
    {
        $this->context->getMappings()->renameFile($index, $newName);
    }

    /**
     * {@inheritdoc}
     */
    protected function onRemove($index)
    {
        $this->context->getMappings()->removeFile($index);
        unset($this->contents[$index]);
    }

    /**
     * {@inheritdoc}
     */
    protected $contextKey = 'sources';

    /**
     * {@inheritdoc}
     */
    protected $keyIndex = 'fileIndex';

    /**
     * {@inheritdoc}
     */
    protected $keyName = 'fileName';

    /**
     * @var string[]
     */
    private $contents = [];
}
