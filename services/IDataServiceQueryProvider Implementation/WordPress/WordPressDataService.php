<?php
/** 
 * Implementation of IServiceProvider.
 * 
 * PHP version 5.3
 * 
 * @category  Service
 * @package   WordPress
 * @author    Bibin Kurian <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
use ODataProducer\Configuration\EntitySetRights;
require_once 'ODataProducer\IDataService.php';
require_once 'ODataProducer\IRequestHandler.php';
require_once 'ODataProducer\DataService.php';
require_once 'ODataProducer\IServiceProvider.php';
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\IServiceProvider;
use ODataProducer\DataService;
require_once 'WordPressMetadata.php';
require_once 'WordPressQueryProvider.php';

/**
 * NorthWindDataService that implements IServiceProvider.
 * 
 * @category  Service
 * @package   WordPress
 * @author    Bibin Kurian <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class WordPressDataService extends DataService implements IServiceProvider
{
    private $_wordPressMetadata = null;
    private $_wordPressQueryProvider = null;
    
    /**
     * This method is called only once to initialize service-wide policies
     * 
     * @param DataServiceConfiguration &$config Data service configuration object
     * 
     * @return void
     */
    public function initializeService(DataServiceConfiguration &$config)
    {
        $config->setEntitySetPageSize('*', 5);
        $config->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $config->setAcceptCountRequests(true);
        $config->setAcceptProjectionRequests(true);
        $config->setMaxDataServiceVersion(DataServiceProtocolVersion::V3);
    }

    /**
     * Get the service like IDataServiceMetadataProvider, IDataServiceQueryProvider,
     * IDataServiceStreamProvider
     * 
     * @param String $serviceType Type of service IDataServiceMetadataProvider, 
     *                            IDataServiceQueryProvider,
     *                            IDataServiceStreamProvider
     * 
     * @see library/ODataProducer/ODataProducer.IServiceProvider::getService()
     * @return object
     */
    public function getService($serviceType)
    {
        if ($serviceType === 'IDataServiceMetadataProvider') {
            if (is_null($this->_wordPressMetadata)) {
                $this->_wordPressMetadata = CreateWordPressMetadata::create();
            }

            return $this->_wordPressMetadata;
        } else if ($serviceType === 'IDataServiceQueryProvider') {
            if (is_null($this->_wordPressQueryProvider)) {
                $this->_wordPressQueryProvider = new WordPressQueryProvider();
            }

            return $this->_wordPressQueryProvider;
        } else if ($serviceType === 'IDataServiceStreamProvider') {
            return new WordPressStreamProvider();
        }

        return null;
    }    
}
?>