<?php

namespace TBela\CSS\Parser;

use Exception;

use TBela\CSS\Event\EventTrait;
use TBela\CSS\Exceptions\IOException;
use TBela\CSS\Interfaces\ValidatorInterface;
use TBela\CSS\Value;
class Lexer
{

    use ParserTrait;
    use EventTrait;

    protected $parentOffset = 0;
    protected $parentStylesheet = null;
    protected  $parentMediaRule = null;

    /**
     * css data
     * @var string
     * @ignore
     */
    protected $css = '';
    protected $src = '';
    protected $context;
    protected $recover = false;

    /**
     * Parser constructor.
     * @param string $css
     * @param object|null $context
     */
    public function __construct($css = '', $context = null)
    {

        $this->css = rtrim($css);
        $this->context = $context;
    }

    /**
     * @param $css
     * @return Lexer
     */
    public function setContent($css)
    {

        $this->css = $css;
        $this->src = '';
        return $this;
    }

    /**
     * @param object $context
     * @return Lexer
     */
    public function setContext($context)
    {

        $this->context = $context;
        $this->parentStylesheet = $context;

        return $this;
    }

    /**
     * @param string $file
     * @param string $media
     * @return Lexer
     * @throws IOException
     */
    public function load($file, $media = '')
    {

        $file = Helper::absolutePath($file, Helper::getCurrentDirectory());

        if (!preg_match('#^(https?:)?//#', $file) && is_file($file)) {

            $content = file_get_contents($file);

        } else {

            $content = Helper::fetchContent($file);
        }

        if ($content === false) {

            throw new IOException(sprintf('File Not Found "%s" => \'%s:%s:%s\'', $file, $this->context->location->src, $this->context->location->end->line, $this->context->location->end->column), 404);
        }

        $this->css = $content;
        $this->src = $file;

        if ($media !== '' && $media != 'all') {

            $root = (object)[

                'type' => 'AtRule',
                'name' => 'media',
                'value' => Value::parse($media, null, true, '', '', true)
            ];

            $this->parentMediaRule = $root;

            $this->on('start', function ($context) use ($root, $file) {

                $root->location = clone $context->location;
                $root->location->start = clone $context->location->start;
                $root->location->end = clone $context->location->end;
                $root->src = $file;
                $this->emit('enter', $root, $this->context, $this->parentStylesheet);
            })->on('end', function ($context) use ($root) {

                if (isset($root->children)) {

                    $i = count($root->children);

                    while ($i--) {

                        $token = $root->children[$i];

                        if (in_array($token->type, ['NestingRule', 'NestingAtRule', 'NestingMediaRule'])) {

                            $root->type = 'NestingMediaRule';
                        }
                    }
                }

                $this->emit('exit', $root, $this->context, $this->parentStylesheet);
            });
        }

        return $this;
    }

