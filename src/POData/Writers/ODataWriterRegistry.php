<?php

namespace POData\Writers;

use POData\Common\Version;
use POData\Writers\IODataWriter;

/**
 * Class ODataWriterRegistry
 * @package POData\Writers\Common
 */
class ODataWriterRegistry
{

    /** @var IODataWriter[]  */
    private $writers = array();


    public function register(IODataWriter $writer)
    {
        $this->writers[] = $writer;
    }

    public function reset()
    {
        $this->writers = array();
    }

    /**
     * @param Version $responseVersion
     * @param $contentType
     *
     * @return IODataWriter|null the writer that can handle the give criteria, or null
     */
    public function getWriter(Version $responseVersion, $contentType){

        foreach($this->writers as $writer)
        {
            if($writer->canHandle($responseVersion, $contentType)) return $writer;
        }

        return null;
    }


}