<?php

namespace UnitTests\POData\ObjectModel;

use Carbon\Carbon;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\IService;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\IOperationContext;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\IType;
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
    private $mockMeta;

    public function Construct()
    {
        $AbsoluteServiceURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc');
        $this->mockMeta = m::mock(IMetadataProvider::class);
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $context = m::mock(IOperationContext::class)->makePartial();
        $this->mockStreamWrapper = m::mock(StreamProviderWrapper::class);
        $this->mockService = $service;
        $this->mockRequest = $request;
        $this->mockWrapper = $wrapper;
        $this->mockWrapper->shouldReceive('getMetaProvider')->andReturn($this->mockMeta);
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
        $mockResourceSet = m::mock(\POData\Providers\Metadata\ResourceSet::class)->makePartial();
        $mockResourceType->shouldReceive('getCustomState')->andReturn($mockResourceSet);

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);
        $this->mockMeta->shouldReceive('resolveResourceType')->andReturn($mockResourceType);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn('Entity');
        $mockResourceSet->shouldReceive('getName')->andReturn('Entity');

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        $editLink = new ODataLink();
        $editLink->url = "Entity(name='bilbo',type=2)";
        $editLink->name = 'edit';
        $editLink->title = null;

        $ret = $foo->writeTopLevelElement($queryResult);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->id);
        $this->assertEquals($editLink, $ret->editLink);
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
        $mockResourceSet = m::mock(\POData\Providers\Metadata\ResourceSet::class)->makePartial();

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockService->shouldReceive('getConfiguration->getEntitySetPageSize')->andReturn(200);

        $orderInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn([])->never();

        $rootNode = m::mock(RootProjectionNode::class);
        $rootNode->shouldReceive('isExpansionSpecified')->andReturn(false);
        $rootNode->shouldReceive('canSelectAllProperties')->andReturn(true)->twice();
        $rootNode->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo)->never();

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn('data');
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);
        $this->mockRequest->shouldReceive('getIdentifier')->andReturn('Entity');
        $this->mockRequest->shouldReceive('getRootProjectionNode')->andReturn($rootNode);
        $this->mockMeta->shouldReceive('resolveResourceType')->andReturn($mockResourceType);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $mockResourceSet->shouldReceive('getName')->andReturn('Entity');
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn('Entity');
        $mockResourceType->shouldReceive('getCustomState')->andReturn($mockResourceSet);

        $e = [$entity, $entity1];
        $queryResult = new QueryResult();
        $queryResult->results = $e;
        $queryResult->hasMore = false;

        $ret = $foo->writeTopLevelElements($queryResult);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);

        $this->assertTrue(is_array($ret->entries));

        $this->assertEquals('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)', $ret->id);
        $this->assertEquals(new ODataTitle('data'), $ret->title);

        $this->assertEquals('self', $ret->selfLink->name);
        $this->assertEquals('data', $ret->selfLink->title);
        $this->assertEquals('Entity', $ret->selfLink->url);

        $this->assertEquals(2, count($ret->entries));

        $this->assertTrue($ret->entries[0] instanceof \POData\ObjectModel\ODataEntry);
        $this->assertTrue($ret->entries[1] instanceof \POData\ObjectModel\ODataEntry);

        $this->assertEquals(
            "http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)",
            $ret->entries[0]->id
        );
        $this->assertEquals(
            "http://192.168.2.1/abm-master/public/odata.svc/Entity(name='dildo',type=3)",
            $ret->entries[1]->id
        );

        $editLink = new ODataLink();
        $editLink->url = "Entity(name='bilbo',type=2)";
        $editLink->name = 'edit';
        $editLink->title = null;

        $this->assertEquals($editLink, $ret->entries[0]->editLink);

        $editLink->url = "Entity(name='dildo',type=3)";
        $this->assertEquals($editLink, $ret->entries[1]->editLink);

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
        $mockResourceSet = m::mock(\POData\Providers\Metadata\ResourceSet::class)->makePartial();
        $mockResourceSet->shouldReceive('getName')->andReturn('Entity');
        $mockResourceType->shouldReceive('getCustomState')->andReturn($mockResourceSet);

        $requestURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)');

        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturn(null);
        $this->mockService->shouldReceive('getConfiguration->getEntitySetPageSize')->andReturn(200);

        $orderInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn([])->twice();

        $rootNode = m::mock(RootProjectionNode::class);
        $rootNode->shouldReceive('isExpansionSpecified')->andReturn(false)->never();
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
        $this->mockMeta->shouldReceive('resolveResourceType')->andReturn($mockResourceType);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
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
        $this->assertEquals(new ODataTitle('data'), $ret->title);

        $this->assertEquals('self', $ret->selfLink->name);
        $this->assertEquals('data', $ret->selfLink->title);
        $this->assertEquals('Entity', $ret->selfLink->url);

        $this->assertEquals(2, count($ret->entries));

        $this->assertTrue($ret->entries[0] instanceof \POData\ObjectModel\ODataEntry);
        $this->assertTrue($ret->entries[1] instanceof \POData\ObjectModel\ODataEntry);

        $this->assertEquals(
            "http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)",
            $ret->entries[0]->id
        );
        $this->assertEquals(
            "http://192.168.2.1/abm-master/public/odata.svc/Entity(name='dildo',type=3)",
            $ret->entries[1]->id
        );

        $editLink = new ODataLink();
        $editLink->url = "Entity(name='bilbo',type=2)";
        $editLink->name = 'edit';
        $editLink->title = null;

        $this->assertEquals($editLink, $ret->entries[0]->editLink);
        $editLink->url = "Entity(name='dildo',type=3)";
        $this->assertEquals($editLink, $ret->entries[1]->editLink);

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

        $rSet = m::mock(ResourceSet::class);
        $rSet->shouldReceive('getName')->andReturn('foobars');

        $primType = m::mock(ResourcePrimitiveType::class)->makePartial();
        $primType->shouldReceive('getCustomState')->andReturn($rSet);
        $primType->shouldReceive('getInstanceType')->andReturn(new StringType());

        $itype = new StringType();
        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('getName')->andReturn('name');
        $rProp->shouldReceive('getInstanceType')->andReturn($itype);
        $rProp->shouldReceive('getResourceType')->andReturn($primType);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);

        $tProp = m::mock(ResourceProperty::class);
        $tProp->shouldReceive('getName')->andReturn('type');
        $tProp->shouldReceive('getInstanceType')->andReturn($itype);
        $tProp->shouldReceive('getResourceType')->andReturn($primType);
        $tProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::BAG])->andReturn(false);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('names');
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(50);
        $resourceSet->shouldReceive('getResourceSet')->andReturn($rSet);

        $refClass = new \ReflectionClass(reusableEntityClass4::class);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['name' => $rProp]);
        $resourceType->shouldReceive('getName')->andReturn('name');
        $resourceType->shouldReceive('getFullName')->andReturn('Data.name');
        $resourceType->shouldReceive('isMediaLinkEntry')->andReturn(false);
        $resourceType->shouldReceive('hasNamedStream')->andReturn(false);
        $resourceType->shouldReceive('getETagProperties')->andReturn([]);
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getAllProperties')->andReturn([$rProp, $tProp]);
        $resourceType->shouldReceive('getCustomState')->andReturn($rSet);
        $resourceType->shouldReceive('getInstanceType')->andReturn($refClass);

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
        $this->mockMeta->shouldReceive('resolveResourceType')->andReturn($resourceType);

        $queryResult = new QueryResult();
        $queryResult->results = $e;

        $ret = $foo->writeTopLevelElements($queryResult);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);
        $this->assertEquals('http://192.168.2.1/abm-master/public/odata.svc/Entity(1)', $ret->id);
    }

    public function testWriteNullPrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $primVal = null;
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals(null, $result->properties['name']->value);
    }

    public function testWriteBooleanPrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new Boolean());

        $primVal = true;
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals('true', $result->properties['name']->value);
    }

    public function testWriteBinaryPrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new Binary());

        $primVal = 'aybabtu';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals('YXliYWJ0dQ==', $result->properties['name']->value);
    }

    public function testWriteDateTimePrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new DateTime());

        $primVal = new \DateTime('2016-01-01');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals('2016-01-01T', substr($result->properties['name']->value, 0, 11));
    }

    public function testWriteStringPrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn(new StringType());

        $primVal = 'Börk, börk, börk!';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals('BÃ¶rk, bÃ¶rk, bÃ¶rk!', $result->properties['name']->value);
    }

    public function testWriteNullTypePrimitive()
    {
        $foo = $this->Construct();

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('typeName');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getInstanceType')->andReturn($iType);

        $primVal = 'Börk, börk, börk!';
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType')->andReturn($iType);
        $property->shouldReceive('getResourceType')->andReturn($type);

        $queryResult = new QueryResult();
        $queryResult->results = $primVal;

        $result = $foo->writeTopLevelPrimitive($queryResult, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties['name']->name);
        $this->assertEquals('typeName', $result->properties['name']->typeName);
        $this->assertEquals('Börk, börk, börk!', $result->properties['name']->value);
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
        $resourceWrap->shouldReceive('getName')->andReturn(null);

        $foo = $this->Construct();

        $this->mockRequest->queryType = QueryType::ENTITIES_WITH_COUNT();
        $this->mockRequest->shouldReceive('getCountValue')->andReturn(2);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($url);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($resourceWrap);

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
        $foo->shouldReceive('getNextLinkUri')->andReturn($odataLink->url)->once();

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
        $this->assertTrue($result->properties[$propertyName] instanceof ODataProperty);
        $this->assertNull($result->properties[$propertyName]->value);
        $this->assertNull($result->properties[$propertyName]->attributeExtensions);
        $this->assertEquals('property', $result->properties[$propertyName]->name);
        $this->assertEquals('typeName', $result->properties[$propertyName]->typeName);
    }

    public function testWriteNonNullComplexValue()
    {
        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('fullName');

        $propType = m::mock(ResourceType::class);
        $propType->shouldReceive('getInstanceType')->andReturn($iType);

        $resProperty = m::mock(ResourceProperty::class);
        $resProperty->shouldReceive('getKind')->andReturn(24);
        $resProperty->shouldReceive('getName')->andReturn('name');
        $resProperty->shouldReceive('getInstanceType')->andReturn($iType);
        $resProperty->shouldReceive('getResourceType')->andReturn($propType);
        $resProperty->shouldReceive('isKindOf')->passthru();

        $complexValue = new reusableEntityClass2('2016-12-25', null);
        $propertyName = 'property';
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getFullName')->andReturn('typeName')->once();
        $type->shouldReceive('getName')->andReturn('typeName')->never();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX());
        $type->shouldReceive('getAllProperties')->andReturn([$resProperty]);

        $queryResult = new QueryResult();
        $queryResult->results = $complexValue;

        $foo = $this->Construct();
        $result = $foo->writeTopLevelComplexObject($queryResult, $propertyName, $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[$propertyName] instanceof ODataProperty);
        $this->assertNull($result->properties[$propertyName]->attributeExtensions);
        //$this->assertNull($result->properties[0]->value);
        $this->assertTrue($result->properties[$propertyName]->value instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[$propertyName]->value->properties['name'] instanceof ODataProperty);
        $this->assertEquals('name', $result->properties[$propertyName]->value->properties['name']->name);
        $this->assertEquals('fullName', $result->properties[$propertyName]->value->properties['name']->typeName);
    }

    public function testWriteTopLevelBagObjectTripAssertion()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY())->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = null;

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $expected = '$bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE &&'
                    .' $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelBagObjectNull()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE())->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = null;

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties['property'] instanceof ODataProperty);
        $this->assertNull($result->properties['property']->attributeExtensions);
        $this->assertNull($result->properties['property']->value);
        $this->assertEquals('property', $result->properties['property']->name);
        $this->assertEquals('Collection(fullName)', $result->properties['property']->typeName);
    }

    public function testWriteTopLevelBagObjectEmptyArray()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE())->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = [];

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties['property'] instanceof ODataProperty);
        $this->assertNull($result->properties['property']->attributeExtensions);
        $this->assertNull($result->properties['property']->value);
        $this->assertEquals('property', $result->properties['property']->name);
        $this->assertEquals('Collection(fullName)', $result->properties['property']->typeName);
    }

    public function testWriteTopLevelBagObjectArrayOfNulls()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE())->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = [null, null];

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties['property'] instanceof ODataProperty);
        $this->assertNull($result->properties['property']->attributeExtensions);
        $this->assertTrue($result->properties['property']->value instanceof ODataBagContent);
        $this->assertNull($result->properties['property']->value->type);
        $this->assertNull($result->properties['property']->value->propertyContents);
        $this->assertEquals('property', $result->properties['property']->name);
        $this->assertEquals('Collection(fullName)', $result->properties['property']->typeName);
    }

    public function testWriteTopLevelBagObjectActualObject()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE())->never();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = new \DateTime();

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $foo = $this->Construct();

        $expected = 'Bag parameter must be null or array';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteTopLevelBagObjectArrayOfPrimitiveObjects()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE())->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');
        $type->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $bag = ['foo', 123];
        $expected = ['foo', '123'];

        $foo = $this->Construct();

        $queryResult = new QueryResult();
        $queryResult->results = $bag;

        $result = $foo->writeTopLevelBagObject($queryResult, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties['property'] instanceof ODataProperty);
        $this->assertNull($result->properties['property']->attributeExtensions);
        $this->assertTrue($result->properties['property']->value instanceof ODataBagContent);
        $this->assertNull($result->properties['property']->value->type);
        $this->assertTrue(is_array($result->properties['property']->value->propertyContents));
        $this->assertEquals($expected, $result->properties['property']->value->propertyContents);
        $this->assertEquals('property', $result->properties['property']->name);
        $this->assertEquals('Collection(fullName)', $result->properties['property']->typeName);
    }

    public function testWriteTopLevelElementWithExpandedProjectionNodes()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $entity = new reusableEntityClass2('2016-12-25', null);

        $url = new Url('https://www.example.org/odata.svc');

        $projNode1 = m::mock(ExpandedProjectionNode::class)->makePartial();
        $projNode1->shouldReceive('getPropertyName')->andReturn('name');
        $projNode2 = m::mock(ExpandedProjectionNode::class)->makePartial();
        $projNode2->shouldReceive('getPropertyName')->andReturn('type');

        $iType = m::mock(IType::class);
        $iType->shouldReceive('getFullTypeName')->andReturn('fullTypeName');

        $navType = m::mock(ResourceType::class)->makePartial();
        $navType->shouldReceive('getInstanceType')->andReturn($iType);

        $resolv = m::mock(ResourceProperty::class)->makePartial();
        $resolv->shouldReceive('getName')->andReturn('name');
        $resolv->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY())->atLeast(1);
        $resolv->shouldReceive('getResourceType')->andReturn($navType);
        $resolv->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $resolv->shouldReceive('isKindOf')->andReturn(false);
        $resolv->shouldReceive('getInstanceType')->andReturn($iType);

        $typeProp = m::mock(ResourceProperty::class)->makePartial();
        $typeProp->shouldReceive('getName')->andReturn('type');
        $typeProp->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY())->atLeast(1);
        $typeProp->shouldReceive('getResourceType')->andReturn($navType);
        $typeProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $typeProp->shouldReceive('isKindOf')->andReturn(false);
        $typeProp->shouldReceive('getInstanceType')->andReturn($iType);

        $rSet = m::mock(ResourceSet::class)->makePartial();
        $rSet->shouldReceive('getName')->andReturn('wrapper');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('customers');
        $type->shouldReceive('getFullName')->andReturn('customers');
        $type->shouldReceive('isMediaLinkEntry')->andReturn(false)->atLeast(1);
        $type->shouldReceive('hasNamedStream')->andReturn(false)->atLeast(1);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY())->atLeast(1);
        $type->shouldReceive('getAllProperties')->andReturn([$resolv, $typeProp]);
        $type->shouldReceive('resolveProperty')->andReturn($resolv);
        $type->shouldReceive('getCustomState')->andReturn($rSet);

        $prop = m::mock(ResourceProperty::class)->makePartial();
        $prop->shouldReceive('getResourceType')->andReturn($type);

        $wrap = m::mock(ResourceSetWrapper::class)->makePartial();
        $wrap->shouldReceive('getName')->andReturn('wrapper');

        $navProp = m::mock(ResourceProperty::class)->makePartial();

        $mockMeta = m::mock(IMetadataProvider::class);
        $mockMeta->shouldReceive('resolveResourceType')->andReturn($type);

        $provWrap = m::mock(ProvidersWrapper::class)->makePartial();
        $provWrap->shouldReceive('getResourceProperties')->andReturn($navProp);
        $provWrap->shouldReceive('getMetaProvider')->andReturn($mockMeta);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::PROPERTY);
        $request->shouldReceive('getProjectedProperty')->andReturn($prop);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($wrap);
        $request->shouldReceive('getRequestUrl')->andReturn($url);
        $request->shouldReceive('getTargetResourceType')->andReturn($type);

        $stack = m::mock(SegmentStack::class)->makePartial();

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
        $foo->shouldReceive('getUpdated')->andReturn($known);

        $queryResult = new QueryResult();
        $queryResult->results = $entity;

        $expectedProp = new ODataPropertyContent();
        $expectedProp->properties = ['name' => new ODataProperty(), 'type' => new ODataProperty()];
        $expectedProp->properties['name']->name = 'name';
        $expectedProp->properties['name']->typeName = '';
        $expectedProp->properties['type']->name = 'type';
        $expectedProp->properties['type']->typeName = '';

        $editLink = new ODataLink();
        $editLink->url = 'customer';
        $editLink->name = 'edit';
        $editLink->title = 'customers';

        $type = new ODataCategory('customers');

        $result = $foo->writeTopLevelElement($queryResult);
        $this->assertTrue($result instanceof ODataEntry);
        $this->assertEquals('/customer', $result->id);
        $this->assertEquals(new ODataTitle('customers'), $result->title);
        $this->assertEquals($editLink, $result->editLink);
        $this->assertEquals($type, $result->type);
        $this->assertEquals('wrapper', $result->resourceSetName);
        $this->assertEquals(0, count($result->links));
        $this->assertEquals(0, count($result->mediaLinks));
        $propContent = $result->propertyContent;
        $this->assertTrue($propContent instanceof ODataPropertyContent);
        $this->assertEquals($expectedProp, $propContent);
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

    public function testWriteElementWithNullResultReturnsNull()
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

        $result = new QueryResult();
        $result->results = null;

        $this->assertNull($foo->writeTopLevelElement($result));
    }

    /**
     * @dataProvider matchPrimitiveProvider
     * @param mixed $input
     * @param mixed $expected
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
