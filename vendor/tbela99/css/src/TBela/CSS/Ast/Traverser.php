<?php

namespace TBela\CSS\Ast;

use TBela\CSS\Event\Event;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Value;

/**
 * Ast|Element traverser
 * @package TBela\CSS\Ast
 */
class Traverser extends Event
{

    /**
     * @var int do not preserve this node
     */
    const IGNORE_NODE = 1;
    /**
     * @var int do not preserve children of this node
     */
    const IGNORE_CHILDREN = 2;

    /**
     * Traverse ast
     * @param \stdClass|ElementInterface $ast
     * @return \stdClass|ElementInterface|null
     */
    public function traverse($ast)
    {

        $ast = clone $ast;
        $result = $this->doTraverse($ast, null);

        if (!is_object($result) || !isset($result->type)) {

            return null;
        }

        return $result;
    }

    /**
     * @param \stdClass|ElementInterface $node
     * @param array $data
     * @return int|\stdClass|ElementInterface
     * @ignore
     */
    protected function process($node, array $data)
    {

        foreach ($data as $res) {

            if ($res === static::IGNORE_NODE) {

                return static::IGNORE_NODE;
            }

            if ($res === static::IGNORE_CHILDREN) {

                return static::IGNORE_CHILDREN;
            }

            if (is_object($res)) {

                return $res;
            }
        }

        return $node;
    }

    /**
     * @param \stdClass|ElementInterface $node
     * @return int|\stdClass|ElementInterface
     * @ignore
     */
    protected function doTraverse($node, $level)
    {

        if (isset($node->value) && is_array($node->value)) {

            $node->value = Value::renderTokens($node->value);
        }

        $result = $this->process($node, $this->emit('enter', $node, $level));

        if ($result === static::IGNORE_NODE) {

            return static::IGNORE_NODE;
        }

        $ignore_children = $result === static::IGNORE_CHILDREN;

        if (is_object($result)) {

            if ($result !== $node) {

                $node = $result;
            }
        }

        if ($node === func_get_arg(0) && is_object($node) && isset($node->children)) {

            $children = $node->children;

            if ($ignore_children) {

                $node->children = [];

                return $node;
            }

            $list = [];

            foreach ($children as $child) {

                $temp_c = $this->doTraverse($child, is_null($level) ? 0 : $level + 1);

                if (is_object($temp_c)) {

                    $list[] = $temp_c;
                } else if ($temp_c !== static::IGNORE_NODE) {

                    if ($temp_c === static::IGNORE_CHILDREN && is_object($child)) {

                        $child->children = [];
                    }

                    $list[] = $child;
                }
            }

            $node->children = $list;
        }

        $result = $this->process($node, $this->emit('exit', $node, $level));

        if ($result === static::IGNORE_NODE) {

            return static::IGNORE_NODE;
        }

        $ignore_children = $result === static::IGNORE_CHILDREN;

        if (is_object($result)) {

            if ($result !== $node) {

                $node = $result;
            }
        }

        if ($ignore_children && is_object($node)) {

            $node->children = [];
        }

        return $node;
    }
}