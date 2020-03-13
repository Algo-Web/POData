<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

//Product Entity Type
class Product2
{
    public $ProductID;
    public $ProductName;
    //Navigation Property to associated Order_Details (ResourceSetReference)
    public $Order_Details;
}
