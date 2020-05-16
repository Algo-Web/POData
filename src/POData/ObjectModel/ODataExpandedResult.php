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
     * @var ODataContainerBase|null
     */
    private $data;

    /**
     * ODataExpandedResult constructor.
     *
     * @param ODataContainerBase|null $data
     */
    public function __construct(ODataContainerBase $data = null)
    {
        $this->data = $data;
    }

    /**
     * @return ODataEntry|null
     */
    public function getEntry(): ?ODataEntry
    {
        return $this->data instanceof ODataEntry ? $this->data : null;
    }

    /**
     * @param  ODataEntry|null     $entry
     * @return ODataExpandedResult
     */
    public function setEntry(?ODataEntry $entry): ODataExpandedResult
    {
        $this->data = $entry;
        return $this;
    }

    /**
     * @return ODataFeed|null
     */
    public function getFeed(): ?ODataFeed
    {
        return $this->data instanceof ODataFeed ? $this->data : null;
    }

    /**
     * @param  ODataFeed|null      $feed
     * @return ODataExpandedResult
     */
    public function setFeed(?ODataFeed $feed): ODataExpandedResult
    {
        $this->data = $feed;
        return $this;
    }

    public function getData(): ODataContainerBase
    {
        return $this->data;
    }
}
