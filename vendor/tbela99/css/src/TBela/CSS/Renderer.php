<?php

namespace TBela\CSS;

use Exception;
use TBela\CSS\Element\Rule;
use TBela\CSS\Element\AtRule;
use TBela\CSS\Event\EventInterface;
use TBela\CSS\Event\EventTrait;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Property\PropertyList;
use TBela\CSS\Value\Set;
use function is_string;

/**
 * Css node Renderer
 * @package TBela\CSS
 */
class Renderer implements EventInterface
{

    use EventTrait;

    const REMOVE_NODE = 1;

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

    /**
     * Identity constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {

        $this->setOptions($options);
    }

    /**
     * render an Element or a Property
     * @param RenderableInterface $element the element to render
     * @param null|int $level indention level
     * @param bool $parent render parent
     * @return string
     * @throws Exception
     */
    public function render(RenderableInterface $element, $level = null, $parent = false)
    {

        if (!empty($this->events['traverse'])) {

            foreach ($this->emit('traverse', $element, $level) as $result) {

                if ($result === static::REMOVE_NODE) {

                    return '';
                }

                if (is_string($result)) {

                    return $result;
                }

                if ($result instanceof RenderableInterface) {

                    $element = $result;
                    break;
                }
            }
        }

        if ($parent && ($element instanceof Element) && !is_null($element['parent'])) {

            return $this->render($element->copy()->getRoot(), $level);
        }

        $indent = str_repeat($this->options['indent'], (int)$level);

        switch ($element->getType()) {

            case 'Comment':

                if ($this->options['remove_comments']) {

                    return '';
                }

                return (is_null($level) ? '' : $indent.$this->options['indent']) . $element['value'];

            case 'Stylesheet':

                return $this->renderCollection($element, $level);

            case 'Declaration':
            case 'Property':

                return $indent . $this->options['indent'] . $this->renderProperty($element);

            case 'Rule':

                return $this->renderRule($element, $level, $indent);

            case 'AtRule':

                return $this->renderAtRule($element, $level, $indent);

            default:

                throw new Exception('Type not supported ' . $element->getType());
        }

        return '';
    }

    /**
     * render a rule
     * @param Rule $element
     * @param int $level
     * @param string $indent
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderRule(Rule $element, $level, $indent)
    {

        $selector = $element->getSelector();

        if (empty($selector)) {

            throw new Exception('The selector cannot be empty');
        }

        $output = $this->renderCollection($element, is_null($level) ? 0 : $level + 1);

        if ($output === '' && $this->options['remove_empty_nodes']) {

            return '';
        }

        $result = $indent . implode(',' . $this->options['glue'] . $indent, $selector);

        if (!$this->options['remove_comments']) {

            $comments = $element->getLeadingComments();

            if (!empty($comments)) {

                $result .= ($this->options['compress'] ? '' : ' ').implode(' ', $comments);
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
     * @param AtRule $element
     * @param int $level
     * @param string $indent
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderAtRule(AtRule $element, $level, $indent)
    {

        if ($element['name'] == 'charset' && !$this->options['charset']) {

            return '';
        }

        $output = '@' . $this->renderName($element);
            $value = $this->renderValue($element);

        if ($value !== '') {

            if ($this->options['compress'] && $value[0] == '(') {

                $output .= $value;
            }

            else {

                $output .= rtrim($this->options['separator'] . $value);
            }
        }

        if ($element->isLeaf()) {

            return $indent . $output . ';';
        }

        $elements = $this->renderCollection($element, $level + 1);

        if ($elements === '' && $this->options['remove_empty_nodes']) {

            return '';
        }

        return $indent . $output . $this->options['indent'] . '{' . $this->options['glue'] . $elements . $this->options['glue'] . $indent . '}';
    }

    /**
     * render a property
     * @param RenderableInterface $element
     * @return string
     * @ignore
     */

    protected function renderProperty(RenderableInterface $element)
    {
        $name = $this->renderName($element);
        $value = $element->getValue();

        $options = [
            'compress' => $this->options['compress'],
            'css_level' => $this->options['css_level'],
            'convert_color' => $this->options['convert_color'] === true ? 'hex' : $this->options['convert_color']];

        if (empty($this->options['compress'])) {

            $value = implode(', ',  array_map(function (Set $value) use($options) {

                return $value->render($options);

            }, $value->split(',')));
        }

        else {

            $value = $value->render($options);
        }

        //->render();

        if ($value == 'none' && in_array($name, ['border', 'border-top', 'border-right', 'border-left', 'border-bottom', 'outline'])) {

            $value = 0;
        }

        if(!$this->options['remove_comments']) {

            $comments = $element->getTrailingComments();

            if (!empty($comments)) {

                $value .= ' '.implode(' ', $comments);
            }
        }

        return trim($name).':'.$this->options['indent'].trim($value);
    }

    /**
     * render a name
     * @param RenderableInterface $element
     * @return string
     * @ignore
     */
    protected function renderName(RenderableInterface $element)
    {

        $result = $element->getName();

        if (!$this->options['remove_comments']) {

            $comments = $element->getLeadingComments();

            if (!empty($comments)) {

                $result.= ' '.implode(' ', $comments);
            }
        }

        return $result;
    }

    /**
     * render a value
     * @param Element $element
     * @return string
     * @return string
     * @ignore
     */
    protected function renderValue(Element $element)
    {
        $result = $element->getValue();

        if (!($result instanceof Set)) {

            $result = Value::parse($result, $element['name']);
            $element->setValue($result);
        }

        $result = $result->render($this->options);

        if (!$this->options['remove_comments']) {

            $trailingComments = $element['trailingcomments'];

            if (!empty($trailingComments)) {

                $result .= ($this->options['compress'] ? '' : ' ').implode(' ', $trailingComments);
            }
        }

        return $result;
    }

    /**
     * render a list
     * @param RuleList $element
     * @param int|null $level
     * @return string
     * @throws Exception
     * @ignore
     */
    protected function renderCollection(RuleList $element, $level)
    {

        $glue = '';
        $type = $element->getType();
        $count = 0;

        if (($this->options['compute_shorthand'] || !$this->options['allow_duplicate_declarations']) && ($type == 'Rule' || ($type == 'AtRule' && $element->hasDeclarations()))) {

            $glue = ';';
            $children = new PropertyList($element, $this->options);
        } else {

            $children = $element->getChildren();
        }

        $result = [];

        foreach ($children as $el) {

            $output = $this->render($el, $level);

            if (trim($output) === '') {

                    continue;

            } else if ($el->getType() != 'Comment') {

                if ($count == 0) {

                    $count++;
                }
            }

            if ($el->getType() != 'Comment') {

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

//        $hash = [];
//
//        $i = count($result);

        // remove identical rules
//        while ($i--) {
//
//            if (!isset($hash[$result[$i]])) {
//
//                $hash[$result[$i]] = 1;
//            } else {
//
//                array_splice($result, $i, 1);
//            }
//        }

        return rtrim(implode($this->options['glue'], $result), $glue . $this->options['glue']);
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

        //    $this->options['convert_color'] = false;
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
