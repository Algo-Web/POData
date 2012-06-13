<?php
/** 
 * The base DataService (DataService.php) should implement this interface 
 * to make sure access to all providers and Operation context are available.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS 
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN 
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
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