<?php

namespace TBela\CSS;

use TBela\CSS\Value\Number;
use TBela\CSS\Value\Number as ValueNumber;

/**
 * Color conversion utils
 *
 * convert:
 * - hwb <=> rgba
 * - hex <=> rgba
 * - hsla <=> rgba
 * - cmyk <=> rgba
 * - lab <=>
 * - lch <=>
 *
 */
class Color
{

    const REF_X = 95.047; // Observer= 2°, Illuminant= D65
    const REF_Y = 100.0;
    const REF_Z = 108.883;

    // name to color
    const COLORS_NAMES = [
        'aliceblue' => '#f0f8ff',
        'antiquewhite' => '#faebd7',
        'aqua' => '#00ffff',
        'aquamarine' => '#7fffd4',
        'azure' => '#f0ffff',
        'beige' => '#f5f5dc',
        'bisque' => '#ffe4c4',
        'black' => '#000000',
        'blanchedalmond' => '#ffebcd',
        'blue' => '#0000ff',
        'blueviolet' => '#8a2be2',
        'brown' => '#a52a2a',
        'burlywood' => '#deb887',
        'cadetblue' => '#5f9ea0',
        'chartreuse' => '#7fff00',
        'chocolate' => '#d2691e',
        'coral' => '#ff7f50',
        'cornflowerblue' => '#6495ed',
        'cornsilk' => '#fff8dc',
        'crimson' => '#dc143c',
        'cyan' => '#00ffff',
        'darkblue' => '#00008b',
        'darkcyan' => '#008b8b',
        'darkgoldenrod' => '#b8860b',
        'darkgray' => '#a9a9a9',
        'darkgrey' => '#a9a9a9',
        'darkgreen' => '#006400',
        'darkkhaki' => '#bdb76b',
        'darkmagenta' => '#8b008b',
        'darkolivegreen' => '#556b2f',
        'darkorange' => '#ff8c00',
        'darkorchid' => '#9932cc',
        'darkred' => '#8b0000',
        'darksalmon' => '#e9967a',
        'darkseagreen' => '#8fbc8f',
        'darkslateblue' => '#483d8b',
        'darkslategray' => '#2f4f4f',
        'darkslategrey' => '#2f4f4f',
        'darkturquoise' => '#00ced1',
        'darkviolet' => '#9400d3',
        'deeppink' => '#ff1493',
        'deepskyblue' => '#00bfff',
        'dimgray' => '#696969',
        'dimgrey' => '#696969',
        'dodgerblue' => '#1e90ff',
        'firebrick' => '#b22222',
        'floralwhite' => '#fffaf0',
        'forestgreen' => '#228b22',
        'fuchsia' => '#ff00ff',
        'gainsboro' => '#dcdcdc',
        'ghostwhite' => '#f8f8ff',
        'gold' => '#ffd700',
        'goldenrod' => '#daa520',
        'gray' => '#808080',
        'grey' => '#808080',
        'green' => '#008000',
        'greenyellow' => '#adff2f',
        'honeydew' => '#f0fff0',
        'hotpink' => '#ff69b4',
        'indianred' => '#cd5c5c',
        'indigo' => '#4b0082',
        'ivory' => '#fffff0',
        'khaki' => '#f0e68c',
        'lavender' => '#e6e6fa',
        'lavenderblush' => '#fff0f5',
        'lawngreen' => '#7cfc00',
        'lemonchiffon' => '#fffacd',
        'lightblue' => '#add8e6',
        'lightcoral' => '#f08080',
        'lightcyan' => '#e0ffff',
        'lightgoldenrodyellow' => '#fafad2',
        'lightgray' => '#d3d3d3',
        'lightgrey' => '#d3d3d3',
        'lightgreen' => '#90ee90',
        'lightpink' => '#ffb6c1',
        'lightsalmon' => '#ffa07a',
        'lightseagreen' => '#20b2aa',
        'lightskyblue' => '#87cefa',
        'lightslategray' => '#778899',
        'lightslategrey' => '#778899',
        'lightsteelblue' => '#b0c4de',
        'lightyellow' => '#ffffe0',
        'lime' => '#00ff00',
        'limegreen' => '#32cd32',
        'linen' => '#faf0e6',
        'magenta' => '#ff00ff',
        'maroon' => '#800000',
        'mediumaquamarine' => '#66cdaa',
        'mediumblue' => '#0000cd',
        'mediumorchid' => '#ba55d3',
        'mediumpurple' => '#9370d8',
        'mediumseagreen' => '#3cb371',
        'mediumslateblue' => '#7b68ee',
        'mediumspringgreen' => '#00fa9a',
        'mediumturquoise' => '#48d1cc',
        'mediumvioletred' => '#c71585',
        'midnightblue' => '#191970',
        'mintcream' => '#f5fffa',
        'mistyrose' => '#ffe4e1',
        'moccasin' => '#ffe4b5',
        'navajowhite' => '#ffdead',
        'navy' => '#000080',
        'oldlace' => '#fdf5e6',
        'olive' => '#808000',
        'olivedrab' => '#6b8e23',
        'orange' => '#ffa500',
        'orangered' => '#ff4500',
        'orchid' => '#da70d6',
        'palegoldenrod' => '#eee8aa',
        'palegreen' => '#98fb98',
        'paleturquoise' => '#afeeee',
        'palevioletred' => '#d87093',
        'papayawhip' => '#ffefd5',
        'peachpuff' => '#ffdab9',
        'peru' => '#cd853f',
        'pink' => '#ffc0cb',
        'plum' => '#dda0dd',
        'powderblue' => '#b0e0e6',
        'purple' => '#800080',
        'red' => '#ff0000',
        'rosybrown' => '#bc8f8f',
        'royalblue' => '#4169e1',
        'saddlebrown' => '#8b4513',
        'salmon' => '#fa8072',
        'sandybrown' => '#f4a460',
        'seagreen' => '#2e8b57',
        'seashell' => '#fff5ee',
        'sienna' => '#a0522d',
        'silver' => '#c0c0c0',
        'skyblue' => '#87ceeb',
        'slateblue' => '#6a5acd',
        'slategray' => '#708090',
        'slategrey' => '#708090',
        'snow' => '#fffafa',
        'springgreen' => '#00ff7f',
        'steelblue' => '#4682b4',
        'tan' => '#d2b48c',
        'teal' => '#008080',
        'thistle' => '#d8bfd8',
        'tomato' => '#ff6347',
        'turquoise' => '#40e0d0',
        'violet' => '#ee82ee',
        'wheat' => '#f5deb3',
        'white' => '#ffffff',
        'whitesmoke' => '#f5f5f5',
        'yellow' => '#ffff00',
        'yellowgreen' => '#9acd32',
        'rebeccapurple' => '#663399',
        'transparent' => '#00000000'
    ];

