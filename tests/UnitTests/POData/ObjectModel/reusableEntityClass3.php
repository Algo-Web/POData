<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel;

class reusableEntityClass3
{
    private $name;
    private $type;

    public function __construct($n, $t)
    {
        $this->name = $n;
        $this->type = $t;
    }
}
