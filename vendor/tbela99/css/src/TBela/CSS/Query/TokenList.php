<?php

namespace TBela\CSS\Query;

use SplObjectStorage;

class TokenList implements TokenInterface
{
    use TokenStringifiableTrait;

    /**
     * @var TokenInterface[][]
     */
    protected $tokens = [];

    /**
     * TokenList constructor.
     * @param TokenInterface[][] $tokens
     */
    public function __construct(array $tokens) {

        $this->tokens = $tokens;
    }

    /**
     * @inheritDoc
     */
    public function filter(array $context)
    {
        $result = [];

        $objectStore = new SplObjectStorage;

        // TODO: Implement filter() method.
        foreach ($this->tokens as $tokens) {

            $data = $context;

            foreach ($tokens as $token) {

                $data = $token->filter($data);

                if (empty($data)) {

                    continue 2;
                }
            }

            if (!empty($data)) {

                foreach ($data as $node) {

                    if (!$objectStore->contains($node)) {

                        $result[] = $node;
                        $objectStore->attach($node);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function render(array $options = [])
    {

        return implode('|', array_map(function ($nodes) use ($options) {

            $result = '';

            foreach ($nodes as $index => $node) {

                $partial = $node->render($options);

                if (in_array($partial, ['.', '..']) && $index > 0) {

                    $partial = '/'.$partial;
                }

                $result .= $partial;
            }

            return $result;

        }, $this->tokens));
    }
}