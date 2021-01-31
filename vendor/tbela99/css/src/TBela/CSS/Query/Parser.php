<?php

namespace TBela\CSS\Query;

use TBela\CSS\Parser\ParserTrait;
use TBela\CSS\Parser\SyntaxError;

class Parser
{
    /**
     * @var int
     */
    protected $position;
    /**
     * @var int
     */
    protected $last;
    /**
     * @var string
     */
    protected $string;
    /**
     * @var array
     */
    protected $tokens;

    /**
     * @param string $string
     * @return TokenList
     * @throws SyntaxError
     */
    public function parse($string)
    {

        $string = trim($string);

        if ($string === '') {

            return new TokenList([]);
        }

        $tokens = [];
        $list = [];
        $hash = [];

        foreach ($this->split($string) as $token) {

            $items = [];

            foreach ($this->split($token, ',') as $item) {

                $item = trim($item);

                if ($item == '*') {

                    $items = ['*'];
                    break;
                }

                if (!isset($hash[$item])) {

                    $hash[$item] = 1;
                    $items[] = $item;
                }
            }

            $item = implode(',', $items);

            if ($item == '*') {

                $list = ['*'];
                break;
            }

            if ($item !== '') {

                $list[] = $item;
            }
        }

        foreach ($list as $token) {

            $this->doParse($token);
            $tokens[] = array_map([Token::class, 'getInstance'], $this->tokens);
        }

        return new TokenList($tokens);
    }

    /**
     * @param string $string
     * @return array
     * @throws SyntaxError
     */
    protected function doParse($string)
    {

        $string = ltrim($string);

        $i = -1;
        $j = strlen($string) - 1;

        $this->position = -1;
        $this->last = $j;
        $this->string = $string;
        $this->tokens = [];

        if (substr($string, 0, 2) == '//') {

            $this->tokens[] = (object)['type' => 'select', 'node' => '*', 'context' => 'root'];
            $i += 2;
        } else if (substr($string, 0, 1) == '/') {

            $this->tokens[] = (object)['type' => 'select', 'node' => '>', 'context' => 'root'];
            $i += 1;
        }

        $this->position = $i;

        while ($this->position < $this->last) {

            $this->parse_selectors();
            $this->parse_path();
        }

        $j = count($this->tokens);

        while ($j--) {

            if (isset($this->tokens[$j + 1]) &&
                $this->tokens[$j + 1]->type == 'select' &&
                in_array($this->tokens[$j + 1]->node, ['.', '..']) &&
                $this->tokens[$j]->type == 'select' &&
                $this->tokens[$j]->node == '>') {

                array_splice($this->tokens, $j, 1);
            }
        }

        // set default context
        if (empty($this->tokens) || (isset($this->tokens[0]) && $this->tokens[0]->type != 'select')) {

            array_unshift($this->tokens, (object)['type' => 'select', 'node' => 'self_or_descendants']);
        }

        return $this->tokens;
    }

    /**
     * @throws SyntaxError
     */
    protected function parse_selectors()
    {

        $j = $this->last;
        $i = $this->position;
        $string = $this->string;

        $buffer = '';

        while ($i < $j && $this->is_whitespace($string[$i + 1])) {

            $i++;
        }

        if (substr($string, $i + 1, 1) == '/') {

            throw new SyntaxError(sprintf('unexpected character %s at position %d', '/', $i + 1), 400);
        }

        while ($i++ < $j) {

            switch ($string[$i]) {

                case '"':
                case "'":

                    $q = $string[$i];
                    $buffer .= $string[$i];

                    while ($i++ < $j) {

                        $buffer .= $string[$i];

                        if ($string[$i] == '\\') {

                            if (isset($string[$i + 1])) {

                                $buffer .= $string[++$i];
                            }
                        } else if ($string[$i] == $q) {

                            break;
                        }
                    }

                    break;

                case '\\':

                    $buffer .= $string[$i];

                    if (isset($string[$i + 1])) {

                        $buffer .= $string[++$i];
                    }

                    break;

                case '/':

                    break 2;

                default:

                    $buffer .= $string[$i];
                    break;
            }
        }

        $this->position = $i;

        if ($buffer !== '') {

            array_splice($this->tokens, count($this->tokens), 0, $this->parse_selector($buffer));
        }
    }

