<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataFeed.
 */
class ODataFeed
{
    /**
     * Feed iD.
     *
     * @var string
     */
    public $id;
    /**
     * Feed title.
     *
     * @var ODataTitle
     */
    public $title;
    /**
     * Feed self link.
     *
     * @var ODataLink
     */
    public $selfLink;
    /**
     * Row count, in case of $inlinecount option.
     *
     * @var int
     */
    public $rowCount = null;
    /**
     * Enter URL to next page, if pagination is enabled.
     *
     * @var ODataLink
     */
    public $nextPageLink = null;
    /**
     * Collection of entries under this feed.
     *
     * @var ODataEntry[]
     */
    public $entries = [];

    /**
     * Last updated timestamp.
     *
     * @var string
     */
    public $updated;

    /**
     * Service Base URI.
     *
     * @var string
     */
    public $baseURI;
    /**
     * @return \POData\ObjectModel\ODataLink
     */
    public function getNextPageLink()
    {
        return $this->nextPageLink;
    }

    /**
     * @param \POData\ObjectModel\ODataLink $nextPageLink
     */
    public function setNextPageLink(ODataLink $nextPageLink)
    {
        foreach (get_object_vars($nextPageLink) as $property) {
            if (null !== $property) {
                $this->nextPageLink = $nextPageLink;
                return;
            }
        }
    }

    /**
     * @return \POData\ObjectModel\ODataEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @param \POData\ObjectModel\ODataEntry[] $entries
     */
    public function setEntries(array $entries)
    {
        $this->entries = $entries;
    }
}
