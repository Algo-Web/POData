<?php


namespace ODataProducer\ObjectModel;

use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\Providers\Metadata\Type\Boolean;

/**
 * Class ODataFeed
 * @package ODataProducer\ObjectModel
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