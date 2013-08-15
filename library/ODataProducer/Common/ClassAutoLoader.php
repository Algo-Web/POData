<?php

namespace ODataProducer\Common;

/**
 * Class ClassAutoLoader
 * @package ODataProducer\Common
 */
class ClassAutoLoader
{
    const FILEEXTENSION = '.php';

    /**
     * @var ClassAutoLoader
     */
    protected static $classAutoLoader;

    /**
     * Register class loader call back
     * 
     * @return void
     */
    public static function register()
    {
        if (self::$classAutoLoader == null) {
            self::$classAutoLoader = new ClassAutoLoader();
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                spl_autoload_register(
                    array(self::$classAutoLoader, 'autoLoadWindows')
                );
            } else {
                spl_autoload_register(
                    array(self::$classAutoLoader, 'autoLoadNonWindows')
                );
            }
        }
    }

    /**
     * Un-Register class loader call back
     * 
     * @return void
     */
    public static function unRegister()
    {
        if (self::$classAutoLoader != null) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                spl_autoload_unregister(
                    array(self::$classAutoLoader, 'autoLoadWindows')
                );
            } else {
                spl_autoload_unregister(
                    array(self::$classAutoLoader, 'autoLoadNonWindows')
                );
            }
            
        }
    }

    /**
     * Callback for class autoloading in Windows environment.
     * 
     * @param string $classPath Path of the class to load
     * 
     * @return void
     */
    public function autoLoadWindows($classPath)
    {
        include_once $classPath . self::FILEEXTENSION;
    }

    /**
     * Callback for class autoloading in linux flavours.
     * 
     * @param string $classPath Path of the class to load
     * 
     * @return void
     */
    public function autoLoadNonWindows($classPath)
    {
        $classPath = str_replace("\\", "/", $classPath);
        include_once $classPath . self::FILEEXTENSION;
    }
}