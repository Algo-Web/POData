<?php

declare(strict_types=1);


namespace POData\Readers;

/**
 * Interface IODataReader
 * @package POData\Readers
 */
interface IODataReader
{
    /**
     * @param $data
     * @return mixed
     */
    public function read($data);

    /**
     * @param \POData\Common\Version $responseVersion
     * @param $contentType
     * @return mixed
     */
    public function canHandle(\POData\Common\Version $responseVersion, $contentType);
}