    // color to name
    const NAMES_COLORS = [
        '#f0f8ff' => 'aliceblue',
        '#faebd7' => 'antiquewhite',
        '#00ffff' => 'aqua',
        '#7fffd4' => 'aquamarine',
        '#f0ffff' => 'azure',
        '#f5f5dc' => 'beige',
        '#ffe4c4' => 'bisque',
        '#000000' => 'black',
        '#ffebcd' => 'blanchedalmond',
        '#0000ff' => 'blue',
        '#8a2be2' => 'blueviolet',
        '#a52a2a' => 'brown',
        '#deb887' => 'burlywood',
        '#5f9ea0' => 'cadetblue',
        '#7fff00' => 'chartreuse',
        '#d2691e' => 'chocolate',
        '#ff7f50' => 'coral',
        '#6495ed' => 'cornflowerblue',
        '#fff8dc' => 'cornsilk',
        '#dc143c' => 'crimson',
        '#00ffff' => 'cyan',
        '#00008b' => 'darkblue',
        '#008b8b' => 'darkcyan',
        '#b8860b' => 'darkgoldenrod',
        '#a9a9a9' => 'darkgray',
        '#a9a9a9' => 'darkgrey',
        '#006400' => 'darkgreen',
        '#bdb76b' => 'darkkhaki',
        '#8b008b' => 'darkmagenta',
        '#556b2f' => 'darkolivegreen',
        '#ff8c00' => 'darkorange',
        '#9932cc' => 'darkorchid',
        '#8b0000' => 'darkred',
        '#e9967a' => 'darksalmon',
        '#8fbc8f' => 'darkseagreen',
        '#483d8b' => 'darkslateblue',
        '#2f4f4f' => 'darkslategray',
        '#2f4f4f' => 'darkslategrey',
        '#00ced1' => 'darkturquoise',
        '#9400d3' => 'darkviolet',
        '#ff1493' => 'deeppink',
        '#00bfff' => 'deepskyblue',
        '#696969' => 'dimgray',
        '#696969' => 'dimgrey',
        '#1e90ff' => 'dodgerblue',
        '#b22222' => 'firebrick',
        '#fffaf0' => 'floralwhite',
        '#228b22' => 'forestgreen',
        '#ff00ff' => 'fuchsia',
        '#dcdcdc' => 'gainsboro',
        '#f8f8ff' => 'ghostwhite',
        '#ffd700' => 'gold',
        '#daa520' => 'goldenrod',
        //    '#808080' => 'gray',
        '#808080' => 'grey',
        '#008000' => 'green',
        '#adff2f' => 'greenyellow',
        '#f0fff0' => 'honeydew',
        '#ff69b4' => 'hotpink',
        '#cd5c5c' => 'indianred',
        '#4b0082' => 'indigo',
        '#fffff0' => 'ivory',
        '#f0e68c' => 'khaki',
        '#e6e6fa' => 'lavender',
        '#fff0f5' => 'lavenderblush',
        '#7cfc00' => 'lawngreen',
        '#fffacd' => 'lemonchiffon',
        '#add8e6' => 'lightblue',
        '#f08080' => 'lightcoral',
        '#e0ffff' => 'lightcyan',
        '#fafad2' => 'lightgoldenrodyellow',
        '#d3d3d3' => 'lightgray',
        '#d3d3d3' => 'lightgrey',
        '#90ee90' => 'lightgreen',
        '#ffb6c1' => 'lightpink',
        '#ffa07a' => 'lightsalmon',
        '#20b2aa' => 'lightseagreen',
        '#87cefa' => 'lightskyblue',
        '#778899' => 'lightslategray',
        '#778899' => 'lightslategrey',
        '#b0c4de' => 'lightsteelblue',
        '#ffffe0' => 'lightyellow',
        '#00ff00' => 'lime',
        '#32cd32' => 'limegreen',
        '#faf0e6' => 'linen',
        '#ff00ff' => 'magenta',
        '#800000' => 'maroon',
        '#66cdaa' => 'mediumaquamarine',
        '#0000cd' => 'mediumblue',
        '#ba55d3' => 'mediumorchid',
        '#9370d8' => 'mediumpurple',
        '#3cb371' => 'mediumseagreen',
        '#7b68ee' => 'mediumslateblue',
        '#00fa9a' => 'mediumspringgreen',
        '#48d1cc' => 'mediumturquoise',
        '#c71585' => 'mediumvioletred',
        '#191970' => 'midnightblue',
        '#f5fffa' => 'mintcream',
        '#ffe4e1' => 'mistyrose',
        '#ffe4b5' => 'moccasin',
        '#ffdead' => 'navajowhite',
        '#000080' => 'navy',
        '#fdf5e6' => 'oldlace',
        '#808000' => 'olive',
        '#6b8e23' => 'olivedrab',
        '#ffa500' => 'orange',
        '#ff4500' => 'orangered',
        '#da70d6' => 'orchid',
        '#eee8aa' => 'palegoldenrod',
        '#98fb98' => 'palegreen',
        '#afeeee' => 'paleturquoise',
        '#d87093' => 'palevioletred',
        '#ffefd5' => 'papayawhip',
        '#ffdab9' => 'peachpuff',
        '#cd853f' => 'peru',
        '#ffc0cb' => 'pink',
        '#dda0dd' => 'plum',
        '#b0e0e6' => 'powderblue',
        '#800080' => 'purple',
        '#ff0000' => 'red',
        '#bc8f8f' => 'rosybrown',
        '#4169e1' => 'royalblue',
        '#8b4513' => 'saddlebrown',
        '#fa8072' => 'salmon',
        '#f4a460' => 'sandybrown',
        '#2e8b57' => 'seagreen',
        '#fff5ee' => 'seashell',
        '#a0522d' => 'sienna',
        '#c0c0c0' => 'silver',
        '#87ceeb' => 'skyblue',
        '#6a5acd' => 'slateblue',
        '#708090' => 'slategray',
        '#708090' => 'slategrey',
        '#fffafa' => 'snow',
        '#00ff7f' => 'springgreen',
        '#4682b4' => 'steelblue',
        '#d2b48c' => 'tan',
        '#008080' => 'teal',
        '#d8bfd8' => 'thistle',
        '#ff6347' => 'tomato',
        '#40e0d0' => 'turquoise',
        '#ee82ee' => 'violet',
        '#f5deb3' => 'wheat',
        '#ffffff' => 'white',
        '#f5f5f5' => 'whitesmoke',
        '#ffff00' => 'yellow',
        '#9acd32' => 'yellowgreen',
        '#663399' => 'rebeccapurple',
        '#00000000' => 'transparent'
    ];

