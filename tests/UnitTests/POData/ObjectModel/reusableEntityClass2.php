<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel;

class reusableEntityClass2
{
    private $name;
    private $type;

    public function __construct($n, $t)
    {
        $this->name = $n;
        $this->type = $t;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
