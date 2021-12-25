<?php

namespace TBela\CSS;

use Exception;
use stdClass;

use TBela\CSS\Interfaces\ParsableInterface;
use TBela\CSS\Interfaces\RuleListInterface;
use TBela\CSS\Parser\Helper;
use TBela\CSS\Parser\ParserTrait;
use TBela\CSS\Parser\SyntaxError;
use function preg_replace_callback;
use function substr;

/**
 * Css Parser
 * @package TBela\CSS
 * ok */
class Parser implements ParsableInterface
{

    use ParserTrait;

    protected $parentOffset = 0;
    protected $parentStylesheet = null;
    protected $parentMediaRule = null;

    protected $errors = [];

    protected $ast = null;
    /**
     * css data
     * @var string
     * @ignore
     */
    protected $css = '';

    /**
     * @var string
     * @ignore
     */
    protected $src = '';
    /**
     * @var array
     * @ignore
     */
    protected $options = [
        'capture_errors' => true,
        'flatten_import' => false,
        'allow_duplicate_rules' => ['font-face'], // set to true for speed
        'allow_duplicate_declarations' => false
    ];

    /**
     * Parser constructor.
     * @param string $css
     * @param array $options
     */
    public function __construct($css = '', array $options = [])
    {
        if ($css !== '') {

            $this->setContent($css);
        }

        $this->setOptions($options);
    }

    /**     * load css content from a file
     * @param string $file
     * @param string $media
     * @return Parser
     * @throws Exception
     */

    public function load($file, $media = '')
    {

        $this->src = Helper::absolutePath($file, Helper::getCurrentDirectory());
        $this->css = $this->getFileContent($file, $media);
        $this->ast = null;

        return $this;
    }

    /**
     * parse css file and append to the existing AST
     * @param string $file
     * @param string $media
     * @return Parser
     * @throws SyntaxError
     * @throws Exception
     */
    public function append($file, $media = '')
    {

        return $this->merge((new self('', $this->options))->load($file, $media));
    }

    /**
     * @param Parser $parser
     * @return Parser
     * @throws SyntaxError
     */
    public function merge($parser)
    {

        assert($parser instanceof self);

        array_splice($this->tokenize()->ast->children, count($this->ast->children), 0, $parser->tokenize()->ast->children);
        array_splice($this->errors, count($this->errors), 0, $parser->errors);
        return $this;
    }

    /**
     * parse css and append to the existing AST
     * @param string $css
     * @param string $media
     * @return Parser
     * @throws SyntaxError
     */
    public function appendContent($css, $media = '')
    {
        if ($media !== '' && $media != 'all') {

            $css = '@media ' . $media . ' { ' . rtrim($css) . ' }';
        }

        $this->css .= rtrim($css);
        return $this->tokenize();
    }

