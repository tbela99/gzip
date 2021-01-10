<?php

namespace TBela\CSS;

use Exception;
use stdClass;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Interfaces\RuleListInterface;
use TBela\CSS\Parser\Helper;
use TBela\CSS\Parser\ParserTrait;
use TBela\CSS\Parser\SyntaxError;
use TBela\CSS\Value\Set;
use function preg_replace_callback;
use function str_replace;
use function substr;

/**
 * Css Parser
 * @package TBela\CSS
 */
class Parser
{

    use ParserTrait;

    /**
     * @var stdClass
     * @ignore
     */
    protected $currentPosition;
    /**
     * @var stdClass
     * @ignore
     */
    protected $previousPosition;
    /**
     * @var int
     * @ignore
     */
    protected $end = 0;


    protected $errors = [];

    /**
     * @var stdClass|null
     * @ignore
     */
    protected $ast = null;

    /**
     * @var RuleListInterface|null
     * @ignore
     */
    protected $element = null;

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
    protected $path = '';

    /**
     * @var array
     * @ignore
     */
    protected $options = [
        'sourcemap' => false,
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

    /**
     * load css content from a file
     * @param string $file
     * @param string $media
     * @return Parser
     * @throws Exception
     */

    public function load($file, $media = '')
    {

        $this->path = $file;
        $this->css = $this->getFileContent($file, $media);
        $this->ast = null;
        $this->element = null;
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

        return $this->appendContent($this->getFileContent($file, $media));
    }

    /**
     * @param Parser $parser
     * @return Parser
     * @throws SyntaxError
     */

    public function merge($parser)
    {

        assert($parser instanceof Parser);

        if (is_null($this->ast)) {

            $this->doParse();
        }

        return $this->appendContent($parser->css);
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

            $css = '@media '.$media.' { '.rtrim($css).' }';
        }

        $this->css .= rtrim($css);
        $this->end = strlen($this->css);

        if (is_null($this->ast)) {

            $this->doParse();
        }

        $this->analyse();

        return $this;
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

            $css = '@media '.$media. '{ '.rtrim($css).' }';
        }

        $this->css = $css;
        $this->path = '';
        $this->ast = null;
        $this->element = null;
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

                $this->options[$key] = $options[$key];

                if ($key == 'allow_duplicate_declarations') {

                    if (is_string($this->options[$key])) {

                        $this->options[$key] = [$this->options[$key]];
                    } else if (is_array($this->options[$key])) {

                        $this->options[$key] = array_flip($this->options[$key]);
                    }
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

//        if (is_null($this->element)) {
//
//            $this->element = Element::getInstance(json_encode(json_decode($this->ast)));
//        }


        /**
         * @var RuleListInterface $element
         */
        $element = Element::getInstance($this->ast);

        if (empty($this->options['sourcemap'])) {

            return (new Traverser())->on('enter', function (Element $element) {

                $element->setLocation(null);

            })->traverse($element);
        }

        return $element;
    }

    public function getAst() {

        if (is_null($this->ast)) {

            $this->doParse();
        }

        return clone $this->ast;
    }

    public function setAst(ElementInterface $element) {

        $this->ast = $element->getAst();
    }

