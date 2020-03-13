<?php


namespace POData\Readers;

use POData\Common\Version;
use POData\Writers\IODataWriter;

class ODataReaderRegistry
{
    /** @var IODataReader[] */
    private $writers = [];

    /**
     * @param IODataReader $writer
     */
    public function register(IODataReader $writer)
    {
        $this->writers[] = $writer;
    }

    public function reset()
    {
        $this->writers = [];
    }

    /**
     * @param Version $responseVersion
     * @param $contentType
     *
     * @return IODataReader|null the writer that can handle the give criteria, or null
     */
    public function getReader(Version $responseVersion, $contentType)
    {
        foreach ($this->writers as $writer) {
            if ($writer->canHandle($responseVersion, $contentType)) {
                return $writer;
            }
        }
        return null;
    }
}
