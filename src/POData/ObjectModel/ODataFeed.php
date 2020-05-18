<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataFeed.
 */
class ODataFeed extends ODataContainerBase
{
    /**
     * Row count, in case of $inlinecount option.
     *
     * @var int
     */
    private $rowCount = null;

    /**
     * Enter URL to next page, if pagination is enabled.
     *
     * @var ODataNextPageLink
     */
    private $nextPageLink = null;
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
    public function __construct(string $id = null, ODataTitle $title = null, ODataLink $selfLink = null, int $rowCount = null, ODataNextPageLink $nextPageLink = null, array $entries = [], string $updated = null, string $baseURI = null)
    {
        parent::__construct($id, $title, $selfLink, $updated, $baseURI);
        $this
            ->setRowCount($rowCount)
            ->setNextPageLink($nextPageLink)
            ->setEntries($entries);
    }


    /**
     * @return int
     */
    public function getRowCount(): ?int
    {
        return $this->rowCount;
    }

    /**
     * @param int $rowCount
     * @return ODataFeed
     */
    public function setRowCount(?int $rowCount): ODataFeed
    {
        $this->rowCount = $rowCount;
        return $this;
    }

    /**
     * @return ODataLink
     */
    public function getNextPageLink(): ?ODataLink
    {
        return $this->nextPageLink;
    }

    /**
     * @param  ODataLink|null $nextPageLink
     * @return ODataFeed
     */
    public function setNextPageLink(?ODataLink $nextPageLink): self
    {
        $this->nextPageLink = null === $nextPageLink || $nextPageLink->isEmpty() ? null : $nextPageLink;
        return $this;
    }

    /**
     * @return ODataEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param  ODataEntry[] $entries
     * @return ODataFeed
     */
    public function setEntries(array $entries): self
    {
        $this->entries = $entries;
        return $this;
    }
}
