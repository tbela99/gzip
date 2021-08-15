<?php

namespace TBela\CSS;

use axy\sourcemap\SourceMap;
use Exception;
use TBela\CSS\Exceptions\IOException;
use TBela\CSS\Interfaces\ParsableInterface;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Parser\Helper;
use TBela\CSS\Property\PropertyList;
use TBela\CSS\Value\Set;
use function is_string;

/**
 * Css node Renderer
 * @package TBela\CSS
 */
class Renderer
{

    protected $options = [
        'glue' => "\n",
        'indent' => ' ',
        'css_level' => 4,
        'separator' => ' ',
        'charset' => false,
        'compress' => false,
        'sourcemap' => false,
        'convert_color' => false,
        'remove_comments' => false,
        'preserve_license' => false,
        'compute_shorthand' => true,
        'remove_empty_nodes' => false,
        'allow_duplicate_declarations' => false
    ];

    /**
     * @var string
     * @ignore
     */
    protected $outFile = '';

    protected $indents = [];

    /**
     * Identity constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {

        $this->setOptions($options);
    }

    /**
     * render an ElementInterface or a Property
     * @param RenderableInterface $element the element to render
     * @param null|int $level indention level
     * @param bool $parent render parent
     * @return string
     * @throws Exception
     */
    public function render(RenderableInterface $element, $level = null, $parent = false)
    {

        if ($parent && ($element instanceof ElementInterface) && !is_null($element['parent'])) {

            return $this->render($element->copy()->getRoot(), $level);
        }

        return $this->renderAst($element->getAst(), $level);
    }

    /**
     * @param \stdClass|ParsableInterface $ast
     * @param int|null $level
     * @return string
     * @throws Exception
     */
    public function renderAst($ast, $level = null)
    {

        $this->outFile = '';

        if ($ast instanceof ParsableInterface) {

            $ast = $ast->getAst();
        }

        switch ($ast->type) {

            case 'Stylesheet':

//                return $this->renderCollection($ast, $level);

            case 'Comment':
            case 'Declaration':
            case 'Property':
            case 'Rule':
            case 'AtRule':

                return $this->{'render' . $ast->type}($ast, $level);

            default:

                throw new Exception('Type not supported ' . $ast->type);
        }

        return '';
    }

    /**
     * @param ParsableInterface|\stdClass $ast
     * @param string $file
     * @return Renderer
     * @throws IOException
     */
    public function save($ast, $file)
    {

        if ($ast instanceof ParsableInterface) {

            $ast = $ast->getAst();
        }

        $data = (object)[
            'sourcemap' => new SourceMap(),
                'position' => (object)[
                'line' => 0,
                'column' => 0
            ]
        ];

        $this->outFile = Helper::absolutePath($file, Helper::getCurrentDirectory());

        $result = $this->walk($ast, $data);
        $map = $file.'.map';

        $json = $data->sourcemap->getData();
//        $json['mappings'] = preg_replace('#;+#', ';', $json['mappings']);

        if (file_put_contents($map, json_encode($json)) === false) {

            throw new IOException("cannot write map into $map", 500);
        }

        if (file_put_contents($file, $result->css."\n/*# sourceMappingURL=".Helper::relativePath($map, dirname($file))." */") === false) {

            throw new IOException("cannot write output into $file", 500);
        }

        return $this;
    }

