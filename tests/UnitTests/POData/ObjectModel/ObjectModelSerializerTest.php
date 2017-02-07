<?php

namespace UnitTests\POData\ObjectModel;

use POData\Common\ODataConstants;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\ObjectModelSerializer;
use POData\Providers\Query\QueryType;
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
use Mockery as m;

class ObjectModelSerializerTest extends \PHPUnit_Framework_TestCase
{
/*    public function testDoNothingTest()
    {
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $foo = new ObjectModelSerializer($service, $request);
        $this->assertTrue(true);
    }*/
    private $mockRequest;
    public function Construct(){
        $AbsoluteServiceURL = new \POData\Common\Url("http://192.168.2.1/abm-master/public/odata.svc");
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $wrapper = m::mock(ProvidersWrapper::class)->makePartial();
        $this->mockRequest = $request;
        $serviceHost = m::mock(\POData\OperationContext\ServiceHost::class)->makePartial();
        $serviceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($AbsoluteServiceURL);
        $wrapper->shouldReceive('getResourceProperties')->andReturn([]);
        $service->shouldReceive('getHost')->andReturn($serviceHost);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $foo = new ObjectModelSerializer ($service, $request);
        return $foo;

    }
    public function testObjectModelSerializerBaseconstructor(){
        $foo = $this->Construct();
        $this->assertTrue(is_object($foo));

    }
    public function testwriteTopLevelElement(){
        $foo= $this->Construct();
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

 public function testwriteTopLevelElements(){
        $foo= $this->Construct();
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



$this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(1)",$ret->id);
$this->assertEquals("data", $ret->title);

$this->assertEquals("self", $ret->selfLink->name);
$this->assertEquals("data", $ret->selfLink->title);
$this->assertEquals("Entity", $ret->selfLink->url);


$this->assertEquals(2,count($ret->entries));

$this->assertTrue($ret->entries[0] instanceof \POData\ObjectModel\ODataEntry);
$this->assertTrue($ret->entries[1] instanceof \POData\ObjectModel\ODataEntry);


$this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='bilbo',type=2)", $ret->entries[0]->id);
$this->assertEquals("http://192.168.2.1/abm-master/public/odata.svc/Entity(name='dildo',type=3)", $ret->entries[1]->id);

$this->assertEquals("Entity(name='bilbo',type=2)", $ret->entries[0]->editLink);
$this->assertEquals("Entity(name='dildo',type=3)", $ret->entries[1]->editLink);

$this->assertTrue($ret->entries[0]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);
$this->assertTrue($ret->entries[1]->propertyContent instanceof \POData\ObjectModel\ODataPropertyContent);


    }



}

class reusableEntityClass4{
        public $name;
        public $type;
}

class reusableEntityClass5{
        private $name;
        private $type;
        public function __construct($n,$t){
            $this->name = $n;
            $this->type = $t;
        }
        public function __get($name){
            return $this->$name;
        }
}

class reusableEntityClass6{
        private $name;
        private $type;
        public function __construct($n,$t){
            $this->name = $n;
            $this->type = $t;
        }
}



