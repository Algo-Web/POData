<?php

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
    public $urls = [];
    /**
     * Enter URL to next page, if pagination is enabled.
     *
     * @var ODataLink
     */
    public $nextPageLink = null;
    /**
     * Enter url Count if inlineCount is requested.
     *
     * @var int
     */
    public $count = null;
}
