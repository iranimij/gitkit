<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita2322ad25a27d5798acbe2c321419ec1
{
    public static $files = array (
        'c504050e8f9dd8260b9dceab0e317aeb' => __DIR__ . '/..' . '/iranimij/wp-options-manager/src/helper-functions.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Iranimij\\WpOptionsManager\\Wp_Options_Manager' => __DIR__ . '/..' . '/iranimij/wp-options-manager/src/wp-options-manager.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInita2322ad25a27d5798acbe2c321419ec1::$classMap;

        }, null, ClassLoader::class);
    }
}
