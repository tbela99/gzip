<?php

namespace TBela\CSS;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use TBela\CSS\Interfaces\ObjectInterface;
use TBela\CSS\Property\Config;
use TBela\CSS\Value\Number;
use TBela\CSS\Value\Set;
use TBela\CSS\Parser\ParserTrait;

/**
 * CSS value base class
 * @package CSS
 * @property-read string|null $value
 * @property-read Set|null $arguments
 * @method string getName()
 * @method \stdClass|null getData()
 * @method \stdClass|null getValue()
 */
abstract class Value implements JsonSerializable, ObjectInterface
{
    use ParserTrait;

    /**
     * var stdClass;
     * @ignore
     */
    protected $data = null;

    /**
     * @var array
     * @ignore
     */
    protected static $defaults = [];

    /**
     * @var array
     * @ignore
     */
    protected static $keywords = [];

    /**
     * @var string|null
     * @ignore
     */
    protected $hash = null;

    /**
     * @var array
     * @ignore
     */
    protected static $cache = [];

    /**
     * Value constructor.
     * @param stdClass $data
     */
    protected function __construct($data)
    {

        $this->data = $data;
    }

    /**
     * Cleanup cache
     * @ignore
     */
    public function __destruct()
    {
        unset (static::$cache[spl_object_hash($this)]);
    }

