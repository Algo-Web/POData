<?php
/**
 * Class which helps to create anonymous functions
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor
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
namespace ODataProducer\UriProcessor\QueryProcessor;
use ODataProducer\Common\Messages;
/**
 * Type for run-time generated function.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class AnonymousFunction
{
    /**
     * An array of parameters to the function represented by this instance
     * 
     * @var array
     */
    private $_parameters;

    /**
     * Paramaters as string seperated by comma
     * 
     * @var string
     */
    private $_parametersAsString;

    /**
     * body of the function represented by this instance
     * 
     * @var string
     */
    private $_code;

    /**
     * Reference to the anonymous function represented by this instance
     * reference will be the name of the function in the form char(0).lamba_n.
     * 
     * @var string
     */
    private $_reference = null;

    /**
     * Create newinstance of AnonymousFunction
     * 
     * @param array  $parameters Array of parameters
     * @param string $code       Body of the function
     */
    public function __construct($parameters, $code)
    {
        $this->_parameters = $parameters;
        foreach ($this->_parameters as $parameter) {
            if (strpos($parameter, '$') !== 0) {
                throw new \InvalidArgumentException(
                    Messages::anonymousFunctionParameterShouldStartWithDollorSymbol()
                );
            } 
        }

        $this->_parametersAsString = implode(', ', $this->_parameters);
        $this->_code = $code;
    }

    /**
     * Gets function parameters as array.
     * 
     * @return array
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Gets function parameters as string seperated by comma
     * 
     * @return string
     */
    public function getParametersAsString()
    {
        return $this->_parametersAsString;
    }

    /**
     * Gets number of parameters
     * 
     * @return int
     */
    public function getParametersCount()
    {
        return count($this->_parameters);
    }

    /**
     * Gets function body
     * 
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Gets refernece to the anonymous function.
     * 
     * @return string
     */
    public function getReference()
    {
        if (is_null($this->_reference)) {
            $this->_reference = create_function(
                $this->_parametersAsString, $this->_code
            );
        }

        return $this->_reference;
    }
}
?>