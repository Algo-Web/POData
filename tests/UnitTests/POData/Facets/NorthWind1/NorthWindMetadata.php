<?php

declare(strict_types=1);

namespace UnitTests\POData\Facets\NorthWind1;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;

class NorthWindMetadata
{
    /**
     * @throws InvalidOperationException
     * @throws \ReflectionException
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
     * @throws \ReflectionException
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
            'Orders',
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
     * @throws InvalidOperationException
     * @throws \ReflectionException
     * @return array
     */
    private static function createMetadataCore()
    {
        $metadata = new SimpleMetadataProvider('NorthWindEntities', 'NorthWind');

        //Register the complex type 'Address2'
        $address2ComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Address2'),
            'Address2'
        );
        $metadata->addPrimitiveProperty($address2ComplexType, 'IsPrimary', EdmPrimitiveType::BOOLEAN());

        //Register the complex type 'Address' with 'Address2' as base class
        $addressComplexType = $metadata->addComplexType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Address4'),
            'Address'
        );
        $metadata->addPrimitiveProperty($addressComplexType, 'HouseNumber', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($addressComplexType, 'LineNumber', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($addressComplexType, 'LineNumber2', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($addressComplexType, 'StreetName', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($addressComplexType, 'IsValid', EdmPrimitiveType::BOOLEAN());
        $metadata->addComplexProperty($addressComplexType, 'Address2', $address2ComplexType);

        //Register the entity (resource) type 'Customer'
        $customersEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Customer2'),
            'Customer'
        );
        $metadata->addKeyProperty($customersEntityType, 'CustomerID', EdmPrimitiveType::STRING());
        $metadata->addKeyProperty($customersEntityType, 'CustomerGuid', EdmPrimitiveType::GUID());
        $metadata->addPrimitiveProperty($customersEntityType, 'CustomerName', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($customersEntityType, 'Country', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($customersEntityType, 'Rating', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($customersEntityType, 'Photo', EdmPrimitiveType::BINARY());
        $metadata->addComplexProperty($customersEntityType, 'Address', $addressComplexType);

        //Register the entity (resource) type 'Order'
        $orderEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Order2'),
            'Order'
        );
        $metadata->addKeyProperty($orderEntityType, 'OrderID', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($orderEntityType, 'OrderDate', EdmPrimitiveType::DATETIME());
        $metadata->addPrimitiveProperty($orderEntityType, 'DeliveryDate', EdmPrimitiveType::DATETIME());
        $metadata->addPrimitiveProperty($orderEntityType, 'ShipName', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($orderEntityType, 'ItemCount', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($orderEntityType, 'QualityRate', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($orderEntityType, 'Price', EdmPrimitiveType::DOUBLE());

        //Register the entity (resource) type 'Product2'
        $productEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Product2'),
            'Product'
        );
        $metadata->addKeyProperty($productEntityType, 'ProductID', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($productEntityType, 'ProductName', EdmPrimitiveType::STRING());

        //Register the entity (resource) type 'Order_Details'
        $orderDetailsEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\OrderDetails2'),
            'Order_Details'
        );
        $metadata->addKeyProperty($orderDetailsEntityType, 'ProductID', EdmPrimitiveType::INT32());
        $metadata->addKeyProperty($orderDetailsEntityType, 'OrderID', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'UnitPrice', EdmPrimitiveType::DECIMAL());
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Quantity', EdmPrimitiveType::INT16());
        $metadata->addPrimitiveProperty($orderDetailsEntityType, 'Discount', EdmPrimitiveType::SINGLE());

        //Register the entity (resource) type 'Employee'
        $employeeEntityType = $metadata->addEntityType(
            new \ReflectionClass('UnitTests\POData\Facets\NorthWind1\Employee2'),
            'Employee'
        );
        $metadata->addKeyProperty($employeeEntityType, 'EmployeeID', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($employeeEntityType, 'FirstName', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($employeeEntityType, 'LastName', EdmPrimitiveType::STRING());
        $metadata->addPrimitiveProperty($employeeEntityType, 'ReportsTo', EdmPrimitiveType::INT32());
        $metadata->addPrimitiveProperty($employeeEntityType, 'Emails', EdmPrimitiveType::STRING(), true);
        //Set Employee entity type as MLE thus the url http://host/NorthWind.svc/Employee(1875)/$value will give the stream associated with employee with id 1875
        $employeeEntityType->setMediaLinkEntry(true);
        //Add a named stream property to the employee entity type
        //so the url http://host/NorthWind.svc/Employee(9831)/TumbNail_48X48 will give stream associated with employee's thumbnail (of size 48 x 48) image
        $streamInfo = new ResourceStreamInfo('TumbNail_48X48');
        $employeeEntityType->addNamedStream($streamInfo);

        //Register the entity (resource) sets
        $customersResourceSet  = $metadata->addResourceSet('Customers', $customersEntityType);
        $ordersResourceSet     = $metadata->addResourceSet('Orders', $orderEntityType);
        $productResourceSet    = $metadata->addResourceSet('Products', $productEntityType);
        $orderDetailsEntitySet = $metadata->addResourceSet('Order_Details', $orderDetailsEntityType);
        $employeeSet           = $metadata->addResourceSet('Employees', $employeeEntityType);
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
