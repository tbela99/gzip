<?php
/**
 * Work with JavaScript/CSS Source Map
 *
 * @package axy\sourcemap
 * @author Oleg Grigoriev <go.vasac@gmail.com>
 * @license https://raw.github.com/axypro/sourcemap/master/LICENSE MIT
 * @link https://github.com/axypro/sourcemap repository
 * @link https://packagist.org/packages/axy/sourcemap composer
 * @link https://github.com/axypro/sourcemap/blob/master/README.md documentation
 * @uses PHP5.4+
 */

namespace axy\sourcemap;

if (!is_file(__DIR__.'/vendor/autoload.php')) {
    throw new \LogicException('Please: composer install');
}

require_once(__DIR__.'/vendor/autoload.php');
