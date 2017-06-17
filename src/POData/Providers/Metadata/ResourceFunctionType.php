<?php

namespace POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataV3\edm\EntityContainer\FunctionImportAnonymousType;

class ResourceFunctionType
{
    private $blacklist = ['exec', 'system', 'eval'];

    /**
     * @property string
     */
    private $functionName = null;

    /**
     * @property \AlgoWeb\ODataMetadata\MetadataV3\edm\EntityContainer\FunctionImportAnonymousType $baseType
     */
    private $baseType = null;

    private $resourceType = null;

    /**
     * ResourceFunctionType constructor.
     * @param string|array $functionName
     * @param FunctionImportAnonymousType $type
     * @param ResourceType $resource
     */
    public function __construct($functionName, FunctionImportAnonymousType $type, ResourceType $resource)
    {
        if (null === $functionName) {
            $msg = "FunctionName must not be null";
            throw new \InvalidArgumentException($msg);
        }

        if (!is_string($functionName) && !is_array($functionName)) {
            $msg = "Function name must be string or array";
            throw new \InvalidArgumentException($msg);
        }

        $isArray = is_array($functionName);
        if ($isArray && 1 == count($functionName)) {
            $functionName = $functionName[0];
            $isArray = false;
        }

        if ($isArray) {
            if (2 < count($functionName)) {
                $msg = "FunctionName must have no more than 2 elements";
                throw new \InvalidArgumentException($msg);
            }
            if (0 == count($functionName)) {
                $msg = "FunctionName must have 1 or 2 elements";
                throw new \InvalidArgumentException($msg);
            }

            if (!is_object($functionName[0]) && !is_string($functionName[0])) {
                $msg = "First element of FunctionName must be either object or string";
                throw new \InvalidArgumentException($msg);
            }
            if (!is_string($functionName[1])) {
                $msg = "Second element of FunctionName must be string";
                throw new \InvalidArgumentException($msg);
            }
            if (is_string($functionName[0])) {
                $functionName[0] = trim($functionName[0]);
                $func = $functionName[0];
                if ('' == $func) {
                    $msg = "First element of FunctionName must not be empty";
                    throw new \InvalidArgumentException($msg);
                }
                $this->checkBlacklist($func, true);
            }
        } else {
            if (!is_string($functionName) || empty(trim($functionName))) {
                $msg = "FunctionName must be a non-empty string";
                throw new \InvalidArgumentException($msg);
            }
            $functionName = trim($functionName);

            $this->checkBlacklist($functionName, false);
        }

        if (!$type->isOK($msg)) {
            throw new \InvalidArgumentException($msg);
        }

        $this->functionName = $functionName;
        $this->baseType = $type;
        $this->resourceType = $resource;
    }

    /**
     * Get endpoint name
     *
     * @return string
     */
    public function getName()
    {
        return $this->baseType->getName();
    }

    /**
     * Get underlying function name
     *
     * @return string
     */
    public function getFunctionName()
    {
        return $this->functionName;
    }

    /**
     * Required parameter list
     *
     * @return array
     */
    public function getParms()
    {
        return $this->baseType->getParameter();
    }

    /**
     * @return ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function get(array $parms = [])
    {
        // check inputs
        $baseParms = $this->getParms();
        $expectedParms = count($baseParms);
        $actualParms = count($parms);
        if ($expectedParms != $actualParms) {
            $msg = "Was expecting ". $expectedParms. " arguments, received ".$actualParms." instead";
            throw new \InvalidArgumentException($msg);
        }

        // commence primary ignition
        return call_user_func_array($this->functionName, $parms);
    }

    /**
     * @param $func
     */
    private function checkBlacklist($func, $fromArray = false)
    {
        if (in_array($func, $this->blacklist) || in_array(strtolower($func), $this->blacklist)) {
            $msg = (true === $fromArray ? "First element of " : "")."FunctionName blacklisted";
            throw new \InvalidArgumentException($msg);
        }
    }
}
