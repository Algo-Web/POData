<?php


namespace POData\Readers\Atom\Processors;


interface INodeHandler
{

    public function __construct($attributes);

    public function handleStartNode($tagNamespace, $tagName, $attributes);

    public function handleEndNode($tagNamespace, $tagName);

    public function handleCharacterData($characters);

    public function handleChildComplete($objectModel);

    public function getObjetModelObject();
}