<?php

namespace UnitTests\POData\Facets\NorthWind2;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;

//Begin Resource Classes

//Complex type base class for Address
class Address3
{
    //Edm.Int32
    public $LineNumber2;
}

//Complex class for Address
class Address1
{
    //Edm.Int32
    public $LineNumber;
    //Edm.String
    public $City;
    //Edm.String
    public $Region;
    //Edm.String
    public $PostalCode;
    //NorthWind.Address2
    public $Address2;
    //Edm.String
    public $Country;
}

//Customer entity type
class Customer1
{
    //Key Edm.String
    public $CustomerID;
    //Edm.String
    public $CompanyName;
    //Edm.String
    public $ContactName;
    //NorthWind.Address
    public $Address;
    //Edm.String
    public $Phone;
    //Navigation Property Orders (ResourceSetReference)
    public $Orders;
}

//Order entity type
class Order1
{
    //Key Edm.Int32
    public $OrderID;
    //Edm.DateTime
    public $OrderDate;
    //Edm.DateTime
    public $ShippedDate;
    //Edm.Decimal
    public $Freight;
    //Edm.String
    public $ShipName;
    //Edm.String
    public $CustomerID;
    //Navigation Property Customer (ResourceReference)
    public $Customer;
    //Navigation Property Order_Details (ResourceSetReference)
    public $Order_Details;
}

//Product Entity Type
class Product1
{
    //Key Edm.Int32
    public $ProductID;
    //Edm.String
    public $ProductName;
    //Edm.Decimal
    public $UnitPrice;
    //Edm.Int16
    public $UnitsInStock;
    //Edm.Int16
    public $UnitsOnOrder;
    //Navigation Property Order_Details (ResourceSetReference)
    public $Order_Details;
}

//Order_Details entity type
class Order_Details1
{
    //Key Edm.Int32
    public $OrderID;
    //Key Edm.Int32
    public $ProductID;
    //Edm.Decimal
    public $UnitPrice;
    //Edm.Int16
    public $Quantity;
    //Edm.Single
    public $Discount;
    //Navigation Property Order (ResourceReference)
    public $Order;
    //Navigation Property Product (ResourceReference)
    public $Product;
}

//Employee entity type, MLE and has named stream as Thumnails_48x48
class Employee1
{
    //Key Edm.Int32
    public $EmployeeID;
     //Edm.String
    public $FirstName;
     //Edm.String
    public $LastName;
     //Bag of strings
    public $Emails;
     //Edm.Binary
    public $Photo;
     //Edm.Int32
    public $ReportsTo;
     //Navigation Property to associated instance of Employee instance representing manager (ResourceReference)
    public $Manager;
     //Navigation Property to associated instance of Employee instances representing subordinates (ResourceSetReference)
    public $Subordinates;
}
//End Resource Classes

class NorthWindMetadata
{
    /**
     * @throws InvalidOperationException
     *
     * @return IMetadataProvider
     */
    public static function Create()
    {
        $metadata = new SimpleMetadataProvider('NorthWindEntities', 'NorthWind');

        //Register the complex type 'Address2'
        $address2ComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Address3'), 'Address2'
        );
        $metadata->addPrimitiveProperty($address2ComplexType, 'LineNumber2', EdmPrimitiveType::STRING);