    public static function hex2rgba_values($hex)
    {

        $hex = static::expand($hex);

        $colors = static::COLORS_NAMES;

        if (isset($colors[$hex])) {

            $hex = static::COLORS_NAMES[$hex];
        }

        switch (strlen($hex)) {

            case 4;

                return [hexdec($hex[1] . $hex[1]), hexdec($hex[2] . $hex[2]), hexdec($hex[3] . $hex[3])];

            case 5;

                return [hexdec($hex[1] . $hex[1]), hexdec($hex[2] . $hex[2]), hexdec($hex[3] . $hex[3]), ValueNumber::compress(round(hexdec($hex[4] . $hex[4]) / 255, 2))];

            case 7;

                return [hexdec($hex[1] . $hex[2]), hexdec($hex[3] . $hex[4]), hexdec($hex[5] . $hex[6])];

            case 9;

                return [hexdec($hex[1] . $hex[2]), hexdec($hex[3] . $hex[4]), hexdec($hex[5] . $hex[6]), ValueNumber::compress(round(hexdec($hex[7] . $hex[8]) / 255, 2))];
        }

        return [];
    }

    public static function cmyk2rgba_values($c, $m, $y, $k, $a = null)
    {

        $rgb = [
            round(255 * (1 - min(1, $c * (1 - $k) + $k))),
            round(255 * (1 - min(1, $m * (1 - $k) + $k))),
            round(255 * (1 - min(1, $y * (1 - $k) + $k)))
        ];

        if (!is_null($a) && $a != 1 && $a !== '') {

            $rgb[] = $a;
        }

        return $rgb;
    }

