<?php

namespace UnitTests\POData\Providers\Metadata;

use \Mockery\Mockery;

use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\Configuration\ServiceConfiguration;
use POData\Configuration\EntitySetRights;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Common\ODataException;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\StringType;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceAssociationSetEnd;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\Providers\Query\QueryResult;

class ProvidersWrapperTestMockery extends \PHPUnit_Framework_TestCase
{
    public function testGetResourceSetsByMatchingName()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn("cheese");

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn("biscuits");

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets('cheese');

        $this->assertEquals(1, count($actual));
        $this->assertEquals('cheese', $actual[0]->getName());
    }

    public function testGetResourceSetsByOverlappingArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn("cheese");

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn("biscuits");

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['biscuits', 'tea']);

        $this->assertEquals(1, count($actual));
        $this->assertEquals('biscuits', $actual[0]->getName());
    }

    public function testGetResourceSetsByDisjointArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn("cheese");

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn("biscuits");

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['coffee', 'tea']);

        $this->assertEquals(0, count($actual));
    }

    public function testGetResourceSetsDefault()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn("cheese");

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn("biscuits");

        $targNames = ['cheese', 'biscuits'];

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(null);

        $this->assertEquals(2, count($actual));

        foreach ($actual as $result) {
            $resultName = $result->getName();
            $this->assertTrue(in_array($resultName, $targNames));
            $targNames = array_diff($targNames, [$resultName]);
        }
        $this->assertEquals(0, count($targNames));
    }

    public function testGetResourceSetsNonArrayNonStringNonNullInput()
    {
        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        $exceptionThrown = false;
        $expectedMessage = 'Input parameter must be absent, null, string or array';

        try {
            $foo->getResourceSets(new \StdClass());
        } catch (\ErrorException $e) {
            $exceptionThrown = ($expectedMessage == $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'Object input should have thrown error exception');
    }

    public static function mockProperty($object, $propertyName, $value)
    {
        $bar = new \ReflectionClass($object);
        $property = $bar->getProperty($propertyName);
        $oldAcc = $property->isPublic() ? true : false;

        $property->setAccessible(true);
        $property->setValue($object, $value);
        $property->setAccessible($oldAcc);
    }
}