<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors\Entry;

use POData\Common\ODataConstants;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\Readers\Atom\Processors\BaseNodeHandler;
use SplStack;

/**
 * Class PropertyProcessor.
 * @package POData\Readers\Atom\Processors\Entry
 */
class PropertyProcessor extends BaseNodeHandler
{
    /**
     * @var SplStack|ODataProperty[]
     */
    private $properties = [];

    /**
     * @var SplStack|ODataPropertyContent[]
     */
    private $propertyContent;

    public function __construct()
    {
        $this->propertyContent = new SplStack();
        $this->propertyContent->push(new ODataPropertyContent([]));
        $this->properties = new SplStack();
    }

    /**
     * @param $tagNamespace
     * @param $tagName
     * @param $attributes
     */
    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        if (strtolower($tagNamespace) === strtolower(ODataConstants::ODATA_METADATA_NAMESPACE) &&
            strtolower($tagName) === strtolower((ODataConstants::ATOM_PROPERTIES_ELEMENT_NAME))
        ) {
            return;
        }
        //TODO: this will need to be expanded with opengis namespaces as well when supported
        assert($tagNamespace === ODataConstants::ODATA_NAMESPACE ||
            $tagNamespace === ODataConstants::ODATA_METADATA_NAMESPACE);

        $property           = new ODataProperty();
        $property->name     = $tagName;
        $property->typeName = $this->arrayKeyOrDefault(
            $attributes,
            ODataConstants::ODATA_METADATA_NAMESPACE . '|' . ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
            null
        );
        $this->properties->push($property);
        $this->propertyContent->push(new ODataPropertyContent([]));
    }

    /**
     * @param $tagNamespace
     * @param $tagName
     */
    public function handleEndNode($tagNamespace, $tagName)
    {
        if (strtolower($tagNamespace) === strtolower(ODataConstants::ODATA_METADATA_NAMESPACE) &&
            strtolower($tagName) === strtolower((ODataConstants::ATOM_PROPERTIES_ELEMENT_NAME))
        ) {
            return;
        }
        // Pops a complex object off the stack
        $prop                                                  = $this->properties->pop();
        $propContent                                           = $this->propertyContent->pop();
        $this->propertyContent->top()[$prop->name] = $prop;

        if (count($propContent) == 0) {
            $prop->value = $this->popCharData();
        } else {
            $prop->value = $propContent;
        }
    }

    /**
     * @param $objectModel
     * @return mixed
     */
    public function handleChildComplete($objectModel)
    {
        //should never be called
    }

    /**
     * @return ODataPropertyContent
     */
    public function getObjetModelObject()
    {
        assert(!$this->propertyContent->isEmpty(), 'prop content should be empty by the time we get to requesting');
        return $this->propertyContent->pop();
    }
}
