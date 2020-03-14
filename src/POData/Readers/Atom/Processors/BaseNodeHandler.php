<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use ParseError;

abstract class BaseNodeHandler
{
    private static $processExceptionMessage =
        'FeedProcessor encountered %s %s Tag with name %s that we don\'t know how to process';

    private $charData = '';

    abstract public function handleStartNode($tagNamespace, $tagName, $attributes);

    abstract public function handleEndNode($tagNamespace, $tagName);

    public function handleCharacterData($characters)
    {
        if (ord($characters) === 10 && empty($this->charData)) {
            return;
        }
        $this->charData .= $characters;
    }

    final public function popCharData()
    {
        $data           = $this->charData;
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

    final protected function onParseError($namespace, $startEnd, $tagName)
    {
        throw new ParseError(sprintf(self::$processExceptionMessage, $namespace, $startEnd, $tagName));
    }
}
