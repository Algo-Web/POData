<?php
/** 
 * A type to hold navigation information.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\ObjectModel;
use ODataProducer\Providers\Metadata\ResourceProperty;
/**
 * A type to hold navigation information.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class NavigationPropertyInfo
{
    public $resourceProperty;
    public $expanded;
    public $value;

    /**
     * Constructs a new instance of NavigationPropertyInfo
     * 
     * @param ResourceProperty &$resourceProperty Metadata of the 
     *                                            navigation property.
     * @param boolean          $expanded          Whether the navigation is expanded
     *                                            or not.   
     */
    public function __construct(ResourceProperty &$resourceProperty, $expanded)
    {
        $this->resourceProperty = $resourceProperty;
        $this->expanded = $expanded;
        $this->value = null;
    }
}
?>