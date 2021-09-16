<?php
/**
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 */

namespace axy\sourcemap\indexed;

/**
 * Wrapper for section "names"
 */
final class Names extends Base
{
    /**
     * {@inheritdoc}
     */
    protected $contextKey = 'names';

    /**
     * {@inheritdoc}
     */
    protected $keyIndex = 'nameIndex';

    /**
     * {@inheritdoc}
     */
    protected $keyName = 'name';

    /**
     * {@inheritdoc}
     */
    protected function onRename($index, $newName)
    {
        $this->context->getMappings()->renameName($index, $newName);
    }

    /**
     * {@inheritdoc}
     */
    protected function onRemove($index)
    {
        $this->context->getMappings()->removeName($index);
    }
}
