<?php

namespace TBela\CSS;

use TBela\CSS\Event\Event;
use TBela\CSS\Interfaces\RuleListInterface;

class Traverser extends Event
{

    const IGNORE_NODE = 1;
    const IGNORE_CHILDREN = 2;

    /**
     * @param Element $element
     * @return Element|null
     */
    public function traverse(Element $element) {

        $result = $this->doTraverse($element);

        if (!($result instanceof Element)) {

            return null;
        }

        return $result;
    }

    protected function process(Element $node, array $data)
    {

        foreach ($data as $res) {

            if ($res === static::IGNORE_NODE) {

                return static::IGNORE_NODE;
            }

            if ($res === static::IGNORE_CHILDREN) {

                return static::IGNORE_CHILDREN;
            }

            if ($res instanceof Element) {

                if ($res !== $node) {

                    return $res;
                }
            }
        }

        return $node;
    }

    protected function doTraverse(Element $node)
    {

        $result = $this->process($node, $this->emit('enter', $node));

        if ($result === static::IGNORE_NODE) {

            return static::IGNORE_NODE;
        }

        $ignore_children = $result === static::IGNORE_CHILDREN;

        if ($result instanceof Element) {

            if ($result !== $node) {

                $node = $result;
            }
        }

        if ($node === func_get_arg(0) && $node instanceof RuleListInterface) {

            $children = $node['children'];

            if ($ignore_children) {

                $node = clone $node;
                $node->removeChildren();

                return $node;
            }

            foreach ($children as $child) {

                $temp_c = $this->doTraverse($child);

                if ($temp_c instanceof Element) {

                    $node->append($temp_c);
                } else if ($temp_c !== static::IGNORE_NODE) {

                    if ($temp_c === static::IGNORE_CHILDREN && $child instanceof RuleListInterface) {

                        $child = clone $child;
                        $child->removeChildren();
                    }

                    $node->append($child);
                }
            }
        }

        $result = $this->process($node, $this->emit('exit', $node));

        if ($result === static::IGNORE_NODE) {

            return static::IGNORE_NODE;
        }

        $ignore_children = $result === static::IGNORE_CHILDREN;

        if ($result instanceof Element) {

            if ($result !== $node) {

                $node = $result;
            }
        }

        if ($ignore_children && $node instanceof RuleListInterface) {

            $node = clone $node;
            $node->removeChildren();
        }

        return $node;
    }
}