<?php


namespace POData\Readers\Atom\Processors;

abstract class BaseNodeHandler
{
    private $charData = '';

    abstract public function __construct($attributes);

    abstract public function handleStartNode($tagNamespace, $tagName, $attributes);

    abstract public function handleEndNode($tagNamespace, $tagName);

    public function handleCharacterData($characters)
    {
        $this->charData .= $characters;
    }

    final public function popCharData()
    {
        $data = $this->charData;
        $this->charData = '';
        return $data;
    }

    abstract public function handleChildComplete($objectModel);

    abstract public function getObjetModelObject();

    final protected function arrayKeyOrDefault($array, $key, $default)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        foreach ($array as $objKey => $value) {
            if (strtolower($key) === strtolower($objKey)) {
                return $value;
            }
        }
        return $default;
    }
}
