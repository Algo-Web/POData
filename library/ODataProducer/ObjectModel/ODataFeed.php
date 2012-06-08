<?php
/** 
 * Representation of a feed, i.e. collection of entities.
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
use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\Providers\Metadata\Type\Boolean;
/**
 * Type to represent an OData feed.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataFeed
{
    /**
     * 
     * Feed iD
     * @var string
     */
    public $id;
    /**
     * 
     * Feed title
     * @var string
     */
    public $title;
    /**
     * 
     * Feed self link
     * @var ODataLink
     */
    public $selfLink;
    /**
     * 
     * Row count, in case of $inlinecount option 
     * @var int
     */
    public $rowCount;
    /**
     * 
     * Enter URL to next page, if pagination is enabled
     * @var ODataLink
     */
    public $nextPageLink;
    /**
     * 
     * Collection of entries under this feed
     * @var array<ODataEntry>
     */
    public $entries;
    /**
     * 
     * Boolean value which check for feed is top level or not.
     * @var Boolean
     */
    public $isTopLevel;

    /**
     * Constructor for Initialization of Feed.
     */
    function __construct()
    {
        $this->entries = array();
        $this->rowCount = null;
        $this->nextPageLink = null;
    }
}
?>