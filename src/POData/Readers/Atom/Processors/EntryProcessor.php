<?php


namespace POData\Readers\Atom;


class EntryProcessor implements INodeHandler
{

    private 

    public function __construct($attributes)
    {

    }

    public function handleStartNode($tagName, $attributes)
    {
        switch($tagName){
            case 'ID':
            case 'TITLE':
            case 'UPDATED':
            case 'LINK':
            case 'CATEGORY':
            case 'PROPERTIES':
            case 'CONTENT':
            case 'AUTHOR':
            default:


        }
    }

    public function handleEndNode($tagName)
    {
        return $this;
    }
}