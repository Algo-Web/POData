<?php
/**
 * Defines the ServiceConfig class
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
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
namespace ODataProducer\Common;
/**
 * Helper class to read and velidate the service config file
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ServiceConfig
{
    /**
     * Read and validates the configuration for the given service.
     * 
     * @param string $serviceName  requested service name
     * @param string &$serviceInfo service info
     * @param string $configFile   config filename for all the services
     * 
     * @return void
     * 
     * @throws ODataException If configuration file 
     * does not exists or malformed.
     */
    public static function validateAndGetsServiceInfo($serviceName,  &$serviceInfo, $configFile = '../../../services/service.config.xml')
    {
        $xml = simplexml_load_file(dirname(__FILE__)."/".$configFile, null, LIBXML_NOCDATA);
        if (!$xml) {
            ODataException::createInternalServerError('service.config file is not in proper XML format');
        }

        if (count($xml->children()) != 1) {
            ODataException::createInternalServerError("Config file has more than one root entries");
        }

        $pathResult = $xml->xpath("/configuration/services/service[@name=\"$serviceName\"]");
        if (empty($pathResult)) {
             ODataException::createBadRequestError("No configuration info found for $serviceName");
        }
                
        $pathResult = $xml->xpath("/configuration/services/service[@name=\"$serviceName\"]/path");
        if (empty($pathResult)) {
            ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file");
        } else {
            $serviceInfo['SERVICE_PATH'] = strval($pathResult[0]);
            if (empty($serviceInfo['SERVICE_PATH'])) {
                ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file or config file is mail formed");
            }
        }
       
        unset($pathResult);
        $pathResult = $xml->xpath("/configuration/services/service[@name=\"$serviceName\"]/classname");
        if (empty($pathResult)) {
            ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file");
        } else {
            $serviceInfo['SERVICE_CLASS'] = strval($pathResult[0]);
            if (empty($serviceInfo['SERVICE_CLASS'])) {
                ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file or config file is mail formed");
            }
        }

        unset($pathResult);
        $pathResult = $xml->xpath("/configuration/services/service[@name=\"$serviceName\"]/baseURL");
        if (empty($pathResult)) {
            ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file");
        } else {
            $serviceInfo['SERVICE_BASEURL'] = strval($pathResult[0]);
            if (empty($serviceInfo['SERVICE_BASEURL'])) {
                ODataException::createInternalServerError("One of the mendatory configuration info were missing in the config file or config file is mail formed");
            }
        }
    }
}