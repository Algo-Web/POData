<?php

namespace UnitTests\POData\Facets\NorthWind1;

//Order_Details entity type
class OrderDetails2
{
    public $OrderID;
    public $ProductID;
    public $UnitPrice;
    public $Quantity;
    public $Discount;
    //Navigation Property to associated Order (ResourceReference)
    public $Order;
    //Navigation Property to associated Product (ResourceReference)
    public $Product;
}
