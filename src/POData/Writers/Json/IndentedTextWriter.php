<?php

declare(strict_types=1);

namespace POData\Writers\Json;

/**
 * Class IndentedTextWriter.
 */
class IndentedTextWriter
{
    /**
     * writer to which Json text needs to be written.
     *
     * @var string
     */
    private $result;

    /**
     * keeps track of the indentLevel.
     *
     * @var int
     */
    private $indentLevel;

    /**
     * keeps track of pending tabs.
     *
     * @var bool
     */
    private $tabsPending;

    /**
     * string representation of tab.
     *
     * @var string
     */
    private $tabString;


    private $eol;
    private $prettyPrint;

    /**
     * Creates a new instance of IndentedTextWriter.
     *
     * @param string $writer writer which IndentedTextWriter wraps
     */
    public function __construct($writer, string $eol, bool $prettyPrint)
    {
        $this->result      = $writer;
        $this->eol         = $prettyPrint ? $eol : '';
        $this->prettyPrint = $prettyPrint;
        $this->tabString   = $prettyPrint ? '    ' : '';
    }

    /**
     * Writes the given string value to the underlying writer.
     *
     * @param string $value string, char, text value to be written
     *
     * @return IndentedTextWriter
     */
    public function writeValue($value)
    {
        $this->outputTabs();
        $this->write($value);

        return $this;
    }

    /**
     * Writes the tabs depending on the indent level.
     */
    private function outputTabs()
    {
        if ($this->tabsPending) {
            $this->write(str_repeat($this->tabString, $this->indentLevel));
            $this->tabsPending = false;
        }
    }

    /**
     * Writes the value to the text stream.
     *
     * @param string $value value to be written
     */
    private function write($value)
    {
        $this->result .= $value;
    }

    /**
     * Writes a new line character to the text stream.
     *
     * @return IndentedTextWriter
     */
    public function writeLine()
    {
        $this->write($this->eol);
        $this->tabsPending = true;

        return $this;
    }

    /**
     * Writes the given text trimmed with no indentation.
     *
     * @param string $value text to be written
     *
     * @return IndentedTextWriter
     */
    public function writeTrimmed($value)
    {
        $this->write(trim($value));

        return $this;
    }

    /**
     * Increases the current indent setting by 1.
     *
     * @return IndentedTextWriter
     */
    public function increaseIndent()
    {
        ++$this->indentLevel;

        return $this;
    }

    /**
     * Decreases the current indent setting by 1, never going below 0.
     *
     * @return IndentedTextWriter
     */
    public function decreaseIndent()
    {
        if ($this->indentLevel > 0) {
            $this->indentLevel--;
        }

        return $this;
    }

    /**
     * @return string the current written text
     *                strReplace as json_encode does not always respect PHP_EOL
     */
    public function getResult()
    {
        return $this->result;
    }
}
