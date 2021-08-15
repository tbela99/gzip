<?php

namespace TBela\CSS;

use Exception;
use InvalidArgumentException;
use stdClass;
use TBela\CSS\Interfaces\ElementInterface;
use TBela\CSS\Interfaces\RenderableInterface;
use TBela\CSS\Interfaces\RuleListInterface;
use TBela\CSS\Query\Evaluator;
use TBela\CSS\Value\Set;
use function is_callable;
use function is_null;
use function str_ireplace;

/**
 * Css node base class
 * @package TBela\CSS
 */
abstract class Element implements ElementInterface  {

    use ArrayTrait;

    /**
     * @var stdClass|null
     * @ignore
     */
    protected $ast = null;
    /**
     * @ignore
     */
    protected $parent = null;

    /**
     * Element constructor.
     * @param object|null $ast
     * @param RuleListInterface|null $parent
     * @throws Exception
     */
    public function __construct($ast = null, $parent = null) {

        assert(is_null($parent) || $parent instanceof RuleListInterface);

        $this->ast = (object) ['type' => str_ireplace(Element::class.'\\', '', static::class)];

        if (!is_null($ast)) {

            if (isset($ast->location->start)) {

                $ast->position = $ast->location->start;
            }

            foreach ($ast as $key => $value) {

                if (is_callable([$this, 'set'.$key])) {

                    $this->{'set'.$key}($value);
                }
                else if (is_callable([$this, 'get'.$key]) || is_callable([$this, $key])) {

                    $this->ast->{$key} = $value;
                }
            }
        }

        if (!is_null($parent)) {

            $parent->append($this);
        }
    }

    /**
     * @param $location
     * @return SourceLocation
     */

    protected function createLocation ($location) {

        return SourceLocation::getInstance($location);
    }

    /**    * @inheritDoc
     */
    public static function getInstance($ast) {

        $type = '';

        if ($ast instanceof Element) {

            return clone $ast;
        }

        if (isset($ast->type)) {

            $type = $ast->type;
            unset($ast->parsingErrors);
        }

        if ($type === '') {

            throw new InvalidArgumentException('Invalid ast provided');
        }

        $className = Element::class.'\\'.ucfirst($ast->type);
        return new $className($ast);
    }

    /**
     * @inheritDoc
     */
    public static function from($css, array $options = [])
    {

        return (new Parser($css, $options))->parse();
    }

    /**
     * @inheritDoc
     */
    public static function fromUrl($url, array $options = [])
    {

        return (new Parser('', $options))->load($url)->parse();
    }

    /**
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> 8c86a81ac1d7c25cacb54574ff654b4493f5feb0
     * @inheritDoc
     */
    public function traverse(callable $fn, $event) {

        return (new Element\Traverser())->on($event, $fn)->traverse($this);
    }

    /**
<<<<<<< HEAD
=======
=======
>>>>>>> ae7546465ede1f52ef71c7afb34da035dda76552
>>>>>>> 8c86a81ac1d7c25cacb54574ff654b4493f5feb0
     *
     * @inheritDoc
     * @throws Parser\SyntaxError
     */

    public function query($query) {

        return (new Evaluator())->evaluate($query, $this);
    }

    /**
     *
     * @inheritDoc
     * @throws Parser\SyntaxError
     */
    public function queryByClassNames($query) {

        return (new Evaluator())->evaluateByClassName($query, $this);
    }

    /**
     * @inheritDoc
     */
    public function getRoot () {

        $element = $this;

        while ($parent = $element->parent) {

            $element = $parent;
        }

        return $element;
    }

    /**
     * @inheritDoc
     */
    public function getValue() {

        if (isset($this->ast->name) && !((isset($this->ast->value) ? $this->ast->value : '') instanceof Set)) {

            $this->ast->value = Value::parse(isset($this->ast->value) ? $this->ast->value : '', $this->ast->name);
        }

        return isset($this->ast->value) ? $this->ast->value : '';
    }

