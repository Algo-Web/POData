<?php
/**
 * Represents value of a bag (collection) property. Bag can be of two types:
 *  (1) Primitive Bag
 *  (2) Complex Bag
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
 * Represents value of a bag (collection) property
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataBagContent
{
    /**
     * The type name of the element
     * @var string
     */
    public $type;
    /**
     * 
     * Represents elements of the bag.
     * @var array<string/PropertyContent>
     */
    public $propertyContents;

    /**
     * Constructs a new instance of ODataBagContent
     */
    public function __construct()
    {
    }
}
?>