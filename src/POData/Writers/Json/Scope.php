<?php

namespace POData\Writers\Json;

/**
 * class representing scope information.
 */
class Scope
{
    /**
     * keeps the count of the nested scopes.
     */
    public $objectCount;

    /**
     *  keeps the type of the scope.
     */
    public $type;

    /**
     * Creates a new instance of scope type.
     *
     * @param int $type type of the scope
     */
    public function __construct($type)
    {
        $this->type = $type;
        $this->objectCount = 0;
    }
}