    /**
     * get property
     * @param string $name
     * @return mixed|null
     * @ignore
     */
    public function __get($name)
    {
        if (isset($this->data->{$name})) {

            return $this->data->{$name};
        }

        if (is_callable([$this, 'get' . $name])) {

            return call_user_func([$this, 'get' . $name]);
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     * @ignore
     */
    public function __isset($name)
    {

        return isset($this->data->{$name});
    }

    /**
     * compute the hash value
     * @return string|null
     */
    public function getHash()
    {

        return $this->data->value;
    }

    /**
     * test if this object matches the specified type
     * @param string $type
     * @return bool
     */
    public function match($type)
    {

        return strtolower($this->data->type) == $type;
    }
    /**
     * get the class name of the specified type
     * @param string $type
     * @return string
     */
    public static function getClassName($type)
    {

        static $classNames = [];

        if (!isset($classNames[$type])) {

            $classNames[$type] = Value::class . '\\' . preg_replace_callback('#(^|-)([a-z])#', function ($matches) {

                    return strtoupper($matches[2]);
                }, $type);
        }

        return $classNames[$type];
    }

    /**
     * value type
     * @return string
     * @ignore
     */
    protected static function type()
    {

        static $types = [];

        if (!isset($types[static::class])) {

            $name = explode('\\', static::class);

            $types[static::class] = preg_replace_callback('#(^|[^A-Z])([A-Z])#', function ($matches) {

                return (empty($matches[1]) ? '' : $matches[1] . '-') . strtolower($matches[2]);
            }, end($name));
        }

        return $types[static::class];
    }

    /**
     * @param object $token
     * @return bool
     * @ignore
     */

    protected static function matchDefaults($token)

    {

        return isset($token->value) && in_array(strtolower($token->value), static::$defaults);
    }

    /**
     * @param object $token
     * @param object|null $previousToken
     * @param object|null $previousValue
     * @param object|null $nextToken
     * @param object|null $nextValue
     * @param int|null $index
     * @param array $tokens
     * @return bool
     */
    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        return $token->type == static::type() || isset($token->value) && static::matchKeyword($token->value);
    }

    /**
     * test if $data matches this class
     * @param stdClass $data
     * @return bool
     */
    protected static function validate($data)
    {

        return isset($data->value);
    }
    /**
     * create an instance
     * @param stdClass $data
     * @return Value
     */
    public static function getInstance($data)
    {

        if ($data instanceof Value) {

            return $data;
        }

        if (!isset($data->type)) {

            throw new InvalidArgumentException('Type property is required: ' . gettype($data) . ':' . var_export($data, true), 400);
        }

        $className = static::getClassName($data->type);

        if (!class_exists($className)) {

            error_log(__METHOD__ . ' missing data type? ' . $className);
            $className = static::class;
        }

        if (!$className::validate($data)) {

            throw new InvalidArgumentException('Invalid argument: $className:' . $className . ' data:' . var_export($data, true), 400);
        }

        return new $className($data);
    }

    /**
     * convert this object to string
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {

        return $this->data->value;
    }

    /**
     * parse a css value
     * @param string $string
     * @param string|Set|null $property
     * @param bool $capture_whitespace
     * @param string $context
     * @param string $contextName
     * @return Set
     */

    public static function parse($string, $property = null, $capture_whitespace = true, $context = '', $contextName = '')

    {
        if ($string instanceof Set) {

            return $string;
        }

        if (trim($property) === '') {

            $property = null;
        }

        $string = trim($string);
        $property = trim($property);

        if ($property !== '') {

            $className = static::getClassName($property);

            if (is_callable([$className, 'doParse'])) {

                try {

                    return call_user_func([$className, 'doParse'], $string, $capture_whitespace, $context, $contextName);
                } catch (\Exception $e) {

//                    throw $e;
                    // failed to parse css property
                }
            }
        }

        return static::doParse($string, $capture_whitespace, $context, $contextName);
    }

    /**
     * remove unnecessary tokens
     * @param array $tokens
     * @param array $options
     * @return array
     */
    public static function reduce(array $tokens, array $options = [])
    {
        $j = count($tokens);

        if ($j > 1) {

            while ($j--) {

                $token = $tokens[$j];

                if ($token->type == 'css-string' && $token->value == '!important' && count($tokens) <= 2) {

                    break;
                }

                if ($token->type == 'whitespace' &&
                    isset($tokens[$j + 1]) &&
                    (in_array($tokens[$j + 1]->type, ['separator', 'operator', 'whitespace', 'css-parenthesis-expression']) ||
                        $tokens[$j + 1]->type == 'css-string' && $tokens[$j + 1]->value == '!important')
                ) {

                    array_splice($tokens, $j, 1);
                } else if (in_array($token->type, ['separator', 'operator', 'css-parenthesis-expression']) && isset($tokens[$j + 1]) && $tokens[$j + 1]->type == 'whitespace') {

                    array_splice($tokens, $j + 1, 1);
                } else if (!empty($options['remove_defaults']) && !in_array($token->type, ['whitespace', 'separator'])) {

                    // check if the previous token has the same type and matches the defaults
                    // same type? no -> remove
                    // same type? yes -> matches defaults? yes -> remove

                    $className = static::getClassName($token->type);

                    if (is_callable($className . '::matchDefaults') && call_user_func($className . '::matchDefaults', $token)) {

                        if ($token->type == 'background-size') {

                            if ((isset($tokens[$j - 2]) && $token->type == $tokens[$j - 2]->type) ||
                                (isset($tokens[$j + 2]) && $token->type == $tokens[$j + 2]->type)) {

                                continue;
                            }
                        }

                        $prefix = Config::getPath('map.' . $token->type . '.prefix');

                        if (!is_null($prefix)) {

                            if (is_array($prefix)) {

                                $prefix = $prefix[1];
                            }
                        }

                        // remove item
                        array_splice($tokens, $j, 1);

                        if (isset($tokens[$j]) && $tokens[$j]->type == 'whitespace') {

                            // remove whitespace after the item removed
                            array_splice($tokens, $j, 1);
                        }

                        $key = isset($tokens[$j - 1]) ? $tokens[$j - 1] : null;

                        if (!is_null($key) && $key->type == 'separator' && $key->value == $prefix) {

                            array_splice($tokens, --$j, 1);
                        }
                    }
                }
            }
        }

        while (true) {

            // remove leading whitespace
            if (isset($tokens[0]) && $tokens[0]->type == 'whitespace') {

                array_shift($tokens);
            } else {

                $count = count($tokens) - 1;

                if (isset($tokens[$count]) && $tokens[$count]->type == 'whitespace') {

                    array_pop($tokens);
                    continue;
                }

                break;
            }
        }

        return $tokens;
    }

    /**
     * parse a css value
     * @param string $string
     * @param bool $capture_whitespace
     * @param string $context
     * @param string $contextName
     * @return Set
     */

    protected static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '')