    public function deduplicate($ast)
    {

        if ((empty($this->options['allow_duplicate_rules']) ||
            $this->options['allow_duplicate_rules'] !== true ||
            empty($this->options['allow_duplicate_declarations']) || $this->options['allow_duplicate_declarations'] !== true)) {

            switch ($ast->type) {

                case 'AtRule':

                    return !empty($ast->hasDeclarations) ? $this->deduplicateDeclarations($ast) : $this->deduplicateRules($ast);

                case 'Stylesheet':

                    return $this->deduplicateRules($ast);

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

            $value = is_string($value) ? Value::parse($value, $name) : $value;
            $signature .= ':value:'.$value->render(['convert_color' => 'hex', 'compress' => true]);
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
                $el = null;

                $allowed = is_array($this->options['allow_duplicate_rules']) ? $this->options['allow_duplicate_rules'] : [];

                while ($total--) {

                    if ($total > 0) {

                        $el = $ast->children[$total];

                        if ($el->type == 'Comment') {

                            continue;
                        }

                        $next = $ast->children[$total - 1];

                        while ($total > 1 && (string) $next->type == 'Comment') {

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

                        while ($signature == $nextSignature) {

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

        if (!empty($this->options['allow_duplicate_declarations']) && !empty($ast->children)) {

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

                    $declaration->parent = null;
                    array_splice($ast->children, $total, 1);
                    continue;
                }

                $hash[$signature] = 1;
            }
        }

        return $ast;
    }

    /**
     *
     * @param string $css
     * @param null $path
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function expand($css, $path = null)
    {

        if (!is_null($path) && $path !== '') {

            if ($path[strlen($path) - 1] != '/') {

                $path .= '/';
            }
        }

        $isRemote = preg_match('#^(https?:)//#', $path);

        $css = preg_replace_callback('#url\(([^)]+)\)#', function ($matches) use ($path, $isRemote) {

            $file = trim(str_replace(array("'", '"'), "", $matches[1]));

            if (strpos($file, 'data:') === 0) {

                return $matches[0];
            }

            if ($isRemote) {

                if (!preg_match('#^(https?:)?//#i', $file)) {

                    if ($file[0] == '/') {

                        $file = $path . substr($file, 1);
                    } else {

                        $file = Helper::resolvePath($path . $file);
                    }
                }
            } else if (!preg_match('#^(/|((https?:)?//))#i', $file)) {

                $file = Helper::resolvePath($path . trim(str_replace(array("'", '"'), "", $matches[1])));
            }

            return 'url(' . preg_replace('#^' . preg_quote(Helper::getCurrentDirectory() . '/', '#') . '#', '', $file) . ')';
        },
            // resolve import directive, note import directive in imported css will NOT be processed
            $this->parseImport($css, $path)
        );

        return $css;
    }

    /**
     * @param string $file
     * @param string $media
     * @return string|bool
     * @throws Exception
     * @ignore
     */
    protected function getFileContent($file, $media = '')
    {

        if (!preg_match('#^(https?:)//#', $file)) {

            if (is_file($file)) {

                $content = $this->expand(file_get_contents($file), preg_replace('#^' . preg_quote(Helper::getCurrentDirectory() . '/', '#') . '#', '', dirname($file)));

                return $media === '' || $media == 'all' ? $content : '@media ' . $media . ' {' . $content . '}';
            }

            throw new Exception('File Not Found', 404);
        } else {

            $content = Helper::fetchContent($file);
        }

        if ($content === false) {

            throw new Exception(sprintf('File Not Found "%s"', $file), 404);
        }

        return $this->expand($content, dirname($file));
    }

    /**
     * @param $css
     * @param string $path
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function parseImport($css, $path = '')
    {

        $comments = [];
        $css = preg_replace_callback('/\/\*(.*?)\*\//sm', function ($matches) use (&$comments) {

            $comments[$matches[0]] = '~~~b' . md5($matches[0]) . 'b~~~';

            return str_replace($matches[0], $comments[$matches[0]], $matches[0]);
        }, $css);

        $css = preg_replace_callback('#@import ([^;]+);#', function ($matches) use ($path) {

            if (preg_match('#(url\(\s*((["\']?)([^\\3]+)\\3)\s*\)|((["\'])([^\\6]+)\\6))(.*)$#s', $matches[1], $match)) {

                $file = Helper::resolvePath(trim(empty($match[4]) ? $match[7] : $match[4]), $path);

                $media = trim($match[8]);

                if (strpos($media, ' ') !== false ) {

                    $media = ' ' . $media;
                }

                $css = $this->getFileContent($file);

                if ($css !== false) {

                    if ($media !== '' && $media !== 'all') {

                        $css = '@media ' . $media . " {\n" . $css . "\n}\n";
                    }

                    return '/* start: @import from ' . $file . ' */' . "\n" . $css . "\n" . '/* end: @import from ' . $file . ' */' . "\n";
                }
            }

            return $matches[0];

        }, $css);

        if (!empty($comments)) {

            $css = str_replace(array_values($comments), array_keys($comments), $css);
        }

