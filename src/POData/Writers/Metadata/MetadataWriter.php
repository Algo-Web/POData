<?php

namespace POData\Writers\Metadata;

use POData\Common\Version;
use POData\Providers\ProvidersWrapper;

/**
 * Class MetadataWriter.
 */
class MetadataWriter
{

    /**
     * Holds reference to the wrapper over service metadata and query provider implementations
     * In this context this provider will be used for gathering metadata information only.
     *
     * @var ProvidersWrapper
     */
    private $providersWrapper;


    /**
     * The DataServiceVersion for this metadata.
     *
     * @var Version
     */
    private $_dataServiceVersion;

    /**
     * Creates new instance of MetadataWriter.
     *
     * @param ProvidersWrapper $provider Reference to the
     *                                   service metadata and query provider wrapper
     */
    public function __construct(ProvidersWrapper $provider)
    {
        $this->providersWrapper = $provider;
    }

    /**
     * Write the metadata in CSDL format.
     *
     * @return string
     */
    public function writeMetadata()
    {
        return $this->providersWrapper->GetMetadataXML();
    }

    /**
     * Gets data service version for this metadata.
     *
     * @return Version
     */
    public function getDataServiceVersion()
    {
        return $this->_dataServiceVersion;
    }


}