    /**
     * @inheritDoc
     */
    public function setValue ($value) {

        $this->ast->value = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getParent () {

        return $this->parent;
    }

    /**
     * @inheritDoc
     */
    public function getType() {

        return $this->ast->type;
    }

    /**
     * @inheritDoc
     */
    public function copy() {

        $parent = $this;
        $copy = $node = clone $this;

        while ($parent = $parent->parent) {

            $ast = clone $parent->ast;

            if (isset($ast->children)) {

                $ast->children = [];
            }

            $parentNode = Element::getInstance($ast);
            $parentNode->append($node);
            $node = $parentNode;
        }

        return $copy;
    }

    /**
     * @inheritDoc
     */
    public function getSrc() {

        return isset($this->ast->src) ? $this->ast->src : null;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() {

        return isset($this->ast->position) ? $this->ast->position : null;
    }

    /**
     * @inheritDoc
     */
    public function setTrailingComments($comments) {

        return $this->setComments($comments, 'trailing');
    }

    /**
     * @inheritDoc
     */
    public function getTrailingComments() {

        return isset($this->ast->trailingcomments) ? $this->ast->trailingcomments : null;
    }

    public function getLocation() {

        return isset($this->ast->location) ? $this->ast->location : null;
    }

    /**
     * @inheritDoc
     */
    public function deduplicate(array $options = ['allow_duplicate_rules' => ['font-face']])
    {

        if ((empty($options['allow_duplicate_rules']) ||
            $options['allow_duplicate_rules'] !== true ||
            empty($options['allow_duplicate_declarations']) || $options['allow_duplicate_declarations'] !== true)) {

            switch ($this->ast->type) {

                case 'AtRule':

                    return !empty($ast->hasDeclarations) ? $this->deduplicateDeclarations($options) : $this->deduplicateRules($options);

                case 'Stylesheet':

                    return $this->deduplicateRules($options);

                case 'Rule':

                    return $this->deduplicateDeclarations($options);
            }
        }

        return $this;
    }

    /**
     * compute signature
     * @return string
     * @ignore
     */
    protected function computeSignature()
    {

        $signature = ['type:' . $this->ast->type];

        $name = isset($this->ast->name) ? $this->ast->name : null;

        if (isset($name)) {

            $signature[] = 'name:' . $name;
        }

        $value = isset($this->ast->value) ? $this->ast->value : null;

        if (isset($value)) {

            $signature[] = 'value:' . $value;
        }

        $selector = isset($this->ast->selector) ? $this->ast->selector : null;

        if (isset($selector)) {

            $signature[] = 'selector:' . implode(',', $selector);
        }

        $vendor = isset($this->ast->vendor) ? $this->ast->vendor : null;

        if (isset($vendor)) {

            $signature[] = 'vendor:' . $vendor;
        }

        return implode(':', $signature);
    }

    /**
<<<<<<< HEAD
     * @param string[]|Value\Comment[]|null $comments
     * @return Element
     */
    protected function setComments($comments, $type) {

        if (empty($comments)) {

            unset($this->ast->{$type.'comments'});
            return $this;
        }

        $this->ast->{$type.'comments'} = array_map(function ($comment) {

            if (is_string($comment)) {

                return $comment;
            }

            if ((isset($comment->type) ? $comment->type : null) != 'Comment') {

                throw new InvalidArgumentException('Comment expected');
            }

            return $comment->value;

        }, $comments);

        return $this;
    }

    /**
=======
>>>>>>> 8c86a81ac1d7c25cacb54574ff654b4493f5feb0
     * @inheritDoc
     */
    public function setLeadingComments($comments) {

        return $this->setComments($comments, 'leading');
    }

    /**
     * @inheritDoc
     */
    public function getLeadingComments() {

        return isset($this->ast->leadingcomments) ? $this->ast->leadingcomments : null;
    }

    /**
     * merge duplicate rules
     * @param array $options
     * @return object
     * @ignore
     */
    protected function deduplicateRules(array $options = [])
    {
        if (!is_null(isset($this->ast->children) ? $this->ast->children : null)) {

            if (empty($options['allow_duplicate_rules']) ||
                is_array($options['allow_duplicate_rules'])) {

                $signature = '';
                $total = count($this->ast->children);
                $el = null;

                $allowed = is_array($options['allow_duplicate_rules']) ? $options['allow_duplicate_rules'] : [];

                while ($total--) {

                    if ($total > 0) {

                        /**
                         * @var Element $el
                         */
                        $el = $this->ast->children[$total];

                        if ((string) $el->ast->type == 'Comment') {

                            continue;
                        }

                        $next = $this->ast->children[$total - 1];

                        while ($total > 1 && (string) $next->ast->type == 'Comment') {

                            $next = $this->ast->children[--$total - 1];
                        }

                        if (!empty($allowed) &&
                            (
                                ($next->ast->type == 'AtRule' && in_array($next->ast->name, $allowed)) ||
                                ($next->ast->type == 'Rule' &&
                                    array_intersect($next->ast->selector, $allowed))
                            )
                        ) {

                            continue;
                        }

                        if ($signature === '') {

                            $signature = $el->computeSignature();
                        }

                        $nextSignature = $next->computeSignature();

                        while ($signature == $nextSignature) {

                            array_splice($this->ast->children, $total - 1, 1);

                            if ($el->ast->type != 'Declaration') {

                                $next->parent = null;
                                array_splice($el->ast->children, 0, 0, $next->ast->children);

                                if (isset($next->ast->location) && isset($el->ast->location)) {

                                    $el->ast->location->start = $next->ast->location->start;
                                }
                            }

                            if ($total == 1) {

                                break;
                            }

                            $next = $this->ast->children[--$total - 1];

                            while ($total > 1 && $next->ast->type == 'Comment') {

                                $next = $this->ast->children[--$total - 1];
                            }

                            $nextSignature = $next->computeSignature();
                        }

                        $signature = $nextSignature;
                    }
                }
            }

            foreach ($this->ast->children as $key => $element) {

                if (is_callable([$element, 'deduplicate'])) {

                    $element->deduplicate($options);
                }
            }
        }

        return $this;
    }

    /**
     * merge duplicate declarations
     * @return Element
     * @ignore
     */
    protected function deduplicateDeclarations(array $options = [])
    {

        if (!empty($options['allow_duplicate_declarations']) && !empty($this->ast->children)) {

            $elements = $this->ast->children;

            $total = count($elements);

            $hash = [];
            $exceptions = is_array($options['allow_duplicate_declarations']) ? $options['allow_duplicate_declarations'] : !empty($options['allow_duplicate_declarations']);

            while ($total--) {

                $declaration = $this->ast->children[$total];

                if ($declaration->ast->type == 'Comment') {

                    continue;
                }

                $name = $declaration['name'];

                if ($name instanceof Value) {

                    $name = $name->render(['remove_comments' => true]);
                }

                if ($exceptions === true || isset($exceptions[$name])) {

                    continue;
                }

                if (isset($hash[$name])) {

                    $declaration->parent = null;
                    array_splice($this->ast->children, $total, 1);
                    continue;
                }

                $hash[$name] = 1;
            }
        }

        return $this;
    }

    public function setAst(ElementInterface $element) {

        $this->ast = $element->getAst();
    }

    public function getAst()
    {

        $ast = clone $this->ast;

        unset($ast->parent);

        if (isset($ast->value)) {

            $ast->value = trim($ast->value);
        }

        if (empty($ast->location)) {

            unset($ast->location);
        }

        if (!empty($ast->children)) {

            foreach ($ast->children as $key => $child) {

                $ast->children[$key] = $child->getAst();
            }
        }

        return $ast;
    }

    /**
     * @return stdClass
     * @ignore
     */
    public function jsonSerialize () {

        return $this->getAst();
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        try {

            return (new Renderer())->render($this, null, true);
        }

        catch (Exception $ex) {

            error_log($ex->getTraceAsString());
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function __clone()
    {
        $this->ast = clone $this->ast;
        $this->parent = null;

        if (isset($this->ast->children)) {

            foreach ($this->ast->children as $key => $value) {

                $this->ast->children[$key] = clone $value;
                $this->ast->children[$key]->parent = $this;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function toObject()
    {
       return $this->ast;
    }
}