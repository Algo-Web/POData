<?php
/** 
 * Auto loader class for loading classes during compile time.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Common;
/** 
 * Auto loader class
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ClassAutoLoader
{
    const FILEEXTENSION = '.php';

    /**
     * @var ODataProducer\Common\ClassAutoLoader
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
?>