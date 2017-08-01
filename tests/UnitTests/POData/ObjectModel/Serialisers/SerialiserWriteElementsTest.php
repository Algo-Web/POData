<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
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
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
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
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $model2 = new Customer2();
        $model2->CustomerID = 2;
        $model2->CustomerGuid = '223e4567-e89b-12d3-a456-426655440000';

        $models = [$model, $model2];

        $results = [new QueryResult(), new QueryResult()];
        $results[0]->results = $models[0];
        $results[1]->results = $models[1];

        $collection = new QueryResult();
        $collection->results = $results;

        $content1 = $this->generateCustomerProperties();
        $content2 = $this->generateCustomerProperties();

        $content1->properties[0]->value = '1';
        $content1->properties[1]->value = '123e4567-e89b-12d3-a456-426655440000';

        $content2->properties[0]->value = '2';
        $content2->properties[1]->value = '223e4567-e89b-12d3-a456-426655440000';

        $linkContent = [$content1, $content2];

        $links = [new ODataLink(), new ODataLink()];
        $links[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $links[0]->title = 'Orders';
        $links[0]->type = 'application/atom+xml;type=feed';
        $links[0]->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $links[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $links[1]->title = 'Orders';
        $links[1]->type = 'application/atom+xml;type=feed';
        $links[1]->url = 'Customers(CustomerID=\'2\',CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')/Orders';

        $entry = [new ODataEntry(), new ODataEntry()];
        $entry[0]->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                        .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry[0]->title = 'Customer';
        $entry[0]->type = 'Customer';
        $entry[0]->editLink = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry[0]->links[] = $links[0];
        $entry[0]->propertyContent = $linkContent[0];
        $entry[0]->resourceSetName = 'Customers';
        $entry[1]->id = 'http://localhost/odata.svc/Customers(CustomerID=\'2\',CustomerGuid'
                        .'=guid\'223e4567-e89b-12d3-a456-426655440000\')';
        $entry[1]->title = 'Customer';
        $entry[1]->type = 'Customer';
        $entry[1]->editLink = 'Customers(CustomerID=\'2\',CustomerGuid=guid\'223e4567-e89b-12d3-a456-426655440000\')';
        $entry[1]->links[] = $links[1];
        $entry[1]->propertyContent = $linkContent[1];
        $entry[1]->resourceSetName = 'Customers';

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = 'Customers';
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entry;

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteMultipleModelsHasPageOverrun()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host, 10);

        $results = new QueryResult();
        $results->hasMore = true;
        $results->results = [];
        for ($i = 1; $i < 301; $i++) {
            $model = new Customer2();
            $model->CustomerID = $i;
            $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
            $res = new QueryResult();
            $res->results = $model;
            $results->results[] = (0 == $i % 2) ? $res : $model;
        }

        $entries = [];
        for ($i = 1; $i < 301; $i++) {
            $editStub = 'Customers(CustomerID=\''.$i.'\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';

            $link = new ODataLink();
            $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
            $link->title = 'Orders';
            $link->type = 'application/atom+xml;type=feed';
            $link->url = $editStub.'/Orders';

            $cand = new ODataEntry();
            $cand->id = 'http://localhost/odata.svc/'.$editStub;
            $cand->editLink = $editStub;
            $cand->title = 'Customer';
            $cand->type = 'Customer';
            $cand->propertyContent = $this->generateCustomerProperties();
            $cand->propertyContent->properties[0]->value = strval($i);
            $cand->propertyContent->properties[1]->value = '123e4567-e89b-12d3-a456-426655440000';
            $cand->links = [$link];
            $cand->resourceSetName = 'Customers';

            $entries[] = $cand;
        }

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $nextLink = new ODataLink();
        $nextLink->name = 'next';
        $nextLink->url = 'http://localhost/odata.svc/Customers?$skiptoken=\'300\''
                         .', guid\'123e4567-e89b-12d3-a456-426655440000\'';


        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = 'Customers';
        $objectResult->selfLink = $selfLink;
        $objectResult->nextPageLink = $nextLink;
        $objectResult->entries = $entries;

        $ironicResult = $ironic->writeTopLevelElements($results);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteModelsOnManyEndOfRelation()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)/Order_Details');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)/Order_Details');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $deet1 = new OrderDetails2();
        $deet1->OrderID = 1;
        $deet1->ProductID = 1;
        $deet2 = new OrderDetails2();
        $deet2->OrderID = 1;
        $deet2->ProductID = 2;

        $collection = new QueryResult();
        $collection->results = [$deet1, $deet2];

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Order_Details';
        $selfLink->url = 'Order_Details';

        $links = [];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[0][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $links[0][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product';
        $links[1][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order';
        $links[1][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product';
        $links[0][0]->title = 'Order';
        $links[0][1]->title = 'Product';
        $links[1][0]->title = 'Order';
        $links[1][1]->title = 'Product';
        $links[0][0]->type = 'application/atom+xml;type=entry';
        $links[0][1]->type = 'application/atom+xml;type=entry';
        $links[1][0]->type = 'application/atom+xml;type=entry';
        $links[1][1]->type = 'application/atom+xml;type=entry';
        $links[0][0]->url = 'Order_Details(ProductID=1,OrderID=1)/Order';
        $links[0][1]->url = 'Order_Details(ProductID=1,OrderID=1)/Product';
        $links[1][0]->url = 'Order_Details(ProductID=2,OrderID=1)/Order';
        $links[1][1]->url = 'Order_Details(ProductID=2,OrderID=1)/Product';

        $entries = [new ODataEntry(), new ODataEntry()];
        $entries[0]->id = 'http://localhost/odata.svc/Order_Details(ProductID=1,OrderID=1)';
        $entries[0]->title = 'Order_Details';
        $entries[0]->editLink = 'Order_Details(ProductID=1,OrderID=1)';
        $entries[0]->type = 'Order_Details';
        $entries[0]->propertyContent = $this->generateOrderDetailsProperties();
        $entries[0]->propertyContent->properties[0]->value = '1';
        $entries[0]->propertyContent->properties[1]->value = '1';
        $entries[0]->links = $links[0];
        $entries[0]->resourceSetName = 'Order_Details';
        $entries[1]->id = 'http://localhost/odata.svc/Order_Details(ProductID=2,OrderID=1)';
        $entries[1]->title = 'Order_Details';
        $entries[1]->editLink = 'Order_Details(ProductID=2,OrderID=1)';
        $entries[1]->type = 'Order_Details';
        $entries[1]->propertyContent = $this->generateOrderDetailsProperties();
        $entries[1]->propertyContent->properties[0]->value = '2';
        $entries[1]->propertyContent->properties[1]->value = '1';
        $entries[1]->links = $links[1];
        $entries[1]->resourceSetName = 'Order_Details';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details';
        $objectResult->title = 'Order_Details';
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entries;

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteTopLevelElementsAllExpanded()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers?$expand=Orders');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers?$expand=Orders');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $order = new Order2();
        $order->OrderID = 1;
        $order->ProductID = 42;

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->Orders = [$order];

        $results = [new QueryResult(), new QueryResult()];
        $results[0]->results = $model;
        $results[1]->results = $model;

        $collection = new QueryResult();
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

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Customers';
        $selfLink->url = 'Customers';

        $subLinks = [new ODataLink(), new ODataLink()];
        $subLinks[0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer';
        $subLinks[0]->title = 'Customer';
        $subLinks[0]->type = 'application/atom+xml;type=entry';
        $subLinks[0]->url = 'Orders(OrderID=1)/Customer';
        $subLinks[1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details';
        $subLinks[1]->title = 'Order_Details';
        $subLinks[1]->type = 'application/atom+xml;type=feed';
        $subLinks[1]->url = 'Orders(OrderID=1)/Order_Details';

        $subEntry = new ODataEntry();
        $subEntry->id = 'http://localhost/odata.svc/Orders(OrderID=1)';
        $subEntry->title = 'Order';
        $subEntry->editLink = 'Orders(OrderID=1)';
        $subEntry->type = 'Order';
        $subEntry->resourceSetName = 'Orders';
        $subEntry->propertyContent = $this->generateOrderProperties();
        $subEntry->propertyContent->properties[0]->value = '1';
        $subEntry->links = $subLinks;

        $subSelf = new ODataLink();
        $subSelf->name = 'self';
        $subSelf->title = 'Orders';
        $subSelf->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';

        $subFeed = new ODataFeed();
        $subFeed->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                       .'=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $subFeed->title = 'Orders';
        $subFeed->selfLink = $subSelf;
        $subFeed->entries = [$subEntry];

        $link = new ODataLink();
        $link->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders';
        $link->title = 'Orders';
        $link->type = 'application/atom+xml;type=feed';
        $link->url = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $link->isCollection = true;
        $link->isExpanded = true;
        $link->expandedResult = $subFeed;

        $entry = new ODataEntry();
        $entry->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                     .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry->title = 'Customer';
        $entry->editLink = 'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $entry->type = 'Customer';
        $entry->resourceSetName = 'Customers';
        $entry->propertyContent = $this->generateCustomerProperties();
        $entry->propertyContent->properties[0]->value = '1';
        $entry->propertyContent->properties[1]->value = '123e4567-e89b-12d3-a456-426655440000';
        $entry->links = [$link];

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Customers';
        $objectResult->title = 'Customers';
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = [$entry, $entry];

        $ironicResult = $ironic->writeTopLevelElements($collection);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteNullTopLevelElements()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $collection = new QueryResult();
        $collection->results = null;

        $models = null;
        $expected = 'assert(): !is_array($entryObjects->results) failed';
        $expectedExceptionClass = \PHPUnit_Framework_Error_Warning::class;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $ironic->writeTopLevelElements($collection);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteElementsWithMediaLinks()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Employees');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Employees');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $mod1 = new Employee2();
        $mod1->EmployeeID = 1;
        $mod1->TumbNail_48X48 = 'Bork bork bork!';
        $mod2 = new Employee2();
        $mod2->EmployeeID = 2;
        $mod2->TumbNail_48X48 = 'Bork bork bork!';

        $collection = new QueryResult();
        $collection->results = [$mod1, $mod2];

        $mediaLink = [new ODataMediaLink('Employee', '/$value', 'Employees(EmployeeID=\'1\')/$value', '*/*', ''),
            new ODataMediaLink('Employee', '/$value', 'Employees(EmployeeID=\'2\')/$value', '*/*', '')];

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

        $links = [];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[] = [new ODataLink(), new ODataLink()];
        $links[0][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $links[0][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $links[1][0]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager';
        $links[1][1]->name = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates';
        $links[0][0]->title = 'Manager';
        $links[0][1]->title = 'Subordinates';
        $links[1][0]->title = 'Manager';
        $links[1][1]->title = 'Subordinates';
        $links[0][0]->type = 'application/atom+xml;type=entry';
        $links[0][1]->type = 'application/atom+xml;type=feed';
        $links[1][0]->type = 'application/atom+xml;type=entry';
        $links[1][1]->type = 'application/atom+xml;type=feed';
        $links[0][0]->url = 'Employees(EmployeeID=\'1\')/Manager';
        $links[0][1]->url = 'Employees(EmployeeID=\'1\')/Subordinates';
        $links[1][0]->url = 'Employees(EmployeeID=\'2\')/Manager';
        $links[1][1]->url = 'Employees(EmployeeID=\'2\')/Subordinates';

        $prop1 = $this->generateEmployeeProperties();
        $prop2 = $this->generateEmployeeProperties();

        $prop1->properties[0]->value = '1';
        $prop2->properties[0]->value = '2';

        $entries = [new ODataEntry(), new ODataEntry];
        $entries[0]->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'1\')';
        $entries[0]->title = 'Employee';
        $entries[0]->editLink =  'Employees(EmployeeID=\'1\')';
        $entries[0]->type = 'Employee';
        $entries[0]->isMediaLinkEntry = true;
        $entries[0]->mediaLink = $mediaLink[0];
        $entries[0]->mediaLinks[] = $mediaLinks[0];
        $entries[0]->links = $links[0];
        $entries[0]->propertyContent = $prop1;
        $entries[0]->resourceSetName = 'Employees';
        $entries[1]->id = 'http://localhost/odata.svc/Employees(EmployeeID=\'2\')';
        $entries[1]->title = 'Employee';
        $entries[1]->editLink =  'Employees(EmployeeID=\'2\')';
        $entries[1]->type = 'Employee';
        $entries[1]->isMediaLinkEntry = true;
        $entries[1]->mediaLink = $mediaLink[1];
        $entries[1]->mediaLinks[] = $mediaLinks[1];
        $entries[1]->links = $links[1];
        $entries[1]->propertyContent = $prop2;
        $entries[1]->resourceSetName = 'Employees';

        $selfLink = new ODataLink();
        $selfLink->name = 'self';
        $selfLink->title = 'Employees';
        $selfLink->url = 'Employees';

        $objectResult = new ODataFeed();
        $objectResult->id = 'http://localhost/odata.svc/Employees';
        $objectResult->title = 'Employees';
        $objectResult->selfLink = $selfLink;
        $objectResult->entries = $entries;

        $ironicResult = $ironic->writeTopLevelElements($collection);

        // zero out etag values
        $ironicResult->entries[0]->mediaLink->eTag = '';
        $ironicResult->entries[1]->mediaLink->eTag = '';
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
        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);

        $meta = NorthWindMetadata::Create();
        $query = m::mock(IQueryProvider::class);

        return [$host, $meta, $query];
    }

    /**
     * @param $query
     * @param $meta
     * @param $host
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host, $pageSize = 200)
    {
        // default data service
        $service = new TestDataService($query, $meta, $host);
        $service->maxPageSize = $pageSize;
        $processor = $service->handleRequest();
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
        $prop1 = new ODataPropertyContent();
        $prop1->properties = [
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty()
        ];
        $prop1->properties[0]->name = 'EmployeeID';
        $prop1->properties[0]->typeName = 'Edm.String';
        $prop1->properties[1]->name = 'FirstName';
        $prop1->properties[1]->typeName = 'Edm.String';
        $prop1->properties[2]->name = 'LastName';
        $prop1->properties[2]->typeName = 'Edm.String';
        $prop1->properties[3]->name = 'ReportsTo';
        $prop1->properties[3]->typeName = 'Edm.Int32';
        $prop1->properties[4]->name = 'Emails';
        $prop1->properties[4]->typeName = 'Collection(Edm.String)';
        return $prop1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateCustomerProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty()
        ];
        $content1->properties[0]->name = 'CustomerID';
        $content1->properties[0]->typeName = 'Edm.String';
        $content1->properties[1]->name = 'CustomerGuid';
        $content1->properties[1]->typeName = 'Edm.Guid';
        $content1->properties[2]->name = 'CustomerName';
        $content1->properties[2]->typeName = 'Edm.String';
        $content1->properties[3]->name = 'Country';
        $content1->properties[3]->typeName = 'Edm.String';
        $content1->properties[4]->name = 'Rating';
        $content1->properties[4]->typeName = 'Edm.Int32';
        $content1->properties[5]->name = 'Photo';
        $content1->properties[5]->typeName = 'Edm.Binary';
        $content1->properties[6]->name = 'Address';
        $content1->properties[6]->typeName = 'Address';
        return $content1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderDetailsProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty()
        ];
        $content1->properties[0]->name = 'ProductID';
        $content1->properties[0]->typeName = 'Edm.Int32';
        $content1->properties[1]->name = 'OrderID';
        $content1->properties[1]->typeName = 'Edm.Int32';
        $content1->properties[2]->name = 'UnitPrice';
        $content1->properties[2]->typeName = 'Edm.Decimal';
        $content1->properties[3]->name = 'Quantity';
        $content1->properties[3]->typeName = 'Edm.Int16';
        $content1->properties[4]->name = 'Discount';
        $content1->properties[4]->typeName = 'Edm.Single';
        return $content1;
    }

    /**
     * @return ODataPropertyContent
     */
    private function generateOrderProperties()
    {
        $content1 = new ODataPropertyContent();
        $content1->properties = [
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty(),
            new ODataProperty()
        ];
        $content1->properties[0]->name = 'OrderID';
        $content1->properties[0]->typeName = 'Edm.Int32';
        $content1->properties[1]->name = 'OrderDate';
        $content1->properties[1]->typeName = 'Edm.DateTime';
        $content1->properties[2]->name = 'DeliveryDate';
        $content1->properties[2]->typeName = 'Edm.DateTime';
        $content1->properties[3]->name = 'ShipName';
        $content1->properties[3]->typeName = 'Edm.String';
        $content1->properties[4]->name = 'ItemCount';
        $content1->properties[4]->typeName = 'Edm.Int32';
        $content1->properties[5]->name = 'QualityRate';
        $content1->properties[5]->typeName = 'Edm.Int32';
        $content1->properties[6]->name = 'Price';
        $content1->properties[6]->typeName = 'Edm.Double';

        return $content1;
    }
}
