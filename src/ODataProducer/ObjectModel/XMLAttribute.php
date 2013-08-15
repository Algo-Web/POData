<?php

namespace ODataProducer\ObjectModel;

/**
 * Class XMLAttribute represents XML attribute
 * @package ODataProducer\ObjectModel
 */
class XMLAttribute
{
    /**
     * 
     * The namespace prefix
     * @var string
     */
    public $nsPrefix;
    /**
     * 
     * The namespace URI. 
     * @var string
     */
    public $nsUri;
    /**
     * 
     * The attribute name
     * @var string
     */
    public $name;
    /**
     * 
     * The attribute value
     * @var string
     */
    public $Value;
}