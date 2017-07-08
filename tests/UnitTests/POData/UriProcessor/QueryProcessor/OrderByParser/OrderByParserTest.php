<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\OrderByParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
//These are in the file loaded by above use statement
//TODO: move to own class files
use UnitTests\POData\Facets\NorthWind1\Address2;
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\Facets\NorthWind1\Order_Details2;
use UnitTests\POData\Facets\NorthWind1\Product2;
use UnitTests\POData\TestCase;

class OrderByParserTest extends TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    protected function setUp()
    {
        $this->mockQueryProvider = m::mock('POData\Providers\Query\IQueryProvider');
    }

    public function testOrderByWithSyntaxError()
    {
        //If a path segment contains ( should throw synax error

        //only asc or desc are allowed as a default segment last segment
        //so if asc/desc is last then next should be end or comma

        //multiple commas not allowed
    }

    //All all test case (which are +ve) check the generated function and

    /**
     * Entities cannot be sorted using bag property.
     */
    public function testOrderByBag()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Employees');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Emails';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for bag property in the path');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('orderby clause does not support Bag property in the path, the property', $odataException->getMessage());
        }
    }

    /**
     * Entities cannot be sorted using complex property.
     */
    public function testOrderByComplex()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Address';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for using complex property as sort key has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Complex property cannot be used as sort key,', $odataException->getMessage());
        }

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Address/Address2';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for using complex property as sort key has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Complex property cannot be used as sort key,', $odataException->getMessage());
        }
    }

    /**
     * Entities cannot be sorted using resource set reference property
     * even resource set is not allowed in order by path.
     */
    public function testOrderByResourceSetReference()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Orders/OrderID';
        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for usage of resource reference set has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Navigation property points to a collection cannot be used in orderby clause', $odataException->getMessage());
        }
    }

    /**
     * Entities cannot be sorted using resource reference property.
     */
    public function testOrderByResourceReference()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Customer';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for usage of resource reference as sort key has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Navigation property cannot be used as sort key,', $odataException->getMessage());
        }

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Order/Customer';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for usage of resource reference as sort key has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Navigation property cannot be used as sort key,', $odataException->getMessage());
        }
    }

    /**
     * A primitive property of a complex type can be used as sort key.
     */
    public function testOrderByPrimitiveInAComplex()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Address/HouseNumber';
        $internalOrderInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $orderByInfo = $internalOrderInfo->getOrderByInfo();
        //There is no navigation (resource reference) property in the orderby path so getNavigationPropertiesUsed should
        //return null (not empty array)
        $this->assertNull($orderByInfo->getNavigationPropertiesUsed());
        //There should be one orderby path segment with two sub path segments
        $orderByPathSegments = $orderByInfo->getOrderByPathSegments();
        $this->assertEquals(count($orderByPathSegments), 1);
        //no sort order, so default to asc
        $this->assertTrue($orderByPathSegments[0]->isAscending());
        //there are two sub path 'Address' and 'HouseNumber'
        $subPathSegments = $orderByPathSegments[0]->getSubPathSegments();
        $this->assertNotNull($subPathSegments);
        $this->assertEquals(count($subPathSegments), 2);
        $this->assertEquals($subPathSegments[0]->getName(), 'Address');
        $this->assertEquals($subPathSegments[1]->getName(), 'HouseNumber');
        $this->assertTrue($subPathSegments[0]->getResourceProperty() instanceof ResourceProperty);
        $this->assertTrue($subPathSegments[1]->getResourceProperty() instanceof ResourceProperty);
        //There is only one sub sorter function
        $subSorters = $internalOrderInfo->getSubSorterFunctions();
        $this->assertNotNull($subSorters);
        $this->assertEquals(count($subSorters), 1);
        //Parmater to this sub sort must be CustomersA, CustomersB