    /**
     * @param \stdClass $ast
     * @param \stdClass $data\
     * @param int|null $level
     * @return object|null
     * @throws Exception
     * @ignore
     */
    protected function walk($ast, $data, $level = null)
    {

        $result = [

            'css' => '',
            'type' => $ast->type,
        ];

        // rule
        switch ($ast->type) {

            case 'Rule':
            case 'AtRule':
            case 'Stylesheet':

                if ($ast->type == 'AtRule'&& $ast->name == 'media' && $ast->value == 'all') {

                    // render children
                    $css = '';
                    $d = clone $data;
                    $d->position = clone $d->position;

                    foreach ($ast->children as $c) {

                        $r = $this->walk($c, $d, $level);

                        if (is_null($r)) {

                            continue;
                        }

                        $p = $r->css.$this->options['glue'];
                        $css .= $p;

                        $this->update($d->position, $p);
                    }

                    $result['css'] = $css;
                    break;
                }

                $type = $ast->type;
                $map = [];

                if ($type == 'Stylesheet') {

                    $ast->css = '';

                    foreach ($ast->children as $c) {

                        $d = clone $data;
                        $d->position = clone $d->position;

                        $child = $this->walk($c, $d);

                        if (is_null($child) || $child->css === '') {

                            continue;
                        }

                        $css = $child->css . $this->options['glue'];
                        $this->update($data->position, $css);

                        $ast->css .= $css;
                    }

                    $result['css'] = rtrim($ast->css);
                } else {

                    if ($type == 'Rule' || ($type == 'AtRule' && isset($ast->children))) {

                        if (!isset($this->indents[$level])) {

                            $this->indents[$level] = str_repeat($this->options['indent'], $level);
                        }

                        $children = isset($ast->children) ? $ast->children : [];

                        if (empty($children) && $this->options['remove_empty_nodes']) {

                            return null;
                        }

                        if ($this->options['compute_shorthand'] || !$this->options['allow_duplicate_declarations']) {

                            $children = new PropertyList(null, $this->options);

                            foreach ((isset($ast->children) ? $ast->children : []) as $child) {

                                if(isset($child->name)) {

                                    $map[$child->name] = $child;
                                }

                                else {

                                    $map[] = $child;
                                }

                                $children->set(isset($child->name) ? $child->name : null, $child->value, $child->type, isset($child->leadingcomments) ? $child->leadingcomments : null, isset($child->trailingcomments) ? $child->trailingcomments : null, isset($child->src) ? $child->src : null);
                            }

                            if ($children->isEmpty() && $this->options['remove_empty_nodes']) {

                                return null;
                            }
                        }
                    }

                    if (!is_null($level)) {

                        $this->update($data->position, $this->indents[$level]);
                    }

                    $this->addPosition($data, $ast);


                    if ($type == 'Rule') {

                        $result['css'] .= $this->renderSelector($ast, $level) . $this->options['indent'] . '{' .
                            $this->options['glue'];

                        $this->update($data->position, substr($result['css'], $level));
                    }

                    else {

                        $media = $this->renderAtRuleMedia($ast, $level);

                        if ($media === '') {

                            return null;
                        }

                        $result['css'] = $media;

                        if (!empty($ast->isLeaf)) {

                            $this->update($data->position, substr($result['css'], $level));
                            break;
                        }

                        $result['css'] .= $this->options['indent'] . '{' . $this->options['glue'];
                        $this->update($data->position, substr($result['css'], $level));
                    }

                    $res = [];

                    foreach ($children as $child) {

                        $declaration = $this->{'render' . $child->type}($child, $level + 1);

                        if ($declaration === '') {

                            continue;
                        }

                        if (isset($res[$declaration])) {

                            unset($res[$declaration]);
                        }

                        $v = isset($child->name) ? $child->name : null;
                        $res[$declaration] = [$declaration, isset($map[$v]) ? $map[$v] : $child];
                    }

                    $css = '';
                    $d = clone $data;
                    $d->position = clone $d->position;
                    $glue = ';' . $this->options['glue'];

                    foreach ($res as $r) {

                        $this->update($d->position, $this->indents[$level + 1]);

                        if (!is_null(isset($r[1]->position) ? $r[1]->position : (isset($r[1]->location->start) ? $r[1]->location->start : null)) && in_array($r[1]->type, ['AtRule', 'Rule'])) {

                            $this->addPosition($d, $r[1]);
                        }

                        $text = $r[0] . ($r[1]->type == 'Comment' ? $this->options['glue'] : $glue);
                        $this->update($d->position, substr($text, $level + 1));
                        $css .= $text;
                    }

                    $result['css'] .= rtrim($css, $glue) . $this->options['glue'] . $this->indents[$level] . '}';
                }

                break;

            case 'Comment':
//            case 'Property':
//            case 'Declaration':

                $css = $this->{'render' . $ast->type}($ast, $level);

                if ($css === '') {

                    return null;
                }

                if (!isset($this->indents[$level])) {

                    $this->indents[$level] = str_repeat($this->options['indent'], $level);
                }

                $c = clone $data;
                $c->position = clone $c->position;
                $this->update($c->position, $this->indents[$level]);
//                $this->addPosition($data, substr($css, $level));

                $result['css'] = $css;
                break;

            default:

                throw new Exception('Type not supported ' . $ast->type);
        }

        return (object)$result;
    }