    /**
     * set css content
     * @param string $css
     * @param string $media
     * @return Parser
     */
    public function setContent($css, $media = '')
    {

        if ($media !== '' && $media != 'all') {

            $css = '@media ' . $media . '{ ' . rtrim($css) . ' }';
        }

        $this->css = $css;
        $this->src = '';
        $this->ast = null;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {

        return $this->css;
    }

    /**
     * set the parser options
     * @param array $options
     * @return Parser
     */
    public function setOptions(array $options)
    {

        foreach ($this->options as $key => $v) {

            if (isset($options[$key])) {

                if ($key == 'allow_duplicate_declarations') {

                    if (is_string($this->options[$key])) {

                        $this->options[$key] = [$this->options[$key]];
                    }

                    if (is_array($this->options[$key])) {

                        $this->options[$key] = array_flip($this->options[$key]);
                    }
                }

                else if ($key == 'allow_duplicate_rules' && is_string($v)) {

                    $this->options[$key] = [$v];
                }

                else {

                    $this->options[$key] = $options[$key];
                }

                if ($key == 'allow_duplicate_rules' && is_array($this->options[$key]) && !in_array('font-face', $this->options[$key])) {

                    $this->options[$key][] = 'font-face';
                }
            }
        }

        $this->ast = null;
        return $this;
    }

    /**
     * parse Css
     * @return RuleListInterface|null
     * @throws SyntaxError
     */
    public function parse()
    {

        if (is_null($this->ast)) {

            $this->doParse();
        }
        return Element::getInstance($this->ast);
    }

    /**
     * @inheritDoc
     * @throws SyntaxError
     */
    public function getAst()
    {

        if (is_null($this->ast)) {

            $this->doParse();
        }

        return clone $this->ast;
    }

    /**
     * @param stdClass $ast
     * @return stdClass
     */
    public function deduplicate($ast)
    {

        if ($this->options['allow_duplicate_rules'] !== true ||
            $this->options['allow_duplicate_declarations'] !== true) {

            switch ($ast->type) {

                case 'Stylesheet':

                    return $this->deduplicateRules($ast);

                case 'AtRule':

                    return !empty($ast->hasDeclarations) ? $this->deduplicateDeclarations($ast) : $this->deduplicateRules($ast);

                case 'Rule':

                    return $this->deduplicateDeclarations($ast);
            }
        }

        return $ast;
    }

    /**
     * compute signature
     * @param stdClass $ast
     * @return string
     * @ignore
     */
    protected function computeSignature($ast)
    {

        $signature = 'type:' . $ast->type;

        $name = isset($ast->name) ? $ast->name : null;

        if (isset($name)) {

            $signature .= ':name:' . $name;
        }

        $value = isset($ast->value) ? $ast->value : null;

        if (isset($value)) {

            $val = is_string($value) ? Value::parse($value, $name) : $value;
            $signature .= ':value:' . $val->render(['convert_color' => 'hex', 'compress' => true]);
        }

        $selector = isset($ast->selector) ? $ast->selector : null;

        if (isset($selector)) {

            $signature .= ':selector:' . (is_array($selector) ? implode(',', $selector) : $selector);
        }

        $vendor = isset($ast->vendor) ? $ast->vendor : null;

        if (isset($vendor)) {

            $signature .= ':vendor:' . $vendor;
        }

        return $signature;
    }

    /**
     * @param stdClass $ast
     * @return stdClass
     */
    protected function deduplicateRules($ast)
    {
        if (isset($ast->children)) {

            if (empty($this->options['allow_duplicate_rules']) ||
                is_array($this->options['allow_duplicate_rules'])) {

                $signature = '';
                $total = count($ast->children);

                $allowed = is_array($this->options['allow_duplicate_rules']) ? $this->options['allow_duplicate_rules'] : [];

                while ($total--) {

                    if ($total > 0) {

                        $el = $ast->children[$total];
                        if ($el->type == 'Comment' || $el->type == 'NestingRule') {

                            continue;
                        }

                        $next = $ast->children[$total - 1];

                        while ($total > 1 && (string)$next->type == 'Comment') {

                            $next = $ast->children[--$total - 1];
                        }

                        if (!empty($allowed) &&
                            (
                                ($next->type == 'AtRule' && in_array($next->name, $allowed)) ||
                                ($next->type == 'Rule' &&
                                    array_intersect(is_array($next->selector) ? $next->selector : [$next->selector], $allowed))
                            )
                        ) {

                            continue;
                        }

                        if ($signature === '') {

                            $signature = $this->computeSignature($el);
                        }

                        $nextSignature = $this->computeSignature($next);

                        while ($next != $el && $signature == $nextSignature) {

                            array_splice($ast->children, $total - 1, 1);

                            if ($el->type != 'Declaration') {

                                $next->parent = null;
                                array_splice($el->children, 0, 0, $next->children);

                                if (isset($next->location) && isset($el->location)) {

                                    $el->location->start = $next->location->start;
                                }
                            }

                            if ($total == 1) {

                                break;
                            }

                            $next = $ast->children[--$total - 1];

                            while ($total > 1 && $next->type == 'Comment') {

                                $next = $ast->children[--$total - 1];
                            }

                            $nextSignature = $this->computeSignature($next);
                        }

                        $signature = $nextSignature;
                    }
                }
            }

            foreach ($ast->children as $key => $element) {

                $ast->children[$key] = $this->deduplicate($element);
            }
        }

        return $ast;
    }

    /**
     * @param stdClass $ast
     * @return stdClass
     */
    protected function deduplicateDeclarations($ast)
    {

        if ($this->options['allow_duplicate_declarations'] !== true && !empty($ast->children)) {

            $elements = $ast->children;
            $total = count($elements);

            $hash = [];
            $exceptions = is_array($this->options['allow_duplicate_declarations']) ? $this->options['allow_duplicate_declarations'] : !empty($this->options['allow_duplicate_declarations']);

            while ($total--) {

                $declaration = $ast->children[$total];

                if ($declaration->type == 'Comment') {

                    continue;
                }

                $signature = $this->computeSignature($declaration);

                if ($exceptions === true || isset($exceptions[$signature])) {

                    continue;
                }

                if (isset($hash[$signature])) {

                    if (isset($declaration->parent)) {

                        $declaration->parent = null;
                    }

                    array_splice($ast->children, $total, 1);
                    continue;
                }

                $hash[$signature] = 1;
            }
        }

        return $ast;
    }

    /**
     * @param string $file
     * @param string $media
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function getFileContent($file, $media = '')
    {

        if (!preg_match('#^(https?:)?//#', $file)) {

            if (is_file($file)) {

                $content = file_get_contents($file);

                return $media === '' || $media == 'all' ? $content : '@media ' . $media . ' {' . $content . '}';
            }

            throw new Exception('File Not Found', 404);
        } else {

            $content = Helper::fetchContent($file);
        }

        if ($content === false) {

            throw new Exception(sprintf('File Not Found "%s"', $file), 404);
        }

        return $content;
    }

    /**
     *
     * @return Parser
     * @ignore
     */
    protected function getRoot()
    {

        if (is_null($this->ast)) {

            $this->ast = (object)[
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

                $this->ast->src = $this->src;
            }
        }

        return $this;
    }

    /**
     * @return Parser $ast
     * @throws SyntaxError
     */
    public function tokenize()
    {

        if (!isset($this->ast)) {
            $this->getRoot();
        }

        if (!isset($this->ast->children)) {

            $this->ast->children = [];
        }

        array_splice($this->ast->children, count($this->ast->children), 0, $this->getTokens());
        $this->deduplicate($this->ast);

        return $this;
    }

    /**
     * @return array
     * @throws SyntaxError
     * @throws Exception
     */
    protected function getTokens()
    {

        $position = $this->ast->location->end;

        $i = $position->index - 1;
        $j = strlen($this->css) - 1;

        $tokens = [];

        while ($i++ < $j) {

            while ($i < $j && static::is_whitespace($this->css[$i])) {

                $this->update($position, $this->css[$i]);
                $position->index += strlen($this->css[$i++]);
            }

            if ($this->css[$i] == '/' && substr($this->css, $i + 1, 1) == '*') {

                $comment = static::match_comment($this->css, $i, $j);

                if ($comment === false) {

                    $this->handleError(sprintf('unterminated comment at %s:%s', $position->line, $position->column));
                }

                $this->update($position, $comment);
                $position->index += strlen($comment);

                // ignore sourcemap #97
                if (strpos($comment, '/*# sourceMappingURL=') !== 0) {

                    $start = clone $position;
                    $token = (object)[
                        'type' => 'Comment',
                        'location' => (object)[
                            'start' => $start,
                            'end' => clone $position
                        ],
                        'value' => $comment
                    ];

                    $token->location->start->index += $this->parentOffset;
                    $token->location->end->index += $this->parentOffset;

                    if ($this->src !== '') {

                        $token->src = $this->src;
                    }

                    $token->location->end->index = max(1, $token->location->end->index - 1);
                    $token->location->end->column = max($token->location->end->column - 1, 1);
                    $tokens[] = $token;
                }

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

            $char = trim(substr($name, -1));

            if (substr($name, 0, 1) != '@' &&
                $char != '{') {

                // $char === ''
                if ('' === trim($name, "; \r\n\t")) {

                    $this->update($position, $name);
                    $position->index += strlen($name);
                    $i += strlen($name) - 1;
                    continue;
                }

                $declaration = !in_array($char, [';', '}']) ? $name : substr($name, 0, -1);
                $declaration = rtrim($declaration, " \r\n\t;}");
                if ($declaration !== '') {

                    $declaration = Value::split($declaration, ':', 2);

                    if (count($declaration) < 2 || $this->ast->type == 'Stylesheet') {

                        $this->handleError(sprintf('invalid declaration %s:%s:%s "%s"', $this->src, $position->line, $position->column, $name));
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
                            $this->parseVendor(trim($declaration[0])),
                            [
                                'value' => rtrim($declaration[1], "\n\r\t ")
                            ]);

                        if ($this->src !== '') {

                            $declaration->src = $this->src;
                        }

                        if (strpos($declaration->name, '/*') !== false) {

                            $leading = [];
                            $declaration->name = trim(Value::parse($declaration->name)->
                            filter(function ($value) use (&$leading) {
                                if ($value->type == 'Comment') {

                                    $leading[] = $value;
                                    return false;
                                }

                                return true;
                            }));

                            if (!empty($leading)) {

                                $declaration->leadingcomments = $leading;
                            }
                        }

                        if (strpos($declaration->value, '/*') !== false) {

                            $trailing = [];
                            $declaration->value = Value::parse($declaration->value)->
                            filter(function ($value) use (&$trailing) {

                                if ($value->type == 'Comment') {

                                    $trailing[] = $value;
                                    return false;
                                }

                                return true;
                            });

                            if (!empty($trailing)) {

                                $declaration->trailingcomments = $trailing;
                            }
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

                        $tokens[] = $declaration;

                        $declaration->location->start->index += $this->parentOffset;
                        $declaration->location->end->index += $this->parentOffset;

                        $declaration->location->end->index = max(1, $declaration->location->end->index - 1);
                        $declaration->location->end->column = max($declaration->location->end->column - 1, 1);
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
                        ], $this->parseVendor($matches[1]),
                            [
                                'value' => trim($matches[2])
                            ]
                        );

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

                        if ($rule->isLeaf) {

                            $rule->isLeaf = !in_array($rule->name, [
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

                        if ($this->src !== '') {

                            $rule->src = $this->src;
                        }

                        if ($rule->name == 'import') {

                            preg_match('#^((url\((["\']?)([^\\3]+)\\3\))|((["\']?)([^\\6]+)\\6))(.*?$)#', $rule->value, $matches);

                            $media = trim($matches[8]);

                            if ($media == 'all') {

                                $media = '';
                            }

                            $file = empty($matches[4]) ? $matches[7] : $matches[4];

                            if (!empty($this->options['flatten_import'])) {

                                $file = Helper::absolutePath($file, dirname($this->src));

                                if ($this->src !== '' && !preg_match('#^((https?:)?//)#i', $file)) {

                                    $curDir = Helper::getCurrentDirectory();

                                    if ($curDir != '/') {

                                        $curDir .= '/';
                                    }

                                    $file = preg_replace('#^' . preg_quote($curDir, '#') . '#', '', $file);

                                }

                                $parser = (new self('', $this->options))->load($file);

                                if (!isset($rule->children)) {

                                    $rule->children = [];
                                }

                                $parser->parentStylesheet = $this->ast;
                                $parser->parentMediaRule = $this->parentMediaRule;
                                $rule->name = 'media';

                                if ($media === '') {

                                    unset($rule->value);
                                } else {

                                    $rule->value = $media;

                                    if ($media != 'all') {

                                        $parser->parentMediaRule = $rule;
                                    }
                                }

                                $rule->children = $parser->getRoot()->getTokens();

                                if (!empty($parser->errors)) {

                                    array_splice($this->errors, count($this->errors), 0, $parser->errors);
                                }

                                unset($rule->isLeaf);
                            } else {

                                $rule->value = trim("\"$file\" $media");
                                unset($rule->hasDeclarations);
                            }

                        } else if ($char == '{') {

                            unset($rule->isLeaf);
                        }

                        if ($char != '{') {

                            $tokens[] = $rule;

                            $this->update($position, $name);
                            $position->index += strlen($name);

                            $rule->location->end = clone $position;
                            $rule->location->end->column = max(1, $rule->location->end->column - 1);
                            $i += strlen($name) - 1;
                            unset($rule->hasDeclarations);
                            continue;
                        }

                    } else {

                        $this->handleError(sprintf('cannot parse rule at %s:%s:%s', $this->src, $position->line, $position->column));
                    }

                    if (!empty($rule->isLeaf)) {

                        $this->update($position, $name);
                        $position->index += strlen($name);

                        $rule->location->end = clone $position;
                        $rule->location->end->index = max(1, $rule->location->end->index - 1);

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
                        'selector' => $selector
                    ];

                    if ($this->src !== '') {

                        $rule->src = $this->src;
                    }

                    if (strpos($name, '/*') !== false) {

                        $leading = [];
                        $rule->selector = Value::parse($rule->selector)->
                        filter(function ($value) use (&$leading) {

                            if ($value->type == 'Comment') {

                                $leading[] = $value;
                                return false;
                            }

                            return true;
                        });

                        $rule->leading = $leading;
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

                $validRule = true;

                if ($rule->type == 'NestingAtRule') {

                    $validRule = in_array($this->ast->type, ['NestingRule', 'Rule']) && substr(ltrim($rule->selector), 0, 1) != '@';

                    if ($validRule) {

                        foreach (Value::split($rule->selector, ',') as $selector) {

                            if (strpos($selector, '&') === false) {

                                $validRule = false;
                                break;
                            }
                        }
                    }

                    if (!$validRule) {

                        $this->handleError(sprintf('invalid nesting at-rule at %s:%s:%s "@nest %s"',
                            $rule->src,
                            $rule->location->start->line,
                            $rule->location->start->column,
                            $rule->selector
                        ));
                    }
                } else if (in_array($rule->type, ['AtRule', 'NestingMedialRule'])) {

                    $validRule = ($rule->type == 'AtRule' && in_array($this->ast->type, ['Rule', 'AtRule', 'NestingRule', 'NestingMediaRule', 'Stylesheet'])) ||
                        ($rule->type == 'NestingMedialRule' && in_array($this->ast->type, ['Rule', 'AtRule', 'NestingRule', 'NestingMediaRule']));

                    if (!$validRule) {

                        $this->handleError(sprintf('invalid nesting %s at %s:%s:%s "@%s %s"',
                            preg_replace_callback('#(^|[a-z])([A-Z])#', function ($matches) {

                                return ($matches[1] === '' ? '' : $matches[1] . '-') . strtolower($matches[2]);

                            }, $rule->type),
                            $rule->src,
                            $rule->location->start->line,
                            $rule->location->start->column,
                            $rule->name,
                            $rule->value
                        ));

                    }
                }

                $body = static::_close($this->css, '}', '{', $i + strlen($name), $j);

                if ($validRule && substr($body, -1) != '}') {

                    $validRule = false;
                    $this->handleError(sprintf('invalid %s at %s:%s:%s "%s"',
                        preg_replace_callback('#(^|[a-z])([A-Z])#', function ($matches) {

                            return ($matches[1] === '' ? '' : $matches[1] . '-') . strtolower($matches[2]);

                        }, $rule->type),
                        $rule->src,
                        $rule->location->start->line,
                        $rule->location->start->column,
                        isset($rule->name) ? $rule->name : $rule->selector
                    ));
                }

                if ($validRule) {

                    $rule->location->end->index += strlen($name);
                    $parser = new self(substr($body, 0, -1), $this->options);
                    $parser->src = $this->src;
                    $parser->ast = $rule;
                    $parser->parentMediaRule = $this->parentMediaRule;

                    $parser->parentStylesheet = $rule->type == 'Rule' ? $rule : $this->ast;
                    $parser->parentOffset = $rule->location->end->index + $this->parentOffset;

                    if ((isset($this->parentStylesheet->type) ? $this->parentStylesheet->type : null) == 'Rule') {

                        $this->parentStylesheet->type = 'NestingRule';
                    }

                    $rule->location->end->index = 0;
                    $rule->location->end->column = max($rule->location->end->column - 1, 1);

                    $parser->ast->children = $parser->getTokens();

                    if (!empty($parser->errors)) {

                        array_splice($this->errors, count($this->errors), 0, $parser->errors);
                    }

                    if (isset($this->parentMediaRule) && $rule->type == 'NestingRule') {

                        $this->parentMediaRule->type = 'NestingMediaRule';
                    }

                    $rule->location->end = clone $position;

                    $rule->location->end->index = max(1, $rule->location->end->index - 1);
                    $rule->location->end->column = max($rule->location->end->column - 1, 1);

                    if ($rule->type == 'AtRule' && $rule->name == 'media' &&
                        isset($rule->value) && $rule->value != '' && $rule->value != 'all') {

                        // top level media rule
                        if (isset($parser->parentMediaRule)) {

                            $parser->parentMediaRule->type = 'NestingMediaRule';
                        }

                        if ($this->ast->type == 'NestingRule' || $this->ast->type == 'NestingAtRule') {

                            $rule->type = 'NestingMediaRule';
                        }

                        // change the current mediaRule
                        $parser->parentMediaRule = $rule;
                    }

                    $errors = [];
                    $e = count($rule->children);

                    while ($e-- > 0) {

                        $child = $rule->children[$e];

                        if (in_array($rule->type, ['AtRule', 'NestingMedialRule']) && $rule->name == 'media' && in_array($child->type, ['AtRule', 'Declaration']) && $this->ast->type == 'Stylesheet') {

                            if ($child->type == 'AtRule' && $child->name == 'media') {

                                $this->handleError(sprintf('invalid nesting %s at %s:%s:%s "@%s %s"',
                                    preg_replace_callback('#(^|[a-z])([A-Z])#', function ($matches) {

                                        return ($matches[1] === '' ? '' : $matches[1] . '-') . strtolower($matches[2]);

                                    }, $child->type),
                                    isset($child->src) ? $child->src : '',
                                    $child->location->start->line,
                                    $child->location->start->column,
                                    $child->name,
                                    $child->value
                                ));
                                array_splice($rule->children, $e, 1);
                            } else if ($child->type == 'Declaration') {

                                $this->handleError(sprintf('invalid declaration at %s:%s:%s "%s"',
                                    isset($child->src) ? $child->src : '',
                                    $child->location->start->line,
                                    $child->location->start->column,
                                    $child->name . ':' . $child->value
                                ));

                                array_splice($rule->children, $e, 1);
                            }

                            continue;
                        }

                        if (in_array($rule->type, ['NestingRule', 'NestingMediaRule', 'NestedAtRule'])) {

                            if ($child->type == 'Declaration' && $e > 0) {

                                $prev = $rule->children[$e - 1];

                                if ($prev->type != 'Comment' && $prev->type != 'Declaration') {

                                    $errors[] = sprintf('invalid declaration at %s:%s:%s "%s"',
                                        isset($child->src) ? $child->src : '',
                                        $child->location->start->line,
                                        $child->location->start->column,
                                        $child->name . ':' . $child->value
                                    );

                                    array_splice($rule->children, $e, 1);
                                    continue;
                                }
                            }

                            if (in_array($child->type, ['Rule', 'NestingRule'])) {

                                $selectors = is_array($child->selector) ? $child->selector : Value::split($child->selector, ',');

                                foreach ($selectors as $selector) {

                                    if (substr(ltrim($selector), 0, 1) != '&') {

                                        $errors[] = sprintf('invalid nesting %s at %s:%s:%s "%s"',
                                            preg_replace_callback('#(^|[a-z])([A-Z])#', function ($matches) {

                                                return ($matches[1] === '' ? '' : $matches[1] . '-') . strtolower($matches[2]);

                                            }, $child->type),
                                            $child->src,
                                            $child->location->start->line,
                                            $child->location->start->column,
                                            implode(', ', $selectors)
                                        );

                                        array_splice($rule->children, $e, 1);
                                        break;
                                    }
                                }
                            }
                        }

                        if (!empty($errors)) {

                            $e = count($errors);

                            while ($e--) {

                                $this->handleError($errors[$e]);
                            }
                        }
                    }

                    $tokens[] = $rule;
                }

                $string = $name . $body;
                $this->update($position, $string);
                $position->index += strlen($string);
                $i += strlen($string) - 1;
            }
        }

        $this->ast->location->end->index = max(1, $this->ast->location->end->index - 1);
        $this->ast->location->end->column = max($this->ast->location->end->column - 1, 1);

        return $tokens;
    }

    /**
     * @throws SyntaxError
     * @throws Exception
     * @ignore
     */
    protected function doParse()
    {

        $this->errors = [];
        $this->css = rtrim($this->css);

        return $this->tokenize();
    }

    /**
     * @param stdClass $position
     * @param string $string
     * @return stdClass
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
     * @param string $message
     * @param int $error_code
     * @throws SyntaxError
     */
    protected function handleError($message, $error_code = 400)
    {

        $error = new SyntaxError($message, $error_code);

        if (!$this->options['capture_errors']) {

            throw $error;
        }

        $this->errors[] = $error;
    }

    /**
     * return parse errors
     * @return Exception[]
     */
    public function getErrors()
    {

        return $this->errors;
    }

    public function __toString()
    {

        try {

            if (!isset($this->ast)) {

                $this->getAst();
            }

            if (isset($this->ast)) {

                return (new Renderer())->renderAst($this->ast);
            }
        } catch (Exception $ex) {

            error_log($ex);
        }

        return '';
    }
}