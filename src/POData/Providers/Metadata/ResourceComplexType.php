<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataV3\edm\TComplexTypeType;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Class ResourceComplexType.
 * @package POData\Providers\Metadata
 */
class ResourceComplexType extends ResourceType
{
    /**
     * Create new instance of ResourceComplexType.
     * @param ReflectionClass $instanceType Instance type for the complex type
     * @param TComplexTypeType $complex Object containing complex type metadata
     *
     * @throws InvalidArgumentException
     */
    public function __construct(ReflectionClass $instanceType, TComplexTypeType $complex)
    {
        $resourceTypeKind = ResourceTypeKind::COMPLEX();
        $bitz = explode('.', $complex->getName());
        $name = array_pop($bitz);
        $namespaceName = null;
        if (0 < count($bitz)) {
            $namespaceName = implode('.', $bitz);
        }
        $baseType = null;
        $isAbstract = false;
        parent::__construct($instanceType, $resourceTypeKind, $name, $namespaceName, $baseType, $isAbstract);
    }
}