        return $css;
    }

    /**
     *
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
        }
    }

    /**
     * @return stdClass|null
     * @throws SyntaxError
     * @throws Exception
     * @ignore
     */
    protected function doParse()
    {

        $this->errors = [];

        if (!empty($this->options['flatten_import'])) {

            $this->css = $this->parseImport($this->css, $this->path === '' ? Helper::getCurrentDirectory() : dirname($this->path));
        }

        $this->css = rtrim($this->css);

        // initialize ast
        $this->getRoot();

        $this->end = strlen($this->css);
        $start = $this->ast->location->start;
        $this->currentPosition = clone $start;

        $this->currentPosition->index = 0;
        $this->previousPosition = clone $this->currentPosition;

        return $this->analyse();
    }

    /**
     * @return stdClass|null
     * @throws SyntaxError
     */
    protected function analyse()
    {

        while ($this->next()) {

            if (substr($this->css, $this->currentPosition->index, 2) == '/*') {

                $comment = static::match_comment($this->css, $this->currentPosition->index, $this->end);

                if ($comment === false) {

                    // unclosed comment
                    $comment = substr($this->css, $this->currentPosition->index) . '*/';
                }

                $this->ast->children[] = $this->parseComment($comment, clone $this->currentPosition);

                $this->update($this->currentPosition, $comment);
                $this->currentPosition->index += strlen($comment);
                continue;
            }

            $substr = static::substr($this->css, $this->currentPosition->index, $this->end - 1, ['{', ';']);

            if (substr($substr, -1) != '{') {

                // parse at-rule
                $node = $this->parseAtRule($substr, clone $this->currentPosition);

                if ($node === false) {

                    $this->errors[] = new Exception(sprintf('cannot parse token at %s:%s : "%s"', $this->previousPosition->line, $this->previousPosition->column,
                        preg_replace('#^(.{40}).*$#sm', '$1... ', $substr)));
                    //     continue;
                } else {

                    $this->ast->children[] = $node;
                }

                $this->update($this->currentPosition, $substr);
                $this->currentPosition->index += strlen($substr);
            } else {

                $position = $this->update(clone $this->currentPosition, $substr);
                $position->index += strlen($substr);

                $block = static::_close($this->css, '}', '{', $position->index, $this->end - 1);

                $type = $this->getBlockType($block);

                if (substr($substr, 0, 1) == '@') {

                    $node = $this->parseAtRule($substr, clone $this->currentPosition, $type);

                } else {

                    $node = $this->parseRule($substr, clone $this->currentPosition);
                }

                if ($node === false) {

                    $rule = $substr . $block;

                    $this->errors[] = new Exception(sprintf('cannot parse token at %s:%s. Ignoring rules : "%s"', $this->previousPosition->line, $this->previousPosition->column, preg_replace('#(.{40}).*$#sm', '$1... ', $rule)));
                    $this->update($this->currentPosition, $rule);
                    $this->currentPosition->index += strlen($rule);

                    continue;

                } else {

                    $this->ast->children[] = $node;

                    $type = $node->type == 'Rule' ? 'statement' : $type;

                    $this->update($this->currentPosition, $substr);
                    $this->currentPosition->index += strlen($substr);

                    if ($type == 'block') {

                        $parser = new Parser($block, array_merge($this->options, ['flatten_import' => false]));
                        $parser->path = $this->path;
                        $parser->ast = $node;

                        $parser->doParse();

                        if (!empty($parser->errors)) {

                            array_splice($this->errors, count($this->errors), 0, $parser->errors);
                        }

                    } else {

                        $this->parseDeclarations($node, substr($block, 0, -1), $position);
                    }

                    $this->update($this->currentPosition, $block);
                    $this->currentPosition->index += strlen($block);
                }

                $position = clone $this->currentPosition;
                $position->column--;

                $node->location->start = clone $this->previousPosition;
                $node->location->end = $position;
            }
        }

        $this->ast->location->end->line = $this->currentPosition->line;
        $this->ast->location->end->index = max(0, $this->currentPosition->index - 1);
        $this->ast->location->end->column = max($this->currentPosition->column - 1, 1);

        return $this->ast = $this->deduplicate($this->ast);
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

            if ($string[$i] == "\n") {

                $position->line++;
                $position->column = 1;
            } else {

                $position->column++;
            }
        }

        return $position;
    }

    /**
     * @return bool
     * @ignore
     */
    protected function next()
    {

        $position = $this->getNextPosition($this->css, $this->currentPosition->index, $this->currentPosition->line, $this->currentPosition->column);

        $this->previousPosition->line = $this->currentPosition->line = $position->line;
        $this->previousPosition->column = $this->currentPosition->column = $position->column;
        $this->previousPosition->index = $this->currentPosition->index = $position->index;

        return $this->currentPosition->index < $this->end - 1;
    }

    /**
     * consume whitespace
     * @param string $input
     * @param int $currentIndex
     * @param int $currentLine
     * @param int $currentColumn
     * @return stdClass
     * @ignore
     */
    protected function getNextPosition($input, $currentIndex, $currentLine, $currentColumn)
    {

        assert(is_int($currentIndex));
        assert(is_int($currentLine));
        assert(is_int($currentColumn));

        $j = strlen($input);
        $i = $currentIndex;

        while ($i < $j) {

            if (!in_array($input[$i], [" ", "\t", "\r", "\n"])) {

                break;
            }

            if ($input[$i++] == "\n") {

                $currentLine++;
                $currentColumn = 1;
            } else {

                $currentColumn++;
            }
        }

        return (object)['line' => $currentLine, 'column' => $currentColumn, 'index' => $i];
    }

    /**
     * @param string $block
     * @return string
     */
    protected function getBlockType($block)
    {

        return substr(static::substr($block, 0, strlen($block) - 1, [';', '{']), -1) == '{' ? 'block' : 'statement';
    }

    /**
     * @param string $comment
     * @param stdClass $position
     * @return stdClass
     * @ignore
     */
    protected function parseComment($comment, $position)
    {

        $this->update($position, $comment);

        $position->column--;
        $position->index += $this->ast->location->start->index + strlen($comment);

        return (object)[
            'type' => 'Comment',
            'location' => (object)[
                'start' => (object)[
                    'line' => $this->currentPosition->line,
                    'column' => $this->currentPosition->column,
                    'index' => $this->ast->location->start->index + $this->currentPosition->index
                ],
                'end' => $position
            ],
            'value' => $comment
        ];
    }

    /**
     * parse @rule like @import, @charset
     * @param string $rule
     * @param stdClass $position
     * @param string $blockType
     * @return false|stdClass
     * @ignore
     */
    protected function parseAtRule($rule, $position, $blockType = '')
    {

        if (substr($rule, 0, 1) != '@') {

            return false;
        }

        //
        if (!preg_match('#^@((-((moz)|(webkit)|(ms)|o)-)?([^\s/;{(]+))([^;{]*)(.?)#s', $rule, $matches)) {

            return false;
        }

        $currentPosition = clone $position;

        $end = substr($rule, -1);

        $rule = rtrim($rule, ";{ \n\t\r");

        $this->update($position, $rule);

        $isLeaf = $end != '{';

        $data = [

            'type' => 'AtRule',
            'location' => (object)[
                'start' => $currentPosition,
                'end' => (object)[
                        'line' => $position->line,
                        'column' => $position->column,
                        'index' => $this->ast->location->start->index + $position->index
                ]
            ],
            'isLeaf' => $isLeaf,
            'hasDeclarations' => !$isLeaf && $blockType == 'statement',
            'name' => trim($matches[7]),
            'vendor' => $matches[3],
            'value' => trim($matches[8])
        ];

        if (empty($matches[3])) {

            unset($data['vendor']);
        }

        if (empty($data['isLeaf'])) {

            unset($data['isLeaf']);
        }

        if (empty($data['hasDeclarations'])) {

            unset($data['hasDeclarations']);
        }

        return $this->doParseComments((object)$data);
    }

    protected function doParseComments($node) {

        if (isset($node->value) && strpos($node->value, '/*') !== false) {

            $trailing = [];

            if (!($node->value instanceof Set)) {

                $node->value = Value::parse($node->value, $node->name);
            }

            $node->value->filter(function (Value $value) use(&$trailing) {

                if ($value->type == 'Comment') {

                    $trailing[] = $value;
                    return false;
                }

                return true;
            });

            if (!empty($trailing)) {

                $node->value = Value::parse(trim($node->value), $node->name);
                $node->trailingcomments = $trailing;
            }
        }

        if (isset($node->selector) || (isset($node->name) && !is_string($node->name))) {

            $leading = [];

            $property = property_exists($node, 'selector') ? 'selector' : 'name';

            if (strpos($node->{$property}, '/*') === false) {

                return $node;
            }

            if (!is_object($node->{$property})) {

                $node->{$property} = Value::parse($node->{$property});
            }

            $node->{$property}->filter(function (Value $value) use(&$leading) {

                if ($value->type == 'Comment') {

                    $leading[] = $value;
                    return false;
                }

                return true;
            });

            if (!empty($leading)) {

                $node->{$property} = Value::parse(trim($node->{$property}));
                $node->leadingcomments = $leading;
            }
        }

        return $node;
    }

    /**
     * @param string $rule
     * @param stdClass $position
     * @return false|stdClass
     * @ignore
     */
    protected function parseRule($rule, $position)
    {

        $selector = rtrim($rule, "{\n\t\r ");

        if (trim($selector) === '') {

            return false;
        }

        $currentPosition = clone $position;
        $this->update($position, $rule);
        $position->column--;
        $position->index += $this->ast->location->start->index + strlen($rule);

        return  $this->doParseComments((object)[

            'type' => 'Rule',
            'location' => (object)[

                'start' => $currentPosition,
                'end' => $position
            ],
            'selector' => $selector
        ]);
    }

    /**
     * @param stdClass $rule
     * @param string $block
     * @param stdClass $position
     * @return stdClass
     * @ignore
     */
    protected function parseDeclarations($rule, $block, $position)
    {


        $j = strlen($block) - 1;
        $i = -1;

        do {

            while (++$i < $j) {

                if (!static::is_whitespace($block[$i])) {

                    break;
                } else {

                    $this->update($position, $block[$i]);
                    $position->index++;
                }
            }

            $statement = static::substr($block, $i, $j, [';', '}']);

            if ($statement === '') {

                break;
            }

            if (in_array(trim($statement), [';', '}'])) {

                $this->update($position, $statement);
                $position->index += strlen($statement);

                $i += strlen($statement);
                continue;
            }

            if (substr($block, $i, 2) == '/*') {

                $comment = static::match_comment($block, $i, $j);

                if ($comment == false) {

                    $comment = substr($block, $i);
                }

                $currentPosition = clone $position;

                $this->update($position, $comment);
                $position->index += strlen($comment);

                $rule->children[] = (object)[

                    'type' => 'Comment',
                    'location' => (object)[
                        'start' => (object)[

                            'line' => $currentPosition->line,
                            'column' => $currentPosition->column,
                            'index' => $this->ast->location->start->index + $currentPosition->index
                        ],
                        'end' => (object)[

                            'line' => $position->line,
                            'column' => $position->column - 1,
                            'index' => $this->ast->location->start->index + $position->index
                        ]
                    ],
                    'value' => $comment
                ];

                $i += strlen($comment) - 1;
                continue;
            }

            $currentPosition = clone $position;
            $this->update($position, $statement);
            $position->index += strlen($statement);


            $i += strlen($statement) - 1;

            if (trim($statement) == '') {

                continue;
            }

            $declaration = explode(':', $statement, 2);

            if (count($declaration) != 2) {

                $this->errors[] = new Exception(sprintf('cannot parse declaration at %s:%s ', $currentPosition->line, $currentPosition->column));
            } else {

                $value = rtrim($statement, "\n\r\t ;}");
                $endPosition = clone $currentPosition;
                $this->update($endPosition, $value);
                $endPosition->index += strlen($value);

                $declaration = (object)array_merge(
                    [
                        'type' => 'Declaration',
                        'location' => (object)[
                            'start' => (object)[

                                'line' => $currentPosition->line,
                                'column' => $currentPosition->column,
                                'index' => $currentPosition->index
                            ],
                            'end' => (object)[

                                'line' => $endPosition->line,
                                'column' => $endPosition->column - 1,
                                'index' => $currentPosition->index + strlen($value)
                            ]
                        ]
                    ],
                    $this->parseVendor(trim($declaration[0])),
                    [
                        'value' => rtrim($declaration[1], "\n\r\t ;}")
                    ]);

                $declaration->name = trim($declaration->name);
                $declaration->value = trim($declaration->value);

                $declaration = $this->doParseComments($declaration);
                $declaration->name = trim($declaration->name);

                $rule->children[] = $declaration;
            }

        } while ($i < $j);

        return $rule;
    }

    /**
     * @param $str
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
     * return parse errors
     * @return Exception[]
     */
    public function getErrors()
    {

        return $this->errors;
    }

    public function __toString() {

        if (!isset($this->ast)) {

            $this->getAst();
        }

        if (isset($this->ast)) {

            return (new Renderer())->renderAst($this->ast);
        }

        return '';
    }
}