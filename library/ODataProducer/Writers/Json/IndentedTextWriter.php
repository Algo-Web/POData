<?php
/**
 * Contains Base class for OData Writers which implements IODataWriter.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Json
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
namespace ODataProducer\Writers\Json;
/**
 * Writes the Json text in indented format
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Writers_Json
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class IndentedTextWriter
{
    /**
     * writer to which Json text needs to be written
     *      
     */
    private $_result;
  
    /**
     * keeps track of the indentLevel
     *      
     */
    private $_indentLevel;
  
    /**
     * keeps track of pending tabs
     *      
     */
    private $_tabsPending;
  
    /**
     * string representation of tab
     *      
     */
    private $_tabString;
  
    /**
     * Creates a new instance of IndentedTextWriter
     * 
     * @param string $writer writer which IndentedTextWriter wraps
     */
    public function __construct($writer)
    {
        $this->_result = $writer;
        $this->_tabString = "    ";
    }
  
    /**
     * Setter
     * is run when writing data to inaccessible properties
     * 
     * @param string $name  name of the property being interacted with
     * @param int    $value the value the name'ed property should be set to
     * 
     * @return void
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case '_indentLevel':
            if ($value < 0) {
                $value = 0;
            }
            $this->_indentLevel = $value;
            break;
        }
    }
   
    /**
     * Getter
     * is utilized for reading data from inaccessible properties
     * 
     * @param string $name name of the property being interacted with
     * 
     * @return the value of the parameter
     */
    public function __get($name)
    {
        $vars = array('_result', '_indentLevel', '_tabsPending', '_tabString');
        if (in_array($name, $vars)) {
            return $this->$name;
        }
    }
   
    /**
     * Writes the given string value to the underlying writer
     * 
     * @param string $value string, char, text value to be written
     * 
     * @return void
     */
    public function writeValue($value)
    {
        $this->_outputTabs();
        $this->_write($value);
    }
   
   
    /**
     * Writes the trimmed text if minimizeWhiteSpeace is set to true
     * 
     * @param string $value value to be written
     * 
     * @return void
     */
    public function writeTrimmed($value)
    {
        $this->_write($value);
    }
   
    /**
     * Writes the tabs depending on the indent level
     * 
     * @return void
     */
    private function _outputTabs()
    {
        if ($this->_tabsPending) {
            for ($i = 0; $i < $this->_indentLevel; $i++) {
                $this->_write($this->_tabString);
            }
            $this->_tabsPending = false;
        }
    }
  
    /**
     * Writes the value to the text stream
     * 
     * @param string $value value to be written
     * 
     * @return void
     */
    private function _write($value)
    {
        $this->_result .= $value;
    }
   
    /**
     * Writes a new line character to the text stream
     * 
     * @return void
     */
    public function writeLine()
    {
        $this->_write("\n");
        $this->_tabsPending = true;
    }
}
?>