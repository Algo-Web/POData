<?php
/** 
 * Class represents properties of a Complex type or entity element instance.
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
use ODataProducer\Providers\Metadata\Type\Boolean;
/**
 * Type represents properties of a Complex type or entity element instance.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataPropertyContent
{
    /**
     * 
     * The collection of properties
     * @var array<odataProperty>
     */
    public $odataProperty;
    /**
     * 
     * To check if top level or not
     * @var Boolean
     */
    public $isTopLevel;

    /**
     * Constructs a new instance of ODataPropertyContent
     * 
     * @param Boolean $isTopLevel Top level or not
     * 
     * @return void
     */
    public function __construct($isTopLevel = false)
    {
        $this->isTopLevel = $isTopLevel;
        $this->odataProperty = array();
    }
}
?>