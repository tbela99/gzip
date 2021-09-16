<?php

namespace TBela\CSS\Value;

use TBela\CSS\Color as ColorUtil;
use TBela\CSS\Value;

/**
 * Css color value
 * Class Color
 * @package TBela\CSS\Value
 */
class Color extends Value
{

    /**
     * @inheritDoc
     */
    protected function __construct($data)
    {

        parent::__construct($data);

        if (isset($data->colorType) && $data->colorType == 'hex') {

            $values = ColorUtil::hex2rgba_values($data->value);

            if (count($values) >= 3) {

                $data->rgba = $values;
            }
        }

        else if (isset($data->name)) {

            $a = static::getNumericValue($data->arguments->{6});

            if ($a == 1) {

                $a = null;
            }

            switch ($data->name) {

                case 'rgb':
                case 'rgba':

                    $data->rgba[] = static::getRGBValue($data->arguments->{0});
                    $data->rgba[] = static::getRGBValue($data->arguments->{2});
                    $data->rgba[]= static::getRGBValue($data->arguments->{4});

                    if (!is_null($a)) {

                        $data->rgba[] =  $a;
                    }

                    break;

                case 'hsl':
                case 'hsla':

                    $data->rgba = ColorUtil::hsl2rgb_values(static::getAngleValue($data->arguments->{0}), static::getNumericValue($data->arguments->{2}), static::getNumericValue($data->arguments->{4}), $a);
                    break;

                case 'hwb':

                    $data->rgba = ColorUtil::hwb2rgba_values(static::getAngleValue($data->arguments->{0}), static::getNumericValue($data->arguments->{2}), static::getNumericValue($data->arguments->{4}), $a);
                    break;

                case 'device-cmyk':

                    $a = static::getNumericValue($data->arguments->{8});

                    if ($a == 1) {

                        $a = null;
                    }

                    $data->rgba = ColorUtil::cmyk2rgba_values(static::getNumericValue($data->arguments->{0}), static::getNumericValue($data->arguments->{2}), static::getNumericValue($data->arguments->{4}), static::getNumericValue($data->arguments->{6}), $a);
                    break;

//                case 'lab':
//
//                    $a = static::getNumericValue($data->arguments->{4});
//
//                    if ($a == 1) {
//
//                        $a = null;
//                    }
//
//                    $data->rgba = ColorUtil::lab2rgba(static::getNumericValue($data->arguments->{0}), static::getNumericValue($data->arguments->{1}), static::getNumericValue($data->arguments->{2}), static::getNumericValue($data->arguments->{6}), $a);
//                    break;
            }
        }
    }

    public function getHash() {

        if (is_null($this->hash)) {

            $this->hash = $this->render(['convert_color' => 'hex']);
        }

        return $this->hash;
    }

    /**
     * @inheritDoc
     */
    protected static function validate($data)
    {

        if (isset($data->name) && isset($data->arguments)) {

            return in_array($data->name, ['rgb', 'rgba', 'hsl', 'hsla', 'hwb', 'device-cmyk']);
        }

        $colors = ColorUtil::COLORS_NAMES;

        return isset($colors[$data->value]) || (isset($data->colorType) && in_array($data->colorType, ['hex', 'keyword']));
    }

    /**
     * @inheritDoc
     */
    public function match($type)
    {

        return $type == 'color';
    }

    /**
     * @inheritDoc
     */
    public function render(array $options = [])
    {

        $key = md5(json_encode($options).json_encode($this->data));

        if (isset(static::$cache[$key])) {

            return static::$cache[$key];
        }

        if (isset($this->data->rgba)) {

            static::$cache[$key] = static::rgba2string($this->data, $options);
        }

        else if (isset($this->data->value)) {

                static::$cache[$key] = $this->data->value;
        } else {

            static::$cache[$key] = $this->data->name.'('.$this->data->arguments->render($options).')';
        }

        return static::$cache[$key];
    }

