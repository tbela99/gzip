<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4c2aa7183d3029549822bb4d1bfaaf34
{
    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Elphin\\IcoFileLoader\\' => 21,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Elphin\\IcoFileLoader\\' => 
        array (
            0 => __DIR__ . '/..' . '/lordelph/icofileloader/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4c2aa7183d3029549822bb4d1bfaaf34::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4c2aa7183d3029549822bb4d1bfaaf34::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
