<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use Closure;
use ParseError;
use POData\Common\ODataConstants;
use SplStack;

abstract class BaseNodeHandler
{
    private static $processExceptionMessage =
        'FeedProcessor encountered %s %s Tag with name %s that we don\'t know how to process';

    private $charData = '';

    /**
     * @var SplStack|callable
     */
    private $tagEndQueue;

    private function resolveNamespaceToMethodTag($tagNamespace)
    {
        $tags = [
            strtolower(ODataConstants::ODATA_METADATA_NAMESPACE) => 'Metadata',
            strtolower(ODataConstants::ATOM_NAMESPACE) => 'Atom',
            strtoLower(ODataConstants::ODATA_NAMESPACE) => 'Dataservice'
        ];
        return $tags[strtolower($tagNamespace)];
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        $methodType = $this->resolveNamespaceToMethodTag($tagNamespace);
        $method     = 'handleStart' . $methodType . ucfirst(strtolower($tagName));
        if (!method_exists($this, $method)) {
            $this->onParseError($methodType, 'Start', $tagName);
        }
        $this->{$method}($attributes);
    }

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


    protected function doNothing()
    {
        return function () {};
    }

    protected function bindHere(Closure $closure)
    {
        return $closure->bindTo($this, get_class($this));
    }
    protected function enqueueEnd(Closure $closure)
    {
        if (null === $this->tagEndQueue) {
            $this->tagEndQueue = new SplStack();
        }
        $this->tagEndQueue->push($this->bindHere($closure));
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        assert(!$this->tagEndQueue->isEmpty(), 'every node that opens should register a end tag');
        $endMethod = $this->tagEndQueue->pop();
        $endMethod();
    }
}
