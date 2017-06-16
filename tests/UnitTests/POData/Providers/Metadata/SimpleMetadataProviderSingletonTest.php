<?php

namespace UnitTests\POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\IsOK;
use AlgoWeb\ODataMetadata\MetadataV3\edm\EntityContainer\FunctionImportAnonymousType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TComplexTypeType;
use AlgoWeb\ODataMetadata\MetadataV3\edm\TEntityTypeType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use UnitTests\POData\TestCase;
use Mockery as m;

class SimpleMetadataProviderSingletonTest extends TestCase
{
    public function testCreateSingleton()
    {
        $functionName = [get_class($this), 'exampleSingleton'];
        $name = "name";
        $return = m::mock(TEntityTypeType::class);
        $return->shouldReceive('getName')->andReturn('name');

        $foo = new SimpleMetadataProvider('string', 'number');
        $foo->createSingleton($name, $return, $functionName);
        $result = $foo->getSingletons();
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
    }

    public function testCreateSingletonWithDuplicateName()
    {
        $functionName = [get_class($this), 'exampleSingleton'];
        $expected = "Singleton name already exists";
        $actual = null;

        $name = "name";
        $return = m::mock(TEntityTypeType::class);
        $return->shouldReceive('getName')->andReturn('name');

        $foo = new SimpleMetadataProvider('string', 'number');
        $foo->createSingleton($name, $return, $functionName);

        try {
            $foo->createSingleton($name, $return, $functionName);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCreateSingletonLiveWithComplexType()
    {
        $functionName = [get_class($this), 'exampleSingleton'];
        $name = 'example';
        $return = new TComplexTypeType();
        $return->setName('example');

        $foo = new SimpleMetadataProvider('string', 'number');
        $foo->createSingleton($name, $return, $functionName);
        $result = $foo->callSingleton('example');
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testCallNonExistentSingleton()
    {
        $expected = "Requested singleton does not exist";
        $actual = null;

        $foo = new SimpleMetadataProvider('string', 'number');
        $name = "Put 'Em High";

        try {
            $foo->callSingleton($name);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCallNonExistentSingletonWithArrayName()
    {
        $expected = "array_key_exists(): The first argument should be either a string or an integer";
        $actual = null;

        $foo = new SimpleMetadataProvider('string', 'number');
        $name = [];

        try {
            $foo->callSingleton($name);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testCallNonExistentSingletonWithObjectName()
    {
        $expected = "array_key_exists(): The first argument should be either a string or an integer";
        $actual = null;

        $foo = new SimpleMetadataProvider('string', 'number');
        $name = new \DateTime();

        try {
            $foo->callSingleton($name);
        } catch (\PHPUnit_Framework_Error_Warning $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }


    public static function exampleSingleton()
    {
        return [];
    }
}