    /**
     * @param \stdClass $position
     * @param string $string
     * @return \stdClass
     * @ignore
     */
    protected function update($position, $string)
    {

        $j = strlen($string);

        for ($i = 0; $i < $j; $i++) {

            if ($string[$i] == "\n") {

                $position->line++;
                $position->column = 0;
            } else {

                $position->column++;
            }
        }

        return $position;
    }

    /**
     * @param \stdClass $data
     * @param \stdClass $ast
     * @ignore
     */
    protected function addPosition($data, $ast)
    {

        if (empty($ast->src)) {

            return;
        }

        $position = isset($ast->location->start) ? $ast->location->start : (isset($ast->position) ? $ast->position : null);

        if (is_null($position)) {

            return;
        }

        $data->sourcemap->addPosition([
            'generated' => [
                'line' => $data->position->line,
                'column' => $data->position->column,
            ],
            'source' => [
                'fileName' => $ast->src,
                'line' => $position->line - 1,
                'column' => $position->column - 1,
            ],
        ]);
    }

    /**
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @ignore
     */
    protected function renderStylesheet($ast, $level)
    {

        return $this->renderCollection($ast, $level);
    }

    /**
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @ignore
     */
    protected function renderComment($ast, $level)
    {

        if ($this->options['remove_comments']) {

            if (!$this->options['preserve_license'] || substr($ast->value, 0, 3) != '/*!') {

                return '';
            }
        }

        settype($level, 'int');

        if (!isset($this->indents[$level])) {

            $this->indents[$level] = str_repeat($this->options['indent'], $level);
        }

        return $this->indents[$level] . $ast->value;
    }

    /**
     * render a rule
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderSelector($ast, $level)
    {

        $selector = $ast->selector;

        if (!isset($selector)) {

            throw new Exception('The selector cannot be empty');
        }

        settype($level, 'int');

        if (!isset($this->indents[$level])) {

            $this->indents[$level] = str_repeat($this->options['indent'], $level);
        }

        $indent = $this->indents[$level];

        $result = $indent;
        $join = ',' . $this->options['glue'] . $indent;

        if (is_string($selector) && preg_match('#[,\s"\']|(\b0)#', $selector)) {

            $selector = array_map(function (Set $set) {

                return $set->render($this->options);
            }, Value::parse($selector)->split(','));
        }

        if (is_array($selector)) {

            foreach ($selector as $sel) {

                $result .= $sel . $join;
            }
        } else {

            $result .= $selector;
        }

        $result = rtrim($result, $join);

        if (!$this->options['remove_comments'] && !empty($ast->leadingcomments)) {

            $comments = $ast->leadingcomments;

            if (!empty($comments)) {

                $join = $this->options['compress'] ? '' : ' ';

                foreach ($comments as $comment) {

                    $result .= $join . $comment;
                }
            }
        }

        return $result;
    }

    /**
     * render a rule
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderRule($ast, $level)
    {

        settype($level, 'int');
        $result = $this->renderSelector($ast, $level);
        $output = $this->renderCollection($ast, $level + 1);

        if ($output === '' && $this->options['remove_empty_nodes']) {

            return '';
        }

        return $result . $this->options['indent'] . '{' .
            $this->options['glue'] .
            $output . $this->options['glue'] .
            $this->indents[$level] .
            '}';
    }

    /**

     * render a rule
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderAtRuleMedia($ast, $level) {

        if ($ast->name == 'charset' && !$this->options['charset']) {

            return '';
        }

        $output = '@' . $this->renderName($ast);
        $value = $this->renderValue($ast);

        if ($value !== '') {

            if ($this->options['compress'] && $value[0] == '(') {

                $output .= $value;
            } else {

                $output .= rtrim($this->options['separator'] . $value);
            }
        }

        settype($level, 'int');

        if (!isset($this->indents[$level])) {

            $this->indents[$level] = str_repeat($this->options['indent'], $level);
        }

        $indent = $this->indents[$level];

        if (!empty($ast->isLeaf)) {

            return $indent . $output . ';';
        }

        return $indent . $output;
    }

    /**
     * render at-rule
     * @param \stdClass $ast
     * @param ?int $level
     * @return string
     * @ignore
     */
    protected function renderAtRule($ast, $level)
    {

        settype($level, 'int');

        $media = $this->renderAtRuleMedia($ast, $level);

        if ($media === '' || !empty($ast->isLeaf)) {

            return $media;
        }

        if ($ast->name == 'media' && $ast->value == 'all') {

            $css = '';
            
            foreach ($ast->children as $child) {

                $r = $this->{'render'.$child->type}($child, $level);

                if ($r === '') {

                    continue;
                }

                $css .= $r.$this->options['glue'];
            }

            return rtrim($css);
        }

        $elements = $this->renderCollection($ast, $level + 1);

        if ($elements === '' && $this->options['remove_empty_nodes']) {

            return '';
        }

        return $media . $this->options['indent'] . '{' . $this->options['glue'] . $elements . $this->options['glue'] . $this->indents[$level] . '}';
    }

