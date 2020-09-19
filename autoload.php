<?php

/**
 * @package     GZip Plugin
 * @subpackage  System.Gzip
 * @copyright   Copyright (C) 2005 - 2018 Thierry Bela.
 *
 * dual licensed
 *
 * @license     LGPL v3
 * @license     MIT License
 */


defined('_JEXEC') or die;

require __DIR__.'/vendor/autoload.php';

spl_autoload_register(function ($name) {

	if (strpos($name, '\\') === 0) {

		$name = substr($name, 1);
	}

	$name = str_replace('\\', '/', $name);

    switch(strtolower($name)):

        case 'patchwork/jsqueeze':

            require __DIR__.'/lib/JSqueeze.php';
            break;

//        case 'patchwork/cssmin':
//
//            require __DIR__.'/lib/cssmin.php';
//            break;

        case 'gzip/gziphelper':

            require __DIR__.'/helper.php';
            break;

        default:

        	if (stripos($name, 'gzip/helpers/') === 0) {

				$path = preg_replace('#Helper$#i', '', substr($name, 13));

				$file = __DIR__.'/helpers/'.$path.'.php';

        		if (is_file($file)) {

					require $file;
					break;
				}
			}

			$file = __DIR__.'/lib/'.$name.'.php';

            if(is_file($file)) {
                    
                require $file;
            }

            break;

    endswitch;
});
