<?php

declare(strict_types=1);

namespace POData\Writers;

use POData\Common\Version;

/**
 * Class ODataWriterRegistry.
 */
class ODataWriterRegistry
{
    /** @var IODataWriter[] */
    private $writers = [];

    /**
     * @param IODataWriter $writer
     */
    public function register(IODataWriter $writer)
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
     * @return IODataWriter|null the writer that can handle the give criteria, or null
     */
    public function getWriter(Version $responseVersion, $contentType)
    {
        foreach ($this->writers as $writer) {
            if ($writer->canHandle($responseVersion, $contentType)) {
                return $writer;
            }
        }
        return null;
    }
}
