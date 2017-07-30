<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\Common\ODataException;
use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourcePrimitiveType;
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
use Symfony\Component\HttpFoundation\ParameterBag;
use UnitTests\POData\Facets\NorthWind1\Address2;
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\Employee2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\ObjectModel\reusableEntityClass1;

class SerialiserWriteElementTest extends SerialiserTestBase
{
    public function testCompareWriteSingleModelWithPropertiesNulled()
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

        $result = new QueryResult();
        $result->results = $model;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
        $numProperties = count($objectResult->propertyContent->properties);
        for ($i = 0; $i < $numProperties; $i++) {
            $propName = $objectResult->propertyContent->properties[$i]->name;
            $objectVal = $objectResult->propertyContent->properties[$i]->value;
            $ironicVal = $ironicResult->propertyContent->properties[$i]->value;
            $this->assertEquals(
                isset($objectVal),
                isset($ironicVal),
                'Values for' . $propName . 'differently null.  '.$i
            );
            $this->assertEquals(
                is_string($objectVal),
                is_string($ironicVal),
                'Values for '. $propName .'not identical'
            );
        }
    }

    public function testExpandOrderAttachedCustomer()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Orders(OrderID=1)?$expand=Customer');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Orders(OrderID=1)?$expand=Customer');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $expandNode = m::mock(ExpandedProjectionNode::class);
        $expandNode->shouldReceive('canSelectAllProperties')->andReturn(true);
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true);
        $node->shouldReceive('canSelectAllProperties')->andReturn(true);
        $node->shouldReceive('findNode')->andReturn($expandNode);

        $object->getRequest()->setRootProjectionNode($node);
        $ironic->getRequest()->setRootProjectionNode($node);

        $address = new Address4();
        $address->IsPrimary = true;
        $address->HouseNumber = 1;
        $address->IsValid = null;

        $cust = new Customer2();
        $cust->CustomerID = 1;
        $cust->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $cust->Address = $address;

        $order = new Order2();
        $order->OrderID = 1;
        $order->Customer = $cust;

        $result = new QueryResult();
        $result->results = $order;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testWriteWithNonNullBagProperty()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $caveJohnson = new Employee2();
        $caveJohnson->EmployeeID = 'Cave Johnson';
        $caveJohnson->Emails = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $result = new QueryResult();
        $result->results = $caveJohnson;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);

        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testExpandEmployeeAttachedManagerWithAllProperties()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')
            ->andReturn('/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');
        $request->shouldReceive('fullUrl')
            ->andReturn('http://localhost/odata.svc/Employees(EmployeeID=\'Bruce\')?$expand=Manager');
        $request->request = new ParameterBag([ '$expand' => "Manager"]);

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $iType = new StringType();

        $rType = m::mock(ResourcePrimitiveType::class);
        $rType->shouldReceive('getFullName')->andReturn('Edm.String');
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $rType->shouldReceive('getInstanceType')->andReturn($iType);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getResourceType')->andReturn($rType);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(true, false);


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
        $expandNode->shouldReceive('isExpansionSpecified')->andReturn(true);
        $expandNode->shouldReceive('findNode')->andReturn(null);

        $node = m::mock(RootProjectionNode::class);
        $node->shouldReceive('getPropertyName')->andReturn('Customer');
        $node->shouldReceive('isExpansionSpecified')->andReturn(true, true, true, true);
        $node->shouldReceive('canSelectAllProperties')->andReturn(false);
        $node->shouldReceive('findNode')->andReturn($expandNode);
        $node->shouldReceive('getChildNodes')->andReturn([$expandNode, $mailNode])->atLeast(1);

        $object->getRequest()->setRootProjectionNode($node);
        $ironic->getRequest()->setRootProjectionNode($node);

        $caveJohnson = new Employee2();
        $caveJohnson->EmployeeID = 'Cave Johnson';
        $caveJohnson->Emails = ['foo', 'bar'];
        $caveJohnson->TumbNail_48X48 = 'foobar';

        $emp = new Employee2();
        $emp->Manager = $caveJohnson;
        $emp->EmployeeID = 'Bruce';
        $emp->TumbNail_48X48 = 'foobar';
        $emp->Emails = null;

        $result = new QueryResult();
        $result->results = $emp;

        $objectResult = $object->writeTopLevelElement($result);
        $ironicResult = $ironic->writeTopLevelElement($result);
        $this->assertEquals(get_class($objectResult), get_class($ironicResult));
        $this->assertEquals($objectResult, $ironicResult);
    }

    public function testCompareWriteSingleModelWithKeyPropertiesNulled()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);

        // default data service
        list($object, $ironic) = $this->setUpSerialisers($query, $meta, $host);

        $model = new Customer2();

        $result = new QueryResult();
        $result->results = $model;

        $expected = 'The serialized resource of type Customer has a null value in key member \'CustomerID\'. Null'
                    .' values are not supported in key members.';
        $expectedExceptionClass = ODataException::class;
        $actual = null;
        $actualExceptionClass = null;

        try {
            $ironic->writeTopLevelElement($result);
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $actual = $e->getMessage();
        }

        $this->assertEquals($expectedExceptionClass, $actualExceptionClass);
        $this->assertEquals($expected, $actual);
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
    private function setUpSerialisers($query, $meta, $host)
    {
        // default data service
        $service = new TestDataService($query, $meta, $host);
        $processor = $service->handleRequest();
        $processor->getRequest()->queryType = QueryType::ENTITIES_WITH_COUNT();
        $processor->getRequest()->setCountValue(1);
        $object = new ObjectModelSerializer($service, $processor->getRequest());
        $ironic = new IronicSerialiser($service, $processor->getRequest());
        return [$object, $ironic];
    }
}
