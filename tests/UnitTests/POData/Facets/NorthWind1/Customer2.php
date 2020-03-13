<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

//Customer entity type
class Customer2
{
    public $CustomerID;
    public $CustomerGuid;
    public $CustomerName;
    public $Address;
    public $Country;
    public $Rating;
    public $Photo;
    //Navigation Property to associated Orders (ResourceSetReference)
    public $Orders;
}
