<?php
/**
 * Represents an XML attribute
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
 * Type to represent XML attribute.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class XMLAttribute
{
    /**
     * 
     * The namespace prefix
     * @var string
     */
    public $nsPrefix;
    /**
     * 
     * The namespace URI. 
     * @var string
     */
    public $nsUri;
    /**
     * 
     * The attribute name
     * @var string
     */
    public $name;
    /**
     * 
     * The attribute value
     * @var string
     */
    public $Value;
}
?>