<?php
/**
 * Contains Base class for OData Writers which implements IODataWriter.
 * 
 *
 *
 */
namespace ODataProducer\Writers\Json;
/**
 * Writes the Json text in indented format
*
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