    {

        return new Set(static::reduce(static::getTokens($string, $capture_whitespace, $context, $contextName)));
    }

    /**
     * parse a css value
     * @param Set|string $string
     * @param bool $capture_whitespace
     * @param string $context
     * @param string $contextName
     * @return array|null
     */
    protected static function getTokens($string, $capture_whitespace = true, $context = '', $contextName = '')
    {

        $string = trim($string);

        $i = -1;
        $j = strlen($string) - 1;

        $buffer = '';
        $tokens = [];

        while (++$i <= $j) {

            switch ($string[$i]) {

                case ' ':
                case "\t":
                case "\n":
                case "\r":

                    if ($buffer !== '') {

                        $tokens[] = static::getType($buffer);
                        $buffer = '';
                    }

                    if ($capture_whitespace) {

                        $k = $i;

                        while (++$k <= $j) {

                            if (preg_match('#\s#', $string[$k])) {

                                continue;
                            }

                            break;
                        }

                        if ($k <= $j) {

                            $token = new stdClass;
                            $token->type = 'whitespace';
                            $tokens[] = $token;
                        }

                        $i = $k - 1;
                    }

                    break;

                case '"':
                case "'":

                    if ($buffer !== '') {

                        $tokens[] = static::getType($buffer);
                        $buffer = '';
                    }

                    $next = $i;

                    while (true) {

                        $next = strpos($string, $string[$i], $next + 1);

                        if ($next !== false) {

                            if ($string[$next - 1] != '\\') {

                                break;
                            }
                        } else {

                            break;
                        }
                    }

                    $token = new stdClass;

                    $token->type = 'css-string';
                    $token->value = substr($string, $i, $next === false ? $j + 1 : $next - $i + 1);

                    $tokens[] = $token;
                    $buffer = '';


                    if ($next === false) {

                        $i = $j;
                        continue 2;
                    }

                    $i = $next;

                    break;

                case '\\':

                    $buffer .= $string[$i];

                    if (isset($string[$i + 1])) {

                        $buffer .= $string[++$i];
                    }

                    break;

                case '[':

                    $params = static::_close($string, ']', '[', $i, $j);

                    if ($params !== false) {


                        if (trim($buffer) !== '') {

                            $tokens[] = static::getType($buffer);
                        }

                        $token = new stdClass;

                        $token->type = 'css-attribute';
                        $token->arguments = Value::parse(substr($params, 1, -1), null, $capture_whitespace, 'attribute');

                        $tokens[] = $token;

                        $buffer = '';
                        $i += strlen($params) - 1;

                    } else {

                        $tokens[] = static::getType($buffer . substr($string, $i));
                        $i = $j;
                    }

                    break;
                case '(':

                    $params = static::_close($string, ')', '(', $i, $j);


                    if ($params !== false) {

                        $token = new stdClass;

                        if (preg_match('#^(-([a-zA-Z]+)-(\S+))#i', $buffer, $matches)) {

                            $token->name = $matches[3];
                            $token->vendor = $matches[2];
                        } else {

                            $token->name = $buffer;
                        }

                        if (in_array(strtolower($token->name), [
                            'rgb', 'rgba', 'hsl', 'hsla', 'hwb', 'device-cmyk' //, 'lab', 'lch' //
                        ])) {

                            $token->type = 'color';
                        } else if ($token->name == 'url') {

                            $token->type = 'css-url';
                        } else if ($token->name == 'format') {

                            $token->type = 'css-src-format';
                        } else {

                            $token->type = $token->name === '' ? 'css-parenthesis-expression' : 'css-function';
                        }

                        $str = substr($params, 1, -1);

                        if ($buffer == 'url') {

                            $t = new stdClass;

                            $t->type = 'css-string';
                            $t->value = $str;
                            $token->arguments = new Set([$t]);
                        } else {

                            if (in_array($buffer, ['or', 'and'])) {

                                $token->name = '';
                                $token->type = 'css-parenthesis-expression';
                                $tokens[] = static::getType($buffer);
                            }

                            $token->arguments = Value::parse($str, null, $capture_whitespace, $token->type, $token->name);
                        }

                        if ($token->type == 'color') {

                            foreach ($token->arguments as $arg) {

                                if($arg->type == 'css-function') {

                                    $token->type = 'css-function';
                                    break;
                                }
                            }
                        }

                        $tokens[] = $token;

                        $buffer = '';
                        $i += strlen($params) - 1;
                    } else {

                        $tokens[] = static::getType($buffer . substr($string, $i));
                        $i = $j;
                    }

                    break;

                case '|':

                    if (isset($string[$i + 1]) && $string[$i + 1] == '|') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType($buffer);
                            $buffer = '';
                        }

                        $token = end($tokens);

                        if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                            array_pop($tokens);
                        }

                        $tokens[] = (object)['type' => 'operator', 'value' => '||'];
                        $i++;
                        break;
                    }