    public static function rgba_values2hex(array $rgb)
    {

        $na = !isset($rgb[3]) || is_null($rgb[3]) || $rgb[3] == 1 || $rgb[3] === '';
        $hex = sprintf($na ? "#%02x%02x%02x" : "#%02x%02x%02x%02x", $rgb[0], $rgb[1], $rgb[2], $na ? 255 : round(255 * $rgb[3]));

        $colors = static::NAMES_COLORS;
        if (isset($colors[$hex]) && strlen($hex) >= strlen(static::NAMES_COLORS[$hex])) {

            return static::NAMES_COLORS[$hex];
        }

        return $hex;
    }

    public static function hwb2rgba_values($hue, $white, $black, $a = null)
    {

        $rgb = static::hsl2rgb_values($hue, 1, .5);

        for ($i = 0; $i < 3; $i++) {

            $rgb[$i] *= (1 - $white - $black);
            $rgb[$i] += $white;
        }

        if (!is_null($a) && $a != 1 && $a !== '') {

            $rgb[] = $a;
        }

        return $rgb;
    }

    public static function rgba2hsl_values($r, $g, $b, $a = null)
    {

        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max([$r, $g, $b]);
        $min = min([$r, $g, $b]);

        $h = $s = 0;
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0; // achromatic
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }

