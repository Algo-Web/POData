<?php
/**
 * Class representing Navigation link
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
 * Type to represent an OData Link.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataLink
{
    /**
     * 
     * Name of the link. This becomes last segment of rel attribute value.
     * @var string
     */
    public $name;
    /**
     * 
     * Title of the link. This become value of title attribute
     * @var string
     */
    public $title;
    /**
     * 
     * Type of link
     * @var string
     */
    public $type;
    /**
     * 
     * Url to the navigation property. This become value of href attribute
     * @var string
     */
    public $url;
    /**
     * 
     * Checks is Expand result contains single entity or collection of 
     * entities i.e. feed.
     * 
     * @var boolean
     */
    public $isCollection;
    /**
     * 
     * The expanded result. This become the inline content of the link
     * @var ODataEntry/ODataFeed
     */
    public $expandedResult;
    /**
     * 
     * True if Link is Expanded, False if not.
     * @var Boolean
     */
    public $isExpanded;

    /**
     * Constructor for Initialization of Link. 
     */
    function __construct()
    {
    }
}
?>