//        $this->assertEquals($subSorters[0]->getParametersAsString(), '$CustomersA, $CustomersB');
        //since there is only one sub sorter, that will be the main sorter
        //asset this by comapring the anonymous function names
        $subSorterName = $subSorters[0];
        $sorter = $internalOrderInfo->getSorterFunction();
        $this->assertNotNull($sorter);
        $mainSorterName = $sorter;
        $this->assertEquals($subSorterName, $mainSorterName);
        //check code inside the anonymous function (see the generated function code)
        /*
            $flag1 = is_null($CustomersA) || is_null($CustomersA->Address) || is_null($CustomersA->Address->HouseNumber);
            $flag2 = is_null($CustomersB) || is_null($CustomersB->Address) || is_null($CustomersB->Address->HouseNumber);
            if($flag1 && $flag2) {
                return 0;
            } else if ($flag1) {
                return 1*-1;
            } else if ($flag2) {
                return 1*1;
            }

            $result = strcmp($CustomersA->Address->HouseNumber, $CustomersB->Address->HouseNumber);
            return 1*$result;
         */
        $customer1 = new Customer2();
        $customer2 = new Customer2();
        //When any properties in the orderby path become null for both parameters then they are equal
        $customer1->Address = null;
        $customer2->Address = null;
        $result = $mainSorterName($customer1, $customer2);
        $this->assertEquals($result, 0);
        //When any properties in the orderby path become null for one parameter
        //and if the sort key for second parametr is not null, then second is greater
        $customer1->Address = new Address4();
        $customer1->Address->HouseNumber = null;
        $customer2->Address = new Address4();
        $customer2->Address->HouseNumber = '123';
        $result = $mainSorterName($customer1, $customer2);
        $this->assertEquals($result, -1);
        //reverse, second is lesser
        $customer1->Address = new Address4();
        $customer1->Address->HouseNumber = '123';
        $customer2->Address = new Address4();
        $customer2->Address->HouseNumber = null;
        $result = $mainSorterName($customer1, $customer2);
        $this->assertEquals($result, 1);
        //Try with not-null value for both sort key
        //'ABC' < 'DEF'
        $customer1->Address = new Address4();
        $customer1->Address->HouseNumber = 'ABC';
        $customer2->Address = new Address4();
        $customer2->Address->HouseNumber = 'DEF';
        $result = $mainSorterName($customer1, $customer2);
        $this->assertLessThan(0, $result);
        //'XYZ' > 'PQR'
        $customer1->Address = new Address4();
        $customer1->Address->HouseNumber = 'XYZ';
        $customer2->Address = new Address4();
        $customer2->Address->HouseNumber = 'PQR';
        $result = $mainSorterName($customer1, $customer2);
        $this->assertGreaterThan(0, $result);

        //'MN' == ''MN'
        $customer1->Address = new Address4();
        $customer1->Address->HouseNumber = 'MN';
        $customer2->Address = new Address4();
        $customer2->Address->HouseNumber = 'MN';
        $result = $mainSorterName($customer1, $customer2);
        $this->assertEquals($result, 0);
    }

    /**
     * Entities cannot be sorted using binary property.
     */
    public function testOrderByBinaryProperty()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Photo';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for usage of binary property has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Binary property is not allowed in orderby', $odataException->getMessage());
        }
    }

    /**
     * A primitive property of a resource reference type can be used as sort key.
     */
    public function testOrderByPrimitiveInAResourceReference()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Order/Customer/Rating';
        $internalOrderInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        //Check the dummy object is initialized properly
        $dummyObject = $internalOrderInfo->getDummyObject();
        $this->assertTrue(is_object($dummyObject));
        $this->assertTrue($dummyObject instanceof Order_Details2);
        $this->assertTrue(isset($dummyObject->Order));
        $this->assertTrue(is_object($dummyObject->Order));
        $this->assertTrue($dummyObject->Order instanceof Order2);
        $this->assertTrue(isset($dummyObject->Order->Customer));
        $this->assertTrue(is_object($dummyObject->Order->Customer));
        $this->assertTrue($dummyObject->Order->Customer instanceof Customer2);
        $orderByInfo = $internalOrderInfo->getOrderByInfo();
        //There are navigation (resource reference) properties in the orderby path so getNavigationPropertiesUsed should
        //not be null
        $naviUsed = $orderByInfo->getNavigationPropertiesUsed();
        $this->assertFalse(is_null($naviUsed));
        //Only one main segment so one element in main collection
        $this->assertEquals(count($naviUsed), 1);
        $this->assertTrue(is_array($naviUsed[0]));
        //two navgations used in first orderby path segment 'Order' and 'Customer'
        $this->assertEquals(count($naviUsed[0]), 2);
        $this->assertTrue($naviUsed[0][0] instanceof ResourceProperty);
        $this->assertTrue($naviUsed[0][1] instanceof ResourceProperty);
        //default to library sorting
        $this->assertTrue($orderByInfo->requireInternalSorting());
        //There should be one orderby path segment with three sub path segments
        $orderByPathSegments = $orderByInfo->getOrderByPathSegments();
        $this->assertEquals(count($orderByPathSegments), 1);
        //no sort order, so default to asc
        $this->assertTrue($orderByPathSegments[0]->isAscending());
        //there are two sub path 'Order', 'Customer' and 'Rating'
        $subPathSegments = $orderByPathSegments[0]->getSubPathSegments();
        $this->assertTrue(!is_null($subPathSegments));
        $this->assertEquals(count($subPathSegments), 3);
        $this->assertEquals($subPathSegments[0]->getName(), 'Order');
        $this->assertEquals($subPathSegments[1]->getName(), 'Customer');
        $this->assertEquals($subPathSegments[2]->getName(), 'Rating');
        $this->assertTrue($subPathSegments[0]->getResourceProperty() instanceof ResourceProperty);
        $this->assertTrue($subPathSegments[1]->getResourceProperty() instanceof ResourceProperty);
        $this->assertTrue($subPathSegments[2]->getResourceProperty() instanceof ResourceProperty);
        //There is only one sub sorter function
        $subSorters = $internalOrderInfo->getSubSorterFunctions();
        $this->assertTrue(!is_null($subSorters));
        $this->assertEquals(count($subSorters), 1);
        //Parmater to this sub sort must be Order_DetailsA, Order_DetailsB
