<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

//Employee entity type, MLE and has named stream as Thumnails
class Employee2
{
    public $EmployeeID;
    public $FirstName;
    public $LastName;
    //Bag of strings
    public $Emails;
    public $ReportsTo;
    //Navigation Property to associated instance of Employee instance representing manager (ResourceReference)
    public $Manager;
    //Navigation Property to associated instance of Employee instances representing subordinates (ResourceSetReference)
    public $Subordinates;
}
