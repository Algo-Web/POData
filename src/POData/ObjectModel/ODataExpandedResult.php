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
     * @var ODataEntry
     */
    public $entry;

    /**
     * Scheme.
     *
     * @var ODataFeed
     */
    public $feed;

    /**
     * ODataExpandedResult constructor.
     *
     * @param ODataEntry|null $entry
     * @param ODataFeed|null $feed
     */
    public function __construct(ODataEntry $entry = null, ODataFeed $feed = null)
    {
        $this->entry = $entry;
        $this->feed = $feed;
    }
}