        //Register the complex type 'Address' with 'Address2' as memebr variable
        $addressComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Address1'), 'Address'
        );

        $metadata->addPrimitiveProperty($addressComplexType, 'LineNumber', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($addressComplexType, 'City', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($addressComplexType, 'Region', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($addressComplexType, 'PostalCode', EdmPrimitiveType::STRING);
        $metadata->addComplexProperty($addressComplexType, 'Address2', $address2ComplexType);
        $metadata->addPrimitiveProperty($addressComplexType, 'Country', EdmPrimitiveType::STRING);

        //Register the entity (resource) type 'Customer'
        $customersEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Customer1'), 'Customer'
        );
        $metadata->addKeyProperty($customersEntityType, 'CustomerID', EdmPrimitiveType::STRING);
        $metadata->addKeyProperty($customersEntityType, 'CompanyName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($customersEntityType, 'ContactName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($customersEntityType, 'Phone', EdmPrimitiveType::STRING);
        $metadata->addComplexProperty($customersEntityType, 'Address', $addressComplexType);

        //Register the entity (resource) type 'Order'
        $orderEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Order1'), 'Order'
        );
        $metadata->addKeyProperty($orderEntityType, 'OrderID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderEntityType, 'OrderDate', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($orderEntityType, 'ShippedDate', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($orderEntityType, 'Freight', EdmPrimitiveType::DECIMAL);
        $metadata->addPrimitiveProperty($orderEntityType, 'ShipName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($orderEntityType, 'CustomerID', EdmPrimitiveType::STRING);

        //Register the entity (resource) type 'Product'
        $productEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Product1'), 'Product'
        );
        $metadata->addKeyProperty($productEntityType, 'ProductID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($productEntityType, 'ProductName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($productEntityType, 'UnitPrice', EdmPrimitiveType::DECIMAL);
        $metadata->addPrimitiveProperty($productEntityType, 'UnitsInStock', EdmPrimitiveType::INT16);
        $metadata->addPrimitiveProperty($productEntityType, 'UnitsOnOrder', EdmPrimitiveType::INT16);

        //Register the entity (resource) type 'Order_Details'
        $orderDetailsEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Order_Details1'), 'Order_Details'
        );
        $metadata->addKeyProperty($orderDetailsEntityType, 'ProductID', EdmPrimitiveType::INT32);
        $metadata->addKeyProperty($orderDetailsEntityType, 'OrderID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'UnitPrice', EdmPrimitiveType::DECIMAL);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Quantity', EdmPrimitiveType::INT16);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Discount', EdmPrimitiveType::SINGLE);

        //Register the entity (resource) type 'Employee'
        $employeeEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind2\Employee1'), 'Employee'
        );
        $metadata->addKeyProperty($employeeEntityType, 'EmployeeID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($employeeEntityType, 'FirstName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($employeeEntityType, 'LastName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($employeeEntityType, 'ReportsTo', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($employeeEntityType, 'Emails', EdmPrimitiveType::STRING, true);
        $metadata->addPrimitiveProperty($employeeEntityType, 'Photo', EdmPrimitiveType::BINARY);
        //Set Employee entity type as MLE thus the url http://host/NorthWind.svc/Employee(1875)/$value will give the stream associated with employee with id 1875
        $employeeEntityType->setMediaLinkEntry(true);
        //Add a named stream property to the employee entity type
        //so the url http://host/NorthWind.svc/Employee(9831)/TumbNail_48X48 will give stream associated with employee's thumbnail (of size 48 x 48) image
        $streamInfo = new ResourceStreamInfo('TumbNail_48X48');
        $employeeEntityType->addNamedStream($streamInfo);

        //Register the entity (resource) sets
        $customersResourceSet = $metadata->addResourceSet('Customers', $customersEntityType);
        $ordersResourceSet = $metadata->addResourceSet('Orders', $orderEntityType);
        $productResourceSet = $metadata->addResourceSet('Products', $productEntityType);
        $orderDetailsEntitySet = $metadata->addResourceSet('Order_Details', $orderDetailsEntityType);
        $employeeSet = $metadata->addResourceSet('Employees', $employeeEntityType);

        //Register the assoications (navigations)
        //Customers (1) <==> Orders (0-*)
        $metadata->addResourceSetReferenceProperty($customersEntityType, 'Orders', $ordersResourceSet);
        $metadata->addResourceReferenceProperty($orderEntityType, 'Customer', $customersResourceSet);
        //Orders (1) <==> Order_Details (0-*)
        //Products (1) <==> Order_Details (0-*)
        $metadata->addResourceReferenceProperty($orderDetailsEntityType, 'Order', $ordersResourceSet);
        $metadata->addResourceReferenceProperty($orderDetailsEntityType, 'Product', $productResourceSet);
        $metadata->addResourceSetReferenceProperty($productEntityType, 'Order_Details', $orderDetailsEntitySet);
        $metadata->addResourceSetReferenceProperty($orderEntityType, 'Order_Details', $orderDetailsEntitySet);
        //Employees (1) <==> Employees (1) 'Manager
        //Employees (1) <==> Employees (*) 'Subordinates
        $metadata->addResourceReferenceProperty($employeeEntityType, 'Manager', $employeeSet);
        $metadata->addResourceSetReferenceProperty($employeeEntityType, 'Subordinates', $employeeSet);

        return $metadata;
    }
}
