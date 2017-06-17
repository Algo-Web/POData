<?php

namespace UnitTests\POData\Providers\Metadata;

use POData\Configuration\IServiceConfiguration;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use UnitTests\POData\TestCase;
use Mockery as m;

class ProvidersWrapperMockeryTest extends TestCase
{
    public function testGetResourceSetsByMatchingName()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets('cheese');

        $this->assertEquals(1, count($actual));
        $this->assertEquals('cheese', $actual[0]->getName());
    }

    public function testGetResourceSetsByOverlappingArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['biscuits', 'tea']);

        $this->assertEquals(1, count($actual));
        $this->assertEquals('biscuits', $actual[0]->getName());
    }

    public function testGetResourceSetsByDisjointArray()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

        $foo = new SimpleMetadataProvider('foobar', 'barfoo');

        self::mockProperty($foo, 'resourceSets', [$resource1, $resource2]);

        $actual = $foo->getResourceSets(['coffee', 'tea']);

        $this->assertEquals(0, count($actual));
    }

    public function testGetResourceSetsDefault()
    {
        $resource1 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource1->shouldReceive('getName')->withNoArgs()->andReturn('cheese');

        $resource2 = \Mockery::mock('POData\Providers\Metadata\ResourceSet');
        $resource2->shouldReceive('getName')->withNoArgs()->andReturn('biscuits');

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

    public function testResolveNullSingleton()
    {
        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $this->assertNull($foo->resolveSingleton('singleton'));
    }

    public function testResolveNonNullSingleton()
    {
        $func = m::mock(ResourceFunctionType::class);
        $func->shouldReceive('getName')->andReturn('hammerTime')->once();

        $meta = m::mock(SimpleMetadataProvider::class)->makePartial();
        $meta->shouldReceive('getSingletons')->andReturn(['singleton' => $func]);
        $query = m::mock(IQueryProvider::class);
        $service = m::mock(IServiceConfiguration::class);

        $foo = new ProvidersWrapper($meta, $query, $service);
        $result = $foo->resolveSingleton('singleton');
        $this->assertTrue($result instanceof ResourceFunctionType);
        $this->assertEquals('hammerTime', $func->getName());
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
