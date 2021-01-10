<?php

namespace TBela\CSS;

use Exception;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Property\PropertyList;
use TBela\CSS\Value\Set;
use function is_string;

/**
 * Css node Renderer
 * @package TBela\CSS
 */
class Renderer
{
    /**
     * @var Traverser
     */
    protected $traverser = null;

    /**
     * @var array
     */
    protected $options = [
        'compress' => false,
        'css_level' => 4,
        'indent' => ' ',
        'glue' => "\n",
        'separator' => ' ',
        'charset' => false,
        'convert_color' => false,
        'remove_comments' => false,
        'compute_shorthand' => true,
        'remove_empty_nodes' => false,
        'allow_duplicate_declarations' => false
    ];

    protected $indents = [];
    protected $events = [];

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

        if (isset($this->traverser)) {

            $result = $this->traverser->traverse($element);

            if ($result instanceof ElementInterface) {

                $element = $result;
            }
        }

        return $this->renderAst($element->getAst(), $level);
    }

        public function renderAst($ast, $level = null)
    {

        switch ($ast->type) {

            case 'Stylesheet':

                return $this->renderCollection($ast, $level);

            case 'Comment':
            case 'Declaration':
            case 'Property':
            case 'Rule':
            case 'AtRule':

                return $this->{'render'.$ast->type}($ast, $level);

            default:

                throw new Exception('Type not supported ' . $ast->type);
        }

        return '';
    }

    /**
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     */

    protected function renderComment($ast, $level) {

        if ($this->options['remove_comments']) {

            return '';
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
    protected function renderRule($ast, $level)
    {

        $selector = $ast->selector;

        if (!isset($selector)) {

            throw new Exception('The selector cannot be empty');
        }

        $output = $this->renderCollection($ast, $level + 1);

        if ($output === '' && $this->options['remove_empty_nodes']) {

            return '';
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

                $result .= $sel.$join;
            }
        }

        else {

            $result .= $selector;
        }

        $result = rtrim($result, $join);

        if (!$this->options['remove_comments'] && !empty($ast->leadingcomments)) {

            $comments = $ast->leadingcomments;

            if (!empty($comments)) {

                $join = $this->options['compress'] ? '' : ' ';

                foreach ($comments as $comment) {

                    $result .= $join.$comment;
                }
            }
        }

        return $result . $this->options['indent'] . '{' .
            $this->options['glue'] .
            $output . $this->options['glue'] .
            $indent .
        '}';
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

        if ($ast->name == 'charset' && !$this->options['charset']) {

            return '';
        }

        $output = '@'. $this->renderName($ast);
        $value = $this->renderValue($ast);

        if ($value !== '') {

            if ($this->options['compress'] && $value[0] == '(') {

                $output .= $value;
            }

            else {

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

        $elements = $this->renderCollection($ast, $level + 1);

        if ($elements === '' && $this->options['remove_empty_nodes']) {

            return '';
        }

        return $indent . $output . $this->options['indent'] . '{' . $this->options['glue'] . $elements . $this->options['glue'] . $indent . '}';
    }

    /**
     * @param \stdClass $ast
     * @param int|null $level
     * @return string
     */
    protected function renderDeclaration($ast, $level) {

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
            'convert_color' => $this->options['convert_color'] === true ? 'hex' : $this->options['convert_color']];

        if (is_string($value)) {

            return $name.':'.$this->options['indent'].$value;
//            $value = Value::parse($value, $ast->name);
        }

        if (empty($this->options['compress'])) {

            $value = implode(', ',  array_map(function (Set $value) use($options) {

                return $value->render($options);

            }, $value->split(',')));
        }

        else {

            $value = $value->render($options);
        }

        if ($value == 'none' && in_array($name, ['border', 'border-top', 'border-right', 'border-left', 'border-bottom', 'outline'])) {

            $value = 0;
        }

        if(!$this->options['remove_comments'] && !empty($ast->trailingcomments)) {

            $comments = $ast->trailingcomments;

            if (!empty($comments)) {

                foreach ($comments as $comment) {

                    $value .= ' '.$comment;
                }
            }
        }

        settype($level, 'int');

        if (!isset($this->indents[$level])) {

            $this->indents[$level] = str_repeat($this->options['indent'], $level);
        }

        return $this->indents[$level].trim($name).':'.$this->options['indent'].trim($value);
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

                    $result .= ' '.$comment;
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

                $result .= $glue.$comment;
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

        $glue = '';
        $type = $ast->type;
        $count = 0;

        if (($this->options['compute_shorthand'] || !$this->options['allow_duplicate_declarations']) && ($type == 'Rule' || ($type == 'AtRule' && !empty($ast->hasDeclarations)))) {

            $glue = ';';
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

            $output = $this->{'render'.$el->type}($el, $level);

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

            $output .= $res.$join;
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

    public function on($type, $callable) {

        if (is_null($this->traverser)) {

            $this->traverser = new Traverser();
        }

        $this->traverser->on($type == 'traverse' ? 'enter' : $type, $callable);

        return $this;
    }

    public function off($type, $callable) {

        if (isset($this->traverser)) {

            $this->traverser->off($type == 'traverse' ? 'enter' : 'traverse', $callable);
        }

        return $this;
    }
}
