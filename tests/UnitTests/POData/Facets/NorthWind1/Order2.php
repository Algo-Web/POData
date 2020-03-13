<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

//Order entity type
class Order2
{
    public $OrderID;
    public $OrderDate;
    public $DeliveryDate;
    public $ShipName;
    public $ItemCount;
    public $QualityRate;
    public $Price;
    //Navigation Property to associated Customer (ResourceReference)
    public $Customer;
    //Navigation Property to associated Order_Details (ResourceSetReference)
    public $Order_Details;
}
