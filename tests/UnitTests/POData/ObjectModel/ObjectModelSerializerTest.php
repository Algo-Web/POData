<?php

namespace UnitTests\POData\ObjectModel;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\Providers\Stream\StreamProviderWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\SegmentStack;
use UnitTests\POData\TestCase;

class ObjectModelSerializerTest extends TestCase
{
    private $mockRequest;
    private $mockWrapper;
    private $serviceHost;
    private $mockService;
    private $mockStreamWrapper;

    public function Construct()
    {
        $AbsoluteServiceURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc');
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $context = m::mock(IOperationContext::class)->makePartial();
        $this->mockStreamWrapper = m::mock(StreamProviderWrapper::class);
        $this->mockService = $service;
        $this->mockRequest = $request;
        $this->mockWrapper = $wrapper;
        $this->serviceHost = m::mock(\POData\OperationContext\ServiceHost::class)->makePartial();
        $this->serviceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($AbsoluteServiceURL);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $wrapper->shouldReceive('getResourceProperties')->andReturn([]);
        $service->shouldReceive('getHost')->andReturn($this->serviceHost);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($this->mockStreamWrapper);
        $foo = new ObjectModelSerializer($service, $request);

        return $foo;
    }

    public function testObjectModelSerializerBaseconstructor()
    {
        $foo = $this->Construct();
        $this->assertTrue(is_object($foo));
    }

