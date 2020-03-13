<?php


namespace POData\Readers\Atom\Processors\Entry;


use POData\Common\ODataConstants;
use POData\ObjectModel\AtomObjectModel\AtomContent;
use POData\ObjectModel\ODataProperty;
use POData\Readers\Atom\Processors\BaseNodeHandler;

class PropertyProcessor extends BaseNodeHandler
{
    /**
     * @var ODataProperty[]
     */
    private $properties = [];
    /**
     * @var ODataProperty
     */
    private $latestProperty;

    public function __construct($attributes)
    {
        $this->properties = [];
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        //TODO: this will need to be expanded with opengis namespaces as well when supported
        assert($tagNamespace === ODataConstants::ODATA_NAMESPACE);
        $this->latestProperty = new ODataProperty();
        $this->latestProperty->name = $tagName;
        $this->latestProperty->typeName = $this->arrayKeyOrDefault(
            $attributes,
            ODataConstants::ODATA_METADATA_NAMESPACE . '|' . ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
            null
        );
        $this->properties[$this->latestProperty->name] = $this->latestProperty;
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        $this->latestProperty->value = $this->popCharData();
    }

    public function handleChildComplete($objectModel)
    {
        //should never be called
    }

    /**
     * @return ODataProperty[]
     */
    public function getObjetModelObject()
    {
        return $this->properties;
    }
}