    /**
     * @param string $selector
     * @param string $context
     * @return array
     * @throws SyntaxError
     */
    protected function parse_selector($selector, $context = 'selector')
    {

        $selector = trim($selector);

        $buffer = '';

        $in_str = false;
        $in_attribute = false;
        $result = [];

        if ($selector == '.') {

            return [(object)['type' => 'select', 'node' => '.']];
        }

        if ($selector == '..') {

            return [(object)['type' => 'select', 'node' => '..']];
        } else {

            $i = -1;
            $j = strlen($selector) - 1;
            $q = '';

            while ($i++ < $j) {

                if ($in_str) {

                    throw new SyntaxError(sprintf('Expected character %s at position %d', $q, $i - 1));
                }

                switch ($selector[$i]) {

                    case '"':
                    case "'":

                        $match = $this->match_token($selector, $selector[$i], $i, $selector[$i]);

                        if ($match === false) {

                            throw new SyntaxError(sprintf('missing %s at position %d', $selector[$i], $j));
                        }


                        if ($buffer !== '') {

                            $result[] = $this->getTokenType($buffer, $context);
                        }

                        $buffer = ParserTrait::stripQuotes($match, true);

                        $result[] = (object)['type' => 'string', 'value' => $buffer, 'q' => preg_match('#^[a-zA-Z_@-][a-zA-Z0-9_@-]+$#', $buffer) ? '' : $selector[$i]];

                        $i += strlen($match) - 1;
                        $buffer = '';
                        break;

                    case ',':

                        if (trim($buffer) !== '') {

                            $result[] = $this->getTokenType($buffer, $context);
                        }

                        $token = end($result);

                        if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                            array_pop($result);
                        }
//
                        $result[] = (object)['type' => 'separator', 'value' => ','];
//
                        while ($i++ < $j) {

                            if (!preg_match('#\s#', $selector[$i])) {

                                $i--;
                                break;
                            }
                        }

                        $buffer = '';
                        break;

                    case '+':

                        if ($context === 'selector') {

                            if ($buffer !== '') {

                                $result[] = static::getType($buffer);
                                $buffer = '';
                            }

                            $token = end($result);

                            if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                                array_pop($result);
                            }

                            $result[] = (object)['type' => 'separator', 'value' => $selector[$i]];

                            while ($i++ < $j) {

                                if (!preg_match('#\s#', $selector[$i])) {

                                    $i--;
                                    $buffer = '';
                                    break;
                                }
                            }

                            break;
                        }

                        $buffer .= $selector[$i];
                        break;

                    case '~':
                    case '>':
                    case '=':
                    case '^':
                    case '*':
                    case '$':
                    case '!':

                        if ($context == 'attribute') {

                            if ($selector[$i] == '=' || (in_array($selector[$i], ['*', '^', '$', '!', '~']) && $i < $j && $selector[$i + 1] == '=')) {

                                if ($buffer !== '') {

                                    $result[] = $this->getTokenType($buffer, $context);
                                    $buffer = '';
                                }

                                if ($i < $j && $selector[$i + 1] == '=') {

                                    $result[] = (object)['type' => 'operator', 'value' => $selector[$i] . $selector[++$i]];
                                    $buffer = '';
                                } else {

                                    $result[] = (object)['type' => 'operator', 'value' => $selector[$i]];
                                }

                            } else {

                                $buffer .= $selector[$i];
                            }

                            break;
                        } else if ($context === 'selector' && in_array($selector[$i], ['>', '~'])) {

                            if ($buffer !== '') {

                                $result[] = static::getType($buffer);
                                $buffer = '';
                            }

                            $token = end($result);

                            if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                                array_pop($result);
                            }

                            $result[] = (object)['type' => 'separator', 'value' => $selector[$i]];

                            while ($i++ < $j) {

                                if (!preg_match('#\s#', $selector[$i])) {

                                    $i--;
                                    $buffer = '';
                                    break;
                                }
                            }

                            break;
                        }
//
//                        else {
//
                        $buffer .= $selector[$i];
                        break;
//                        }
//
//                        if ($buffer !== '') {
//
//                            $result[] = $this->getTokenType($buffer, $context);
//                        }
//
//                        $result[] = (object)['type' => 'operator', 'value' => $selector[$i]];
//                        $buffer = '';
//                        break;

                    case '|':

                        if (isset($selector[$i + 1]) && $selector[$i + 1] == '|') {

                            if ($buffer !== '') {

                                $result[] = $this->getTokenType($buffer, $context);
                                $buffer = '';
                            }

                            $token = end($result);

                            if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                                array_pop($result);
                            }

                            $result[] = (object)['type' => 'separator', 'value' => '||'];
                            $i++;

                            while ($i++ < $j) {

                                if (!preg_match('#\s#', $selector[$i])) {

                                    $i--;
                                    $buffer = '';
                                    break;
                                }
                            }

                            break;
                        }