    /**
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @ignore
     */
    protected function renderDeclaration($ast, $level)
    {

        return $this->renderProperty($ast, $level);
    }

    /**
     * render a property
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @ignore
     */

    protected function renderProperty($ast, $level)
    {
        if ($ast->type == 'Comment') {

            return empty($this->options['compress']) ? '' : $ast->value;
        }

        $name = $this->renderName($ast);
        $value = $ast->value;

        $options = [
            'compress' => $this->options['compress'],
            'css_level' => $this->options['css_level'],
            'convert_color' => $this->options['convert_color'] === true ? 'hex' : $this->options['convert_color']
        ];
//
//        if (is_string($value)) {
//
//            if (!isset($this->indents[$level])) {
//
//                $this->indents[$level] = str_repeat($this->options['indent'], (int)$level);
//            }
//
//            return $this->indents[$level] . $name . ':' . $this->options['indent'] . $value;
//        }

        if (is_string($value)) {

            $value = Value::parse($value, $name);
        }

        if (empty($this->options['compress'])) {

            if (is_string($value)) {

                $value = Value::parse($value, $name);
            }

            $value = implode(', ', array_map(function (Set $value) use ($options) {

                return $value->render($options);

            }, $value->split(',')));
        } else {

            $value = $value->render($options);
        }

        if ($value == 'none') {

            if (in_array($name, ['border', 'border-top', 'border-right', 'border-left', 'border-bottom', 'outline'])) {

                $value = 0;
            }
        }

        else if (in_array($name, ['background', 'background-image', 'src'])) {

                $value = preg_replace_callback('#(^|\s)url\(\s*(["\']?)([^)\\2]+)\\2\)#', function ($matches) {

                    if (strpos($matches[3], 'data:') !== false) {

                        return $matches[0];
                    }

                    return $matches[1].'url('.Helper::relativePath($matches[3], $this->outFile === '' ? Helper::getCurrentDirectory() : dirname($this->outFile)).')';
                }, $value);
            }

        if (!$this->options['remove_comments'] && !empty($ast->trailingcomments)) {

            $comments = $ast->trailingcomments;

            if (!empty($comments)) {

                foreach ($comments as $comment) {

                    $value .= ' ' . $comment;
                }
            }
        }

        settype($level, 'int');

        if (!isset($this->indents[$level])) {

            $this->indents[$level] = str_repeat($this->options['indent'], $level);
        }

        return $this->indents[$level] . trim($name) . ':' . $this->options['indent'] . trim($value);
    }

