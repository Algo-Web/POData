<?php

namespace UnitTests\POData\ObjectModel;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\IService;
use POData\ObjectModel\ObjectModelSerializerBase;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;
use ReflectionException;
use UnitTests\POData\TestCase;

class ObjectModelSerializerBaseTest extends TestCase
{
    private $mockRequest;
    private $service;
    private $serviceHost;

    public function Construct()
    {
        $AbsoluteServiceURL = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc');
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $serviceHost = m::mock(\POData\OperationContext\ServiceHost::class)->makePartial();
        $serviceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($AbsoluteServiceURL);
        $service->shouldReceive('getHost')->andReturn($serviceHost);
        $this->mockRequest = $request;
        $this->service = $service;
        $this->serviceHost = $serviceHost;
        $foo = new ObjectModelSerializerDummy($service, $request);

        return $foo;
    }

    public function testObjectModelSerializerBaseconstructor()
    {
        $foo = $this->Construct();
        $this->assertTrue(is_object($foo));
    }

    public function testGetEntryInstanceKey()
    {
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $resourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $foo = $this->Construct();
        $entity = new reUsableentityClass1();
        $entity->name = 'bilbo';
        $entity->type = 2;
        $ret = $foo->getEntryInstanceKey($entity, $resourceType, 'Data');
        $this->assertEquals("Data(name='bilbo',type=2)", $ret);
    }

    public function testGetEntryInstanceKeyWith__Get()
    {
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $resourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $foo = $this->Construct();
        $entity = new reUsableentityClass2('bilbo', 2);
        $ret = $foo->getEntryInstanceKey($entity, $resourceType, 'Data');
        $this->assertEquals("Data(name='bilbo',type=2)", $ret);
    }

    public function testGetEntryInstanceKeyWithPrivate()
    {
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $resourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $foo = $this->Construct();
        $entity = new reUsableentityClass3('bilbo', 2);
        $ret = $foo->getEntryInstanceKey($entity, $resourceType, 'Data');
        $this->assertEquals("Data(name='bilbo',type=2)", $ret);
    }

    public function testGetEntryInstanceKeyThrowException()
    {
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn('name');
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn('type');
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = ['name' => $resourceProperty, 'type'=>$resourceProperty2];
        $resourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);
        $resourceType->shouldReceive('getName')->andReturn('ComplexString');

        $entity = new reusableEntityClass2(null, null);

        $foo = $this->Construct();

        $expected = "The serialized resource of type ComplexString has a null value in key member 'name'. Null"
                    .' values are not supported in key members.';
        $actual = null;
        try {
            $foo->getEntryInstanceKey($entity, $resourceType, 'container');
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testgetCurrentResourceSetWrapper()
    {
        $foo = $this->Construct();
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn(true);
        $ret = $foo->getCurrentResourceSetWrapper();
        $this->assertEquals(true, $ret);
    }

    public function testisRootResourceSet()
    {
        $foo = $this->Construct();
        $ret = $foo->isRootResourceSet();
        $this->assertEquals(true, $ret, 'isRootResourceSet 1');
    }

    public function testGetPropertyValueFromMagicMethod()
    {
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('type');

        $foo = $this->Construct();
        $entity = new reusableEntityClass2('up', 'down');

        $expected = 'down';
        $actual = $foo->getPropertyValue($entity, $type, $property);
        $this->assertEquals($expected, $actual);
    }

    public function testGetPropertyValueWithoutMagicMethod()
    {
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('type');

        $foo = $this->Construct();
        $entity = new reusableEntityClass3('up', 'down');

        $expected = 'down';
        $actual = $foo->getPropertyValue($entity, $type, $property);
        $this->assertEquals($expected, $actual);
    }

    public function testGetPropertyValueWithoutMagicMethodReflectionException()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('String');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->withAnyArgs()->andReturn('String')->ordered();
        $property->shouldReceive('getName')->withAnyArgs()->andThrow(new ReflectionException())->ordered();
        $property->shouldReceive('getName')->withAnyArgs()->andReturn('String')->ordered();

        $foo = $this->Construct();
        $entity = new reusableEntityClass3('up', 'down');

        $expected = 'objectModelSerializer failed to access or initialize the property String of String,'
                    .' Please contact provider.';
        $actual = null;
        try {
            $foo->getPropertyValue($entity, $type, $property);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetCurrentExpandedNodeIsBelowRoot()
    {
        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('findNode')->andReturn($node)->once();
        $node->shouldReceive('getSkipCount')->andReturn(1);
        $node->shouldReceive('getTakeCount')->andReturn(2);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('getSegmentNames')->andReturn(['hammer', 'time']);

        $foo = $this->Construct();
        $this->mockRequest->shouldReceive('getRootProjectionNode')->andReturn($node);
        $foo->setStack($stack);

        $result = $foo->getCurrentExpandedProjectionNode();
        $this->assertTrue($result instanceof ExpandedProjectionNode);
        $this->assertEquals(1, $result->getSkipCount());
        $this->assertEquals(2, $result->getTakeCount());
    }

    public function testGetETagForEntry()
    {
        $prop1 = m::mock(ResourceProperty::class);
        $prop1->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\DateTime());
        $prop1->shouldReceive('getName')->andReturn('name');

        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());
        $prop2->shouldReceive('getName')->andReturn('type');

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getName')->andReturn('String');
        $type->shouldReceive('getETagProperties')->andReturn([$prop1, $prop2]);

        $entity = new reusableEntityClass2('2016-12-25', null);

        $foo = $this->Construct();

        $expected = 'W/"datetime\'2016-12-25\',null"';
        $actual = $foo->getETagForEntry($entity, $type);
        $this->assertEquals($expected, $actual);
    }

