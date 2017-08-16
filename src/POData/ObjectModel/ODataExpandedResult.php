<?php
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 5:41 AM
 */

namespace POData\ObjectModel;


class ODataExpandedResult
{
    /**
     * Term.
     *
     * @var ODataEntry
     */
    public $entry;

    /**
     * Scheme
     *
     * @var ODataFeed
     */
    public $feed;

    public function __construct(ODataEntry $Entry = null,ODataFeed $Feed = null)
    {
        $this->entry = $Entry;
        $this->feed = $Feed;
    }
}