                        $buffer .= $selector[$i];
                        break;

                    case ' ':
                    case "\t":
                    case "\n":
                    case "\r":

                        if ($buffer !== '') {

                            $result[] = $this->getTokenType($buffer, $context);
                            $buffer = '';
                        }

                        while ($i < $j && $this->is_whitespace($selector[++$i])) ;

                        if ($selector[$i] == '/') {

                            break 2;
                        }

                        $result[] = $this->getTokenType(' ', $context);
                        $i--;
                        break;

                    case '[':

                        if ($buffer !== '') {

                            $result[] = $this->getTokenType($buffer, $context);
                            $buffer = '';
                        }

                        if ($context == 'attribute') {

                            throw new SyntaxError(sprintf('Unexpected character %s at position %d in "%s"', $selector[$i], $i, $selector));
                        }

                        $in_attribute = true;
                        $match = $this->match_token($selector, ']', $i, '[');

                        if ($match === false) {

                            throw new SyntaxError(sprintf('missing %s in "%s"', '"]"', $selector));
                        }

                        if ($buffer !== '') {

                            $result[] = $this->getTokenType($buffer, $context);
                        }

                        $data = $this->parse_selector(substr($match, 1, -1), 'attribute');

                        if (isset($data[0])) {

                            $data = $data[0];

                            if (isset($data->value)) {

                                if (count($data->value) == 1 &&
                                    isset($data->value[0]->value) &&
                                    is_numeric($data->value[0]->value)) {

                                    $data->value[0]->type = 'index';
                                    $data->value[0]->value = +$data->value[0]->value;
                                } else if (count($data->value) == 3) {

                                    if ($data->value[1]->type == 'operator' &&
                                        $data->value[0]->type == $data->value[2]->type &&
                                        $data->value[0]->value === $data->value[2]->value) {

                                        $data = [];
                                    }
                                }
                            }

                            if (!empty($data)) {

                                $result[] = $data;
                            }
                        }

                        $in_attribute = false;
                        $i += strlen($match) - 1;

                        $buffer = '';
                        break;

                    case '(':

                        $in_attribute = true;
                        $match = $this->match_token($selector, ')', $i, '(');

                        if ($match === false) {

                            throw new SyntaxError(sprintf('missing %s in "%s"', '")"', $selector));
                        }

                        if ($context == 'selector') {

                            $buffer .= $match;
                            $i += strlen($match) - 1;
                            $in_attribute = false;
                            break;
                        }

                        $data = $this->parse_selector(substr($match, 1, -1), 'function');

                        $data = $data[0];

                        $data->name = $buffer;
                        $data->arguments = $data->value;

                        unset($data->value);

                        $result[] = $data;

                        $in_attribute = false;
                        $buffer = '';
                        $i += strlen($match) - 1;
                        break;
                    case '\\':

                        if (isset($selector[$i + 1])) {

                            if (!in_array($selector[$i + 1], ['[', ']'])) {

                                $buffer .= $selector[$i];
                            }

                            $buffer .= $selector[++$i];
                        } else {

                            $buffer .= $selector[$i];
                        }

                        break;

                    default:

