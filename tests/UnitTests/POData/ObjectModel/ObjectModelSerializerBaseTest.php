<?php

namespace UnitTests\POData\ObjectModel;

use POData\Common\ODataConstants;
use POData\IService;
use POData\ObjectModel\ObjectModelSerializerBase;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Common\Messages;
use Mockery as m;

class ObjectModelSerializerBaseTest extends \PHPUnit_Framework_TestCase
{
/*    public function testDoNothing()
    {
//        $service = m::mock(IService::class);
//        $request = m::mock(RequestDescription::class)->makePartial();
//        $foo = new ObjectModelSerializerDummy($service, $request);
        $this->assertTrue(true);
    }*/

    public function Construct(){
	$AbsoluteServiceURL = new \POData\Common\Url("http://192.168.2.1/abm-master/public/odata.svc");
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $serviceHost = m::mock(\POData\OperationContext\ServiceHost::class)->makePartial();
        $serviceHost->shouldReceive('getAbsoluteServiceUri')->andReturn($AbsoluteServiceURL);
        $service->shouldReceive('getHost')->andReturn($serviceHost);
        $foo = new ObjectModelSerializerDummy($service, $request);
	return $foo;
        
    }
    public function testObjectModelSerializerBaseconstructor(){
        $foo = $this->Construct();
        $this->assertTrue(is_object($foo));

    }
    public function testGetEntryInstanceKey(){
        $resourceType = m::mock(ResourceType::class)->makePartial();

        $resourceProperty = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty->shouldReceive('getName')->andReturn("name");
        $resourceProperty->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\EdmString());

        $resourceProperty2 = m::mock(\POData\Providers\Metadata\ResourceProperty::class)->makePartial();
        $resourceProperty2->shouldReceive('getName')->andReturn("type");
        $resourceProperty2->shouldReceive('getInstanceType')->andReturn(new \POData\Providers\Metadata\Type\Int32());

	$keysProperty = array("name" => $resourceProperty, "type"=>$resourceProperty2);
        $resourceType->shouldReceive('getKeyProperties')->andReturn($keysProperty);

        $foo = $this->Construct();
        $entity = new reUsableentityClass1();
	$entity->name = "bilbo";
        $entity->type = 2;
        $ret = $foo->getEntryInstanceKey($entity,$resourceType,"Data");
        $this->assertEquals("Data(name='bilbo',type=2)", $ret);

    }
}


class reusableEntityClass1{
	public $name;
	public $type;
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

     public function getEntryInstanceKey($entityInstance, ResourceType $resourceType, $containerName){
         return parent::getEntryInstanceKey($entityInstance, $resourceType, $containerName);
     }

}