    public function testPushSegmentForNavigationWrongTypeThrowsException()
    {
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::COMPLEX);

        $foo = $this->Construct();
        $expected = 'pushSegmentForNavigationProperty should not be called with non-entity type';
        $actual = null;

        try {
            $foo->pushSegmentForNavigationProperty($prop2);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testPushSegmentForNavigationEmptySegments()
    {
        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('getSegmentNames')->andReturn([]);

        $foo = $this->Construct();
        $foo->setStack($stack);
        $expected = 'assert(): Segment names should not be empty failed';
        $actual = null;

        try {
            $foo->pushSegmentForNavigationProperty($prop2);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testPushSegmentForNavigationSuccess()
    {
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $prop2 = m::mock(ResourceProperty::class);
        $prop2->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);
        $prop2->shouldReceive('getName')->andReturn('hammer');

        $resourceWrap = m::mock(ResourceSetWrapper::class);
        $resourceWrap->shouldReceive('getResourceType')->andReturn($resourceType);

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('getSegmentNames')->andReturn(['hammer', 'time']);
        $stack->shouldReceive('pushSegment')->andReturn(true)->once();
        $stack->shouldReceive('getSegmentWrappers')->andReturn([$resourceWrap]);

        $foo = $this->Construct();
        $foo->setStack($stack);
        $this->service->shouldReceive('getProvidersWrapper->getResourceSetWrapperForNavigationProperty')
            ->andReturn($resourceWrap);

        $result = $foo->pushSegmentForNavigationProperty($prop2);
        $this->assertTrue(true === $result);
    }

    public function testProjectionNodesWithNoCurrentExpandedNode()
    {
        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturnNull()->once();
        $this->assertNull($foo->getProjectionNodes());
    }

    public function testProjectionNodesWithCurrentExpandedNode()
    {
        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getChildNodes')->andReturn([])->once();
        $node->shouldReceive('canSelectAllProperties')->andReturn(false)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $result = $foo->getProjectionNodes();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testShouldExpandSegmentWithNoCurrentExpandedNode()
    {
        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturnNull()->once();
        $this->assertFalse($foo->shouldExpandSegment('abc'));
    }

    public function testShouldExpandSegmentWithCurrentExpandedNodeNotMatchingProperty()
    {
        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getChildNodes')->never();
        $node->shouldReceive('findNode')->withArgs(['abc'])->andReturnNull()->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $this->assertFalse($foo->shouldExpandSegment('abc'));
    }

    public function testShouldExpandSegmentWithCurrentExpandedNodeIsMatchingProperty()
    {
        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getChildNodes')->never();
        $node->shouldReceive('findNode')->withArgs(['abc'])->andReturn($node)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $this->assertTrue($foo->shouldExpandSegment('abc'));
    }

    public function testGetNextPageLinkQueryParametersForRootResourceSetNoResultsNoCounts()
    {
        $foo = $this->Construct();
        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturnNull()->atLeast(1);
        $this->mockRequest->shouldReceive('getTopOptionCount')->andReturnNull()->once();

        $expected = '';
        $actual = $foo->getNextPageLinkQueryParametersForRootResourceSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextPageLinkQueryParametersForRootResourceSetNoResultsHasCounts()
    {
        $foo = $this->Construct();
        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturnNull()->atLeast(1);
        $this->mockRequest->shouldReceive('getTopOptionCount')->andReturn(11)->once();
        $this->mockRequest->shouldReceive('getTopCount')->andReturn(1)->once();

        $expected = '$top=10&';
        $actual = $foo->getNextPageLinkQueryParametersForRootResourceSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextPageLinkQueryParametersForRootResourceSetHasResultsHasCounts()
    {
        $foo = $this->Construct();
        $this->serviceHost->shouldReceive('getQueryStringItem')->andReturn('bork')->atLeast(1);
        $this->mockRequest->shouldReceive('getTopOptionCount')->andReturn(11)->once();
        $this->mockRequest->shouldReceive('getTopCount')->andReturn(1)->once();

        $expected = '$filter=bork&$expand=bork&$orderby=bork&$inlinecount=bork&$select=bork&$top=10&';
        $actual = $foo->getNextPageLinkQueryParametersForRootResourceSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextLinkUriSkipTokenNull()
    {
        $orderInfo = m::mock(InternalOrderByInfo::class);
        $orderInfo->shouldReceive('buildSkipTokenValue')->andReturnNull()->once();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a'])->once();

        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo);

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $expected = 'assert(): !is_null($skipToken) failed';
        $actual = null;

        $entity = new reusableEntityClass2('2016-12-25', null);

        try {
            $foo->getNextLinkUri($entity, null);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextLinkUriIsRootResourceSet()
    {
        $url = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc');

        $orderInfo = m::mock(InternalOrderByInfo::class);
        $orderInfo->shouldReceive('buildSkipTokenValue')->andReturn('skippage')->once();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a'])->once();

        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('isRootResourceSet')->andReturn(true)->once();
        $foo->shouldReceive('getNextPageLinkQueryParametersForRootResourceSet')
            ->andReturn('entity$filter=bork&$expand=bork&$orderby=bork&$inlinecount=bork&$select=bork&$top=10&')
            ->once();

        $expected = 'http://192.168.2.1/abm-master/public/odata.svc?entity$filter=bork&$expand=bork&$orderby=bork'
                    .'&$inlinecount=bork&$select=bork&$top=10&$skip=skippage';
        $actual = null;

        $entity = new reusableEntityClass2('2016-12-25', null);

        $actual = $foo->getNextLinkUri($entity, $url->getUrlAsString());
        $this->assertEquals($expected, $actual->url);
    }

    public function testGetNextLinkUriIsNotRootResourceSet()
    {
        $url = new \POData\Common\Url('http://192.168.2.1/abm-master/public/odata.svc');

        $orderInfo = m::mock(InternalOrderByInfo::class);
        $orderInfo->shouldReceive('buildSkipTokenValue')->andReturn('skippage')->once();
        $orderInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a'])->once();

        $node = m::mock(ExpandedProjectionNode::class);
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($orderInfo)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('isRootResourceSet')->andReturn(false)->once();
        $foo->shouldReceive('getNextPageLinkQueryParametersForExpandedResourceSet')
            ->andReturn('entity$filter=bork&$expand=bork&$orderby=bork&$inlinecount=bork&$select=bork&$top=10&')
            ->once();

        $expected = 'http://192.168.2.1/abm-master/public/odata.svc?entity$filter=bork&$expand=bork&$orderby=bork'
                    .'&$inlinecount=bork&$select=bork&$top=10&$skip=skippage';
        $actual = null;

        $entity = new reusableEntityClass2('2016-12-25', null);

        $actual = $foo->getNextLinkUri($entity, $url->getUrlAsString());
        $this->assertEquals($expected, $actual->url);
    }

    public function testNeedNextPageLink()
    {
        $resourceWrapper = m::mock(ResourceSetWrapper::class);
        $resourceWrapper->shouldReceive('getResourceSetPageSize')->andReturn(200)->once();

        $stack = m::mock(SegmentStack::class);
        $stack->shouldReceive('getSegmentNames')->andReturn(['hammer'])->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getCurrentResourceSetWrapper')->andReturn($resourceWrapper)->once();
        $foo->shouldReceive('getRequest->getTopOptionCount')->andReturn(42);
        $foo->setStack($stack);

        $this->assertFalse($foo->needNextPageLink(42));
    }

    public function testGenerateNextLinkUrlNeedsPlainSkippage()
    {
        $object = new \stdClass();

        $internalInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $internalInfo->shouldReceive('buildSkipTokenValue')->andReturn('200');
        $internalInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a'])->once();

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($internalInfo)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('getNextPageLinkQueryParametersForExpandedResourceSet')->andReturn('Customers(42)/Orders');
        $foo->shouldReceive('isRootResourceSet')->andReturn(false);

        $expected = 'http://localhost/odata.svc?Customers(42)/Orders$skip=200';
        $actual = $foo->getNextLinkUri($object, 'http://localhost/odata.svc/');
        $this->assertEquals($expected, $actual->url);
    }

    public function testGenerateNextLinkUrlNeedsSkipToken()
    {
        $object = new \stdClass();

        $internalInfo = m::mock(InternalOrderByInfo::class)->makePartial();
        $internalInfo->shouldReceive('buildSkipTokenValue')->andReturn('\'University+of+Loamshire\'');
        $internalInfo->shouldReceive('getOrderByPathSegments')->andReturn(['a', 'b'])->once();

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getInternalOrderByInfo')->andReturn($internalInfo)->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();
        $foo->shouldReceive('getNextPageLinkQueryParametersForExpandedResourceSet')->andReturn('Customers(42)/Orders');
        $foo->shouldReceive('isRootResourceSet')->andReturn(false);

        $expected = 'http://localhost/odata.svc?Customers(42)/Orders$skiptoken=\'University+of+Loamshire\'';
        $actual = $foo->getNextLinkUri($object, 'http://localhost/odata.svc/');
        $this->assertEquals($expected, $actual->url);
    }

    public function testGetNextPageLinkQueryParametersForExpandedResourceSetNothingToExpand()
    {
        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn(null)->once();

        $this->assertNull($foo->getNextPageLinkQueryParametersForExpandedResourceSet());
    }

    public function testGetNextPageLinkQueryParametersForExpandedResourceSetCurrentNodeHasNoChildren()
    {
        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getChildNodes')->andReturn([])->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $expected = null;
        $actual = $foo->getNextPageLinkQueryParametersForExpandedResourceSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextPageLinkQueryParametersForExpandedResourceSetCurrentNodeHasOneNonExpansionChild()
    {
        $rootNode = m::mock(ProjectionNode::class)->makePartial();
        $rootNode->shouldReceive('getPropertyName')->andReturn('fooBar');

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getChildNodes')->andReturn([$rootNode])->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $expected = '$expand=fooBar&';
        $actual = $foo->getNextPageLinkQueryParametersForExpandedResourceSet();
        $this->assertEquals($expected, $actual);
    }

    public function testGetNextPageLinkQueryParametersForExpandedResourceSetCurrentNodeHasOneRootExpansionChild()
    {
        $rootNode = m::mock(RootProjectionNode::class)->makePartial();
        $rootNode->shouldReceive('getPropertyName')->andReturn('fooBar');
        $rootNode->shouldReceive('canSelectAllProperties')->andReturn(true);

        $node = m::mock(ExpandedProjectionNode::class)->makePartial();
        $node->shouldReceive('getChildNodes')->andReturn([$rootNode])->once();

        $foo = m::mock(ObjectModelSerializerDummy::class)->shouldAllowMockingProtectedMethods()->makePartial();
        $foo->shouldReceive('getCurrentExpandedProjectionNode')->andReturn($node)->once();

        $expected = '$expand=fooBar&';
        $actual = $foo->getNextPageLinkQueryParametersForExpandedResourceSet();
        $this->assertEquals($expected, $actual);
    }
}

class reusableEntityClass1
{
    public $name;
    public $type;
}

class reusableEntityClass2
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

class reusableEntityClass3
{
    private $name;
    private $type;

    public function __construct($n, $t)
    {
        $this->name = $n;
        $this->type = $t;
    }
}

class ObjectModelSerializerDummy extends ObjectModelSerializerBase
{
    /**
     * Creates new instance of ObjectModelSerializerTest.
     *
     * @param IService           $service
     * @param RequestDescription $request the  request submitted by the client
     */
    public function __construct(IService $service, RequestDescription $request)
    {
        parent::__construct($service, $request);
    }

    public function setStack(SegmentStack $stack)
    {
        $this->stack = $stack;
    }

    public function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName)
    {
        return parent::getEntryInstanceKey($entityInstance, $resourceType, $containerName);
    }

    public function getCurrentResourceSetWrapper()
    {
        return parent::getCurrentResourceSetWrapper();
    }

    public function isRootResourceSet()
    {
        return parent::isRootResourceSet();
    }

    public function getPropertyValue($entity, ResourceType $resourceType, ResourceProperty $resourceProperty)
    {
        return parent::getPropertyValue($entity, $resourceType, $resourceProperty);
    }

    public function getCurrentExpandedProjectionNode()
    {
        return parent::getCurrentExpandedProjectionNode();
    }

    public function getETagForEntry($entryObject, ResourceType $resourceType)
    {
        return parent::getETagForEntry($entryObject, $resourceType);
    }

    public function pushSegmentForNavigationProperty(ResourceProperty &$resourceProperty)
    {
        return parent::pushSegmentForNavigationProperty($resourceProperty);
    }

    public function shouldExpandSegment($navigationPropertyName)
    {
        return parent::shouldExpandSegment($navigationPropertyName);
    }

    public function getNextPageLinkQueryParametersForRootResourceSet()
    {
        return parent::getNextPageLinkQueryParametersForRootResourceSet();
    }

    public function getNextPageLinkQueryParametersForExpandedResourceSet()
    {
        return parent::getNextPageLinkQueryParametersForExpandedResourceSet();
    }

    public function getNextLinkUri(&$lastObject, $absoluteUri)
    {
        return parent::getNextLinkUri($lastObject, $absoluteUri);
    }

    public function needNextPageLink($resultSetCount)
    {
        return parent::needNextPageLink($resultSetCount);
    }
}
