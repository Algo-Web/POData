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
     * @var ODataNextPageLink|null
     */
    private $nextPageLink = null;
    /**
     * Enter url Count if inlineCount is requested.
     *
     * @var int|null
     */
    private $count = null;

    /**
     * ODataURLCollection constructor.
     * @param ODataURL[] $urls
     * @param ODataNextPageLink  $nextPageLink
     * @param int        $count
     */
    public function __construct(array $urls = [], ODataNextPageLink $nextPageLink = null, int $count = null)
    {
        $this
            ->setUrls($urls)
            ->setNextPageLink($nextPageLink)
            ->setCount($count);
    }
    /**
     * @return ODataURL[]
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * @param  ODataURL[]         $urls
     * @return ODataURLCollection
     */
    public function setUrls(array $urls): ODataURLCollection
    {
        $this->urls = $urls;
        return $this;
    }

    /**
     * @return ODataNextPageLink|null
     */
    public function getNextPageLink(): ?ODataNextPageLink
    {
        return $this->nextPageLink;
    }

    /**
     * @param  ODataNextPageLink|null     $nextPageLink
     * @return ODataURLCollection
     */
    public function setNextPageLink(?ODataNextPageLink $nextPageLink): ODataURLCollection
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
     * @param  int|null           $count
     * @return ODataURLCollection
     */
    public function setCount(?int $count): ODataURLCollection
    {
        $this->count = $count;
        return $this;
    }
}
