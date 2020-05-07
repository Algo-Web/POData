<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use Closure;
use ParseError;
use POData\Common\ODataConstants;
use SplStack;

/**
 * Class BaseNodeHandler.
 * @package POData\Readers\Atom\Processors
 */
abstract class BaseNodeHandler
{
    private static $processExceptionMessage =
        'FeedProcessor encountered %s %s Tag with name %s that we don\'t know how to process';

    private $charData = '';

    /**
     * @var SplStack|callable
     */
    private $tagEndQueue;

    /**
     * @param $tagNamespace
     * @param $tagName
     * @param $attributes
     */
    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        $methodType = $this->resolveNamespaceToMethodTag($tagNamespace);
        $method     = 'handleStart' . $methodType . ucfirst(strtolower($tagName));
        if (!method_exists($this, $method)) {
            $this->onParseError($methodType, 'Start', $tagName);
        }
        $this->{$method}($attributes);
    }

    /**
     * @param $tagNamespace
     * @return mixed
     */
    private function resolveNamespaceToMethodTag($tagNamespace)
    {
        $tags = [
            strtolower(ODataConstants::ODATA_METADATA_NAMESPACE) => 'Metadata',
            strtolower(ODataConstants::ATOM_NAMESPACE) => 'Atom',
            strtoLower(ODataConstants::ODATA_NAMESPACE) => 'Dataservice'
        ];
        return $tags[strtolower($tagNamespace)];
    }

    /**
     * @param $namespace
     * @param $startEnd
     * @param $tagName
     */
    final protected function onParseError($namespace, $startEnd, $tagName)
    {
        throw new ParseError(sprintf(self::$processExceptionMessage, $namespace, $startEnd, $tagName));
    }

    /**
     * @param $characters
     */
    public function handleCharacterData($characters)
    {
        if (ord($characters) === 10 && empty($this->charData)) {
            return;
        }
        $this->charData .= $characters;
    }

    /**
     * @return string
     */
    final public function popCharData()
    {
        $data           = $this->charData;
        $this->charData = '';
        return $data;
    }

    /**
     * @param $objectModel
     * @return mixed
     */
    abstract public function handleChildComplete($objectModel);

    abstract public function getObjetModelObject();

    /**
     * @param $tagNamespace
     * @param $tagName
     */
    public function handleEndNode($tagNamespace, $tagName)
    {
        assert(!$this->tagEndQueue->isEmpty(), 'every node that opens should register a end tag');
        $endMethod = $this->tagEndQueue->pop();
        $endMethod();
    }

    /**
     * @param $array
     * @param $key
     * @param $default
     * @return mixed
     */
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

    /**
     * @return Closure
     */
    protected function doNothing()
    {
        return function () {
        };
    }

    /**
     * @param Closure $closure
     */
    protected function enqueueEnd(Closure $closure)
    {
        if (null === $this->tagEndQueue) {
            $this->tagEndQueue = new SplStack();
        }
        $this->tagEndQueue->push($this->bindHere($closure));
    }

    /**
     * @param  Closure $closure
     * @return Closure
     */
    protected function bindHere(Closure $closure)
    {
        return $closure->bindTo($this, get_class($this));
    }
}
