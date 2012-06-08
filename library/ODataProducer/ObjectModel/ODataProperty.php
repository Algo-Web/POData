<?php
/**
 * Represents a property that comes under "m:properties" node or entry 
 * or under complex property
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\ObjectModel;
/**
 * Type to represent OData property.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataProperty
{
    /**
     * 
     * The name of the property
     * @var string
     */
    public $name;
    /**
     * 
     * The property type name
     * @var string
     */
    public $typeName;
    /**
     * 
     * The property attribute extensions
     * @var array<XMLAttribute>
     */
    public $attributeExtensions;
    /**
     * 
     * The value of the property. 
     * @var string/ODataPropertyContent/ODataBagContent
     */
    public $value;

    /**
     * Constructor for Initialization of Odata Property.
     */
    function __construct()
    {
    }
}
?>