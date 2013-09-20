<?php

namespace POData\Writers\Json;


use MyCLabs\Enum\Enum;
use POData\Writers\Json\IndentedTextWriter;
use POData\Common\ODataConstants;

/**
 * Class JsonWriter
 * @package POData\Writers\Json
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
    private $_jsonDataWrapper;

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
     * @param string $jsonDataWrapper the json text that wraps a piece of data. defaults to json light's "value" :
     */
    public function __construct($writer, $jsonDataWrapper = '"value" : ')
    {
        $this->_writer = new IndentedTextWriter($writer);
	    $this->_jsonDataWrapper = $jsonDataWrapper;
    }

    /**
     * End the current scope
     * 
     * @return JsonWriter
     */
    public function endScope()
    {
        $this->_writer
	        ->writeLine()
	        ->decreaseIndent();
 
        if (array_pop($this->_scopes)->type == $this->_scopeType['Array']) {
            $this->_writer->writeValue("]");
        } else {
            $this->_writer->writeValue("}");
        }

	    return $this;

    }

    /**
     * Start the array scope
     * 
     * @return JsonWriter
     */
    public function startArrayScope()
    {
        $this->_startScope($this->_scopeType['Array']);
	    return $this;
    }

    /**
     * Write the "d" wrapper text
     * 
     * @return JsonWriter
     */
    public function writeDataWrapper()
    {
        $this->_writer->writeValue($this->_jsonDataWrapper);
	    return $this;
    }

    /**
     * Write the "results" header for the data array
     * 
     * @return JsonWriter
     */
    public function writeDataArrayName()
    {
        $this->writeName(ODataConstants::JSON_RESULT_NAME);
	    return $this;
    }

    /**
     * Start the object scope
     *
     * @return JsonWriter
     */
    public function startObjectScope()
    {
        $this->_startScope($this->_scopeType['Object']);
	    return $this;
    }

    /**
     * Write the name for the object property
     * 
     * @param string $name name of the object property
     * 
     * @return JsonWriter
     */
    public function writeName($name)
    {
        $currentScope = end($this->_scopes);
        if ($currentScope && $currentScope->type == $this->_scopeType['Object']) {
            if ($currentScope->objectCount != 0) {
                $this->_writer->writeTrimmed(", ");
            }

            $currentScope->objectCount++;
        }

        $this->_writeCore($name, true /*quotes*/);
        $this->_writer->writeTrimmed(": ");

	    return $this;
    }

    /**
     * JSON write a basic data type (string, number, boolean, null)
     * 
     * @param mixed  $value value to be written
     * @param string $type  data type of the value
     * 
     * @return JsonWriter
     */
    public function writeValue($value, $type = null)
    {
        switch ($type) {
	        case 'Edm.Boolean':
	        case 'Edm.Int16':
	        case 'Edm.Int32':
	        case 'Edm.Byte':
	        case 'Edm.SByte':
	            $this->_writeCore($value, /* quotes */ false);
	            break;


	        case 'Edm.Int64':
	        case 'Edm.Guid':
	        case 'Edm.Decimal':
	        case 'Edm.Binary':
	            $this->_writeCore($value, /* quotes */ true);
	            break;

	        case 'Edm.Single':
	        case 'Edm.Double':
	            if (is_infinite($value) || is_nan($value)) {
	                $this->_writeCore("null", /* quotes */ true);
	            } else {
	                $this->_writeCore($value, /* quotes */ false);
	            }

	            break;


	        case 'Edm.DateTime':
	            $dateTime = new \DateTime($value, new \DateTimeZone('UTC'));
	            $timeStamp = $dateTime->getTimestamp();
	            $formattedDateTime = sprintf($this->_jsonDateTimeFormat, $timeStamp);
	            $this->_writeCore($formattedDateTime, /* quotes */ true);
	            break;


	        case 'Edm.String':
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


	        default:
	            $this->_writeCore($this->_quoteJScriptString($value), /* quotes */ true);
        }

	    return $this;
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
     * @return void
     */
    private function _writeCore($text, $quotes)
    {
        if (count($this->_scopes) != 0) {
            $currentScope = end($this->_scopes);
            if ($currentScope->type == $this->_scopeType['Array']) {
                if ($currentScope->objectCount != 0) {
                    $this->_writer->writeTrimmed(", ");
                }

                $currentScope->objectCount++;
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
     * @return void
     */
    private function _startScope($type)
    {
        if (count($this->_scopes) != 0) {
            $currentScope = end($this->_scopes);
            if (($currentScope->type == $this->_scopeType['Array'])
                && ($currentScope->objectCount != 0)
            ) {
                $this->_writer->writeTrimmed(", ");
            }

            $currentScope->objectCount++;
        }

        $scope = new Scope($type);
        array_push($this->_scopes, $scope);

        if ($type == $this->_scopeType['Array']) {
            $this->_writer->writeValue("[");
        } else {
            $this->_writer->writeValue("{");
        }

        $this->_writer
	        ->increaseIndent()
            ->writeLine();
    }

    /**
     * return the indented result
     * 
     * @return string
     */
    public function getJsonOutput()
    {
        return $this->_writer->getResult();
    }
}



/**
 * class representing scope information 
*
 */
class Scope
{
    /**
     * keeps the count of the nested scopes
     *      
     */
    public $objectCount;

    /**
     *  keeps the type of the scope
     *      
     */
    public $type;

    /**
     * Creates a new instance of scope type
     * 
     * @param int $type type of the scope
     */
    public function __construct($type)
    {
        $this->type = $type;
	    $this->objectCount = 0;
    }

}