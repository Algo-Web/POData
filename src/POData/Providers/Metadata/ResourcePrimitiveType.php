<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

use InvalidArgumentException;
use POData\Providers\Metadata\Type\IType;

/**
 * Class ResourcePrimitiveType.
 * @package POData\Providers\Metadata
 */
class ResourcePrimitiveType extends ResourceType
{
    /**
     * Create new instance of ResourcePrimitiveType.
     * @param IType $primitive Instance type for the primitive type
     *
     * @throws InvalidArgumentException
     */
    public function __construct(IType $primitive)
    {
        $resourceTypeKind = ResourceTypeKind::PRIMITIVE();
        $bitz             = explode('.', $primitive->getName());
        $name             = array_pop($bitz);
        $namespaceName    = null;
        if (0 < count($bitz)) {
            $namespaceName = implode('.', $bitz);
        }
        $baseType   = null;
        $isAbstract = false;
        parent::__construct($primitive, $resourceTypeKind, $name, $namespaceName, $baseType, $isAbstract);
    }
}