    /**
     * render a name
     * @param \stdClass $ast
     * @return string
     * @ignore
     */
    protected function renderName($ast)
    {

        $result = $ast->name;

        if (!$this->options['remove_comments'] && !empty($ast->leadingcomments)) {

            $comments = $ast->leadingcomments;

            if (!empty($comments)) {

                foreach ($comments as $comment) {

                    $result .= ' ' . $comment;
                }
            }
        }

        return $result;
    }

    /**
     * render a value
     * @param \stdClass $ast
     * @return string
     * @ignore
     */
    protected function renderValue($ast)
    {
        $result = $ast->value;

        if (!($result instanceof Set)) {

            $result = Value::parse($result, $ast->name);
            $ast->value = $result;
        }

        $result = $result->render($this->options);

        if (!$this->options['remove_comments'] && !empty($ast->trailingcomments)) {

            $trailingComments = $ast->trailingcomments;
        }

        if (!empty($trailingComments)) {

            $glue = $this->options['compress'] ? '' : ' ';

            foreach ($trailingComments as $comment) {

                $result .= $glue . $comment;
            }
        }

        return $result;
    }

    /**
     * render a list
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     * @ignore
     */

    protected function renderCollection($ast, $level)
    {

        $type = $ast->type;
        $glue = ($type == 'Rule' || ($type == 'AtRule' && !empty($ast->hasDeclarations))) ? ';' : '';
        $count = 0;

        if (($this->options['compute_shorthand'] || !$this->options['allow_duplicate_declarations']) && $glue == ';') {

            $children = new PropertyList(null, $this->options);

            if (isset($ast->children)) {

                foreach ($ast->children as $child) {

                    $children->set(isset($child->name) ? $child->name : null, $child->value, $child->type, isset($child->leadingcomments) ? $child->leadingcomments : null, isset($child->trailingcomments) ? $child->trailingcomments : null);
                }
            }
        } else {

            $children = isset($ast->children) ? $ast->children : [];
        }

        $result = [];
        settype($level, 'int');

        foreach ($children as $el) {

            if (!($el instanceof \stdClass)) {

                $el = $el->getAst();
            }

            $output = $this->{'render' . $el->type}($el, $level);

            if (trim($output) === '') {

                continue;

            } else if ($el->type != 'Comment') {

                if ($count == 0) {

                    $count++;
                }
            }

            if ($el->type != 'Comment') {

                $output .= $glue;
            }

            if (isset($result[$output])) {

                unset($result[$output]);
            }

            $result[$output] = $output;
        }

        if ($this->options['remove_empty_nodes'] && $count == 0) {

            return '';
        }

        $join = $this->options['glue'];
        $output = '';

        foreach ($result as $res) {

            $output .= $res . $join;
        }

        return rtrim($output, $glue . $this->options['glue']);
    }

    /**
     * Set output formatting
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {

        if (!empty($options['compress'])) {

            $this->options['glue'] = '';
            $this->options['indent'] = '';

            if (!$this->options['convert_color']) {

                $this->options['convert_color'] = 'hex';
            }

            $this->options['charset'] = false;
            $this->options['remove_comments'] = true;
            $this->options['remove_empty_nodes'] = true;
        } else {

            $this->options['glue'] = "\n";
            $this->options['indent'] = ' ';
        }

        foreach ($options as $key => $value) {

            if (array_key_exists($key, $this->options)) {

                $this->options[$key] = $value;
            }
        }

        if ($this->options['convert_color'] === true) {

            $this->options['convert_color'] = 'hex';
        }

        if (isset($options['allow_duplicate_declarations'])) {

            $this->options['allow_duplicate_declarations'] = is_string($options['allow_duplicate_declarations']) ? [$options['allow_duplicate_declarations']] : $options['allow_duplicate_declarations'];
        }

        $this->indents = [];

        return $this;
    }

    /**
     * return the options
     * @param string|null $name
     * @param mixed $default return value
     * @return array
     */
    public function getOptions($name = null, $default = null)
    {

        if (is_null($name)) {

            return $this->options;
        }

        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }
}
