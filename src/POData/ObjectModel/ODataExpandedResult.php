<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 5:41 AM.
 */
namespace POData\ObjectModel;

/**
 * Class ODataExpandedResult.
 * @package POData\ObjectModel
 */
class ODataExpandedResult
{
    /**
     * Term.
     *
     * @var ODataEntry|null
     */
    public $entry;

    /**
     * Scheme.
     *
     * @var ODataFeed|null
     */
    public $feed;

    /**
     * ODataExpandedResult constructor.
     *
     * @param ODataEntry|null $entry
     * @param ODataFeed|null  $feed
     */
    public function __construct(ODataEntry $entry = null, ODataFeed $feed = null)
    {
        $this->entry = $entry;
        $this->feed  = $feed;
    }

    /**
     * @return ODataEntry|null
     */
    public function getEntry(): ?ODataEntry
    {
        return $this->entry;
    }

    /**
     * @param ODataEntry|null $entry
     * @return ODataExpandedResult
     */
    public function setEntry(?ODataEntry $entry): ODataExpandedResult
    {
        $this->entry = $entry;
        return $this;
    }

    /**
     * @return ODataFeed|null
     */
    public function getFeed(): ?ODataFeed
    {
        return $this->feed;
    }

    /**
     * @param ODataFeed|null $feed
     * @return ODataExpandedResult
     */
    public function setFeed(?ODataFeed $feed): ODataExpandedResult
    {
        $this->feed = $feed;
        return $this;
    }
}
