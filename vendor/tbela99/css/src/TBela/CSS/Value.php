<?php

namespace TBela\CSS;

use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use TBela\CSS\Interfaces\ObjectInterface;
use TBela\CSS\Property\Config;
use TBela\CSS\Value\Number;
use TBela\CSS\Parser\ParserTrait;

/**
 * CSS value base class
 * @package CSS
 * @property-read string|null $value
 * @property-read array|null $arguments
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

//    abstract public static function doRender(object $data, array $options = []);

    /**
     * Value constructor.
     * @param object $data
     */
    protected function __construct(object $data)
    {

        $this->data = $data;
    }

    public static function renderTokens(array $tokens, array $options = [], $join = null)
    {

//        echo new \Exception('no reason');

        $result = '';

        foreach ($tokens as $token) {

            if (!is_object($token)) {

                throw new \Exception(sprintf('invalid token %s', var_export($token, true)));
            }

            switch ($token->type) {

                case 'font':
                case 'operator':
                case 'separator':
                case 'background':
                case 'font-style':
                case 'font-family':
                case 'font-variant':
                case 'outline-style':
                case 'background-clip':
                case 'background-size':
                case 'background-repeat':
                case 'background-attachment':

                    $result .= $token->value;

                    if (isset($token->unit) && $token->value !== '0') {

                        $result .= $token->unit;
                    }

                    if ($token->value == ',' && empty($options['compress'])) {

                        $result .= isset($join) ? $join : ' ';
                    }

                    break;
                case 'whitespace':

                    $result .= ' ';
                    break;

                case 'css-src-format':

                    $result .= $token->name . '("' . Value::renderTokens($token->arguments) . '")';
                    break;

                case 'css-function':
                case 'invalid-css-function':
                case 'css-parenthesis-expression':

                    $result .= $token->name . '(' . Value::renderTokens($token->arguments, $options) . ')';;
                    break;

                case 'unit':
                case 'color':
                case 'number':
                case 'css-url':
                case 'font-size':
                case 'css-string':
                case 'line-height':
                case 'font-weight':
                case 'outline-color':
                case 'css-attribute':
                case 'css-src-format':
                case 'outline-width':
                case 'background-color':
                case 'background-image':
                case 'background-origin':
                case 'background-position':

                    $className = static::getClassName($token->type);
                    $result .= $className::doRender($token, $options);
                    break;
                case 'Comment':
                    $result .= $token->value;
                    break;
                default:
                    throw new \Exception(sprintf("Not implemented: %s:\n%s", $token->type, var_export($token, true)), 501);
            }
        }

        return $result;
    }

    /**
     * @param stdClass $type
     * @param bool $preserve_quotes
     * @return void
     */
    private static function parseString(stdClass $type, $preserve_quotes)
    {
        if (!$preserve_quotes && strlen($type->value) > 2 &&
            in_array($type->value[0], ['"', "'"]) &&
            $type->value[0] == substr($type->value, -1)) {

            $value = substr($type->value, 1, -1);

            if (!preg_match('#^\d#', $value) &&
                preg_match('#^[\w_-]+$#', $value)) {

                $type->value = $value;
            }
        }
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
     * test if this object matches the specified type
     * @param string $type
     * @return bool
     */
    public static function match($data, $type)
    {

        return strtolower($data->type) == $type;
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
     * compare parsed values
     * @param array $value
     * @param array $otherValue
     * @return bool
     * @throws \Exception
     */
    public static function equals(array $value, array $otherValue)
    {

        return static::renderTokens($value, ['convert_color' => 'hex']) == static::renderTokens($otherValue, ['convert_color' => 'hex']);
    }

    /**
     * avoid parsing string
     * @param $string
     * @return string | bool
     *
     */
    public static function format($string, array &$comments = null)
    {

        $result = '';

        $string = trim($string);
        $j = strlen($string) - 1;
        $i = -1;

        while ($i++ < $j) {

            switch ($string[$i]) {

                case ' ':
                case "\t":
                case "\r":
                case "\n":

                    while ($i + 1 < $j && static::is_whitespace($string[$i + 1])) {

                        $i++;
                    }

                    if ((isset($string[$i + 1]) ? $string[$i + 1] : ',') != ',') {

                        $result .= ' ';
                    }

                    break;

                case '"':
                case "'":

                    $k = $i;

                    while (++$k < $j) {

                        if ($string[$k] == $string[$i] && $string[$k - 1] != '\\') {

                            break;
                        }
                    }

                    if ($k > $j || $string[$k] != $string[$i]) {

                        // unterminated string
//                        $result .= substr($string, $i);
//                        throw new SyntaxError(sprintf('unclosed string. missing "%s"', $string[$i]), 400);

                        return false;
                    } else {

                        $result .= substr($string, $i, $k - $i + 1);
                    }

                    $i = $k;

                    break;

                case ',':

                    $result .= ',';

                    while ($i < $j && static::is_whitespace($string[$i + 1])) {

                        $i++;
                    }

                    break;

                case '[':
                case '(':

                    $start = $string[$i] == '[' ? '[' : '(';
                    $end = $string[$i] == '[' ? ']' : ')';

                    $params = static::_close($string, $end, $start, $i, $j);

                    if ($params === false) {

//                        throw new SyntaxError(sprintf('missing "%s"', $end), 400);
                        return false;

                    } else {

                        $res = static::format(substr($params, 1, -1), $comments);

                        if ($res === false) {

                            return false;
                        }

                        $result .= $start . $res . $end;
                        $i += strlen($params) - 1;
                    }

                    break;

                case '/':

                    $comment = '/';

                    if ($i < $j && $string[$i + 1] == '*') {

                        $comment .= '*';
                        $i++;

                        while ($i++ < $j) {

                            if ($string[$i] == '\\') {

                                $comment .= '\\';

                                if ($i < $j) {

                                    $comment .= $string[++$i];
                                    continue;
                                }
                            }

                            $comment .= $string[$i];

                            if ($i < $j && $string[$i] == '*' && $string[$i + 1] == '/') {

                                $comment .= $string[++$i];

                                if (!is_null($comments)) {

                                    $comments[] = $comment;
                                    while ($i < $j && static::is_whitespace($string[$i + 1])) {

                                        $i++;
                                    }
                                } else {

                                    $result .= $comment;
                                }

                                break 2;
                            }
                        }

//                        throw new SyntaxError("unterminated comment", 400);
                        return false;
                    } else {

                        $result .= '/';
                    }

                    break;

                case '<':

                    if (substr($string, $i, 4) == '<!--') {

                        $comment = '<!--';
                        $i += 3;

                        while ($i++ < $j) {

                            if ($string[$i] == '-' && $i < $j - 3 && substr($string, $i, 4) == '--!>') {

                                $comment .= '--!>';
                                $i += 3;

                                if (!is_null($comments)) {

                                    $comments[] = $comment;
                                } else {

                                    $result .= $comment;
                                }

                                break 2;
                            }

                            $comment .= $string[$i];
                        }

                        // invalid comment
                        return false;
                    } else {

                        $result .= $string[$i];
                    }

                    break;

                default:

                    $result .= $string[$i];
                    break;
            }
        }

        return rtrim($result);
    }

    /**
     * parse a css value
     * @param string $string
     * @param string|null $property
     * @param bool $capture_whitespace
     * @param string $context
     * @param string $contextName
     * @param bool $preserve_quotes
     * @return array
     */
    public static function parse($string, $property = null, $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {
        if (is_array($string)) {

            return $string;
        }

        if (trim($property) === '') {

            $property = null;
        }

        $string = trim($string);
        $property = strtolower($property);

        if ($property !== '') {

            $className = static::getClassName($property);

            if (is_callable([$className, 'doParse'])) {

                try {

                    return call_user_func([$className, 'doParse'], $string, $capture_whitespace, $context, $contextName, $preserve_quotes);
                } catch (\Exception $e) {

//                    throw $e;
                    // failed to parse css property
                }
            }
        }

        return static::doParse($string, $capture_whitespace, $context, $contextName, $preserve_quotes);
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

                if ($token->type == 'css-string' && $token->value === '') {

                    array_splice($tokens, $j, 1);
                    continue;
                }

                if ($token->type == 'css-string' && $token->value == '!important' && count($tokens) <= 2) {

                    break;
                }

                if ($token->type == 'whitespace' &&
                    isset($tokens[$j + 1]) &&
                    (in_array($tokens[$j + 1]->type, ['separator', 'operator', 'whitespace']) ||
                        $tokens[$j + 1]->type == 'css-string' && $tokens[$j + 1]->value == '!important')
                ) {

                    array_splice($tokens, $j, 1);
                } else if (in_array($token->type, ['separator', 'operator']) && isset($tokens[$j + 1]) && $tokens[$j + 1]->type == 'whitespace') {

                    array_splice($tokens, $j + 1, 1);
                } else if (!empty($options['remove_defaults']) && !in_array($token->type, ['whitespace', 'separator'])) {

                    // check if the previous token has the same type and matches the defaults
                    // same type? no -> remove
                    // same type? yes -> matches defaults? yes -> remove

                    $className = static::getClassName($token->type);

                    if (is_callable($className . '::matchDefaults') && call_user_func($className . '::matchDefaults', $token)) {

                        if (in_array($token->type, ['background-size', 'background-repeat'])) {

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
     * @param bool $preserve_quotes
     * @return array
     */
    protected static function doParse($string, $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {

        return static::reduce(static::getTokens($string, $capture_whitespace, $context, $contextName, $preserve_quotes));
    }

    /**
     * parse a css value
     * @param string $string
     * @param bool $capture_whitespace
     * @param string $context
     * @param string $contextName
     * @param booll $preserve_quotes
     * @return array|null
     */
    public static function getTokens($string, $capture_whitespace = true, $context = '', $contextName = '', $preserve_quotes = false)
    {

        $string = static::split(trim($string));

        $i = -1;
        $j = count($string) - 1;

        $buffer = '';
        $tokens = [];

        while (++$i <= $j) {

            switch ($string[$i]) {

                case "\0":

                    $buffer .= '\fffd';
                    break;

                case '<':

                    if (implode('', array_slice($string, $i, 4)) == '<!--') {

                        if (trim($buffer) !== '') {

                            $tokens[] = static::getType($buffer);
                        }

                        $k = $i + 3;

                        $buffer = '<!--';

                        while ($k++ < $j) {

                            $buffer .= $string[$k];
                            if ($string[$k] == '-' && implode('', array_slice($string, $k, 3)) == '-->') {

                                $buffer .= '->';

                                $tokens[] = (object)[
                                    'type' => 'Comment',
                                    'value' => $buffer
                                ];

                                $buffer = '';
                                $i = $k + 2;
                                break 2;
                            }
                        }

                        // unclosed comment
                        $tokens[] = (object)[
                            'type' => 'invalid-comment',
                            'value' => $buffer
                        ];

                        $buffer = '';
                        $i = $j;
                        break;
                    }

                    $buffer .= '<';
                    break;

                case ';':

                    if ($context == 'invalid-css-function') {

                        $tokens[] = static::getType($buffer . ';');
                        $buffer = '';

                    } else if ($buffer !== '') {

                        $tokens[] = static::getType($buffer);
                        $buffer = '';
                    }

                    break;


                case ' ':
                case "\t":
                case "\n":
                case "\r":

                    if (rtrim($buffer) !== '') {

                        $tokens[] = static::getType($buffer, $preserve_quotes);
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

                        $tokens[] = static::getType($buffer, $preserve_quotes);
                    }

                    $next = $i;

                    while (true) {

                        $next = static::indexOf($string, $string[$i], $next + 1);

                        if ($next !== false) {

                            if ($string[$next - 1] != '\\') {

                                break;
                            }
                        } else {

                            break;
                        }
                    }

                    $token = new stdClass;

                    if ($next === false) {

                        $token->type = 'invalid-css-string';
                        $token->value = implode('', array_slice($string, $i + 1));
                        $token->q = $string[$i];
                    } else {

                        $token->type = 'css-string';
                        $token->value = implode('', array_slice($string, $i, $next - $i + 1));

                        self::parseString($token, $preserve_quotes);
                    }

                    if ($token->value !== '') {

                        $tokens[] = $token;
                    }

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

                            $tokens[] = static::getType($buffer, $preserve_quotes);
                        }

                        $token = new stdClass;

                        $token->type = 'css-attribute';
                        $token->arguments = Value::parse(substr($params, 1, -1), null, $capture_whitespace, 'attribute', '', $preserve_quotes);

                        $tokens[] = $token;

                        $buffer = '';
                        $i += strlen($params) - 1;

                    } else {

                        $tokens[] = static::getType($buffer . substr($string, $i), $preserve_quotes);
                        $buffer = '';
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

                            self::parseString($t, $preserve_quotes);

                            $token->arguments = [$t];
                        } else {

                            if (in_array($buffer, ['or', 'and'])) {

                                $token->name = '';
                                $token->type = 'css-parenthesis-expression';
                                $tokens[] = static::getType($buffer, $preserve_quotes);
                            }

                            $token->arguments = Value::parse($str, null, $capture_whitespace, $token->type, $token->name, $preserve_quotes);
                        }

                        if (!empty($token->name)) {

                            foreach ($token->arguments as $arg) {

                                if ((isset($arg->name) ? $arg->name : '') == 'var') {


                                    $token->type = 'css-function';
                                    break;
                                }
                            }
                        }

                        $tokens[] = $token;

                        $buffer = '';
                        $i += strlen($params) - 1;
                    } else {

                        if ($buffer === '') {

                            $tokens[] = static::getType($buffer . substr($string, $i), $preserve_quotes);
                        } else {

                            $token = (object)[
                                'type' => 'invalid-css-function',
                                'name' => $buffer,
                                'arguments' => []
                            ];

                            $args = implode('', array_slice($string, $i + 1));

                            if (trim($args) !== '') {

                                $token->arguments = Value::parse($args, '', '', $token->type, $token->name, $preserve_quotes);
                            }

                            $tokens[] = $token;
                        }

                        $buffer = '';
                        $i = $j;
                    }

                    break;

                case '|':

                    if (isset($string[$i + 1]) && $string[$i + 1] == '|') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType($buffer, $preserve_quotes);
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

                            $tokens[] = static::getType($buffer, $preserve_quotes);
                        }

                        $tokens[] = (object)['type' => 'separator', 'value' => $string[$i]];
                        $buffer = '';
                        break;
                    }

                    if ($context == 'css-function' && trim($buffer) === '' && static::is_whitespace($string[$i + 1])) {

                        $tokens[] = (object)['type' => 'css-string', 'value' => $string[$i]];
                        $buffer = '';
                        break;
                    }

                    $buffer .= $string[$i];
                    break;

                case '/':

                    if ($i < $j && $string[$i + 1] == '*') {

                        if ($buffer !== '') {

                            $tokens[] = static::getType($buffer, $preserve_quotes);
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
                        } else {

                            $token = new stdClass;

                            $token->type = 'invalid-comment';
                            $token->value = implode('', array_slice($string, $i));

                            $tokens[] = $token;

                            $i = $j;
                            $buffer = '';
                            break;
                        }
                    }

                    $token = static::getType($buffer, $preserve_quotes);

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

                            $tokens[] = static::getType($buffer, $preserve_quotes);
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

                        $tokens[] = static::getType($buffer, $preserve_quotes);
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

                            $tokens[] = static::getType(rtrim($buffer), $preserve_quotes);
                            $buffer = '';
                        }
                    }

                    $buffer .= $string[$i];
            }
        }

        if ($buffer !== '') {

            $tokens[] = static::getType($buffer, $preserve_quotes);
        }

        return $tokens;
    }

    /**
     * escape multibyte sequence
     * @param string $value
     * @return string
     */
    public static function escape($value)
    {

        $result = '';

        foreach (preg_split('##u', $value, -1, PREG_SPLIT_NO_EMPTY) as $val) {

            if ($val == "\0") {

                $result .= '\FFFD';
                continue;
            }

            $base = \mb_ord($val);

            if ($base > 128) {

                $result .= '\\' . strtoupper(base_convert($base, 10, 16));
            } else {

                $result .= $val;
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param mixed $search
     * @param int $offset
     * @return false|int
     */
    protected static function indexOf(array $array, $search, $offset = 0)
    {

        $length = count($array);

        for ($i = $offset; $i < $length; $i++) {

            if ($array[$i] === $search) {

                return $i;
            }
        }

        return false;
    }

    /**
     * @param string $token
     * @param bool $preserve_quotes
     * @return stdClass
     */
    protected static function getType($token, $preserve_quotes = false)
    {

        $type = new stdClass;

        $type->value = $token;

        if (substr($token, 0, 1) != '#' && is_numeric($token)) {

            $type->type = 'number';
        } else if ($token == 'currentcolor' || array_key_exists($token, Color::COLORS_NAMES) || preg_match('#^\#([a-f0-9]{8}|[a-f0-9]{6}|[a-f0-9]{4}|[a-f0-9]{3})$#i', $token)) {

            $type->type = 'color';
            $type->colorType = $token == 'currentcolor' ? 'keyword' : 'hex';
        } else if (preg_match('#^(((\+|-)?(?=\d*[.eE])([0-9]+\.?[0-9]*|\.[0-9]+)([eE](\+|-)?[0-9]+)?)|(\d+|(\d*\.\d+)))([a-zA-Z]+|%)$#', $token, $matches)) {

            $type->type = 'unit';
            $type->value = $matches[1];
            $type->unit = $matches[9];
        } else {

            $type->type = 'css-string';

            self::parseString($type, $preserve_quotes);
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

            $val = $value;

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
        $string = trim($string, ";\n\t\r ");
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

        return Number::compress((isset($value->unit) ? $value->unit : '') == '%' ? $value->value / 100 : Value::renderTokens([$value], $options));
    }

    /**
     * @param Value $value
     * @return string
     */
    public static function getRGBValue($value)
    {

        return Number::compress((isset($value->unit) ? $value->unit : '') == '%' ? 255 * $value->value / 100 : $value->value);
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

        switch (isset($value->unit) ? $value->unit : '') {

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