    /**
     * @return Lexer
     * @throws SyntaxError
     * @throws Exception
     */
    public function tokenize()
    {

        $position = $this->context->location->end;

        $i = $position->index - 1;
        $j = strlen($this->css) - 1;
        $recover = false;

        $this->emit('start', $this->context);

        while ($i++ < $j) {

            while ($i < $j && static::is_whitespace($this->css[$i])) {

                $this->update($position, $this->css[$i]);
                $position->index += strlen($this->css[$i++]);
            }

            $comment = false;
            $token = null;

            if ($this->css[$i] == '/' && substr($this->css, $i + 1, 1) == '*') {

                $comment = static::match_comment($this->css, $i, $j);

                if ($comment === false) {

                    $comment = substr($this->css, $i);

                    $token = (object)[
                        'type' => 'InvalidComment',
                        'location' => (object)[
                            'start' => clone $position,
                            'end' => $this->update(clone $position, $comment)
                        ],
                        'value' => Value::escape($comment)
                    ];
                } else {

                    $token = (object)[
                        'type' => 'Comment',
                        'location' => (object)[
                            'start' => clone $position,
                            'end' => $this->update(clone $position, $comment)
                        ],
                        'value' => Value::escape($comment)
                    ];
                }
            } else if ($this->css[$i] == '<' && substr($this->css, $i, 4) == '<!--') {

                $k = $i + 3;
                $comment = '<!--';

                while ($k++ < $j) {

                    $comment .= $this->css[$k];

                    if ($this->css[$k] == '-' && substr($this->css, $k, 3) == '-->') {

                        $comment .= '->';

                        $token = (object)[
                            'type' => 'Comment',
                            'location' => (object)[
                                'start' => clone $position,
                                'end' => $this->update(clone $position, $comment)
                            ],
                            'value' => Value::escape($comment)
                        ];
                        break;
                    }
                }

                // unclosed comment
                if (is_null($token)) {

                    $token = (object)[
                        'type' => 'InvalidComment',

                        'location' => (object)[
                            'start' => clone $position,
                            'end' => $this->update(clone $position, $comment)
                        ],
                        'value' => Value::escape($comment)
                    ];
                }
            }

            if ($comment !== false) {

                $token->location->end->index += strlen($comment) - 1;
                $token->location->end->column = max($token->location->end->column - 1, 1);

                if ($this->src !== '') {

                    $token->src = $this->src;
                }

                $this->emit('enter', $token, $this->context, $this->parentStylesheet);

                $this->update($position, $comment);
                $position->index += strlen($comment);

                $i += strlen($comment) - 1;
                continue;
            }

            $name = static::substr($this->css, $i, $j, ['{', ';', '}']);

            if ($name === false) {

                $name = substr($this->css, $i);
            }

            if (trim($name) === '') {

                $this->update($position, $name);
                $position->index += strlen($name);
                continue;
            }

            $char = substr(trim($name), -1);

            if (substr($name, 0, 1) != '@' &&
                $char != '{') {

                // $char === ''
                if ('' === trim($name, "; \r\n\t")) {

                    $this->update($position, $name);
                    $position->index += strlen($name);
                    $i += strlen($name) - 1;
                    continue;
                }

                $declaration = ltrim(rtrim($name, " \r\n\t}"), " ;\r\n\t}");

                if ($declaration !== '') {

                    $parts = Value::split($declaration, ':', 2);

                    if (count($parts) < 2 || $this->context->type == 'Stylesheet') {

                        $token = (object)[
                            'type' => 'InvalidDeclaration',
                            'location' => (object)[
                                'start' => clone $position,
                                'end' => $this->update(clone $position, $declaration)
                            ],
                            'value' => rtrim($declaration, "\n\r\t ")
                        ];

                        $this->emit('enter', $token, $this->context, $this->parentStylesheet);

                    } else {

                        $end = clone $position;

                        $string = rtrim($name);
                        $this->update($end, $string);
                        $end->index += strlen($string);

                        $declaration = (object)array_merge(
                            [
                                'type' => 'Declaration',
                                'location' => (object)[
                                    'start' => clone $position,
                                    'end' => $end
                                ]
                            ],
                            $this->parseVendor(trim($parts[0])),
                            [
                                'value' => Value::escape(rtrim($parts[1], "\n\r\t "))
                            ]);

                        if ($this->src !== '') {

                            $declaration->src = $this->src;
                        }

                        if (in_array($declaration->name, ['src', 'background', 'background-image'])) {

                            $declaration->value = preg_replace_callback('#(^|[\s,/])url\(\s*(["\']?)([^)\\2]+)\\2\)#', function ($matches) {

                                $file = trim($matches[3]);
                                if (strpos($file, 'data:') !== false) {

                                    return $matches[0];
                                }

                                if (!preg_match('#^(/|((https?:)?//))#', $file)) {

                                    $file = Helper::absolutePath($file, dirname($this->src));
                                }

                                return $matches[1] . 'url(' . $file . ')';

                            }, $declaration->value);
                        }

                        $this->parseComments($declaration);

                        $data = $declaration->value;

                        if (is_array($data)) {

                            while (($end = end($data))) {

                                if (isset($end->value)) {

                                    if ($end->value == ';') {

                                        array_pop($data);
                                        continue;
                                    } else {

                                        if (empty($end->q)) {

                                            $end->value = rtrim($end->value, ';');
                                            $data[count($data) - 1] = $end;
                                        }

                                        break;
                                    }
                                }

                                break;
                            }

                            $isValidDeclaration = true;

                            foreach ($data as $key => $value) {

                                if ($isValidDeclaration && strpos($value->type, 'invalid-') === 0) {

                                    if ($value->type == 'invalid-css-function') {

                                        $c = count($value->arguments);

                                        while ($c--) {

                                            if ($value->arguments[$c]->type == 'invalid-comment' || substr(isset($value->arguments[$c]->value) ? $value->arguments[$c]->value : '', -1) == ';') {

                                                if ($value->arguments[$c]->type == 'invalid-comment') {

                                                    array_splice($value->arguments, $c, 1);
                                                } else {

                                                    $isValidDeclaration = false;
                                                    break;
                                                }
                                            } else {

                                                break;
                                            }
                                        }
                                    }

                                    // invalid declaration
                                    if ($isValidDeclaration) {

                                        $className = Value::getClassName($value->type);
                                        $data[$key] = $className::doRecover($value);
                                    }
                                }
                            }

                            $declaration->value = $data;

                            if (!$isValidDeclaration) {

                                $declaration->type = 'InvalidDeclaration';
                            }
                        }

                        $declaration->location->start->index += $this->parentOffset;
                        $declaration->location->end->index += $this->parentOffset;

                        $declaration->location->end->index = max(1, $declaration->location->end->index - 1);
                        $declaration->location->end->column = max($declaration->location->end->column - 1, 1);

                        $this->emit('enter', $declaration, $this->context, $this->parentStylesheet);
                    }
                }

                $this->update($position, $name);
                $position->index += strlen($name);

                $i += strlen($name) - 1;
                continue;
            }

            if ($name[0] == '@' || $char == '{') {

                if ($name[0] == '@') {

                    // at-rule
                    if (preg_match('#^@([a-z-]+)([^{;}]*)#', trim($name, ";{ \n\r\t"), $matches)) {

                        $rule = (object)array_merge([
                            'type' => 'AtRule',
                            'location' => (object)[
                                'start' => clone $position,
                                'end' => clone $position
                            ],
                            'isLeaf' => true,
                            'hasDeclarations' => $char == '{',
                        ], $this->parseVendor($matches[1])
                        );

                        $rule->value = Value::parse(trim($matches[2]), null, true, '', '', $rule->name == 'charset');

                        if ($rule->hasDeclarations) {

                            $rule->hasDeclarations = !in_array($rule->name, [
                                'media',
                                'document',
                                'container',
                                'keyframes',
                                'supports',
                                'font-feature-values'
                            ]);
                        }

                        if ($this->src !== '') {

                            $rule->src = $this->src;
                        }

                        if ($rule->name == 'import') {

                                preg_match('#^((url\((["\']?)([^\\3]+)\\3\))|((["\']?)([^\\6]+)\\6))(.*?$)#', is_array($rule->value) ? Value::renderTokens($rule->value) : $rule->value, $matches);

                                $media = trim($matches[8]);

                                if ($media == 'all') {

                                    $media = '';
                                }

                                $file = empty($matches[4]) ? $matches[7] : $matches[4];

                                $rule->value = trim("\"$file\" $media");
                                unset($rule->hasDeclarations);

                        } else if ($char == '{') {

                            unset($rule->isLeaf);
                        }

                        if (!empty($rule->isLeaf)) {

                            $rule->isLeaf = $char == ';' || $char === '' || !in_array($rule->name, [
                                    'page',
                                    'font-face',
                                    'viewport',
                                    'counter-style',
                                    'swash',
                                    'annotation',
                                    'ornaments',
                                    'stylistic',
                                    'styleset',
                                    'character-variant',
                                    'property',
                                    'color-profile'
                                ]);
                        }

                        if ($char == '{') {

                            unset($rule->isLeaf);
                        }

                    } else {

                        $body = static::_close($this->css, '}', '{', $i + strlen($name), $j);

                        if ($body === false) {

                            $i = $j;
                            break;
                        } else {

                            $name .= $body;
                        }

                        $rule = (object)[

                            'type' => 'InvalidAtRule',
                            'name' => '',
                            'location' => (object)[
                                'start' => clone $position,
                                'end' => $this->update(clone $position, $name)
                            ],
                            'value' => Value::escape($name)
                        ];

                        $rule->location->start->index += $this->parentOffset;
                        $rule->location->end->index += $this->parentOffset;

                        $rule->location->end->index = max(1, $rule->location->end->index - 1);
                        $rule->location->end->column = max($rule->location->end->column - 1, 1);

                        $this->emit('enter', $rule, $this->context, $this->parentStylesheet);

                        $this->update($position, $name);
                        $position->index += strlen($name);
                        $i += strlen($name) - 1;
                        continue;
                    }

                    if (!empty($rule->isLeaf)) {

                        $this->update($position, $name);
                        $position->index += strlen($name);

                        $rule->location->end = clone $position;
                        $rule->location->end->index = max(1, $rule->location->end->index - 1);

                        $this->parseComments($rule);
                        $this->emit('enter', $rule, $this->context, $this->parentStylesheet);

                        $i += strlen($name) - 1;
                        continue;
                    }

                } else {

                    $selector = rtrim(substr($name, 0, -1));
                    $rule = (object)[

                        'type' => 'Rule',
                        'location' => (object)[

                            'start' => clone $position,
                            'end' => clone $position
                        ],
                        'selector' => Value::escape($selector)
                    ];

                    if ($this->src !== '') {

                        $rule->src = $this->src;
                    }
                }

                if ($rule->type == 'AtRule') {

                    if ($rule->name == 'nest') {

                        $rule->type = 'NestingAtRule';
                        $rule->selector = $rule->value;

                        unset($rule->name);
                        unset($rule->value);
                        unset($rule->hasDeclarations);
                    }
                }

                $this->update($rule->location->end, $name);

                $body = static::_close($this->css, '}', '{', $i + strlen($name), $j);

                $validRule = true;

                if (substr($body, -1) != '}') {

                    // if EOF then we must recover this rule #102
                    $recover = $this->context->type == 'Stylesheet' || $this->recover;

                    if ($recover) {

                        $body = substr($this->css, $i + strlen($name));
                    } else {

                        $validRule = false;
                        $rule->type = 'InvalidRule';
                        $rule->value = $body;
                        $rule->location->end->index = max(1, $rule->location->end->index - 1);
                        $rule->location->end->column = max($rule->location->end->column - 1, 1);
                        $this->emit('enter', $rule, $this->context, $this->parentStylesheet);
                    }
                }

                $ignoreRule = $rule->type == 'AtRule' && $rule->name == 'media' && (empty($rule->value) || $rule->value == 'all');

                if ($validRule) {

                    if (!$ignoreRule) {

                        $validRule = $this->getStatus('enter', $rule) == ValidatorInterface::VALID;
                    }
                }

                if ($validRule) {

                    $rule->location->end->index += strlen($name);

                    $parser = new self($recover ? $body : substr($body, 0, -1), $rule);
                    $parser->src = $this->src;
                    $parser->events = $this->events;
                    $parser->parentOffset = $rule->location->end->index + $this->parentOffset;
                    $parser->recover = $recover;

                    $parser->off('start')->off('end');

                    $parser->parentMediaRule = $this->parentMediaRule;

                    if (isset($this->parentMediaRule) && $rule->type == 'NestingRule') {

                        $this->parentMediaRule->type = 'NestingMediaRule';
                    } else if ($rule->type == 'AtRule' && $rule->name == 'media' &&
                        isset($rule->value) && $rule->value != '' && $rule->value != 'all') {

                        // top level media rule
                        if (isset($parser->parentMediaRule)) {

                            $parser->parentMediaRule->type = 'NestingMediaRule';
                        }

                        if ($this->context->type == 'NestingRule' || $this->context->type == 'NestingAtRule') {

                            $rule->type = 'NestingMediaRule';
                        }

                        // change the current mediaRule
                        $parser->parentMediaRule = $rule;
                    }

                    $parser->parentStylesheet = !in_array($rule->type, ['AtRule', 'NestingMediaRule']) ? $rule : $this->parentStylesheet;

                    $parser->parentOffset = $rule->location->end->index + $this->parentOffset;
                    $parser->recover = $recover;

                    if (isset($this->parentStylesheet->type) && $this->parentStylesheet->type == 'Rule') {

                        $this->parentStylesheet->type = 'NestingRule';
                    }

                    $rule->location->end->index = 0;
                    $rule->location->end->column = max($rule->location->end->column - 1, 1);

                    $parser->tokenize();

                    $this->update($rule->location->end, substr($body, strlen($parser->css)));
                    $rule->location->end->index += 1;

                    $rule->location->end->index = max(1, $rule->location->end->index - 1);
                    $rule->location->end->column = max($rule->location->end->column - 1, 1);

                    if (!$ignoreRule) {

                        $this->parseComments($rule);
                        $this->emit('exit', $rule, $this->context, $this->parentStylesheet);
                    }
                }

                $string = $name . $body;
                $this->update($position, $string);
                $position->index += strlen($string);
                $i += strlen($string) - 1;
            }
        }

        $this->context->location->end->index = max(1, $this->context->location->end->index - 1);
        $this->context->location->end->column = max($this->context->location->end->column - 1, 1);

        $this->emit('end', $this->context);
        return $this;
    }

