<?php

namespace TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class BackgroundImage extends CssFunction
{

    use ValueTrait;

    public static $keywords = ['none'];
    public static $defaults = ['none'];

    /**
     * @inheritDoc
     */
    protected static function validate($data) {

        if (isset($data->value)) {

            return in_array($data->value, static::$keywords);
        }

        return isset($data->name) && isset($data->arguments) && is_array($data->arguments);
    }

    public function render(array $options = [])
    {
        return isset($this->data->value) ? $this->data->value : parent::render($options);
    }

    public static function doRender($data, array $options = [])
    {
        return isset($data->value) ? $data->value : parent::doRender($data, $options);
    }

    public static function matchToken($token, $previousToken = null, $previousValue = null, $nextToken = null, $nextValue = null, $index = null, array $tokens = [])
    {

        return $token->type == static::type() || (isset($token->name) &&
                in_array($token->name, [
                    'url',
                    'linear-gradient',
                    'element',
                    'image',
                    'cross-fade',
                    'image-set'
                ]));
    }
}
