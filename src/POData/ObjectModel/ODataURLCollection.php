<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataURLCollection represent collection of links for $links request.
 */
class ODataURLCollection
{
    /**
     * Array of ODataURL.
     *
     * @var ODataURL[]
     */
    private $urls = [];
    /**
     * Enter URL to next page, if pagination is enabled.
     *
     * @var ODataLink|null
     */
    public $nextPageLink = null;
    /**
     * Enter url Count if inlineCount is requested.
     *
     * @var int|null
     */
    public $count = null;

    /**
     * ODataURLCollection constructor.
     * @param ODataURL[] $urls
     * @param ODataLink $nextPageLink
     * @param int $count
     */
    public function __construct(array $urls = [], ODataLink $nextPageLink = null, int $count = null)
    {
        $this->urls = $urls;
        $this->nextPageLink = $nextPageLink;
        $this->count = $count;
    }
    /**
     * @return ODataURL[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * @param ODataURL[] $urls
     * @return ODataURLCollection
     */
    public function setUrls(array $urls): ODataURLCollection
    {
        $this->urls = $urls;
        return $this;
    }

    /**
     * @return ODataLink|null
     */
    public function getNextPageLink(): ?ODataLink
    {
        return $this->nextPageLink;
    }

    /**
     * @param ODataLink|null $nextPageLink
     * @return ODataURLCollection
     */
    public function setNextPageLink(?ODataLink $nextPageLink): ODataURLCollection
    {
        $this->nextPageLink = $nextPageLink;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * @param int|null $count
     * @return ODataURLCollection
     */
    public function setCount(?int $count): ODataURLCollection
    {
        $this->count = $count;
        return $this;
    }
}
