<?php

namespace TBela\CSS\Value;

use \TBela\CSS\Value;

/**
 * Css string value
 * @package TBela\CSS\Value
 */
class FontStretch extends Value
{

    use UnitTrait;
    /**
     * @var array
     * @ignore
     */
    protected static $keywords = [
        'normal' => '100%',
        'semi-condensed' => '87.5%',
        'condensed' => '75%',
        'extra-condensed' => '62.5%',
        'ultra-condensed' => '50%',
        'semi-expanded' => '112.5%',
        'expanded' => '125%',
        'extra-expanded' => '150%',
        'ultra-expanded' => '200%'
    ];

    protected static $defaults = ['normal', '100%'];

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        if (!empty($options['compress'])) {

            $value = $this->data->value;

            if (isset(static::$keywords[$value])) {

                return static::$keywords[$value];
            }
        }

        return $this->data->value;
    }

    /**
     * @inheritDoc
     */
    public static function keywords () {

        return array_keys(static::$keywords);
    }
}