<?php
/**
 * Provides a writer implementaion for Json format 
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Json
 * @author    Bibin Kurian <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Writers\Json;
use ODataProducer\Writers\Json\IndentedTextWriter;
use ODataProducer\Common\ODataConstants;
/**
 * Json text writer.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Json
 * @author    Bibin Kurian <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class JsonWriter
{
    /**
     * Json datetime format.
     *
     */
    private $_jsonDateTimeFormat = "\/Date(%s)\/";

    /**
     * Text used to start a data object wrapper in JSON.
     *
     */
    private $_jsonDataWrapper = "\"d\" : ";

    /**
     * Writer to write text into
     *
     */
    private $_writer;

    /**
     * scope of the json text - object, array, etc
     *
     */
    private $_scopes = array();

    /**
     * Various scope types for Json writer
     *
     */
    private $_scopeType = array('Array' => 0, 'Object' => 1);

    /**
     * Creates a new instance of Json writer
     * 
     * @param string $writer writer to which text needs to be written
     */
    public function __construct($writer)
    {
        $this->_writer = new IndentedTextWriter($writer);
    }

    /**
     * End the current scope
     * 
     * @return nothing
     */
    public function endScope()
    {
        $this->_writer->writeLine();
        $this->_writer->_indentLevel--;
 
        $scope = array_pop($this->_scopes);
        if ($scope->Type == $this->_scopeType['Array']) {
            $this->_writer->writeValue("]");
        } else {
            $this->_writer->writeValue("}");
        }
    }

    /**
     * Start the array scope
     * 
     * @return nothing
     */
    public function startArrayScope()
    {
        $this->_startScope($this->_scopeType['Array']);
    }

    /**
     * Write the "d" wrapper text
     * 
     * @return nothing
     */
    public function writeDataWrapper()
    {
        $this->_writer->writeValue($this->_jsonDataWrapper);
    }

    /**
     * Write the "results" header for the data array
     * 
     * @return nothing
     */
    public function writeDataArrayName()
    {
        $this->writeName(ODataConstants::JSON_RESULT_NAME);
    }

    /**
     * Start the object scope
     *
     * @return nothing
     */
    public function startObjectScope()
    {
        $this->_startScope($this->_scopeType['Object']);
    }

    /**
     * Write the name for the object property
     * 
     * @param string $name name of the object property
     * 
     * @return nothing
     */
    public function writeName($name)
    {
        $currentScope = end($this->_scopes);
        if ($currentScope->Type == $this->_scopeType['Object']) {
            if ($currentScope->ObjectCount != 0) {
                $this->_writer->writeTrimmed(", ");
            }

            $currentScope->ObjectCount++;
        }

        $this->_writeCore($name, true /*quotes*/);
        $this->_writer->writeTrimmed(": ");
    }

    /**
     * JSON write a basic data type (string, number, boolean, null)
     * 
     * @param mixed  $value value to be written
     * @param string $type  data type of the value
     * 
     * @return nothing
     */
    public function writeValue($value, $type = null)
    {
        switch (true) {
        case ($type == 'Edm.Boolean'):
            $this->_writeCore($value, /* quotes */ false);
            break;

        case ($type == 'Edm.Int16'):
            $this->_writeCore($value, /* quotes */ false);
            break;
                
        case ($type == 'Edm.Int32'):
            $this->_writeCore($value, /* quotes */ false);
            break;

        case ($type == 'Edm.Int64'):
            $this->_writeCore($value, /* quotes */ true);
            break;

        case ($type == 'Edm.Single'):
            if (is_infinite($value) || is_nan($value)) {
                $this->_writeCore("null", /* quotes */ true);
            } else {
                $this->_writeCore($value, /* quotes */ false);
            }

            break;

        case ($type == 'Edm.Double'):
            if (is_infinite($value) || is_nan($value)) {
                $this->_writeCore("null", /* quotes */ true);
            } else {
                $this->_writeCore($value, /* quotes */ false);
            }

            break;

        case ($type == 'Edm.Guid'):
            $this->_writeCore($value, /* quotes */ true);
            break;

        case ($type == 'Edm.Decimal'):
            $this->_writeCore($value, /* quotes */ true);
            break;

        case ($type == 'Edm.DateTime'):
            $dateTime = new \DateTime($value, new \DateTimeZone('UTC'));
            $timeStamp = $dateTime->getTimestamp();
            $formattedDateTime = sprintf($this->_jsonDateTimeFormat, $timeStamp);
            $this->_writeCore($formattedDateTime, /* quotes */ true);
            break;

        case ($type == 'Edm.Byte'):
            $this->_writeCore($value, /* quotes */ false);
            break;

        case ($type == 'Edm.SByte'):
            $this->_writeCore($value, /* quotes */ false);
            break;

        case ($type == 'Edm.String'):
            if ($value == null) {
                $this->_writeCore("null", /* quotes */ false);
            } else {
                $jsonEncoded = json_encode($value);
                //json_encode always escapes a solidus (forward slash, %x2F), 
                //this will be a problem when encoding urls
                //JSON_UNESCAPED_SLASHES not available in earlier versions of php 5.3
                //So removing escaping forward slashes manually
                $jsonEncoded = str_replace('\\/', '/', $jsonEncoded);
                //since json_encode is already appending chords 
                //there is no need to set it again
                $this->_writeCore($jsonEncoded, /* quotes */ false);
            }
            break;

        case ($type == 'Edm.Binary'):
            $this->_writeCore($value, /* quotes */ true);
            break;

        default:
            $this->_writeCore($this->_quoteJScriptString($value), /* quotes */ true);
        }
    }

    /**
     * Returns the string value with special characters escaped
     * 
     * @param string $string input string value
     * 
     * Returns the string value with special characters escaped.
     * 
     * @return string
     */
    private function _quoteJScriptString($string)
    {
        // Escape ( " \ / \n \r \t \b \f) characters with a backslash.
        $search  = array('\\', "\n", "\t", "\r", "\b", "\f", '"');
        $replace = array('\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\"');
        $processedString  = str_replace($search, $replace, $string);
        // Escape some ASCII characters(0x08, 0x0c)
        $processedString = str_replace(array(chr(0x08), chr(0x0C)), array('\b', '\f'), $processedString);
        return $processedString;
    }

    /**
     * Write the string value with/without quotes
     * 
     * @param string $text   value to be written
     * @param string $quotes put quotes around the value if this value is true
     * 
     * @return nothing
     */
    private function _writeCore($text, $quotes)
    {
        if (count($this->_scopes) != 0) {
            $currentScope = end($this->_scopes);
            if ($currentScope->Type == $this->_scopeType['Array']) {
                if ($currentScope->ObjectCount != 0) {
                    $this->_writer->writeTrimmed(", ");
                }

                $currentScope->ObjectCount++;
            }
        }

        if ($quotes && $text !== 'null') {
            $this->_writer->writeValue('"');
        }

        $this->_writer->writeValue($text);
        if ($quotes && $text !== 'null') {
            $this->_writer->writeValue('"');
        }
    }

    /**
     * Start the scope given the scope type
     * 
     * @param int $type scope type
     * 
     * @return nothing
     */
    private function _startScope($type)
    {
        if (count($this->_scopes) != 0) {
            $currentScope = end($this->_scopes);
            if (($currentScope->Type == $this->_scopeType['Array']) 
                && ($currentScope->ObjectCount != 0)
            ) {
                $this->_writer->writeTrimmed(", ");
            }

            $currentScope->ObjectCount++;
        }

        $scope = new Scope($type);
        array_push($this->_scopes, $scope);

        if ($type == $this->_scopeType['Array']) {
            $this->_writer->writeValue("[");
        } else {
            $this->_writer->writeValue("{");
        }

        $this->_writer->_indentLevel++;
        $this->_writer->writeLine();
    }

    /**
     * return the intented result
     * 
     * @return string
     */
    public function getJsonOutput()
    {
        return $this->_writer->_result;
    }
}

/**
 * class representing scope information 
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Json
 * @author    Bibin Kurian <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class Scope
{
    /**
     * keeps the count of the nested scopes
     *      
     */
    private $_objectCount;

    /**
     *  keeps the type of the scope
     *      
     */
    private $_type;

    /**
     * Creates a new instance of scope type
     * 
     * @param int $type type of the scope
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * setter for scope
     * 
     * @param string $name  name of the varriable to be set
     * @param int    $value value of the varriable
     * 
     * @return nothing
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'ObjectCount':
            $this->_objectCount = $value;
            break;
        case 'Type':
            $this->_type = $value;
            break;
        }
    }

    /**
     * getter for scope
     * 
     * @param string $name name of the varriable to be get
     * 
     * @return int
     */
    public function __get($name)
    {
        switch ($name) {
        case 'ObjectCount':
            return $this->_objectCount;
            break;
        case 'Type':
            return $this->_type;
            break;
        }
    }
}
?>