    public static function rgba2string($data, array $options = []) {

        $hex = ColorUtil::rgba_values2hex($data->rgba, $options);
        $value = $hex;
        $css3 = isset($options['css_level']) && $options['css_level'] < 4;

        if (empty($options['convert_color'])) {

            $options['convert_color'] = isset($data->colorType) ? $data->colorType : $data->name;
        }

        $colors = ColorUtil::NAMES_COLORS;

        if (isset($colors[$hex])) {

            $hex = ColorUtil::NAMES_COLORS[$hex];
        }

        if (isset($options['convert_color'])) {

            if (is_bool($options['convert_color'])) {

                // convert hex color to css3 rgba
                $options['convert_color'] = $options['convert_color'] ? 'hex' : (isset($data->colorType) ? $data->colorType : $data->name);
            }

            //
            if ($css3 && substr($value, 0, 1) == '#' && in_array(strlen($value), [5, 9])) {

                $options['convert_color'] = isset($data->name) ? $data->name : 'rgba';
            }

            if ($options['convert_color'] == 'hex') {

                $hex = ColorUtil::shorten($hex);
            }

            switch ($options['convert_color']) {

                case 'hsl':
                case 'hsla':

                    $value = static::rgba_values2hsl($data->rgba, $options);
                    break;

                case 'hwb':

                    $value = static::rgba2hwb_values($data->rgba, $options);
                    break;


                case 'rgb':
                case 'rgba':

                    $value = static::rgba_values2rgba($data->rgba, $options);
                    break;

                case 'cmyk':
                case 'device-cmyk':

                    $value = static::rgba2cmyk_values($data->rgba, $options);
                    break;

                case 'hex':
                default:

                    return $hex;
            }
        }

        return substr($hex, 0, 1) != '#' ? (strlen($hex) <= strlen($value) ? $hex : $value) : $value;
    }

    public static function rgba_values2rgba(array $rgba_values, array $options = []) {

        $compress = !empty($options['compress']);
        $css4 = !empty($options['css_level']) && $options['css_level'] > 3;
        $rgba = $css4 || count($rgba_values) == 3 ? 'rgb' : 'rgba';
        $space = $compress ? '' : ' ';
        $glue = $css4 ? ' ' : ','.$space;
        $alpha_sep = ($css4 ? $space.'/' : ',').$space;

        $format = '%s'.$glue.'%s'.$glue.'%s'.(count($rgba_values) == 3 ? '' : $alpha_sep.'%s');

        array_unshift($rgba_values, $rgba.'('.$format.')');

        return call_user_func_array('sprintf', $rgba_values);
    }

    public static function rgba_values2hsl(array $rgba_values, array $options = []) {

        $rgba_values = call_user_func_array([ColorUtil::class, 'rgba2hsl_values'], $rgba_values);

        $compress = !empty($options['compress']);
        $css4 = !empty($options['css_level']) && $options['css_level'] > 3;
        $hsla = $css4 || count($rgba_values) == 3 ? 'hsl' : 'hsla';
        $space = $compress ? '' : ' ';
        $glue = $css4 ? ' ' : ','.$space;
        $alpha_sep = ($css4 ? $space.'/' : ',').$space;

        $format = '%s'.$glue.'%s'.$glue.'%s'.(count($rgba_values) == 3 ? '' : $alpha_sep.'%s');

        array_unshift($rgba_values, $hsla.'('.$format.')');

        return call_user_func_array('sprintf', $rgba_values);
    }

    public static function rgba2hwb_values(array $rgba_values, array $options = []) {

        $rgba_values = call_user_func_array([ColorUtil::class, 'rgba2hwb_values'], $rgba_values);

        $compress = !empty($options['compress']);
        $glue = ' ';
        $alpha_sep = $compress ? '/' : ' / ';

        $format = '%s'.$glue.'%s'.$glue.'%s'.(count($rgba_values) == 3 ? '' : $alpha_sep.'%s');

        array_unshift($rgba_values, 'hwb('.$format.')');

        return call_user_func_array('sprintf', $rgba_values);
    }

    public static function rgba2cmyk_values(array $rgba_values, array $options = []) {

        $rgba_values = call_user_func_array([ColorUtil::class, 'rgba2cmyk_values'], $rgba_values);

        $compress = !empty($options['compress']);
        $glue = ' ';
        $alpha_sep = $compress ? '/' : ' / ';

        $format = '%s'.$glue.'%s'.$glue.'%s'.$glue.'%s'.(count($rgba_values) == 4 ? '' : $alpha_sep.'%s');

        array_unshift($rgba_values, 'device-cmyk('.$format.')');

        return call_user_func_array('sprintf', $rgba_values);
    }
}
