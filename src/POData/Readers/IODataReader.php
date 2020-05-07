<?php

declare(strict_types=1);


namespace POData\Readers;

use POData\Common\Version;

/**
 * Interface IODataReader.
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
     * @param Version $responseVersion
     * @param $contentType
     * @return mixed
     */
    public function canHandle(Version $responseVersion, $contentType);
}