                    $buffer .= $string[$i];
                    break;

                case '>':
                case '+':

                    if ($context === '') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType($buffer);
                        }

                        $tokens[] = (object)['type' => 'separator', 'value' => $string[$i]];
                        $buffer = '';
                        break;
                    }

                    if ($context == 'css-function' && trim($buffer) === '' && static::is_whitespace(substr($string, $i + 1, 1))) {

                        $tokens[] = (object)['type' => 'css-string', 'value' => $string[$i]];
                        $buffer = '';
                        break;
                    }

                    $buffer .= $string[$i];
                    break;

                case '/':

                    if ($i < $j && $string[$i + 1] == '*' && $string[$i] == '/') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType($buffer);
                            $buffer = '';
                        }

                        $params = static::match_comment($string, $i, $j);

                        if ($params !== false) {

                            $token = new stdClass;

                            $token->type = 'Comment';
                            $token->value = $params;

                            $tokens[] = $token;

                            $i += strlen($params) - 1;
                            $buffer = '';
                            break;
                        }
                    }

                    $token = static::getType($buffer);

                    if (trim($buffer) === '') {

                        $tokens[] = (object)['type' => $context == 'css-function' ? 'operator' : 'separator', 'value' => '/'];
                        $buffer = '';
                        break;
                    }

                    if (in_array($token->type, ['unit', 'number'])) {

                        $tokens[] = $token;
                        $tokens[] = (object)['type' => $context == 'css-function' ? 'operator' : 'separator', 'value' => '/'];
                        $buffer = '';
                        break;
                    }

                    $buffer .= $string[$i];
                    break;

                case '~':
                case '^':
                case '$':
                case '=':

                    if (($string[$i] == '~' && $context === '') ||
                        $context == 'attribute' && ($string[$i] == '=' || (isset($string[$i + 1]) && $string[$i + 1] == '='))) {

                        if (trim($buffer) !== '') {

                            $tokens[] = static::getType($buffer);
                        }

                        $token = end($tokens);

                        if ((isset($token->type) ? $token->type : '') == 'whitespace') {

                            array_pop($tokens);
                        }

                        $buffer = '';

                        if ($string[$i] == '=') {

                            $tokens[] = (object)['type' => $context === 'attribute' ? 'operator' : 'css-string', 'value' => '='];
                        } else if ($context === 'attribute') {

                            $tokens[] = (object)['type' => 'operator', 'value' => $string[$i++] . '='];
                        } else {

                            $tokens[] = (object)['type' => 'css-string', 'value' => $string[$i++]];
                        }

                        break;
                    }

                    $buffer .= $string[$i];
                    break;

                case ',':
                case ':':

                    if ($string[$i] == ':' && $context != 'css-parenthesis-expression') {

                        $buffer .= $string[$i];
                        continue 2;
                    }

                    if ($buffer !== '') {

                        $tokens[] = static::getType($buffer);
                    }

                    $token = new stdClass;
                    $token->type = 'separator';
                    $token->value = $string[$i];
                    $tokens[] = $token;

                    $buffer = '';
                    break;

                default:

                    if ($string[$i] == '!') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType(rtrim($buffer));
                            $buffer = '';
                        }
                    }

                    $buffer .= $string[$i];
            }
        }

        if ($buffer !== '') {

            $tokens[] = static::getType($buffer);
        }

        return $tokens;
    }

    /**
     * @param $token
     * @return stdClass
     */
    protected static function getType($token)
    {

        $type = new stdClass;

        $type->value = $token;
        $colors = Color::COLORS_NAMES;

        if (substr($token, 0, 1) != '#' && is_numeric($token)) {

            $type->type = 'number';
        } else if ($token == 'currentcolor' || isset($colors[$token]) || preg_match('#^\#([a-f0-9]{8}|[a-f0-9]{6}|[a-f0-9]{4}|[a-f0-9]{3})$#i', $token)) {

            $type->type = 'color';
            $type->colorType = $token == 'currentcolor' ? 'keyword' : 'hex';
        } else if (preg_match('#^(((\+|-)?(?=\d*[.eE])([0-9]+\.?[0-9]*|\.[0-9]+)([eE](\+|-)?[0-9]+)?)|(\d+|(\d*\.\d+)))([a-zA-Z]+|%)$#', $token, $matches)) {

            $type->type = 'unit';
            $type->value = $matches[1];
            $type->unit = $matches[9];
        } else {

            $type->type = 'css-string';
        }

        return $type;
    }

    /**
     * convert to an object
     * @return stdClass
     */
    public function toObject()
    {

        $result = new stdClass;

        foreach ($this->data as $key => $value) {

            $val = null;
            if ($value instanceof Set) {

                $val = array_map(function ($value) {

                    return $value->toObject();
                }, $value->toArray());
            } else {

                $val = $value;
            }

            if (!is_null($key) && $key !== false && $key !== "") {

                $result->{$key} = $val;
            }
        }

        return $result;
    }

    /**
     * return the list of keywords
     * @return array
     * @ignore
     */
    public static function keywords()
    {

        return static::$keywords;
    }

    /**
     * @param string $string
     * @param array|null $keywords
     * @return string|null
     * @ignore
     */

    public static function matchKeyword($string, array $keywords = null)

    {

        if (is_null($keywords)) {

            $keywords = static::keywords();
        }

        $string = static::stripQuotes($string, true);

        foreach ($keywords as $keyword) {

            if (strcasecmp($string, $keyword) === 0) {

                return $keyword;
            }
        }

        return null;
    }

    /**
     * @param Value|null $value
     * @param array $options
     * @return string
     */

    public static function getNumericValue($value, array $options = [])

    {

        if (is_null($value) || $value->value === '') {

            return null;
        }

        return Number::compress($value->unit == '%' ? $value->value / 100 : $value->render($options));
    }

    /**
     * @param Value $value
     * @return string
     */

    public static function getRGBValue(Value $value)

    {

        return Number::compress($value->unit == '%' ? 255 * $value->value / 100 : $value->value);
    }

    /**
     * @param Value|null $value
     * @param array $options
     * @return string
     */

    public static function getAngleValue($value, array $options = [])
    {

        if (is_null($value) || $value->value === '') {

            return null;
        }

        switch ($value->unit) {

            case 'rad':

                return floatval((string)$value->value) / (2 * pi());

            case 'grad':

                return floatval((string)$value->value) / 400;
            case 'turn':
                // do nothing
                return floatval((string)$value->value);
        }

        return floatval((string)$value->value) / 360;
    }

    /**
     * convert to string
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    public function jsonSerialize()
    {
        return $this->render();
    }
}