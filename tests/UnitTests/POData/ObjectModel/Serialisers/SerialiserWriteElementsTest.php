<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext as OperationContextAdapter;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\Facets\NorthWind1\OrderDetails2;
use UnitTests\POData\ObjectModel\reusableEntityClass1;

class SerialiserWriteElementsTest extends SerialiserTestBase
{
    public function testCompareWriteMultipleModelsNoPageOverrun()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $model2               = new Customer2();
        $model2->CustomerID   = 2;
        $model2->CustomerGuid = '223e4567-e89b-12d3-a456-426655440000';

        $models = [$model, $model2];

        $results             = [new QueryResult(), new QueryResult()];
        $results[0]->results = $models[0];
        $results[1]->results = $models[1];

        $collection          = new QueryResult();
        $collection->results = $results;

        $content1 = $this->generateCustomerProperties();
        $content2 = $this->generateCustomerProperties();

        $content1['CustomerID']->value   = '1';
        $content1['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';

        $content2['CustomerID']->value   = '2';
        $content2['CustomerGuid']->value = '223e4567-e89b-12d3-a456-426655440000';

        $linkContent = [$content1, $content2];

        $links                  = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
                'Orders',
                'application/atom+xml;type=feed',
                'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders',
                true,
                null,
                false
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
                'Orders',
                'application/atom+xml;type=feed',
                'Customers(CustomerID=\'2\',CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')/Orders',
                true,
                null,
                false
            )
        ];

        $entry        = [new ODataEntry(), new ODataEntry()];
        $entry[0]->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                        . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry[0]->title         = new ODataTitle('Customer');
        $entry[0]->type          = new ODataCategory('NorthWind.Customer');
        $entry[0]->editLink      = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\','
            . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $entry[0]->links[]         = $links[0];
        $entry[0]->propertyContent = $linkContent[0];
        $entry[0]->resourceSetName = 'Customers';
        $entry[0]->updated         = '2017-01-01T00:00:00+00:00';
        $entry[1]->id              = 'http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
                        . '=guid\'223e4567-e89b-12d3-a456-426655440000\')';
        $entry[1]->title         = new ODataTitle('Customer');
        $entry[1]->type          = new ODataCategory('NorthWind.Customer');
        $entry[1]->editLink      = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'2\','
            . 'CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')');
        $entry[1]->links[]         = $links[1];
        $entry[1]->propertyContent = $linkContent[1];
        $entry[1]->resourceSetName = 'Customers';
        $entry[1]->updated         = '2017-01-01T00:00:00+00:00';

        $selfLink        = new ODataLink('self', 'Customers', null, 'Customers');

        $objectResult           = new ODataFeed();
        $objectResult->id       = 'http://localhost/odata.svc/Customers';
        $objectResult->title    = new ODataTitle('Customers');
        $objectResult->setSelfLink($selfLink);
        $objectResult->entries  = $entry;
        $objectResult->updated  = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI  = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteMultipleModelsHasPageOverrun()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host, 10);

        $results          = new QueryResult();
        $results->hasMore = true;
        $results->results = [];
        for ($i = 1; $i < 301; $i++) {
            $model               = new Customer2();
            $model->CustomerID   = $i;
            $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
            $res                 = new QueryResult();
            $res->results        = $model;
            $results->results[]  = (0 == $i % 2) ? $res : $model;
        }

        $entries = [];
        for ($i = 1; $i < 301; $i++) {
            $editStub = 'Customers(CustomerID=\'' . $i . '\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';

            $link               = new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
                'Orders',
                'application/atom+xml;type=feed',
                $editStub . '/Orders',
                true
            );

            $cand                                                     = new ODataEntry();
            $cand->id                                                 = 'http://localhost/odata.svc/' . $editStub;
            $cand->editLink                                           = new ODataLink('edit', 'Customer', null, $editStub);
            $cand->title                                              = new ODataTitle('Customer');
            $cand->type                                               = new ODataCategory('NorthWind.Customer');
            $cand->propertyContent                                    = $this->generateCustomerProperties();
            $cand->propertyContent['CustomerID']->value               = strval($i);
            $cand->propertyContent['CustomerGuid']->value             = '123e4567-e89b-12d3-a456-426655440000';
            $cand->links                                              = [$link];
            $cand->resourceSetName                                    = 'Customers';
            $cand->updated                                            = '2017-01-01T00:00:00+00:00';

            $entries[] = $cand;
        }

        $selfLink        = new ODataLink('self', 'Customers', null, 'Customers');

        $nextLink       = new ODataLink('next', null, null, 'http://localhost/odata.svc/Customers?$skiptoken=\'300\''
            . ', guid\'123e4567-e89b-12d3-a456-426655440000\'');


        $objectResult               = new ODataFeed();
        $objectResult->id           = 'http://localhost/odata.svc/Customers';
        $objectResult->title        = new ODataTitle('Customers');
        $objectResult->setSelfLink($selfLink);
        $objectResult->nextPageLink = $nextLink;
        $objectResult->entries      = $entries;
        $objectResult->updated      = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI      = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElements($results);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteModelsOnManyEndOfRelation()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)/Order_Details');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)/Order_Details');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $deet1            = new OrderDetails2();
        $deet1->OrderID   = 1;
        $deet1->ProductID = 1;
        $deet2            = new OrderDetails2();
        $deet2->OrderID   = 1;
        $deet2->ProductID = 2;

        $collection          = new QueryResult();
        $collection->results = [$deet1, $deet2];

        $selfLink        = new ODataLink('self', 'Order_Details', null, 'Order_Details');

        $links              = [];
        $links[]            = [new ODataLink(), new ODataLink()];
        $links[]            = [new ODataLink(), new ODataLink()];
        $links[0][0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order');
        $links[0][1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product');
        $links[1][0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order');
        $links[1][1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product');
        $links[0][0]->setTitle('Order');
        $links[0][1]->setTitle('Product');
        $links[1][0]->setTitle('Order');
        $links[1][1]->setTitle('Product');
        $links[0][0]->setType('application/atom+xml;type=entry');
        $links[0][1]->setType('application/atom+xml;type=entry');
        $links[1][0]->setType('application/atom+xml;type=entry');
        $links[1][1]->setType('application/atom+xml;type=entry');
        $links[0][0]->setUrl('Order_Details(ProductID=1,OrderID=1)/Order');
        $links[0][1]->setUrl('Order_Details(ProductID=1,OrderID=1)/Product');
        $links[1][0]->setUrl('Order_Details(ProductID=2,OrderID=1)/Order');
        $links[1][1]->setUrl('Order_Details(ProductID=2,OrderID=1)/Product');

        $entries                                                     = [new ODataEntry(), new ODataEntry()];
        $entries[0]->id                                              = 'http://localhost/odata.svc/Order_Details(ProductID=1,OrderID=1)';
        $entries[0]->title                                           = new ODataTitle('Order_Details');
        $entries[0]->editLink                                        = new ODataLink('edit', 'Order_Details', null, 'Order_Details(ProductID=1,OrderID=1)');
        $entries[0]->type                                            = new ODataCategory('NorthWind.Order_Details');
        $entries[0]->propertyContent                                 = $this->generateOrderDetailsProperties();
        $entries[0]->propertyContent['ProductID']->value             = '1';
        $entries[0]->propertyContent['OrderID']->value               = '1';
        $entries[0]->links                                           = $links[0];
        $entries[0]->resourceSetName                                 = 'Order_Details';
        $entries[0]->updated                                         = '2017-01-01T00:00:00+00:00';
        $entries[1]->id                                              = 'http://localhost/odata.svc/Order_Details(ProductID=2,OrderID=1)';
        $entries[1]->title                                           = new ODataTitle('Order_Details');
        $entries[1]->editLink                                        = new ODataLink('edit', 'Order_Details', null, 'Order_Details(ProductID=2,OrderID=1)');
        $entries[1]->type                                            = new ODataCategory('NorthWind.Order_Details');
        $entries[1]->propertyContent                                 = $this->generateOrderDetailsProperties();
        $entries[1]->propertyContent['ProductID']->value             = '2';
        $entries[1]->propertyContent['OrderID']->value               = '1';
        $entries[1]->links                                           = $links[1];
        $entries[1]->resourceSetName                                 = 'Order_Details';
        $entries[1]->updated                                         = '2017-01-01T00:00:00+00:00';

        $objectResult           = new ODataFeed();
        $objectResult->id       = 'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details';
        $objectResult->title    = new ODataTitle('Order_Details');
        $objectResult->setSelfLink($selfLink);
        $objectResult->entries  = $entries;
        $objectResult->updated  = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI  = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteTopLevelElementsAllExpanded()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers?$expand=Orders');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers?$expand=Orders');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $order            = new Order2();
        $order->OrderID   = 1;
        $order->ProductID = 42;

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->Orders       = [$order];

        $results             = [new QueryResult(), new QueryResult()];
        $results[0]->results = $model;
        $results[1]->results = $model;

        $collection          = new QueryResult();
        $collection->results = $results;

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Orders');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $selfLink        = new ODataLink('self', 'Customers', null, 'Customers');


        $detailsFeed                  = new ODataFeed();
        $detailsFeed->id              = 'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details';
        $detailsFeed->title           = new ODataTitle('Order_Details');
        $detailsFeed->setSelfLink(new ODataLink('self', 'Order_Details', null, 'Orders(OrderID=1)/Order_Details'));

        $customerEntry                  = new ODataEntry();
        $customerEntry->resourceSetName = 'Customer';

        $subLinks                    = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer',
                'Customer',
                'application/atom+xml;type=entry',
                'Orders(OrderID=1)/Customer',
                false,
                new ODataExpandedResult($customerEntry),
                true
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details',
                'Order_Details',
                'application/atom+xml;type=feed',
                'Orders(OrderID=1)/Order_Details',
                true,
                new ODataExpandedResult($detailsFeed),
                true
            )
        ];

        $subEntry                                                = new ODataEntry();
        $subEntry->id                                            = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $subEntry->title                                         = new ODataTitle('Order');
        $subEntry->editLink                                      = new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)');
        $subEntry->type                                          = new ODataCategory('NorthWind.Order');
        $subEntry->resourceSetName                               = 'Orders';
        $subEntry->propertyContent                               = $this->generateOrderProperties();
        $subEntry->propertyContent['OrderID']->value             = '1';
        $subEntry->links                                         = $subLinks;
        $subEntry->updated                                       = '2017-01-01T00:00:00+00:00';

        $subSelf        = new ODataLink('self', 'Orders', null, 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders');

        $subFeed     = new ODataFeed();
        $subFeed->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                       . '=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $subFeed->title    = new ODataTitle('Orders');
        $subFeed->setSelfLink($subSelf);
        $subFeed->entries  = [$subEntry];
        $subFeed->updated  = '2017-01-01T00:00:00+00:00';

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
            'Orders',
            'application/atom+xml;type=feed',
            'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders',
            true,
            new ODataExpandedResult($subFeed),
            true
        );

        $entry     = new ODataEntry();
        $entry->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                     . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry->title                                              = new ODataTitle('Customer');
        $entry->editLink                                           = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $entry->type                                               = new ODataCategory('NorthWind.Customer');
        $entry->resourceSetName                                    = 'Customers';
        $entry->propertyContent                                    = $this->generateCustomerProperties();
        $entry->propertyContent['CustomerID']->value               = '1';
        $entry->propertyContent['CustomerGuid']->value             = '123e4567-e89b-12d3-a456-426655440000';
        $entry->links                                              = [$link];
        $entry->updated                                            = '2017-01-01T00:00:00+00:00';

        $objectResult           = new ODataFeed();
        $objectResult->id       = 'http://localhost/odata.svc/Customers';
        $objectResult->title    = new ODataTitle('Customers');
        $objectResult->setSelfLink($selfLink);
        $objectResult->entries  = [$entry, $entry];
        $objectResult->updated  = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI  = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteTopLevelElementsWithEmptyArrayPayloadAndHasMore()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host, 10);

        $collection          = new QueryResult();
        $collection->results = [];
        $collection->hasMore = true;

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Orders');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $result = $ironic->writeTopLevelElements($collection);
        $this->assertEquals(0, count($result->entries));
    }

    public function testWriteTopLevelElementsWithEmptyCollectionPayloadAndHasMore()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host, 10);

        $collection          = new QueryResult();
        $collection->results = [];
        $collection->hasMore = true;

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Orders');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, false);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $result = $ironic->writeTopLevelElements($collection);
        $this->assertEquals(0, count($result->entries));
    }

    public function testWriteNullTopLevelElements()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $collection          = new QueryResult();
        $collection->results = null;

        $models                 = null;
        $expected               = '!is_array($entryObjects->results)';
        $expectedExceptionClass = InvalidOperationException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelElements($collection);
        } catch (InvalidOperationException $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteElementsWithMediaLinks()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Employees');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Employees');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $mod1                 = new Employee2();
        $mod1->EmployeeID     = 1;
        $mod1->TumbNail_48X48 = 'Bork bork bork!';
        $mod2                 = new Employee2();
        $mod2->EmployeeID     = 2;
        $mod2->TumbNail_48X48 = 'Bork bork bork!';

        $collection          = new QueryResult();
        $collection->results = [$mod1, $mod2];

        $mediaLink = [
            new ODataMediaLink(
                'NorthWind.Employee',
                '/$value',
                'Employees(EmployeeID=\'1\')/$value',
                '*/*',
                '',
                'edit-media'
            ),
            new ODataMediaLink(
                'NorthWind.Employee',
                '/$value',
                'Employees(EmployeeID=\'2\')/$value',
                '*/*',
                '',
                'edit-media'
            )
        ];

        $mediaLinks = [
            new ODataMediaLink(
                'TumbNail_48X48',
                'Employees(EmployeeID=\'1\')/TumbNail_48X48',
                'Employees(EmployeeID=\'1\')/TumbNail_48X48',
                'application/octet-stream',
                ''
            ),
            new ODataMediaLink(
                'TumbNail_48X48',
                'Employees(EmployeeID=\'2\')/TumbNail_48X48',
                'Employees(EmployeeID=\'2\')/TumbNail_48X48',
                'application/octet-stream',
                ''
            )
        ];

        $links                     = [];
        $links[]                   = [new ODataLink(), new ODataLink()];
        $links[]                   = [new ODataLink(), new ODataLink()];
        $links[0][0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager');
        $links[0][1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates');
        $links[1][0]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager');
        $links[1][1]->setName('http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates');
        $links[0][0]->setTitle('Manager');
        $links[0][1]->setTitle('Subordinates');
        $links[1][0]->setTitle('Manager');
        $links[1][1]->setTitle('Subordinates');
        $links[0][0]->setType('application/atom+xml;type=entry');
        $links[0][1]->setType('application/atom+xml;type=feed');
        $links[1][0]->setType('application/atom+xml;type=entry');
        $links[1][1]->setType('application/atom+xml;type=feed');
        $links[0][0]->setUrl('Employees(EmployeeID=\'1\')/Manager');
        $links[0][1]->setUrl('Employees(EmployeeID=\'1\')/Subordinates');
        $links[1][0]->setUrl('Employees(EmployeeID=\'2\')/Manager');
        $links[1][1]->setUrl('Employees(EmployeeID=\'2\')/Subordinates');
        $links[0][0]->setIsCollection(false);
        $links[0][1]->setIsCollection(true);
        $links[0][0]->setIsCollection(false);
        $links[1][1]->setIsCollection(true);
        $links[0][0]->setIsExpanded(false);
        $links[0][1]->setIsExpanded(false);
        $links[0][0]->setIsExpanded(false);
        $links[1][1]->setIsExpanded(false);

        $prop1 = $this->generateEmployeeProperties();
        $prop2 = $this->generateEmployeeProperties();

        $prop1['EmployeeID']->value = '1';
        $prop2['EmployeeID']->value = '2';

        $entries                      = [new ODataEntry(), new ODataEntry()];
        $entries[0]->id               = 'http://localhost/odata.svc/Employees(EmployeeID=\'1\')';
        $entries[0]->title            = new ODataTitle('Employee');
        $entries[0]->editLink         = new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'1\')');
        $entries[0]->type             = new ODataCategory('NorthWind.Employee');
        $entries[0]->isMediaLinkEntry = true;
        $entries[0]->mediaLink        = $mediaLink[0];
        $entries[0]->mediaLinks[]     = $mediaLinks[0];
        $entries[0]->links            = $links[0];
        $entries[0]->propertyContent  = $prop1;
        $entries[0]->resourceSetName  = 'Employees';
        $entries[0]->updated          = '2017-01-01T00:00:00+00:00';
        $entries[1]->id               = 'http://localhost/odata.svc/Employees(EmployeeID=\'2\')';
        $entries[1]->title            = new ODataTitle('Employee');
        $entries[1]->editLink         = new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'2\')');
        $entries[1]->type             = new ODataCategory('NorthWind.Employee');
        $entries[1]->isMediaLinkEntry = true;
        $entries[1]->mediaLink        = $mediaLink[1];
        $entries[1]->mediaLinks[]     = $mediaLinks[1];
        $entries[1]->links            = $links[1];
        $entries[1]->propertyContent  = $prop2;
        $entries[1]->resourceSetName  = 'Employees';
        $entries[1]->updated          = '2017-01-01T00:00:00+00:00';

        $selfLink        = new ODataLink('self', 'Employees', null, 'Employees');

        $objectResult           = new ODataFeed();
        $objectResult->id       = 'http://localhost/odata.svc/Employees';
        $objectResult->title    = new ODataTitle('Employees');
        $objectResult->setSelfLink($selfLink);
        $objectResult->entries  = $entries;
        $objectResult->updated  = '2017-01-01T00:00:00+00:00';
        $objectResult->baseURI  = 'http://localhost/odata.svc/';

        $ironicResult = $ironic->writeTopLevelElements($collection);

        // zero out etag values
        $ironicResult->entries[0]->mediaLink->eTag     = '';
        $ironicResult->entries[1]->mediaLink->eTag     = '';
        $ironicResult->entries[0]->mediaLinks[0]->eTag = '';
        $ironicResult->entries[1]->mediaLinks[0]->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op   = new OperationContextAdapter($request);
        $host = new ServiceHost($op);

        $meta  = NorthWindMetadata::Create();
        $query = m::mock(IQueryProvider::class);

        return [$host, $meta, $query];
    }

    /**
     * @param $query
     * @param $meta
     * @param $host
     * @param  mixed $pageSize
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host, $pageSize = 200)
    {
        // default data service
        $service                            = new TestDataService($query, $meta, $host);
        $service->maxPageSize               = $pageSize;
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return [$object, $ironic];
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateEmployeeProperties()
    {
        return new ODataPropertyContent(
            [
                'EmployeeID' => new ODataProperty('EmployeeID', 'Edm.String', null),
                'FirstName' => new ODataProperty('FirstName', 'Edm.String', null),
                'LastName' => new ODataProperty('LastName', 'Edm.String', null),
                'ReportsTo' => new ODataProperty('ReportsTo', 'Edm.Int32', null),
                'Emails' => new ODataProperty('Emails', 'Collection(Edm.String)', null)
            ]
        );
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateCustomerProperties()
    {
        return new ODataPropertyContent(
            [
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', null),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', null),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', null),
                'Country' => new ODataProperty('Country', 'Edm.String', null),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', null),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderDetailsProperties()
    {
        return new ODataPropertyContent(
            [
                'ProductID' => new ODataProperty('ProductID', 'Edm.Int32', null),
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', null),
                'UnitPrice' => new ODataProperty('UnitPrice', 'Edm.Decimal', null),
                'Quantity' => new ODataProperty('Quantity', 'Edm.Int16', null),
                'Discount' => new ODataProperty('Discount', 'Edm.Single', null)
            ]
        );
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderProperties()
    {
        return new ODataPropertyContent([
            'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', null),
            'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
            'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
            'ShipName' => new ODataProperty('ShipName', 'Edm.String', null),
            'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', null),
            'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
            'Price' => new ODataProperty('Price', 'Edm.Double', null)
        ]);
    }
}
