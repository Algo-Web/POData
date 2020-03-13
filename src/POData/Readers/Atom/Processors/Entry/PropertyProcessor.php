<?php


namespace POData\Readers\Atom\Processors;


use POData\ObjectModel\AtomObjectModel\AtomContent;

class PropertiyProcessor implements INodeHandler
{
    private $atomContent;

    public function __construct($attributes)
    {
        $this->atomContent = new AtomContent(
            $attributes['type'],
            $attributes['src']
        );
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        // TODO: Implement handleStartNode() method.
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        // TODO: Implement handleEndNode() method.
    }

    public function handleChildComplete($objectModel)
    {
        // TODO: Implement handleChildComplete() method.
    }

    public function handleCharacterData($characters)
    {
        // TODO: Implement handleCharacterData() method.
    }

    public function getObjetModelObject()
    {
        // TODO: Implement getObjetModelObject() method.
    }
}