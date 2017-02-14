<?php

namespace UnitTests\POData\ObjectModel;

use POData\Common\ODataConstants;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\ObjectModelSerializer;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\RequestDescription;
use POData\IService;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\ODataException;
use POData\Common\Messages;
use POData\Common\Url;

use Mockery as m;
use POData\UriProcessor\SegmentStack;

class ObjectModelSerializerTest extends \PHPUnit_Framework_TestCase
{
    private $mockRequest;
    private $mockWrapper;

    public function Construct()
    {
        $AbsoluteServiceURL = new \POData\Common\Url("http://192.168.2.1/abm-master/public/odata.svc");
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $this->mockRequest = $request;
        $this->mockWrapper = $wrapper;
        $serviceHost = m::mock(\POData\OperationContext\ServiceHost::class)->makePartial();
        $serviceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($AbsoluteServiceURL);
        $wrapper->shouldReceive('getResourceProperties')->andReturn([]);
        $service->shouldReceive('getHost')->andReturn($serviceHost);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
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
        $entity->name = "bilbo";
        $entity->type = 2;
        $mockResourceType = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $mockResourceSetWrapper = m::mock(\POData\Providers\Metadata\ResourceSetWrapper::class)->makePartial();

        $requestURL = new \POData\Common\Url("http://192.168.2.1/abm-master/public/odata.svc/Entity(1)");

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn("data");
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn("name");
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn("type");
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = array("name" => $resourceProperty, "type"=>$resourceProperty2);
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(2);
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn("Entity");

        $ret = $foo->writeTopLevelElement($entity);
        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->id);
        $this->assertEquals("Entity(name='bilbo',type=2)", $ret->editLink);
        $this->assertEquals("Entity", $ret->resourceSetName);
    }

    public function testwriteTopLevelElements()
    {
        $foo = $this->Construct();
        $entity = new reusableEntityClass4();
        $entity->name = "bilbo";
        $entity->type = 2;
        $entity1 =  new reusableEntityClass4();
        $entity1->name = "dildo";
        $entity1->type = 3;

        $mockResourceType = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $mockResourceSetWrapper = m::mock(\POData\Providers\Metadata\ResourceSetWrapper::class)->makePartial();

        $requestURL = new \POData\Common\Url("http://192.168.2.1/abm-master/public/odata.svc/Entity(1)");

        $this->mockRequest->shouldReceive('getTargetSource')->andReturn(2);
        $this->mockRequest->shouldReceive('getContainerName')->andReturn("data");
        $this->mockRequest->shouldReceive('getTargetResourceType')->andReturn($mockResourceType);
        $this->mockRequest->shouldReceive('getTargetResourceSetWrapper')->andReturn($mockResourceSetWrapper);
        $this->mockRequest->shouldReceive('getRequestUrl')->andReturn($requestURL);
        $this->mockRequest->shouldReceive('getIdentifier')->andReturn("Entity");

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn("name");
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn("type");
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

        $keysProperty = array("name" => $resourceProperty, "type"=>$resourceProperty2);
        $mockResourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $mockResourceType->shouldReceive('getResourceTypeKind')->andReturn(2);
        $mockResourceSetWrapper->shouldReceive('getName')->andReturn("Entity");

        $e = [$entity,$entity1];
        $ret = $foo->writeTopLevelElements($e);
        $this->assertTrue($ret instanceof \POData\ObjectModel\ODataFeed);
        $this->assertTrue($ret->selfLink instanceof \POData\ObjectModel\ODataLink);

        $this->assertTrue(is_array($ret->entries));

        $this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(1)", $ret->id);
        $this->assertEquals("data", $ret->title);

        $this->assertEquals("self", $ret->selfLink->name);
        $this->assertEquals("data", $ret->selfLink->title);
        $this->assertEquals("Entity", $ret->selfLink->url);

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

    public function testWriteNullPrimitive()
    {
        $foo = $this->Construct();

        $primVal = null;
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
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

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
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

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
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

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
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

        $primVal = "Börk, börk, börk!";
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
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

        $primVal = "Börk, börk, börk!";
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getName')->andReturn('name');
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('typeName');
        $property->shouldReceive('getResourceType')->andReturn($type);

        $result = $foo->writeTopLevelPrimitive($primVal, $property);
        $this->assertTrue($result instanceof ODataPropertyContent, get_class($result));
        $this->assertEquals('name', $result->properties[0]->name);
        $this->assertEquals('typeName', $result->properties[0]->typeName);
        $this->assertEquals("Börk, börk, börk!", $result->properties[0]->value);
    }

    public function testWriteNullUrlElement()
    {
        $foo = $this->Construct();

        $result = $foo->writeUrlElement(null);
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

        $result = $foo->writeUrlElement('bar');
        $this->assertEquals('/customer', $result->url);
    }

    public function testWriteNullUrlCollection()
    {
        $foo = $this->Construct();
        $this->mockRequest->queryType = QueryType::ENTITIES_WITH_COUNT();
        $this->mockRequest->shouldReceive('getCountValue')->andReturn(1);
        $result = $foo->writeUrlElements(null);
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

        $objects = [ 'customer', 'supplier'];

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('writeUrlElement')->withArgs(['supplier'])->andReturn('/supplier')->once();
        $foo->shouldReceive('writeUrlElement')->withArgs(['customer'])->andReturn('/customer')->once();
        $foo->shouldReceive('getStack->getSegmentWrappers')->andReturn([]);
        $foo->shouldReceive('getRequest')->andReturn($this->mockRequest);
        $foo->shouldReceive('needNextPageLink')->andReturn(true)->once();
        $foo->shouldReceive('getNextLinkUri')->andReturn($odataLink)->once();

        $result = $foo->writeUrlElements($objects);
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

        $foo = $this->Construct();
        $result = $foo->writeTopLevelComplexObject($complexValue, $propertyName, $type);
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
        $type->shouldReceive('getName')->andReturn('typeName')->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX)->once();
        $type->shouldReceive('getAllProperties')->andReturn([$resProperty]);

        $foo = $this->Construct();
        $result = $foo->writeTopLevelComplexObject($complexValue, $propertyName, $type);
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

        $foo = $this->Construct();

        $expected = 'assert(): $bagItemResourceTypeKind != ResourceTypeKind::PRIMITIVE &&'
                    .' $bagItemResourceTypeKind != ResourceTypeKind::COMPLEX failed';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($bag, 'property', $type);
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

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($bag, 'property', $type);
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

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($bag, 'property', $type);
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

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($bag, 'property', $type);
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
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');

        $bag = new \DateTime();

        $foo = $this->Construct();

        $expected = 'assert(): Bag parameter must be null or array failed';
        $actual = null;

        try {
            $foo->writeTopLevelBagObject($bag, 'property', $type);
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

        $bag = [ 'foo', 123];
        $expected = ['foo', '123'];

        $foo = $this->Construct();

        $result = $foo->writeTopLevelBagObject($bag, 'property', $type);
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

    public function testWriteTopLevelBagObjectArrayOfComplexObjects()
    {
        $propType = m::mock(ResourceType::class);
        $propType->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive("getKind")->andReturn(ResourcePropertyKind::PRIMITIVE)->once();
        $property->shouldReceive('getInstanceType->getFullTypeName')->andReturn('fullTypeName')->once();
        $property->shouldReceive('getName')->andReturn('propertyName');
        $property->shouldReceive('getResourceType')->andReturn($propType)->once();
        $property->shouldReceive('isKindOf')->andReturn(false)->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX)->once();
        $type->shouldReceive('getFullName')->andReturn('fullName');
        $type->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());
        $type->shouldReceive('getAllProperties')->andReturn([$property]);

        $bag = [ 'foo', 123];
        $expected = ['foo', '123'];

        $foo = m::mock(ObjectModelSerializer::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getPropertyValue')->andReturn('foo', 123);

        $result = $foo->writeTopLevelBagObject($bag, 'property', $type);
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertTrue($result->properties[0] instanceof ODataProperty);
        $this->assertNull($result->properties[0]->attributeExtensions);
        $this->assertTrue($result->properties[0]->value instanceof ODataBagContent);
        $this->assertNull($result->properties[0]->value->type);
        $this->assertTrue(is_array($result->properties[0]->value->propertyContents));
        $firstProp = $result->properties[0]->value->propertyContents[0];
        $secondProp = $result->properties[0]->value->propertyContents[1];
        $this->assertEquals("propertyName", $firstProp->properties[0]->name);
        $this->assertEquals("fullTypeName", $firstProp->properties[0]->typeName);
        $this->assertEquals(null, $firstProp->properties[0]->attributeExtensions);
        $this->assertEquals("foo", $firstProp->properties[0]->value);
        $this->assertEquals("propertyName", $secondProp->properties[0]->name);
        $this->assertEquals("fullTypeName", $secondProp->properties[0]->typeName);
        $this->assertEquals(null, $secondProp->properties[0]->attributeExtensions);
        $this->assertEquals("123", $secondProp->properties[0]->value);
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
        $prop1->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE)->once();
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
        $prop3->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE)->once();
        $prop3->shouldReceive('isKindOf')->andReturn(false);
        $prop3->shouldReceive('getResourceType')->andReturn($nuType);
        $prop3->shouldReceive('getName')->andReturn('type');
        $prop3->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::COMPLEX);
        $prop4 = m::mock(ResourceProperty::class);
        $prop4->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE)->once();
        $prop4->shouldReceive('isKindOf')->andReturn(false);
        $prop4->shouldReceive('getName')->andReturn('name');
        $prop4->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::COMPLEX)->once();
        $type->shouldReceive('getName')->andReturn('nuName');
        $type->shouldReceive('getAllProperties')->andReturn([$prop1, $prop2]);
        $propType->shouldReceive('getAllProperties')->andReturn([$prop3, $prop4]);

        $currentNode = m::mock(ExpandedProjectionNode::class);
        $currentNode->shouldReceive('getChildNodes')->andReturn([$kidNode1, $kidNode2])->once();
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

        try {
            $foo->writeTopLevelComplexObject($complexValue, 'property', $type);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
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