//        $this->assertEquals($subSorters[0]->getParametersAsString(), '$Order_DetailsA, $Order_DetailsB');
        //since there is only one sub sorter, that will be the main sorter
        //asset this by comapring the anonymous function names
        $subSorterName = $subSorters[0];
        $sorter = $internalOrderInfo->getSorterFunction();
        $this->assertTrue(!is_null($sorter));
        $mainSorterName = $sorter;
        $this->assertEquals($subSorterName, $mainSorterName);
    }

    /**
     * Orderby path cannot contain invisible resource reference property.
     */
    public function testOrderByWithInvisibleResourceReferencePropertyInThePath()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        //Make 'Orders' visible, make 'Customers' invisible
        $configuration->setEntitySetAccessRule('Orders', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        assert(null != $resourceSetWrapper);
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Customer/CustomerID';

        try {
            OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
            $this->fail('An expected ODataException for navigation to invisible resource set has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringEndsWith("(Check the resource set of the navigation property 'Customer' is visible)", $odataException->getMessage());
        }
    }

    /**
     * test parser with multiple path segment which does not have common ancestors.
     */
    public function testOrderByWithMultiplePathSegment1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Order/Price desc, Product/ProductName asc';
        $internalOrderInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        //Check the dummy object is initialized properly
        $dummyObject = $internalOrderInfo->getDummyObject();
        $this->assertTrue(is_object($dummyObject));
        $this->assertTrue($dummyObject instanceof Order_Details2);
        $this->assertTrue(isset($dummyObject->Order));
        $this->assertTrue(is_object($dummyObject->Order));
        $this->assertTrue($dummyObject->Order instanceof Order2);
        $this->assertTrue(isset($dummyObject->Product));
        $this->assertTrue(is_object($dummyObject->Product));
        $this->assertTrue($dummyObject->Product instanceof Product2);
        $orderByInfo = $internalOrderInfo->getOrderByInfo();
        //There are navigation (resource reference) properties in the orderby path so getNavigationPropertiesUsed should
        //not be null
        $naviUsed = $orderByInfo->getNavigationPropertiesUsed();
        $this->assertFalse(is_null($naviUsed));
        //two path segment so two element in main collection
        $this->assertEquals(count($naviUsed), 2);
        $this->assertTrue(is_array($naviUsed[0]));
        $this->assertTrue(is_array($naviUsed[1]));
        //one navgations used in first orderby 'Order'
        $this->assertEquals(count($naviUsed[0]), 1);
        //one navgations used in second orderby 'Prodcut'
        $this->assertEquals(count($naviUsed[1]), 1);
        $this->assertTrue($naviUsed[0][0] instanceof ResourceProperty);
        $this->assertTrue($naviUsed[1][0] instanceof ResourceProperty);
        //default to library sorting
        $this->assertTrue($orderByInfo->requireInternalSorting());
        //There should be two orderby path segment with two sub path segments
        $orderByPathSegments = $orderByInfo->getOrderByPathSegments();
        $this->assertEquals(count($orderByPathSegments), 2);
        //sort order specified Order/Price desc
        $this->assertFalse($orderByPathSegments[0]->isAscending());
        //sort order specified Prodcut/ProductName asc
        $this->assertTrue($orderByPathSegments[1]->isAscending());
        //there are two sub path 'Address' and 'HouseNumber'
        $subPathSegments = $orderByPathSegments[0]->getSubPathSegments();
        $this->assertTrue(!is_null($subPathSegments));
        $this->assertEquals(count($subPathSegments), 2);
        $this->assertEquals($subPathSegments[0]->getName(), 'Order');
        $this->assertEquals($subPathSegments[1]->getName(), 'Price');
        $this->assertTrue($subPathSegments[0]->getResourceProperty() instanceof ResourceProperty);
        $this->assertTrue($subPathSegments[1]->getResourceProperty() instanceof ResourceProperty);
        $subPathSegments = $orderByPathSegments[1]->getSubPathSegments();
        $this->assertTrue(!is_null($subPathSegments));
        $this->assertEquals(count($subPathSegments), 2);
        $this->assertEquals($subPathSegments[0]->getName(), 'Product');
        $this->assertEquals($subPathSegments[1]->getName(), 'ProductName');
        $this->assertTrue($subPathSegments[0]->getResourceProperty() instanceof ResourceProperty);
        $this->assertTrue($subPathSegments[1]->getResourceProperty() instanceof ResourceProperty);
        //There is two one sub sorter function
        $subSorters = $internalOrderInfo->getSubSorterFunctions();
        $this->assertTrue(!is_null($subSorters));
        $this->assertEquals(count($subSorters), 2);
        //Parmater to first sub sort must be $Order_DetailsA, $Order_DetailsB
//        $this->assertEquals($subSorters[0]->getParametersAsString(), '$Order_DetailsA, $Order_DetailsB');
//        $this->assertEquals($subSorters[1]->getParametersAsString(), '$Order_DetailsA, $Order_DetailsB');
        //generate sub sorter functions with different names
        $subSorterName1 = $subSorters[0];
        $subSorterName2 = $subSorters[1];
//        $this->assertNotEquals($subSorterName1, $subSorterName2);
        $sorter = $internalOrderInfo->getSorterFunction();
        $this->assertTrue(!is_null($sorter));
        $mainSorterName = $sorter;
        //Test the function generated for 'Order/Price desc' path
        /*
         //Function Name: lambda_1

         $flag1 = is_null($Order_DetailsA) || is_null($Order_DetailsA->Order) || is_null($Order_DetailsA->Order->Price);
         $flag2 = is_null($Order_DetailsB) || is_null($Order_DetailsB->Order) || is_null($Order_DetailsB->Order->Price);
         if($flag1 && $flag2) {
            return 0;
         } else if ($flag1) {
            return -1*-1;
         } else if ($flag2) {
            return -1*1;
         }

         $result = $Order_DetailsA->Order->Price > $Order_DetailsB->Order->Price;
         return -1*$result;
         */
        $OrderDetails1 = new Order_Details2();
        $OrderDetails2 = new Order_Details2();
        //When any properties in the orderby path become null for both parameters then they are equal
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = null;
        $OrderDetails2->Order = null;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, 0);
        //When any properties in the orderby path become null for one parameter
        //and if the sort key for second parametr is not null, then second is considered as lesser for desc
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = null;
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Order->Price = 12;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, 1);
        //reverse, second is greater
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = 12;
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Order->Price = null;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, -1);
        //Try with not-null value for both sort key
        //12 < 13
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = 12;
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Order->Price = 13;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, 1);
        //14 > 10
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = 14;
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Order->Price = 10;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, -1);
        //6 == 6
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Order->Price = 6;
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Order->Price = 6;
        $result = $subSorterName1($OrderDetails1, $OrderDetails2);
        $this->assertEquals($result, 0);
        //No test for function generated for 'Product/ProductName asc' path, already done in the testcase testOrderByPrimitiveInAComplex
        /*
        //Function Name: lambda_2

        $flag1 = is_null($Order_DetailsA) || is_null($Order_DetailsA->Product) || is_null($Order_DetailsA->Product->ProductName);
        $flag2 = is_null($Order_DetailsB) || is_null($Order_DetailsB->Product) || is_null($Order_DetailsB->Product->ProductName);
        if($flag1 && $flag2) {
            return 0;
        } else if ($flag1) {
            return 1*-1;
        } else if ($flag2) {
            return 1*1;
        }

        $result = strcmp($Order_DetailsA->Product->ProductName, $Order_DetailsB->Product->ProductName);
        return 1*$result;
         */

        //Test the top level function
        /*
            //Function Name: lambda_3

            $result = call_user_func_array(chr(0) . 'lambda_1', array($Order_DetailsA, $Order_DetailsB));
            if ($result != 0) {
                return $result;
            }

            $result = call_user_func_array(chr(0) . 'lambda_2', array($Order_DetailsA, $Order_DetailsB));
            if ($result != 0) {
                return $result;
            }

            return $result;
         */
        $OrderDetails1->Order = new Order2();
        $OrderDetails1->Product = new Product2();
        $OrderDetails1->Order->Price = 6;
        $OrderDetails1->Product->ProductName = 'AB';
        $OrderDetails2->Order = new Order2();
        $OrderDetails2->Product = new Product2();
        $OrderDetails2->Order->Price = 6;
        $OrderDetails2->Product->ProductName = 'DE';
        $result = $mainSorterName($OrderDetails1, $OrderDetails2);
        $this->assertLessThan(0, $result);
    }

    /**
     * test parser with multiple path segment which has common ancestors.
     */
    public function testOrderByWithMultiplePathSegment2()
    {
    }

    /**
     * Test whether order by parser identify and remove path duplication.
     */
    public function testOrderByWithPathDuplication()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Order/Price desc, Product/ProductName asc, Order/Price asc';
        $internalOrderInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        //The orderby path Order/Price appears twice, but parser will consider only first path
        $orderByInfo = $internalOrderInfo->getOrderByInfo();
        //There are navigation (resource reference) properties in the orderby path so getNavigationPropertiesUsed should
        //not be null
        $naviUsed = $orderByInfo->getNavigationPropertiesUsed();
        $this->assertFalse(is_null($naviUsed));
        //3 path segment are there, but last one is duplicate of first one, so parser removes last one
        $this->assertEquals(count($naviUsed), 2);
        $this->assertTrue(is_array($naviUsed[0]));
        $this->assertTrue(is_array($naviUsed[1]));
        //one navgations used in first orderby 'Order'
        $this->assertEquals(count($naviUsed[0]), 1);
        //one navgations used in second orderby 'Prodcut'
        $this->assertEquals(count($naviUsed[1]), 1);
        $this->assertEquals($naviUsed[0][0]->getName(), 'Order');
        $this->assertEquals($naviUsed[1][0]->getName(), 'Product');
        $orderByPathSegments = $orderByInfo->getOrderByPathSegments();
        $this->assertEquals(count($orderByPathSegments), 2);
    }

    public function testParseOrderByClauseWithBlankString()
    {
        $wrapper = m::mock(ResourceSetWrapper::class);
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass());
        $orderBy = " ";
        $provider = m::mock(ProvidersWrapper::class);

        $expected = "assert(): OrderBy clause must not be trimmable to an empty string failed";
        $actual = null;

        try {
            OrderByParser::parseOrderByClause($wrapper, $type, $orderBy, $provider);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