    public function testwriteTopLevelElement()
    {
        $foo = $this->Construct();
        $entity = new reusableEntityClass4();
        $entity->name = 'bilbo';
        $entity->type = 2;
        $mockResourceType = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $mockResourceSetWrapper = m::mock(\POData\Providers\Metadata\ResourceSetWrapper::class)->makePartial();

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(2);
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn('Entity');

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        $ret = $foo->writeTopLevelElement($queryResult);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->id);
        $this->assertEquals("Entity(name='bilbo',type=2)", $ret->editLink);
        $this->assertEquals('Entity', $ret->resourceSetName);
    }

    public function testwriteTopLevelElementsWithoutHasMore()
    {
        $foo = $this->Construct();
        $entity = new reusableEntityClass4();
        $entity->name = 'bilbo';
        $entity->type = 2;
        $entity1 = new reusableEntityClass4();
        $entity1->name = 'dildo';
        $entity1->type = 3;

        $mockResourceType = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $mockResourceSetWrapper = m::mock(\POData\Providers\Metadata\ResourceSetWrapper::class)->makePartial();

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockService->shouldReceive('getConfiguration->getEntitySetPageSize')->andReturn(200);

        $orderInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn([])->never();

        $rootNode = m::mock(RootProjectionNode::class);
        $rootNode->shouldReceive('isExpansionSpecified')->andReturn(false)->once();
        $rootNode->shouldReceive('canSelectAllProperties')->andReturn(true)->twice();
        $rootNode->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo)->never();

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);
        $this->mockRequest->shouldReceive('getIdentifier')->andReturn('Entity');
        $this->mockRequest->shouldReceive('getRootProjectionNode')->andReturn($rootNode);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(2);
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn('Entity');

        $e = [$entity, $entity1];
        $queryResult = new QueryResult();
        $queryResult->results = $e;
        $queryResult->hasMore = false;

        $ret = $foo->writeTopLevelElements($queryResult);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);

        $this->assertTrue(is_array($ret->entries));

        $this->assertEquals('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)', $ret->id);
        $this->assertEquals('data', $ret->title);

        $this->assertEquals('self', $ret->selfLink->name);
        $this->assertEquals('data', $ret->selfLink->title);
        $this->assertEquals('Entity', $ret->selfLink->url);

        $this->assertEquals(2, count($ret->entries));

        $this->assertTrue($ret->entries[0] instanceof \POData\ObjectModel\ODataEntry);
        $this->assertTrue($ret->entries[1] instanceof \POData\ObjectModel\ODataEntry);

        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->entries[0]->id);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='dildo',type=3)", $ret->entries[1]->id);

        $this->assertEquals("Entity(name='bilbo',type=2)", $ret->entries[0]->editLink);
        $this->assertEquals("Entity(name='dildo',type=3)", $ret->entries[1]->editLink);

        $this->assertTrue($ret->entries[0]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);
        $this->assertTrue($ret->entries[1]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);
    }

    public function testwriteTopLevelElementsOnly()
    {
        $foo = $this->Construct();
        $entity = new reusableEntityClass4();
        $entity->name = 'bilbo';
        $entity->type = 2;
        $entity1 = new reusableEntityClass4();
        $entity1->name = 'dildo';
        $entity1->type = 3;

        $mockResourceType = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $mockResourceSetWrapper = m::mock(\POData\Providers\Metadata\ResourceSetWrapper::class)->makePartial();

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockService->shouldReceive('getConfiguration->getEntitySetPageSize')->andReturn(200);

        $orderInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn([])->twice();

        $rootNode = m::mock(RootProjectionNode::class);
        $rootNode->shouldReceive('isExpansionSpecified')->andReturn(false)->once();
        $rootNode->shouldReceive('canSelectAllProperties')->andReturn(true)->twice();
        $rootNode->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo)->once();

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);
        $this->mockRequest->shouldReceive('getIdentifier')->andReturn('Entity');
        $this->mockRequest->shouldReceive('getRootProjectionNode')->andReturn($rootNode);
        $this->mockRequest->shouldReceive('getTopOptionCount')->andReturn(300);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(2);
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn('Entity');

        $e = [$entity, $entity1];
        $queryResult = new QueryResult();
        $queryResult->results = $e;
        $queryResult->hasMore = true;

        $ret = $foo->writeTopLevelElements($queryResult);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);

        $this->assertTrue(is_array($ret->entries));

        $this->assertEquals('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)', $ret->id);
        $this->assertEquals('data', $ret->title);

        $this->assertEquals('self', $ret->selfLink->name);
        $this->assertEquals('data', $ret->selfLink->title);
        $this->assertEquals('Entity', $ret->selfLink->url);

        $this->assertEquals(2, count($ret->entries));

        $this->assertTrue($ret->entries[0] instanceof \POData\ObjectModel\ODataEntry);
        $this->assertTrue($ret->entries[1] instanceof \POData\ObjectModel\ODataEntry);

        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->entries[0]->id);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='dildo',type=3)", $ret->entries[1]->id);

        $this->assertEquals("Entity(name='bilbo',type=2)", $ret->entries[0]->editLink);
        $this->assertEquals("Entity(name='dildo',type=3)", $ret->entries[1]->editLink);

        $this->assertTrue($ret->entries[0]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);
        $this->assertTrue($ret->entries[1]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);
    }

    public function testwriteTopLevelElementsViaProperty()
    {
        $foo = $this->Construct();
        $entity = new reusableEntityClass4();
        $entity->name = 'bilbo';
        $entity->type = 2;
        $entity1 = new reusableEntityClass4();
        $entity1->name = 'riptide';
        $entity1->type = 3;
        $e = [$entity, $entity1];

        $this->mockService->shouldReceive('getConfiguration->getEntitySetPageSize')->andReturn(200);

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE);
        $property->shouldReceive('getName')->andReturn('name');

        $itype = new StringType();
        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('name');
        $rProp->shouldReceive('getInstanceType')->andReturn($itype);

        $rSet = m::mock(ResourceSet::class);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('names');
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(50);
        $resourceSet->shouldReceive('getResourceSet')->andReturn($rSet);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['name' => $rProp]);
        $resourceType->shouldReceive('getName')->andReturn('name');
        $resourceType->shouldReceive('getFullName')->andReturn('Data.name');
        $resourceType->shouldReceive('isMediaLinkEntry')->andReturn(false);
        $resourceType->shouldReceive('hasNamedStream')->andReturn(false);
        $resourceType->shouldReceive('getETagProperties')->andReturn([]);
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');
        $this->mockRequest->shouldReceive('getTargetSource')
            ->andReturn(TargetSource::PROPERTY);
        $this->mockRequest->shouldReceive('getProjectedProperty')
            ->andReturn($property);
        $this->mockRequest->shouldReceive('getIdentifier')->andReturn('name');
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceSet);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('Data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($resourceType);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);

        $queryResult = new QueryResult();
        $queryResult->results = $e;

        $ret = $foo->writeTopLevelElements($queryResult);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(1)", $ret->id);
    }

    public function testWriteNullPrimitive()
    {
        $foo = $this->Construct();

        $primVal = null;
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals(null, $result->properties[0]->value);
    }

    public function testWriteBooleanPrimitive()
    {
        $foo = $this->Construct();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new Boolean());

        $primVal = true;
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals('true', $result->properties[0]->value);
    }

    public function testWriteBinaryPrimitive()
    {
        $foo = $this->Construct();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new Binary());

        $primVal = 'aybabtu';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals('YXliYWJ0dQ==', $result->properties[0]->value);
    }

    public function testWriteDateTimePrimitive()
    {
        $foo = $this->Construct();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new DateTime());

        $primVal = new \DateTime('2016-01-01');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals('2016-01-01T', substr($result->properties[0]->value, 0, 11));
    }

    public function testWriteStringPrimitive()
    {
        $foo = $this->Construct();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new StringType());

        $primVal = 'Börk, börk, börk!';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals('BÃ¶rk, bÃ¶rk, bÃ¶rk!', $result->properties[0]->value);
    }

    public function testWriteNullTypePrimitive()
    {
        $foo = $this->Construct();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(null);

        $primVal = 'Börk, börk, börk!';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals('Börk, börk, börk!', $result->properties[0]->value);
    }

    public function testWriteNullUrlElement()
    {
        $foo = $this->Construct();

        $queryResult = new QueryResult();
        $queryResult->results = null;

        $result = $foo->writeUrlElement($queryResult);
        $this->assertEquals(null, $result->url);
    }

    public function testWriteNonNullUrlElement()
    {
        $type = m::mock(ResourceType::class);

        $wrap = m::mock(ResourceSetWrapper::class);
        $wrap->shouldReceive('getResourceType')->andReturn($type);
        $wrap->shouldReceive('getName')->andReturn('resourceWrapper');

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($wrap);
        $foo->shouldReceive('getEntryInstanceKey')->andReturn('customer')->once();

        $queryResult = new QueryResult();
        $queryResult->results = 'bar';

        $result = $foo->writeUrlElement($queryResult);
        $this->assertEquals('/customer', $result->url);
    }

    public function testWriteNullUrlCollection()
    {
        $foo = $this->Construct();
        $this->mockRequest->queryType = QueryType::ENTITIES_WITH_COUNT();
        $this->mockRequest->shouldReceive('getCountValue')->andReturn(1);

        $queryResult = new QueryResult();
        $queryResult->results = null;

        $result = $foo->writeUrlElements($queryResult);
        $this->assertEquals(0, count($result->urls));
        $this->assertNull($result->nextPageLink);
        $this->assertEquals(1, $result->count);
    }

    public function testWriteNonNullUrlCollection()
    {
        $url = new Url('https://www.example.org/odata.svc');

        $odataLink = new ODataLink();
        $odataLink->name = ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING;
        $odataLink->url = 'https://www.example.org/odata.svc/customer?skipToken=200';

        $resourceWrap = m::mock(ResourceSetWrapper::class);

        $foo = $this->Construct();

        $this->mockRequest->queryType = QueryType::ENTITIES_WITH_COUNT();
        $this->mockRequest->shouldReceive('getCountValue')->andReturn(2);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($url);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceWrap);

        $objects = ['customer', 'supplier'];

        $supplier = new QueryResult();
        $supplier->results = 'supplier';

        $customer = new QueryResult();
        $customer->results = 'customer';

        $queryResult = new QueryResult();
        $queryResult->results = [$supplier, $customer];
        $queryResult->hasMore = true;

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('writeUrlElement')->withArgs([$supplier])->andReturn('/supplier')->once();
        $foo->shouldReceive('writeUrlElement')->withArgs([$customer])->andReturn('/customer')->once();
        $foo->shouldReceive('getStack->getSegmentWrappers')->andReturn([]);
        $foo->shouldReceive('getRequest')->andReturn($this->mockRequest);
        $foo->shouldReceive('needNextPageLink')->andReturn(true)->never();
        $foo->shouldReceive('getNextLinkUri')->andReturn($odataLink)->once();

        $result = $foo->writeUrlElements($queryResult);
        $expectedUrl = $odataLink->url;
        $this->assertEquals($expectedUrl, $result->nextPageLink->url);
        $this->assertEquals(2, $result->count);
    }

    public function testWriteNullComplexValue()
    {
        $complexValue = null;
        $propertyName = 'property';
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getFullName')->andReturn('typeName')->once();

        $queryResult = new QueryResult();
        $queryResult->results = $complexValue;

        $foo = $this->Construct();
        $result = $foo->writeTopLevelComplexObject($queryResult, $propertyName, $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->value);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertEquals('property', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
    }

    public function testWriteNonNullComplexValue()
    {
        $propType = m::mock(ResourceType::class);
        $propType->shouldReceive('getFullTypeName')->andReturn('fullName');
        $propType->shouldReceive('getInstanceType')->andReturn($propType);

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(24);
        $resProperty->shouldReceive('getName')->andReturn('name');
        $resProperty->shouldReceive('getInstanceType')->andReturn($propType);
        $resProperty->shouldReceive('getResourceType')->andReturn($propType);
        $resProperty->shouldReceive('isKindOf')->passthru();

        $complexValue = new reusableEntityClass2('2016-12-25', null);
        $propertyName = 'property';
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getFullName')->andReturn('typeName')->once();
        $type->shouldReceive('getName')->andReturn('typeName')->never();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX)->once();
        $type->shouldReceive('getAllProperties')->andReturn([$resProperty]);

        $queryResult = new QueryResult();
        $queryResult->results = $complexValue;

        $foo = $this->Construct();
        $result = $foo->writeTopLevelComplexObject($queryResult, $propertyName, $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        //$this->assertNull($result->properties[0]->value);
        $this->assertTrue($result->properties[0]->value instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0]->value->properties[0] instanceof ODataProperty);
        $this->assertEquals('name', $result->properties[0]->value->properties[0]->name);
        $this->assertEquals('fullName', $result->properties[0]->value->properties[0]->typeName);
    }

    public function testWriteTopLevelBagObjectTripAssertion()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY)->once();

        $bag = null;

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $expected = 'assert(): $bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE &&'
                    .' $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX failed';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelBagObjectNull()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = null;

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertNull($result->properties[0]->value);
        $this->assertEquals('property', $result->properties[0]->name);
        $this->assertEquals('Collection(fullName)', $result->properties[0]->typeName);
    }

    public function testWriteTopLevelBagObjectEmptyArray()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = [];

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertNull($result->properties[0]->value);
        $this->assertEquals('property', $result->properties[0]->name);
        $this->assertEquals('Collection(fullName)', $result->properties[0]->typeName);
    }

    public function testWriteTopLevelBagObjectArrayOfNulls()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = [null, null];

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertTrue($result->properties[0]->value instanceof ODataBagContent);
        $this->assertNull($result->properties[0]->value->type);
        $this->assertNull($result->properties[0]->value->propertyContents);
        $this->assertEquals('property', $result->properties[0]->name);
        $this->assertEquals('Collection(fullName)', $result->properties[0]->typeName);
    }

    public function testWriteTopLevelBagObjectActualObject()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->never();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = new \DateTime();

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $expected = 'assert(): Bag parameter must be null or array failed';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelBagObjectArrayOfPrimitiveObjects()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');
        $type->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $bag = ['foo', 123];
        $expected = ['foo', '123'];

        $foo = $this->Construct();

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertTrue($result->properties[0]->value instanceof ODataBagContent);
        $this->assertNull($result->properties[0]->value->type);
        $this->assertTrue(is_array($result->properties[0]->value->propertyContents));
        $this->assertEquals($expected, $result->properties[0]->value->propertyContents);
        $this->assertEquals('property', $result->properties[0]->name);
        $this->assertEquals('Collection(fullName)', $result->properties[0]->typeName);
    }

    public function testWriteTopLevelComplexObjectWithExpandedPropertiesTripsComplexObjectLoopException()
    {
        $complexValue = new reusableEntityClass2('2016-12-25', null);

        $kidNode1 = m::mock(ExpandedProjectionNode::class);
        $kidNode1->shouldReceive('getPropertyName')->andReturn('wun');
        $kidNode1->shouldReceive('getName')->andReturn('wunName');
        $kidNode1->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $kidNode2 = m::mock(ExpandedProjectionNode::class);
        $kidNode2->shouldReceive('getPropertyName')->andReturn('too');
        $kidNode2->shouldReceive('getName')->andReturn('tooName');
        $kidNode1->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::COMPLEX);

        $propType = m::mock(ResourceType::class);
        $propType->shouldReceive('getFullName')->andReturn('fullName');
        $propType->shouldReceive('getName')->andReturn('name');
        $propType->shouldReceive('isMediaLinkEntry')->andReturn(false);
        $propType->shouldReceive('hasNamedStream')->andReturn(false);
        $propType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $propType->shouldReceive('resolveProperty')->withArgs(['wun'])->andReturn($kidNode1);
        $propType->shouldReceive('resolveProperty')->withArgs(['too'])->andReturn($kidNode2);

        $nuType = m::mock(ResourceType::class);
        $nuType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX);

        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE)->twice();
        $prop1->shouldReceive('isKindOf')->andReturn(false);
        $prop1->shouldReceive('getName')->andReturn('type');
        $prop1->shouldReceive('getResourceType')->andReturn($propType);
        $prop1->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->once();
        $prop2->shouldReceive('isKindOf')->andReturn(false);
        $prop2->shouldReceive('getName')->andReturn('name');
        $prop2->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $prop3 = m::mock(ResourceProperty::class);
        $prop3->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE)->never();
        $prop3->shouldReceive('isKindOf')->andReturn(false);
        $prop3->shouldReceive('getResourceType')->andReturn($nuType);
        $prop3->shouldReceive('getName')->andReturn('type');
        $prop3->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $prop4 = m::mock(ResourceProperty::class);
        $prop4->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->never();
        $prop4->shouldReceive('isKindOf')->andReturn(false);
        $prop4->shouldReceive('getName')->andReturn('name');
        $prop4->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX)->once();
        $type->shouldReceive('getName')->andReturn('nuName');
        $type->shouldReceive('getAllProperties')->andReturn([$prop1, $prop2]);
        $propType->shouldReceive('getAllProperties')->andReturn([$prop3, $prop4]);

        $currentNode = m::mock(ExpandedProjectionNode::class);
        $currentNode->shouldReceive('getChildNodes')->andReturn([$kidNode1, $kidNode2])->never();
        $currentNode->shouldReceive('canSelectAllProperties')->andReturn(false);
        $currentNode->shouldReceive('getName')->andReturn('oldName');

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('getSegmentNames')->andReturn(['foo', 'bar']);

        $resourceWrapper = m::mock(ResourceSetWrapper::class);
        $resourceWrapper->shouldReceive('getResourceType')->andReturn($propType);
        $resourceWrapper->shouldReceive('getName')->andReturn('wrapper');

        $provWrapper = m::mock(ProvidersWrapper::class);
        $provWrapper->shouldReceive('getResourceProperties')->andReturn([]);

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($currentNode);
        $foo->shouldReceive('shouldExpandSegment')->andReturn(true);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($resourceWrapper);
        $foo->shouldReceive('pushSegmentForNavigationProperty')->andReturn(true);
        $foo->shouldReceive('getEntryInstanceKey')->andReturn('idle');
        $foo->shouldReceive('getETagForEntry')->andReturn(null);
        $foo->shouldReceive('getService->getProvidersWrapper')->andReturn($provWrapper);
        $foo->shouldReceive('getPropertyValue')->andReturn(['wun', 'too']);

        $expected = 'Internal Server Error. The type \'name\' has inconsistent metadata and runtime type info.';
        $actual = null;

        $queryResult = new QueryResult();
        $queryResult->results = $complexValue;

        try {
            $foo->writeTopLevelComplexObject($queryResult, 'property', $type);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelElementWithExpandedProjectionNodes()
    {
        $entity = new reusableEntityClass2('2016-12-25', null);

        $url = new Url('https://www.example.org/odata.svc');

        $projNode1 = m::mock(ExpandedProjectionNode::class)->makePartial();
        $projNode2 = m::mock(ExpandedProjectionNode::class)->makePartial();

        $navType = m::mock(ResourceType::class)->makePartial();

        $resolv = m::mock(ResourceProperty::class)->makePartial();
        $resolv->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY)->twice();
        $resolv->shouldReceive('getResourceType')->andReturn($navType);
        $resolv->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $resolv->shouldReceive('isKindOf')->andReturn(false);
        $resolv->shouldReceive('getInstanceType->getFullTypeName')->andReturn('fullTypeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('customers');
        $type->shouldReceive('getFullName')->andReturn('customers');
        $type->shouldReceive('isMediaLinkEntry')->andReturn(false)->once();
        $type->shouldReceive('hasNamedStream')->andReturn(false)->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY)->once();
        $type->shouldReceive('resolveProperty')->andReturn($resolv);

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $wrap = m::mock(ResourceSetWrapper::class)->makePartial();
        $wrap->shouldReceive('getName')->andReturn('wrapper');

        $navProp = m::mock(ResourceProperty::class)->makePartial();

        $provWrap = m::mock(ProvidersWrapper::class)->makePartial();
        $provWrap->shouldReceive('getResourceProperties')->andReturn($navProp);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::PROPERTY);
        $request->shouldReceive('getProjectedProperty')->andReturn($prop);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);
        $request->shouldReceive('getRequestUrl')->andReturn($url);

        $stack = m::mock(SegmentStack::class)->makePartial();
        $stack->shouldReceive('pushSegment')->andReturnNull()->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->once();

        $context = m::mock(IOperationContext::class)->makePartial();

        $streamWrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getProvidersWrapper')->andReturn($provWrap);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($streamWrap);

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($wrap);
        $foo->shouldReceive('getEntryInstanceKey')->andReturn('customer');
        $foo->shouldReceive('getETagForEntry')->andReturn(null);
        $foo->shouldReceive('getProjectionNodes')->andReturn([$projNode1, $projNode2])->once();
        $foo->shouldReceive('shouldExpandSegment')->andReturn(true);
        $foo->shouldReceive('getPropertyValue')->andReturn('propertyValue');

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        $result = $foo->writeTopLevelElement($queryResult);
        $this->assertTrue($result instanceof ODataEntry);
        $this->assertEquals('/customer', $result->id);
        $this->assertEquals('customers', $result->title);
        $this->assertEquals('customer', $result->editLink);
        $this->assertEquals('customers', $result->type);
        $this->assertEquals('wrapper', $result->resourceSetName);
        $this->assertEquals(0, count($result->links));
        $this->assertEquals(0, count($result->mediaLinks));
        $propContent = $result->propertyContent;
        $this->assertTrue($propContent instanceof ODataPropertyContent);
        $properties = $propContent->properties;
        $this->assertEquals(2, count($properties));
        $this->assertEquals($properties[0], $properties[1]);
    }

    public function testWriteTopLevelElementWithExpandedProjectionNodesNullTypeKindThrowException()
    {
        $entity = new reusableEntityClass2('2016-12-25', null);

        $url = new Url('https://www.example.org/odata.svc');

        $projNode1 = m::mock(ExpandedProjectionNode::class)->makePartial();
        $projNode2 = m::mock(ExpandedProjectionNode::class)->makePartial();

        $navType = m::mock(ResourceType::class)->makePartial();

        $resolv = m::mock(ResourceProperty::class)->makePartial();
        $resolv->shouldReceive('getTypeKind')->andReturn(null)->once();
        $resolv->shouldReceive('getResourceType')->andReturn($navType);
        $resolv->shouldReceive('isKindOf')->andReturn(false);
        $resolv->shouldReceive('getInstanceType->getFullTypeName')->andReturn('fullTypeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('customers');
        $type->shouldReceive('getFullName')->andReturn('customers');
        $type->shouldReceive('isMediaLinkEntry')->andReturn(false)->once();
        $type->shouldReceive('hasNamedStream')->andReturn(false)->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY)->once();
        $type->shouldReceive('resolveProperty')->andReturn($resolv);

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $wrap = m::mock(ResourceSetWrapper::class)->makePartial();
        $wrap->shouldReceive('getName')->andReturn('wrapper');

        $navProp = m::mock(ResourceProperty::class)->makePartial();

        $provWrap = m::mock(ProvidersWrapper::class)->makePartial();
        $provWrap->shouldReceive('getResourceProperties')->andReturn($navProp);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::PROPERTY);
        $request->shouldReceive('getProjectedProperty')->andReturn($prop);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);
        $request->shouldReceive('getRequestUrl')->andReturn($url);

        $stack = m::mock(SegmentStack::class)->makePartial();
        $stack->shouldReceive('pushSegment')->andReturn(true)->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();

        $context = m::mock(IOperationContext::class)->makePartial();

        $streamWrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getProvidersWrapper')->andReturn($provWrap);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($streamWrap);

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($wrap);
        $foo->shouldReceive('getEntryInstanceKey')->andReturn('customer');
        $foo->shouldReceive('getETagForEntry')->andReturn(null);
        $foo->shouldReceive('getProjectionNodes')->andReturn([$projNode1, $projNode2])->once();
        $foo->shouldReceive('shouldExpandSegment')->andReturn(true);
        $foo->shouldReceive('getPropertyValue')->andReturn('propertyValue');

        $expected = 'assert(): $propertyTypeKind != Primitive or Bag or ComplexType failed';
        $actual = null;

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        try {
            $result = $foo->writeTopLevelElement($queryResult);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelElementBagWithExpandedProjectionAndNavigationNodesInconsistentMetadata()
    {
        $entity = new reusableEntityClass2('foo', 'bar');

        $url = new Url('https://www.example.org/odata.svc');

        $projNode1 = m::mock(ExpandedProjectionNode::class)->makePartial();
        $projNode2 = m::mock(ExpandedProjectionNode::class)->makePartial();

        $navType = m::mock(ResourceType::class)->makePartial();
        $navType->shouldReceive('getResourcePropertyKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->never();

        $resolv = m::mock(ResourceProperty::class)->makePartial();
        $resolv->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY)->twice();
        $resolv->shouldReceive('getResourceType')->andReturn($navType);
        $resolv->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(true);
        $resolv->shouldReceive('isKindOf')->andReturn(false);
        $resolv->shouldReceive('getInstanceType->getFullTypeName')->andReturn('fullTypeName');
        $resolv->shouldReceive('getName')->andReturn('customers');
        $resolv->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->twice();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('customers');
        $type->shouldReceive('getFullName')->andReturn('customers');
        $type->shouldReceive('isMediaLinkEntry')->andReturn(false)->once();
        $type->shouldReceive('hasNamedStream')->andReturn(false)->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY)->once();
        $type->shouldReceive('resolveProperty')->andReturn($resolv);

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $wrap = m::mock(ResourceSetWrapper::class)->makePartial();
        $wrap->shouldReceive('getName')->andReturn('wrapper');
        $wrap->shouldReceive('getResourceType')->andReturn($navType);

        $navProp = m::mock(ResourceProperty::class)->makePartial();
        $navProp->shouldReceive('getResourcePropertyKind')
            ->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->never();

        $provWrap = m::mock(ProvidersWrapper::class)->makePartial();
        $provWrap->shouldReceive('getResourceProperties')->andReturn(['customers' => $navProp]);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::PROPERTY);
        $request->shouldReceive('getProjectedProperty')->andReturn($prop);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);
        $request->shouldReceive('getRequestUrl')->andReturn($url);

        $stack = m::mock(SegmentStack::class)->makePartial();
        $stack->shouldReceive('pushSegment')->andReturn(true)->once();
        $stack->shouldReceive('popSegment')->andReturnNull()->never();

        $context = m::mock(IOperationContext::class)->makePartial();

        $streamWrap = m::mock(StreamProviderWrapper::class)->makePartial();

        $service = m::mock(IService::class)->makePartial();
        $service->shouldReceive('getProvidersWrapper')->andReturn($provWrap);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getStreamProviderWrapper')->andReturn($streamWrap);

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getRequest')->andReturn($request);
        $foo->shouldReceive('getStack')->andReturn($stack);
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($wrap);
        $foo->shouldReceive('getEntryInstanceKey')->andReturn('customer');
        $foo->shouldReceive('getETagForEntry')->andReturn(null);
        $foo->shouldReceive('getProjectionNodes')->andReturn([$projNode1, $projNode2])->once();
        $foo->shouldReceive('shouldExpandSegment')->andReturn(true);
        $foo->shouldReceive('pushSegmentForNavigationProperty')->andReturn(true);
        $foo->shouldReceive('getPropertyValue')->andReturn('propertyValue');

        $expected = 'Internal Server Error. The type \'\' has inconsistent metadata and runtime type info.';
        $actual = null;

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        try {
            $result = $foo->writeTopLevelElement($queryResult);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSetService()
    {
        $oldUrl = 'http://localhost/odata.svc';
        $newUrl = 'http://localhost/megamix.svc';
        $oldService = m::mock(IService::class);
        $oldService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn($oldUrl);
        $newService = m::mock(IService::class);
        $newService->shouldReceive('getHost->getAbsoluteServiceUri->getUrlAsString')
            ->andReturn($newUrl);
        $request = null;

        $foo = new ObjectModelSerializer($oldService, $request);
        $this->assertEquals($oldUrl, $foo->getService()->getHost()->getAbsoluteServiceUri()->getUrlAsString());

        $foo->setService($newService);
        $this->assertEquals($newUrl, $foo->getService()->getHost()->getAbsoluteServiceUri()->getUrlAsString());
    }

    /**
     * @dataProvider matchPrimitiveProvider
     */
    public function testResourceKindMatchesPrimitive($input, $expected)
    {
        $result = ObjectModelSerializer::isMatchPrimitive($input);
        $this->assertEquals($expected, $result);
    }

    public function matchPrimitiveProvider()
    {
        return [
            [0, false], [1, false], [2, false], [3, false], [4, false], [5, false], [6, false], [7, false],
            [8, false], [9, false], [10, false], [11, false], [12, false], [13, false], [14, false], [15, false],
            [16, true], [17, false], [18, false], [19, false], [20, true], [21, false], [22, false], [23, false],
            [24, true], [25, false], [26, false], [27, false], [28, true], [29, false], [30, false], [31, false],
            [32, false], [33, false], [34, false], [35, false], [36, false], [37, false], [38, false], [39, false],
            [40, false], [41, false], [42, false], [43, false], [44, false], [45, false], [46, false], [47, false],
            [48, false], [49, false], [50, false], [51, false], [52, false], [53, false], [54, false], [55, false],
            [56, false], [57, false], [58, false], [59, false], [60, false], [61, false], [62, false], [63, false],
            [64, false], [65, false], [66, false], [67, false], [68, false], [69, false], [70, false], [71, false],
            [72, false], [73, false], [74, false], [75, false], [76, false], [77, false], [78, false], [79, false],
            [80, false], [81, false], [82, false], [83, false], [84, false], [85, false], [86, false], [87, false],
            [88, false], [89, false], [90, false], [91, false], [92, false], [93, false], [94, false], [95, false],
            [96, false], [97, false], [98, false], [99, false], [100, false], [101, false], [102, false], [103, false],
            [104, false], [105, false], [106, false], [107, false], [108, false], [109, false], [110, false], [111, false],
            [112, false], [113, false], [114, false], [115, false], [116, false], [117, false], [118, false], [119, false],
            [120, false], [121, false], [122, false], [123, false], [124, false], [125, false], [126, false], [127, false],
        ];
    }
}

class reusableEntityClass4
{
    public $name;
    public $type;
}

class reusableEntityClass5
{
    private $name;
    private $type;

    public function __construct($n, $t)
    {
        $this->name = $n;
        $this->type = $t;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}

class reusableEntityClass6
{
    private $name;
    private $type;

    public function __construct($n, $t)
    {
        $this->name = $n;
        $this->type = $t;
    }
}
