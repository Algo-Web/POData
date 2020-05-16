<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataFeed.
 */
class ODataFeed extends ODataContainerBase
{

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
     * ODataFeed constructor.
     * @param string       $id
     * @param ODataTitle   $title
     * @param ODataLink    $selfLink
     * @param int          $rowCount
     * @param ODataLink    $nextPageLink
     * @param ODataEntry[] $entries
     * @param string       $updated
     * @param string       $baseURI
     */
    public function __construct(string $id = null, ODataTitle $title = null, ODataLink $selfLink = null, int $rowCount = null, ODataLink $nextPageLink = null, array $entries = [], string $updated = null, string $baseURI = null)
    {
        parent::__construct($id, $title, $updated, $baseURI);
        $this->selfLink     = $selfLink;
        $this->rowCount     = $rowCount;
        $this->nextPageLink = $nextPageLink;
        $this->entries      = $entries;
    }

    /**
     * @return ODataLink
     */
    public function getNextPageLink()
    {
        return $this->nextPageLink;
    }

    /**
     * @param ODataLink $nextPageLink
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
     * @return ODataEntry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @param ODataEntry[] $entries
     */
    public function setEntries(array $entries)
    {
        $this->entries = $entries;
    }
}
