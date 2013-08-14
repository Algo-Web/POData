<?php
/** 
 * The base DataService (DataService.php) should implement this interface 
 * to make sure access to all providers and Operation context are available.
 * 

 */
namespace ODataProducer;
use ODataProducer\OperationContext\DataServiceHost;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Configuration\DataServiceConfiguration;
/**
 * Interface for DataService
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
interface IDataService
{
    /**
     * This method is called only once to initialize service-wide policies.
     * 
     * @param DataServiceConfiguration &$config data service configuration
     * 
     * @return nothing
     */
    public function initializeService(DataServiceConfiguration &$config);

    /**
     * Gets refernce to the configuration class to access the
     * configuration set by the developer.
     * 
     * @return IDataServiceConfiguration
     */
    public function getServiceConfiguration();

    /**
     * Gets reference to wrapper class instance over IDSQP and IDSMP 
     * implementation
     * 
     * @return MetadataQueryProviderWrapper
     */
    public function getMetadataQueryProviderWrapper();

    /**
     * Gets reference to wrapper class instance over IDSSP implementation.
     * 
     * @return DataServiceStreamProviderWrapper
     */
    public function getStreamProviderWrapper();

    /**
     * To set reference to the DataServiceHost instance created by the 
     * dispathcer.
     * 
     * @param DataServiceHost $dataServiceHost data service host
     * 
     * @return nothing
     */
    public function setHost(DataServiceHost $dataServiceHost);

    /**
     * Hold reference to the DataServiceHost instance created by dispatcher,
     * using this library can access headers and body of Http Request 
     * dispatcher received and the Http Response Dispatcher is going to send.
     * 
     * @return IDataServiceHost
     */
    public function getHost();
    
    /**
     * To get reference to operation context where we have direct access to
     * headers and body of Http Request we have received and the Http Response
     * We are going to send.
     * 
     * @return DataServiceOperationContext
     */
    public function getOperationContext();
}
?>