            $h /= 6;
        }

        $s = ValueNumber::compress(round($s * 100));
        $l = ValueNumber::compress(round($l * 100));

        $values = [ValueNumber::compress(round($h * 360)), $s == 0 ? 0 : $s . '%', $l == 0 ? 0 : $l . '%'];

        if (!is_null($a) && $a != 1 && $a !== '') {

            $values[] = $a;
        }

        return $values;
    }

    public static function hsl2rgb_values($h, $s, $l, $a = null)
    {

        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);

        if ($v > 0) {

            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            switch ($sextant) {
                case 0:
                    $r = $v;
                    $g = $mid1;
                    $b = $m;
                    break;
                case 1:
                    $r = $mid2;
                    $g = $v;
                    $b = $m;
                    break;
                case 2:
                    $r = $m;
                    $g = $v;
                    $b = $mid1;
                    break;
                case 3:
                    $r = $m;
                    $g = $mid2;
                    $b = $v;
                    break;
                case 4:
                    $r = $mid1;
                    $g = $m;
                    $b = $v;
                    break;
                case 5:
                    $r = $v;
                    $g = $m;
                    $b = $mid2;
                    break;
            }
        }

        $rgba = [round($r * 255), round($g * 255), round($b * 255)];

        if (!is_null($a) && $a != 1) {

            $rgba[] = $a;
        }

        return $rgba;
    }

    public static function rgba2hwb_values($r, $g, $b, $a = null, $fallback = 0)
    {

        $r *= 100 / 255;
        $g *= 100 / 255;
        $b *= 100 / 255;

        $hue = static::rgb2hue($r, $g, $b, $fallback);
        $whiteness = static::rgb2whiteness($r, $g, $b);
        $value = static::rgb2value($r, $g, $b);
        $blackness = 100 - $value;

        $hue = round($hue);
        $blackness = $blackness == 0 ? 0 : Number::compress($blackness);

        $result = [Number::compress($hue), $whiteness == 0 ? 0 : Number::compress(round($whiteness)) . '%', $blackness == 0 ? 0 : Number::compress(round($blackness)) . '%'];

        if (!is_null($a) && $a != 1 && $a !== '') {

            $result[] = Number::compress(round($a / 255, 2));
        }

        return $result;
    }

    public static function rgba2cmyk_values($r, $g, $b, $a = null, $fallback = 0)
    {

        $r /= 255;
        $g /= 255;
        $b /= 255;

        $k = 1 - max($r, $g, $b);

        if ($k == 1) {

            $c = $m = $y = 0;
        } else {

            $div = 1 - $k;

            $c = (1 - $r - $k) / $div;
            $m = (1 - $g - $k) / $div;
            $y = (1 - $b - $k) / $div;
        }

        $result = [
            $c == 0 ? 0 : Number::compress(round(100 * $c)) . '%',
            $m == 0 ? 0 : Number::compress(round(100 * $m)) . '%',
            $y == 0 ? 0 : Number::compress(round(100 * $y)) . '%',
            $k == 0 ? 0 : Number::compress(round(100 * $k)) . '%'];

        if (!is_null($a) && $a != 1 && $a !== '') {

            $result[] = $a;
        }

        return $result;
    }

    /*
     // rgba <=> lab <=> lc
    public static function rgba2lab($r, $g, $b, $a = null)
    {

        $xyz = static::rgba2xyz($r, $g, $b);

        return static::xyz2lab($xyz[0], $xyz[1], $xyz[2], $a);
    }

    public static function rgba2lch($r, $g, $b, $a = null)
    {

        $xyz = static::rgba2xyz($r, $g, $b);
        $lab = static::xyz2lab($xyz[0], $xyz[1], $xyz[2]);
        $lch = static::lab2lch($lab[0], $lab[1], $lab[2]);

        if (!is_null($a) && $a != 1) {

            $lch[] = $a;
        }

        return $lch;
    }

    public static function rgba2xyz($r, $g, $b, $a = null)
    {

        $r = static::pivotRgb($r / 255);
        $g = static::pivotRgb($g / 255);
        $b = static::pivotRgb($b / 255);

        // Observer. = 2°, Illuminant = D65
        $xyz = [
            $r * 0.4124 + $g * 0.3576 + $b * 0.1805,
            $r * 0.2126 + $g * 0.7152 + $b * 0.0722,
            $r * 0.0193 + $g * 0.1192 + $b * 0.9505,
        ];

        if (!is_null($a) && $a != 1) {

            $xyz[] = $a;
        }

        return $xyz;
    }

    public static function xyz2lab($x, $y, $z, $a = null)
    {

        $x1 = static::pivotXyz($x / static::REF_X);
        $y1 = static::pivotXyz($y / static::REF_Y);
        $z1 = static::pivotXyz($z / static::REF_Z);

        $lab = [116 * $y1 - 16, 500 * ($x1 - $y1), 200 * ($y1 - $z1)];

        if (!is_null($a) && $a != 1) {

            $lab[] = $a;
        }

        return $lab;
    }

    public static function lab2xyz($L, $A, $B, $a = null)
    {

        $var_Y = ($L + 16) / 116;
        $var_X = $A / 500 + $var_Y;
        $var_Z = $var_Y - $B / 200;

        $Y_3 = $var_Y ** 3;
        $X_3 = $var_X ** 3;
        $Z_3 = $var_Z ** 3;

        $var_Y = $Y_3 > 0.008856 ? $Y_3 : ($var_Y - 16 / 116) / 7.787;
        $var_X = $X_3 > 0.008856 ? $X_3 : ($var_X - 16 / 116) / 7.787;
        $var_Z = $Z_3 > 0.008856 ? $Z_3 : ($var_Z - 16 / 116) / 7.787;

        $xyz = [$var_X * static::REF_X, $var_Y * static::REF_Y, $var_Z * static::REF_Z];

        if (!is_null($a) && $a != 1) {

            $xyz[] = $a;
        }

        return $xyz;
    }

    public static function xyz2rgb($X, $Y, $Z, $a = null)
    {
        //X, Y and Z input refer to a D65/2° standard illuminant.
        //sR, sG and sB (standard RGB) output range = 0 ÷ 255

        $var_X = $X / 100;
        $var_Y = $Y / 100;
        $var_Z = $Z / 100;

        $var_R = $var_X * 3.2406 + $var_Y * -1.5372 + $var_Z * -0.4986;
        $var_G = $var_X * -0.9689 + $var_Y * 1.8758 + $var_Z * 0.0415;
        $var_B = $var_X * 0.0557 + $var_Y * -0.204 + $var_Z * 1.057;

        $var_R = $var_R > 0.0031308 ? 1.055 * ($var_R ** (1 / 2.4)) - 0.055 : 12.92 * $var_R;
        $var_G = $var_G > 0.0031308 ? 1.055 * ($var_G ** (1 / 2.4)) - 0.055 : 12.92 * $var_G;
        $var_B = $var_B > 0.0031308 ? 1.055 * ($var_B ** (1 / 2.4)) - 0.055 : 12.92 * $var_B;

        return [round($var_R * 255), round($var_G * 255), round($var_B * 255)];
    }

    public static function lab2rgba($L, $A, $B, $a = null)
    {

        $xyz = static::lab2xyz($L, $A, $B);
        return static::xyz2rgb($xyz[0], $xyz[1], $xyz[2], $a);
    }

    public static function lab2lch($L, $A, $B, $a = null)
    {

        $H = atan2($B, $A); //Quadrant by signs

        $H = $H > 0 ? $H * 180 / M_PI : 360 - abs($H) * 180 / M_PI;

        $C = sqrt($A * $A + $B * $B);

        $lch = [$L, $C, $H];

        if (!is_null($a) && $a != 1) {

            $lch[] = $a;
        }

        return $lch;
    }

    public static function lch2lab($L, $C, $H, $a = null)
    {

        $var_H = ($H * M_PI) / 180;

        $A = cos($var_H) * $C;
        $B = sin($var_H) * $C;

        $lab = [$L, $A, $B];

        if (!is_null($a) && $a != 1) {

            $lab[] = $a;
        }

        return $lab;
    }

    public static function lch2rgba($L, $C, $H, $a = null) {

        $lab = static::lch2lab($L, $C, $H);
        $xyz = static::lab2xyz($lab[0], $lab[1], $lab[2]);
        $rgba = static::xyz2rgb($xyz[0], $xyz[1], $xyz[2]);

        if (!is_null($a) && $a != 1) {

            $rgba[] = $a;
        }

        return $rgba;
    }

    protected static function pivotRgb($n)
    {

        return ($n > 0.04045 ? (($n + 0.055) / 1.055) ** 2.4 : $n / 12.92) * 100;
    }

    protected static function pivotXyz($n)
    {

        return $n > 0.008856 ? $n ** (1 / 3) : 7.787 * $n + 16 / 116;
    }
    */

    protected static function rgb2hue($r, $g, $b, $fallback = 0)
    {

        $value = static::rgb2value($r, $g, $b);
        $whiteness = static::rgb2whiteness($r, $g, $b);

        $delta = $value - $whiteness;

        if ($delta > 0) {

            // calculate segment
            $segment = $value === $r ? ($g - $b) / $delta : ($value === $g
                ? ($b - $r) / $delta
                : ($r - $g) / $delta);

            // calculate shift
            $shift = $value === $r ? $segment < 0
                ? 360 / 60
                : 0 / 60 : ($value === $g
                ? 120 / 60
                : 240 / 60);

            // calculate hue
            return ($segment + $shift) * 60;
        }

        return $fallback;
    }

    protected static function rgb2value($r, $g, $b)
    {

        return max($r, $g, $b);
    }

    protected static function rgb2whiteness($r, $g, $b)
    {

        return min($r, $g, $b);
    }

    public static function expand($color)
    {

        $color = strtolower($color);

        if ($color[0] != '#') {

            return $color;
        }

        if (strlen($color) >= 7) {

            if (strlen($color) > 7 && $color[7] . $color[8] == 'ff') {

                return substr($color, 0, 7);
            }

            return $color;
        }

        $expanded = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];

        if (strlen($color) == 5) {

            if ($color[4] != 'f') {

                $expanded .= $color[4] . $color[4];
            }
        }

        return $expanded;
    }

    public static function shorten($str)
    {

        $regExp = '\#([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3';

        $color = strtolower($str);

        $colors = static::COLORS_NAMES;

        if (isset($colors[$color])) {

            $str = $color;
            $color = static::COLORS_NAMES[$color];
        }

        if (strlen($color) == 5 && $color[4] == 'f') {

            $color = substr($color, 0, 4);
        }

        if (strlen($color) == 9) {

            $regExp .= '([0-9a-f])\4';
        }

        if (preg_match('#' . $regExp . '#', $color, $matches)) {

            $color = '#' . $matches[1] . $matches[2] . $matches[3];

            if (isset($matches[4]) && $matches[4] != 'f') {

                $color .= $matches[4];
            }

            //    return $color;
        }

        return strlen($str) <= strlen($color) ? $str : $color;
    }}
