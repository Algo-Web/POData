<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\Common\ODataException;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;
use POData\ObjectModel\ODataBagContent;
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
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;

class SerialiserWriteElementTest extends SerialiserTestBase
{
    public function testCompareWriteSingleModelWithPropertiesNulled()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        /** @var IronicSerialiser $ironic */
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model               = new Customer2();
        $model->CustomerID   = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';

        $result          = new QueryResult();
        $result->results = $model;

        $link               = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
            'Orders',
            'application/atom+xml;type=feed',
            'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders',
            true
        );

        $propContent             = new ODataPropertyContent(
            [
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', null),
                'Country' => new ODataProperty('Country', 'Edm.String', null),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', null),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', null)
            ]
        );

        $objectResult     = new ODataEntry(
            'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')', null, new ODataTitle('Customer'),
            new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')'),
            new ODataCategory('NorthWind.Customer'),
            $propContent,
            [],
            null,
            [$link],
            null,
            false,
            'Customers',
            '2017-01-01T00:00:00+00:00',
            'http://localhost/odata.svc/'
        );

        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent);
        $keys          = array_keys($objectResult->propertyContent->getPropertys());
        for ($i = 0; $i < $numProperties; $i++) {
            $propName  = $objectResult->propertyContent[$keys[$i]]->getName();
            $objectVal = $objectResult->propertyContent[$keys[$i]]->getValue();
            $ironicVal = $ironicResult->propertyContent[$keys[$i]]->getValue();
            $this->assertEquals(
                isset($objectVal),
                isset($ironicVal),
                'Values for' . $propName . 'differently null.  ' . $i
            );
            $this->assertEquals(
                is_string($objectVal),
                is_string($ironicVal),
                'Values for ' . $propName . 'not identical'
            );
        }
    }

    public function testExpandOrderAttachedCustomer()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)?$expand=Customer');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)?$expand=Customer');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        /** @var IronicSerialiser $ironic */
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $ironic->getRequest()->setRootProjectionNode($node);

        $address              = new Address4();
        $address->IsPrimary   = true;
        $address->HouseNumber = 1;
        $address->IsValid     = null;

        $cust               = new Customer2();
        $cust->CustomerID   = 1;
        $cust->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $cust->Address      = $address;

        $order           = new Order2();
        $order->OrderID  = 1;
        $order->Customer = $cust;

        $result          = new QueryResult();
        $result->results = $order;

        $propContent             = new ODataPropertyContent(
            [
                'OrderID' => new ODataProperty('OrderID', 'Edm.Int32', '1'),
                'OrderDate' => new ODataProperty('OrderDate', 'Edm.DateTime', null),
                'DeliveryDate' => new ODataProperty('DeliveryDate', 'Edm.DateTime', null),
                'ShipName' => new ODataProperty('ShipName', 'Edm.String', null),
                'ItemCount' => new ODataProperty('ItemCount', 'Edm.Int32', null),
                'QualityRate' => new ODataProperty('QualityRate', 'Edm.Int32', null),
                'Price' => new ODataProperty('Price', 'Edm.Double', null)
            ]
        );

        $addressContent             = new ODataPropertyContent(
            [
                'HouseNumber' => new ODataProperty('HouseNumber', 'Edm.String', '1'),
                'LineNumber' => new ODataProperty('LineNumber', 'Edm.Int32', null),
                'LineNumber2' => new ODataProperty('LineNumber2', 'Edm.Int32', null),
                'StreetName' => new ODataProperty('StreetName', 'Edm.String', null),
                'IsValid' => new ODataProperty('IsValid', 'Edm.Boolean', null),
                'Address2' => new ODataProperty('Address2', 'Address2', null)
            ]
        );

        $linkPropContent             = new ODataPropertyContent(
            [
                'CustomerID' => new ODataProperty('CustomerID', 'Edm.String', '1'),
                'CustomerGuid' => new ODataProperty('CustomerGuid', 'Edm.Guid', '123e4567-e89b-12d3-a456-426655440000'),
                'CustomerName' => new ODataProperty('CustomerName', 'Edm.String', null),
                'Country' => new ODataProperty('Country', 'Edm.String', null),
                'Rating' => new ODataProperty('Rating', 'Edm.Int32', null),
                'Photo' => new ODataProperty('Photo', 'Edm.Binary', null),
                'Address' => new ODataProperty('Address', 'Address', $addressContent)
            ]
        );

        $linkRawResult     = new ODataFeed();
        $linkRawResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\','
                             . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders';
        $linkRawResult->setTitle(new ODataTitle('Orders'));
        $linkRawResult->setSelfLink(new ODataLink('self', 'Orders', null, 'Customers(CustomerID=\'1\','
            . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')/Orders'));


        $linkResult     = new ODataEntry(
            'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')',
            null,
            new ODataTitle('Customer'),
            new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\','
            . 'CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')'),
            new ODataCategory('NorthWind.Customer'),
            $linkPropContent,
            [],
            null,
            [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Orders',
                'Orders',
                'application/atom+xml;type=feed',
                'Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567'
                . '-e89b-12d3-a456-426655440000\')/Orders',
                true,
                new ODataExpandedResult($linkRawResult),
                true
            )
        ],
            null,
            false,
            'Customers',
            '2017-01-01T00:00:00+00:00',
            null
        );

        $linkFeedResult                  = new ODataFeed(
            'http://localhost/odata.svc/Orders(OrderID=1)/Order_Details',
            new ODataTitle('Order_Details'),
            new ODataLink('self', 'Order_Details', null, 'Orders(OrderID=1)/Order_Details'),
            null,
            null,
            [],
            null,
            null
        );

        $links                    = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Customer',
                'Customer',
                'application/atom+xml;type=entry',
                'Orders(OrderID=1)/Customer',
                false,
                new ODataExpandedResult($linkResult),
                true
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Order_Details',
                'Order_Details',
                'application/atom+xml;type=feed',
                'Orders(OrderID=1)/Order_Details',
                true,
                new ODataExpandedResult($linkFeedResult),
                true
            )
        ];

        $objectResult                  = new ODataEntry(
            'http://localhost/odata.svc/Orders(OrderID=1)',
            null, new ODataTitle('Order'),
            new ODataLink('edit', 'Order', null, 'Orders(OrderID=1)'),
            new ODataCategory('NorthWind.Order'),
            $propContent,
            [],
            null,
            $links,
            null,
            false,
            'Orders',
            '2017-01-01T00:00:00+00:00',
            'http://localhost/odata.svc/'
        );

        $ironicResult = $ironic->writeTopLevelElement($result);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteWithNonNullBagProperty()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')');
        $request->shouldReceive('getRawUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        /** @var IronicSerialiser $ironic */
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $caveJohnson                 = new Employee2();
        $caveJohnson->EmployeeID     = 'Cave Johnson';
        $caveJohnson->Emails         = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $result          = new QueryResult();
        $result->results = $caveJohnson;

        $emailBag                   = new ODataBagContent(null, ['foo', 'bar']);

        $propContent             = new ODataPropertyContent(
            [
                'EmployeeID' => new ODataProperty('EmployeeID', 'Edm.String', 'Cave Johnson'),
                'FirstName' => new ODataProperty('FirstName', 'Edm.String', null),
                'LastName' => new ODataProperty('LastName', 'Edm.String', null),
                'ReportsTo' => new ODataProperty('ReportsTo', 'Edm.Int32', null),
                'Emails' => new ODataProperty('Emails', 'Collection(Edm.String)', $emailBag)
            ]
        );


        $mediaLink = new ODataMediaLink(
            'NorthWind.Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            '',
            'edit-media'
        );

        $mediaArray = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $links                  = [
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
                'Manager',
                'application/atom+xml;type=entry',
                'Employees(EmployeeID=\'Cave+Johnson\')/Manager'
            ),
            new ODataLink(
                'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates',
                'Subordinates',
                'application/atom+xml;type=feed',
                'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates',
                true,
                null,
                false
            )
        ];

        $objectResult                   = new ODataEntry(
            'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')',
            null,
            new ODataTitle('Employee'),
            new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Cave+Johnson\')'),
            new ODataCategory('NorthWind.Employee'),
            $propContent,
            [$mediaArray],
            $mediaLink,
            $links,
            null,
            true,
            'Employees',
            '2017-01-01T00:00:00+00:00',
            'http://localhost/odata.svc/'

        );

        $ironicResult = $ironic->writeTopLevelElement($result);

        // zero out etag values
        $ironicResult->mediaLink->eTag     = '';
        $ironicResult->mediaLinks[0]->eTag = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testExpandEmployeeAttachedManagerWithAllProperties()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');
        $request->shouldReceive('getRawUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        /** @var IronicSerialiser $ironic */
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $rType = m::mock(ResourcePrimitiveType::class);
        $rType->shouldReceive('getFullName')->andReturn('Edm.String');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getResourceType')->andReturn($rType);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG()])->andReturn(true, false);

        $mailNode = m::mock(ExpandedProjectionNode::class);
        $mailNode->shouldReceive('getResourceProperty')->andReturn($rProp);
        $mailNode->shouldReceive('getPropertyName')->andReturn('Emails');
        $mailNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $mailNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $mailNode->shouldReceive('findNode')->andReturn(null);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('getResourceProperty')->andReturn($rProp);
        $expandNode->shouldReceive('getPropertyName')->andReturn('Manager');
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->withArgs(['Subordinates'])->andReturn(false)->times(0);
        $node->shouldReceive('isExpansionSpecified')->withArgs(['Manager'])->andReturn(true)->times(0);
        $node->shouldReceive('canSelectAllProperties')->andReturn(false);
        $node->shouldReceive('findNode')->andReturn($expandNode, $expandNode, null);
        $node->shouldReceive('getChildNodes')->andReturn([$expandNode, $mailNode])->times(1);

        $ironic->getRequest()->setRootProjectionNode($node);

        $caveJohnson                 = new Employee2();
        $caveJohnson->EmployeeID     = 'Cave Johnson';
        $caveJohnson->Emails         = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $emp                 = new Employee2();
        $emp->Manager        = $caveJohnson;
        $emp->EmployeeID     = 'Bruce';
        $emp->TumbNail_48X48 = 'foobar';
        $emp->Emails         = null;

        $result          = new QueryResult();
        $result->results = $emp;

        $media1 = new ODataMediaLink(
            'NorthWind.Employee',
            '/$value',
            'Employees(EmployeeID=\'Bruce\')/$value',
            '*/*',
            '',
            'edit-media'
        );
        $media2 = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Bruce\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Bruce\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $propContent                           = new ODataPropertyContent(
            [
                'EmployeeID' => new ODataProperty('EmployeeID', 'Edm.String', 'Cave Johnson'),
                'FirstName'  => new ODataProperty('FirstName', 'Edm.String', null),
                'LastName'   => new ODataProperty('LastName', 'Edm.String', null),
                'ReportsTo'  => new ODataProperty('ReportsTo', 'Edm.Int32', null),
                'Emails'     => new ODataProperty('Emails', 'Collection(Edm.String)', new ODataBagContent(null, ['foo', 'bar']))
            ]
        );

        $managerMedia1 = new ODataMediaLink(
            'NorthWind.Employee',
            '/$value',
            'Employees(EmployeeID=\'Cave+Johnson\')/$value',
            '*/*',
            '',
            'edit-media'
        );
        $managerMedia2 = new ODataMediaLink(
            'TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'Employees(EmployeeID=\'Cave+Johnson\')/TumbNail_48X48',
            'application/octet-stream',
            ''
        );

        $managerResult                  = new ODataEntry();
        $managerResult->resourceSetName = 'Employee';

        $managerLink1                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
            'Manager',
            'application/atom+xml;type=entry',
            'Employees(EmployeeID=\'Cave+Johnson\')/Manager',
            false
        );
        $managerLink1->setIsExpanded(true);
        $managerLink1->setExpandedResult(new ODataExpandedResult($managerResult));
        $managerLink2                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Subordinates',
            'Subordinates',
            'application/atom+xml;type=feed',
            'Employees(EmployeeID=\'Cave+Johnson\')/Subordinates',
            true
        );
        $managerLink2->setIsExpanded(false);


        $manager                   = new ODataEntry(
            'http://localhost/odata.svc/Employees(EmployeeID=\'Cave+Johnson\')',
            null,
            new ODataTitle('Employee'),
            new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Cave+Johnson\')'),
            new ODataCategory('NorthWind.Employee'),
            $propContent,
            [$managerMedia2],
            $managerMedia1,
            [$managerLink1, $managerLink2],
            null,
            true,
            'Employees',
            '2017-01-01T00:00:00+00:00',
            null
        );

        $link                 = new ODataLink(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Manager',
            'Manager',
            'application/atom+xml;type=entry',
            'Employees(EmployeeID=\'Bruce\')/Manager',
            false,
            new ODataExpandedResult($manager),
            true
        );

        $objContent                       = new ODataPropertyContent(
            [
                'Emails' => new ODataProperty('Emails', 'Collection(Edm.String)', null)
            ]
        );

        $objectResult                   = new ODataEntry(
            'http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')',
            null, new ODataTitle('Employee'),
            new ODataLink('edit', 'Employee', null, 'Employees(EmployeeID=\'Bruce\')'),
            new ODataCategory('NorthWind.Employee'),
            $objContent,
            [$media2],
            $media1,
            [$link],
            null,
            true,
            'Employees',
            '2017-01-01T00:00:00+00:00',
            'http://localhost/odata.svc/'
        );

        $ironicResult = $ironic->writeTopLevelElement($result);

        // flatten, remove and zero out etags - haven't yet figured out how to freeze etag generation
        $ironicResult->mediaLinks[0]->eTag                                            = '';
        $ironicResult->mediaLink->eTag                                                = '';
        $ironicResult->links[0]->getExpandedResult()->getEntry()->mediaLinks[0]->eTag = '';
        $ironicResult->links[0]->getExpandedResult()->getEntry()->mediaLink->eTag     = '';

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteSingleModelWithKeyPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('getRawUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        $ironic = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();

        $result          = new QueryResult();
        $result->results = $model;

        $expected = 'The serialized resource of type Customer has a null value in key member \'CustomerID\'. Null'
                    . ' values are not supported in key members.';
        $expectedExceptionClass = ODataException::class;
        $actual                 = null;
        $actualExceptionClass   = null;

        try {
            $ironic->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual               = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
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
     * @return array
     */
    private function setUpSerialisers($query, $meta, $host)
    {
        // default data service
        $service                            = new TestDataService($query, $meta, $host);
        $processor                          = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return $ironic;
    }
}