    /**
     *
     * @return object
     * @ignore
     */
    public function createContext()
    {

        $context = (object)[
            'type' => 'Stylesheet',
            'location' => (object)[
                'start' => (object)[
                    'line' => 1,
                    'column' => 1,
                    'index' => 0
                ],
                'end' => (object)[
                    'line' => 1,
                    'column' => 1,
                    'index' => 0
                ]
            ]
        ];

        if ($this->src !== '') {

            $context->src = $this->src;
        }

        return $context;
    }

    /**
     * @param object $position
     * @param string $string
     * @return object
     * @ignore
     */
    protected function update($position, $string)
    {

        $j = strlen($string);

        for ($i = 0; $i < $j; $i++) {

            if ($string[$i] == PHP_EOL) {

                $position->line++;
                $position->column = 1;
            } else {

                $position->column++;
            }
        }

        return $position;
    }

    protected function parseComments($token)
    {

        $property = property_exists($token, 'name') ? 'name' : (property_exists($token, 'selector') ? 'selector' : null);

        if ($property && !is_array($token->{$property})) {

            $comments = [];
            $formatted = Value::format($token->{$property}, $comments);

            if ($formatted !== false) {

                $token->{$property} = $formatted;

                if (!empty($comments)) {

                    $token->leadingcomments = $comments;
                }
            }

            else {

                $leading = [];
                $tokens = Value::parse($token->{$property}, null, true, '', '', true);

                $k = count($tokens);

                while ($k--) {

                    $t = $tokens[$k];

                    if ($t->type == 'Comment') {

                        $leading[] = $t->value;
                        array_splice($tokens, $k, 1);
                    }
                }

                $tokens = Value::reduce($tokens);
                $token->{$property} = $property == 'name' ? Value::renderTokens($tokens) : $tokens;

                if (!empty($leading)) {

                    $token->leadingcomments = $leading;
                }
            }
        }

        if (property_exists($token, 'value')) {

            if (is_array($token->value)) {

                return;
            }

            $comments = [];
            $formatted = Value::format($token->value, $comments);

            if ($formatted !== false) {

                $token->value = rtrim($formatted, "; \r\n\t");

                if (!empty($comments)) {

                    $token->trailingcomments = $comments;
                }
            }

            else {

                if (is_string($token->value)) {

                    $token->value = Value::parse($token->value, in_array($token->type, ['Declaration', 'Property']) ? $token->name : null, true, '', '', true);
                }

                $trailing = [];
                $k = count($token->value);

                while ($k--) {

                    if ($token->value[$k]->type == 'invalid-comment') {

                        array_splice($token->value, $k, 1);
                    } else if ($token->value[$k]->type == 'Comment') {

                        if (substr($token->value[$k]->value, 0, 4) == '<!--') {

                            array_splice($token->value, $k, 1);
                            continue;
                        }

                        $trailing[] = $token->value[$k]->value;
                        array_splice($token->value, $k, 1);
                    }
                }

                if (!empty($trailing)) {

                    $token->trailingcomments = array_reverse($trailing);
                }

                $token->value = Value::reduce($token->value, ['remove_defaults' => true]);
            }
        }
    }

    /**
     * @param string $str
     * @return array
     * @ignore
     */
    protected function parseVendor($str)
    {

        if (preg_match('/^(-([a-zA-Z]+)-(\S+))/', trim($str), $match)) {

            return [

                'name' => $match[3],
                'vendor' => $match[2]
            ];
        }

        return ['name' => $str];
    }

    /**
     * @param string $event
     * @param object $rule
     * @return int
     */
    protected function getStatus($event, $rule)
    {
        foreach ($this->emit($event, $rule, $this->context, $this->parentStylesheet) as $status) {

            if (is_int($status)) {

                return $status;
            }
        }

        return ValidatorInterface::VALID;
    }
}