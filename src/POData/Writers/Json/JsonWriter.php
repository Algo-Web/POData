<?php

namespace POData\Writers\Json;

use Carbon\Carbon;

/**
 * Class JsonWriter.
 */
class JsonWriter
{
    /**
     * Json datetime format.
     */
    private $jsonDateTimeFormat = "\/Date(%s)\/";

    /**
     * Writer to write text into.
     */
    private $writer;

    /**
     * scope of the json text - object, array, etc.
     */
    private $scopes = [];

    /**
     * Various scope types for Json writer.
     */
    private $scopeType = ['Array' => 0, 'Object' => 1];

    /**
     * Creates a new instance of Json writer.
     *
     * @param string $writer writer to which text needs to be written
     */
    public function __construct($writer)
    {
        $this->writer = new IndentedTextWriter($writer);
    }

    /**
     * End the current scope.
     *
     * @return JsonWriter
     */
    public function endScope()
    {
        $this->writer->writeLine()->decreaseIndent();

        if (array_pop($this->scopes)->type == $this->scopeType['Array']) {
            $this->writer->writeValue(']');
        } else {
            $this->writer->writeValue('}');
        }

        return $this;
    }

    /**
     * Start the array scope.
     *
     * @return JsonWriter
     */
    public function startArrayScope()
    {
        $this->startScope($this->scopeType['Array']);

        return $this;
    }

    /**
     * Write the "results" header for the data array.
     *
     * @return JsonWriter
     */
    public function writeDataArrayName()
    {
        $this->writeName($this->dataArrayName);

        return $this;
    }

    /**
     * Start the object scope.
     *
     * @return JsonWriter
     */
    public function startObjectScope()
    {
        $this->startScope($this->scopeType['Object']);

        return $this;
    }

    /**
     * Write the name for the object property.
     *
     * @param string $name name of the object property
     *
     * @return JsonWriter
     */
    public function writeName($name)
    {
        $currentScope = end($this->scopes);
        if ($currentScope && $currentScope->type == $this->scopeType['Object']) {
            if ($currentScope->objectCount != 0) {
                $this->writer->writeTrimmed(', ');
            }

            ++$currentScope->objectCount;
        }

        $this->writeCore($name, true /*quotes*/);
        $this->writer->writeTrimmed(': ');

        return $this;
    }

    /**
     * JSON write a basic data type (string, number, boolean, null).
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
                $this->writeCore($value, /* quotes */ false);
                break;

            case 'Edm.Int64':
            case 'Edm.Guid':
            case 'Edm.Decimal':
            case 'Edm.Binary':
                $this->writeCore($value, /* quotes */ true);
                break;

            case 'Edm.Single':
            case 'Edm.Double':
                if (is_infinite($value) || is_nan($value)) {
                    $this->writeCore('null', /* quotes */ true);
                } else {
                    $this->writeCore($value, /* quotes */ false);
                }

                break;

            case 'Edm.DateTime':
                $dateTime = new Carbon($value, new \DateTimeZone('UTC'));
                $formattedDateTime = $dateTime->format('U')*1000;
                $this->writeCore('/Date(' . $formattedDateTime . ')/', /* quotes */ true);
                break;

            case 'Edm.String':
                if ($value == null) {
                    $this->writeCore('null', /* quotes */ false);
                } else {
                    $jsonEncoded = json_encode($value);
                    //json_encode always escapes a solidus (forward slash, %x2F),
                    //this will be a problem when encoding urls
                    //JSON_UNESCAPED_SLASHES not available in earlier versions of php 5.3
                    //So removing escaping forward slashes manually
                    $jsonEncoded = str_replace('\\/', '/', $jsonEncoded);
                    //since json_encode is already appending chords
                    //there is no need to set it again
                    $this->writeCore($jsonEncoded, /* quotes */ false);
                }
                break;

            default:
                $this->writeCore($this->quoteJScriptString($value), /* quotes */ true);
        }

        return $this;
    }

    /**
     * Returns the string value with special characters escaped.
     *
     * @param string $string input string value
     *
     * Returns the string value with special characters escaped
     *
     * @return string
     */
    private function quoteJScriptString($string)
    {
        // Escape ( " \ / \n \r \t \b \f) characters with a backslash.
        $search = ['\\', "\n", "\t", "\r", "\b", "\f", '"'];
        $replace = ['\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'];
        $processedString = str_replace($search, $replace, $string);
        // Escape some ASCII characters - namely, 0x08 and 0x0c
        $processedString = str_replace([chr(0x08), chr(0x0C)], ['\b', '\f'], $processedString);

        return $processedString;
    }

    /**
     * Write the string value with/without quotes.
     *
     * @param string $text   value to be written
     * @param bool   $quotes put quotes around the value if this value is true
     */
    private function writeCore($text, $quotes)
    {
        if (0 != count($this->scopes)) {
            $currentScope = end($this->scopes);
            if ($currentScope->type == $this->scopeType['Array']) {
                if (0 != $currentScope->objectCount) {
                    $this->writer->writeTrimmed(', ');
                }

                ++$currentScope->objectCount;
            }
        }

        if ($quotes && 'null' !== $text) {
            $this->writer->writeValue('"');
        }

        $this->writer->writeValue($text);
        if ($quotes && 'null' !== $text) {
            $this->writer->writeValue('"');
        }
    }

    /**
     * Start the scope given the scope type.
     *
     * @param int $type scope type
     */
    private function startScope($type)
    {
        if (0 != count($this->scopes)) {
            $currentScope = end($this->scopes);
            if (($currentScope->type == $this->scopeType['Array']) && (0 != $currentScope->objectCount)) {
                $this->writer->writeTrimmed(', ');
            }

            ++$currentScope->objectCount;
        }

        $scope = new Scope($type);
        array_push($this->scopes, $scope);

        if ($type == $this->scopeType['Array']) {
            $this->writer->writeValue('[');
        } else {
            $this->writer->writeValue('{');
        }

        $this->writer->increaseIndent()->writeLine();
    }

    /**
     * return the indented result.
     *
     * @return string
     */
    public function getJsonOutput()
    {
        return $this->writer->getResult();
    }
}
