<?php

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;

//Begin Resource Classes

//Complex type base class for Address
class Address2
{
    public $IsPrimary;
}

//Complex class for Address
class Address4
{
    public $HouseNumber;
    public $LineNumber;
    public $LineNumber2;
    public $StreetName;
    public $IsValid;
    public $Address2;
}

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

//Product Entity Type
class Product2
{
    public $ProductID;
    public $ProductName;
    //Navigation Property to associated Order_Details (ResourceSetReference)
    public $Order_Details;
}

//Order_Details entity type
class Order_Details2
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

//Employee entity type, MLE and has named stream as Thumnails
class Employee2
{
    public $EmployeeID;
    public $FirstName;
    public $LastName;
     //Bag of strings
    public $Emails;
    public $ReportsTo;
     //Navigation Property to associated instance of Employee instance represeting manager (ResourceReference)
    public $Manager;
     //Navigation Property to associated instance of Employee instances represeting subordinates (ResourceSetReference)
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
        list($metadata,
            $customersEntityType,
            $orderEntityType,
            $productEntityType,
            $orderDetailsEntityType,
            $employeeEntityType,
            $customersResourceSet,
            $ordersResourceSet,
            $productResourceSet,
            $orderDetailsEntitySet,
            $employeeSet) = self::createMetadataCore();

        //Register the associations (navigations)
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

    /**
     * @throws InvalidOperationException
     *
     * @return IMetadataProvider
     */
    public static function CreateBidirectional()
    {
        list($metadata,
            $customersEntityType,
            $orderEntityType,
            $productEntityType,
            $orderDetailsEntityType,
            $employeeEntityType,
            $customersResourceSet,
            $ordersResourceSet,
            $productResourceSet,
            $orderDetailsEntitySet,
            $employeeSet) = self::createMetadataCore();

        //Register the associations (navigations)
        //Customers (1) <==> Orders (0-*)
        $metadata->addResourceReferencePropertyBidirectional(
            $customersEntityType,
            $orderEntityType,
            "Orders",
            'Customer'
        );
        //Orders (1) <==> Order_Details (0-*)
        //Products (1) <==> Order_Details (0-*)
        $metadata->addResourceReferencePropertyBidirectional(
            $productEntityType,
            $orderDetailsEntityType,
            'Order_Details',
            'Product'
        );
        $metadata->addResourceReferencePropertyBidirectional(
            $orderEntityType,
            $orderDetailsEntityType,
            'Order_Details',
            'Order'
        );
        //Employees (1) <==> Employees (1) 'Manager
        //Employees (1) <==> Employees (*) 'Subordinates
        $metadata->addResourceReferencePropertyBidirectional(
            $employeeEntityType,
            $employeeEntityType,
            'Subordinates',
            'Manager'
        );

        return $metadata;
    }

    /**
     * @return array
     * @throws InvalidOperationException
     */
    private static function createMetadataCore()
    {
        $metadata = new SimpleMetadataProvider('NorthWindEntities', 'NorthWind');

        //Register the complex type 'Address2'
        $address2ComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Address2'),
            'Address2'
        );
        $metadata->addPrimitiveProperty($address2ComplexType, 'IsPrimary', EdmPrimitiveType::BOOLEAN);

        //Register the complex type 'Address' with 'Address2' as base class
        $addressComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Address4'),
            'Address'
        );
        $metadata->addPrimitiveProperty($addressComplexType, 'HouseNumber', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($addressComplexType, 'LineNumber', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($addressComplexType, 'LineNumber2', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($addressComplexType, 'StreetName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($addressComplexType, 'IsValid', EdmPrimitiveType::BOOLEAN);
        $metadata->addComplexProperty($addressComplexType, 'Address2', $address2ComplexType);

        //Register the entity (resource) type 'Customer'
        $customersEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Customer2'),
            'Customer'
        );
        $metadata->addKeyProperty($customersEntityType, 'CustomerID', EdmPrimitiveType::STRING);
        $metadata->addKeyProperty($customersEntityType, 'CustomerGuid', EdmPrimitiveType::GUID);
        $metadata->addPrimitiveProperty($customersEntityType, 'CustomerName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($customersEntityType, 'Country', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($customersEntityType, 'Rating', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($customersEntityType, 'Photo', EdmPrimitiveType::BINARY);
        $metadata->addComplexProperty($customersEntityType, 'Address', $addressComplexType);

        //Register the entity (resource) type 'Order'
        $orderEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Order2'),
            'Order'
        );
        $metadata->addKeyProperty($orderEntityType, 'OrderID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderEntityType, 'OrderDate', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($orderEntityType, 'DeliveryDate', EdmPrimitiveType::DATETIME);
        $metadata->addPrimitiveProperty($orderEntityType, 'ShipName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($orderEntityType, 'ItemCount', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderEntityType, 'QualityRate', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderEntityType, 'Price', EdmPrimitiveType::DOUBLE);

        //Register the entity (resource) type 'Product2'
        $productEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Product2'),
            'Product'
        );
        $metadata->addKeyProperty($productEntityType, 'ProductID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($productEntityType, 'ProductName', EdmPrimitiveType::STRING);

        //Register the entity (resource) type 'Order_Details'
        $orderDetailsEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Order_Details2'),
            'Order_Details'
        );
        $metadata->addKeyProperty($orderDetailsEntityType, 'ProductID', EdmPrimitiveType::INT32);
        $metadata->addKeyProperty($orderDetailsEntityType, 'OrderID', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'UnitPrice', EdmPrimitiveType::DECIMAL);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Quantity', EdmPrimitiveType::INT16);
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Discount', EdmPrimitiveType::SINGLE);

        //Register the entity (resource) type 'Employee'
        $employeeEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Employee2'),
            'Employee'
        );
        $metadata->addKeyProperty($employeeEntityType, 'EmployeeID', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($employeeEntityType, 'FirstName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($employeeEntityType, 'LastName', EdmPrimitiveType::STRING);
        $metadata->addPrimitiveProperty($employeeEntityType, 'ReportsTo', EdmPrimitiveType::INT32);
        $metadata->addPrimitiveProperty($employeeEntityType, 'Emails', EdmPrimitiveType::STRING, true);
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
        return array(
            $metadata,
            $customersEntityType,
            $orderEntityType,
            $productEntityType,
            $orderDetailsEntityType,
            $employeeEntityType,
            $customersResourceSet,
            $ordersResourceSet,
            $productResourceSet,
            $orderDetailsEntitySet,
            $employeeSet
        );
    }
}
