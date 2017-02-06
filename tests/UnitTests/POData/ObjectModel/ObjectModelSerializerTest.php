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



