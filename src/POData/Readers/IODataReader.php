<?php

declare(strict_types=1);


namespace POData\Readers;

interface IODataReader
{
    public function read($data);

    public function canHandle(\POData\Common\Version $responseVersion, $contentType);
}
