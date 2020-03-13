<?php


namespace POData\Readers\Atom;

use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\Readers\Atom\Processors\EntryProcessor;
use POData\Readers\Atom\Processors\FeedProcessor;
use POData\Readers\Atom\Processors\BaseNodeHandler;
use POData\Readers\IODataReader;
use SplStack;

class AtomODataReader implements IODataReader
{
    /**
     * XmlParser
     *
     * @var resource
     */
    private $parser;
    /**
     * @var SplStack|BaseNodeHandler[[]
     */
    private $stack;
    /**
     * @var ODataFeed|ODataEntry
     */
    private $objectModel = [];

    public function __construct()
    {
        $this->parser = xml_parser_create_ns('UTF-8', '|');
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, [$this, 'tagOpen'], [$this, 'tagClose']);
        xml_set_character_data_handler($this->parser, [$this, 'characterData']);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);
        $this->stack = new SplStack();
    }

    public function __destruct()
    {
        xml_parser_free($this->parser);
        unset($this->parser);
    }

    public function read($data)
    {
        xml_parse($this->parser, $data, true);
        return $this->objectModel;
    }

    public function characterData($parser, $data)
    {
        if ($this->stack->isEmpty()) {
            throw new \ParseError('enchanted character data outside of xml tag');
        }
        $this->stack->top()->handleCharacterData($data);
    }

    public function tagOpen($parser, $tag, $attributes)
    {
        switch (strtolower($tag)) {
            case strtolower(ODataConstants::ATOM_NAMESPACE . '|' . ODataConstants::ATOM_FEED_ELEMENT_NAME):
                $this->stack->push(new FeedProcessor($attributes));
                break;
            case strtolower(ODataConstants::ATOM_NAMESPACE . '|' . ODataConstants::ATOM_ENTRY_ELEMENT_NAME):
                $this->stack->push(new EntryProcessor($attributes));
                break;
            default:
                if ($this->stack->isEmpty()) {
                    throw new \ParseError(sprintf('encountered node %s while not in a feed or a stack', $tag));
                }
                list($namespsace, $name) = explode('|', $tag);
                $this->stack->top()->handleStartNode($namespsace, $name, $attributes);
        }
    }

    public function tagClose($parser, $tag)
    {
        switch (strtolower($tag)) {
            case strtolower(ODataConstants::ATOM_NAMESPACE . '|' . ODataConstants::ATOM_FEED_ELEMENT_NAME):
            case strtolower(ODataConstants::ATOM_NAMESPACE . '|' . ODataConstants::ATOM_ENTRY_ELEMENT_NAME):
                $process = $this->stack->pop();
                if ($this->stack->isEmpty()) {
                    $this->objectModel = $process->getObjetModelObject();
                } else {
                    $this->stack->top()->handleChildComplete($process->getObjetModelObject());
                }
                break;
            default:
                if ($this->stack->isEmpty()) {
                    throw new \ParseError('encountered node %s while not in a feed or a stack');
                }
                list($namespsace, $name) = explode('|', $tag);
                $this->stack->top()->handleEndNode($namespsace, $name);
        }
    }

    public function canHandle(\POData\Common\Version $responseVersion, $contentType)
    {
         return MimeTypes::MIME_APPLICATION_ATOM == $contentType || MimeTypes::MIME_APPLICATION_XML === $contentType;
    }
}
