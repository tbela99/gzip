<?php

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip *
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */


defined('_JEXEC') or die;

spl_autoload_register(function ($name) {

    switch(strtolower($name)):

        case 'patchwork\jsqueeze':

            require __DIR__.'/lib/JSqueeze.php';
            break;

        case 'patchwork\cssmin':

            require __DIR__.'/lib/cssmin.php';
            break;

        case 'gzip\gziphelper':

            require __DIR__.'/helper.php';
            break;

        default:

            $file = __DIR__.'/lib/'.str_replace('\\', '/', $name).'.php';

            if(is_file($file)) {
                    
                require $file; 
            }

            break;

    endswitch;
});
