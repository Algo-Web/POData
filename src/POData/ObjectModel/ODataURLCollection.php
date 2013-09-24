<?php

namespace POData\ObjectModel;


/**
 * Class ODataURLCollection represent collection of links for $links request.
 * @package POData\ObjectModel
 */
class ODataURLCollection
{
    /**
     * 
     * Array of ODataURL
     * @var ODataURL[]
     */
    public $urls = array();
    /**
     * 
     * Enter URL to next page, if pagination is enabled
     * @var ODataLink
     */
    public $nextPageLink = null;
    /**
     * 
     * Enter url Count if inlineCount is requested
     * @var integer
     */
    public $count = null;

}