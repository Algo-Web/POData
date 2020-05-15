<?php

declare(strict_types=1);

namespace POData\Providers\Metadata\Type;

/**
 * Class String.
 */
class StringType extends EdmString
{
    /**
     * Checks this type (String) is compatible with another type
     * Note: implementation of IType::isCompatibleWith.
     *
     * @param IType $type Type to check compatibility
     *
     * @return bool
     */
    public function isCompatibleWith(IType $type): bool
    {
        return parent::isCompatibleWith($type);
    }
}
