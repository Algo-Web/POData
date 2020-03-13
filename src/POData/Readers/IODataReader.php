<?php


namespace POData\Readers;

interface IODataReader
{
    public function read($data);

    public function canHandle(\POData\Common\Version $responseVersion, $contentType);

}
