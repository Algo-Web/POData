<?php

namespace POData\Providers\Query;

class QueryResult
{
    /**
     * @var object[]|object|null
     */
    public $results;

    /***
     * @var int|null
     */
    public $count;

    /***
     * @var bool|null
     */
    public $hasMore;

    /**
     * @param int      $count
     * @param int|null $top
     * @param int|null $skip
     *
     * @throws \InvalidArgumentException if $count is not numeric
     *
     * @return int the paging adjusted count
     */
    public static function adjustCountForPaging($count, $top, $skip)
    {
        if (!is_numeric($count)) {
            throw new \InvalidArgumentException('$count');
        }

        //treat nulls like 0
        if (null === $skip) {
            $skip = 0;
        }

        $count = $count - $skip; //eliminate the skipped records
        if ($count < 0) {
            return 0;
        } //if there aren't enough to skip, the count is 0

        if (null === $top) {
            return $count;
        } //if there's no top, then it's the count as is

        return intval(min($count, $top)); //count is top, unless there aren't enough records
    }
}
