<?php

use POData\Configuration\EntitySetRights;
require_once 'POData\IBaseService.php';
require_once 'POData\IRequestHandler.php';
require_once 'POData\DataService.php';
require_once 'POData\IServiceProvider.php';
use POData\Configuration\ServiceProtocolVersion;
use POData\Configuration\ServiceConfiguration;
use POData\BaseService;
use POData\OperationContext\ServiceHost;
use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\UriProcessor\UriProcessor;
require_once 'WordPressMetadata.php';
require_once 'WordPressQueryProvider.php';
require_once 'WordPressDSExpressionProvider.php';


class WordPressDataService extends BaseService
{
    private $_wordPressMetadata = null;
    private $_wordPressQueryProvider = null;
    private $_wordPressExpressionProvider = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param ServiceConfiguration &$config Data service configuration object
     * 
     * @return void
     */
    public function initializeService(ServiceConfiguration &$config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(ServiceProtocolVersion::V3);
    }

    /**
     * Get the service like IMetadataProvider, IDataServiceQueryProvider,
     * IStreamProvider
     * 
     * @param String $serviceType Type of service IMetadataProvider,
     *                            IDataServiceQueryProvider,
     *                            IStreamProvider
     * 
     * @see library/POData.IServiceProvider::getService()
     * @return stdClass
     */
    public function getService($serviceType)
    {
      if(($serviceType === 'IMetadataProvider') ||
        ($serviceType === 'IQueryProvider') ||
        ($serviceType === 'IStreamProvider')) {
        if (is_null($this->_wordPressExpressionProvider)) {
        $this->_wordPressExpressionProvider = new WordPressDSExpressionProvider();    			
        }    	
      }
        if ($serviceType === 'IMetadataProvider') {
            if (is_null($this->_wordPressMetadata)) {
                $this->_wordPressMetadata = CreateWordPressMetadata::create();
                // $this->_wordPressMetadata->mappedDetails = CreateWordPressMetadata::mappingInitialize();
            }
            return $this->_wordPressMetadata;
        } else if ($serviceType === 'IQueryProvider') {
            if (is_null($this->_wordPressQueryProvider)) {
                $this->_wordPressQueryProvider = new WordPressQueryProvider();
            }
            return $this->_wordPressQueryProvider;
        } else if ($serviceType === 'IStreamProvider') {
            return new WordPressStreamProvider();
        }
        return null;
    }
}