                        $buffer .= $selector[$i];
                        break;
                }
            }
        }

        if ($in_attribute) {

            throw new SyntaxError(sprintf('Expected character %s at position %d', ']', $i - 1));
        }

        if ($in_str) {

            throw new SyntaxError(sprintf('Expected character %s at position %d', $q, $i - 1));
        }

        if ($buffer !== '') {

            $result[] = $this->getTokenType($buffer, $context);
        }

        return [(object)['type' => $context, 'value' => $result]];
    }

    /**
     * @param string $token
     * @param string $context
     * @return object
     */
    protected function getTokenType($token, $context)
    {

        $value = trim($token);

        if ($value === '' && $token !== '') {

            $value = ' ';
        }

        $result = (object)['type' => $value === ' ' ? 'whitespace' : 'string', 'value' => $value];

        if (substr($token, 0, 1) == '@' && $context != 'selector') {

            $result->type = 'attribute_name';
            $result->value = substr($result->value, 1);
        }

        return $result;
    }

    /**
     * @throws SyntaxError
     */
    protected function parse_path()
    {

        $j = strlen($this->string) - 1;

        while ($this->position <= $j && $this->is_whitespace($this->string[$this->position])) {

            $this->position++;
        }

        $substr = substr($this->string, $this->position, 2);

        if ($substr == '//') {

            $this->position += 1;
            $this->tokens[] = (object)['type' => 'select', 'node' => '*'];
        } else if ($substr !== false) {
            $token = substr($substr, 0, 1);

            if ($token == '/') {

                //    $this->position++;
                $this->tokens[] = (object)['type' => 'select', 'node' => '>'];
            } else if ($token !== '') {

                var_dump($token, $substr);

                throw new SyntaxError(sprintf('expected "%s" at position %d', $token, $this->position));
            }
        }
    }

    protected function is_whitespace($char)
    {

        return preg_match('#^\s+$#sm', $char);
    }

    protected function match_token($string, $close, $position, $start)
    {

        $j = strlen($string) - 1;
        $i = $position;

        $buffer = $string[$i];
        $in_str = true;

        $match = 1;

        while ($i++ < $j) {

            switch ($string[$i]) {

                case '\\':

                    $buffer .= $string[$i];

                    while ($i++ < $j) {

                        $buffer .= $string[$i];

                        if ($string[$i] == $close && $string[$i - 1] != '\\') {

                            return $buffer;
                        }
                    }

                    break;

                case $close:

                    if (!isset($string[$i - 1]) || $string[$i] != '\\') {

                        $match--;
                    }

                    $buffer .= $string[$i];

                    if ($match === 0) {

                        return $buffer;
                    }

                    break;

                default:

                    if (!is_null($start) && (!isset($string[$i - 1]) || $string[$i] != '\\') && $string[$i] === $start) {

                        $match++;
                    }

                    $buffer .= $string[$i];
                    break;
            }
        }

        if ($in_str) {

            return false;
        }

        return $buffer;
    }

    public function split($string, $char = '|')
    {

        $result = [];

        $i = -1;
        $j = strlen($string);

        $buffer = '';

        while (++$i < $j) {

            switch ($string[$i]) {

                case $char:

                    if ($string[$i] == '|' && isset($string[$i + 1]) && $string[$i + 1] == '|') {

                        $buffer .= '||';
                        $i++;
                        break;
                    }

                    $result[] = $buffer;
                    $buffer = '';
                    break;

                case '"':
                case "'":

                    $buffer .= $string[$i];

                    $token = $string[$i];

                    while (++$i < $j) {

                        $buffer .= $string[$i];

                        if ($string[$i] == $token && $string[$i - 1] != '\\') {

                            break;
                        }
                    }

                    break;

                case '[':

                    $buffer .= $string[$i];

                    while (++$i < $j) {

                        $buffer .= $string[$i];

                        if ($string[$i] == ']' && $string[$i - 1] != '\\') {

                            break;
                        }
                    }

                    break;

                default:

                    $buffer .= $string[$i];
                    break;
            }
        }

        if ($buffer !== '') {

            $result[] = $buffer;
        }

        return $result;
    }
}