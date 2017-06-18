<?php

namespace UnitTests\POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Providers\Metadata\reusableEntityClass4;
use UnitTests\POData\Providers\Metadata\reusableEntityClass5;
use UnitTests\POData\TestCase;
use Mockery as m;

class SegmentParserMockeryTest extends TestCase
{
    private $metadataProvider;
    private $providersWrapper;
    private $serviceConfiguration;

    /** @var IQueryProvider */
    protected $mockQueryProvider;

    protected function commencePrimaryIgnition(IMetadataProvider $meta)
    {
        $this->metadataProvider = $meta;
        $this->serviceConfiguration = new ServiceConfiguration($this->metadataProvider);
        $this->serviceConfiguration->setEntitySetAccessRule('*', EntitySetRights::ALL);

        $this->mockQueryProvider = m::mock('POData\Providers\Query\IQueryProvider');

        $this->providersWrapper = new ProvidersWrapper(
            $this->metadataProvider,
            $this->mockQueryProvider,
            $this->serviceConfiguration,
            false
        );
    }

    public function testSingletonAsFirstSegment()
    {
        $functionName = [get_class($this), 'exampleSingleton'];
        $forward = new reusableEntityClass4('foo', 'bar');
        $refForward = new \ReflectionClass($forward);

        $foo = new SimpleMetadataProvider('string', 'number');
        $fore = $foo->addEntityType(new \ReflectionClass($refForward), 'fore');
        $foo->addResourceSet('foreSet', $fore);

        $name = "Foobar";

        $foo->createSingleton($name, $fore, $functionName);

        $this->commencePrimaryIgnition($foo);

        $segments = ['Foobar'];
        $result = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(1, count($result));
        $result = $result[0];
        $keyDesc = $result->getKeyDescriptor();
        $this->assertEquals('Foobar', $result->getIdentifier());
        $this->assertNull($keyDesc);
        $this->assertEquals(TargetKind::SINGLETON(), $result->getTargetKind());
        $this->assertEquals(TargetSource::ENTITY_SET, $result->getTargetSource());
        $this->assertTrue($result->isSingleResult());
        $wrapper = $result->getTargetResourceSetWrapper();
        $this->assertNotNull($wrapper);
        $this->assertTrue($wrapper instanceof ResourceSetWrapper);
    }

    public function testSingletonAsLaterSegment()
    {
        $expected = "Singleton must be first element";
        $actual = null;

        $functionName = [get_class($this), 'exampleSingleton'];
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');
        $refForward = new \ReflectionClass($forward);
        $refBack = new \ReflectionClass($back);

        $foo = NorthWindMetadata::Create();
        $fore = $foo->addEntityType(new \ReflectionClass($refForward), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass($refBack), 'back');
        $foo->addResourceSet('foreSet', $fore);
        $foo->addResourceSet('backSet', $aft);

        $name = "Foobar";

        $foo->createSingleton($name, $fore, $functionName);

        $this->commencePrimaryIgnition($foo);

        $segments = [
            "Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Foobar'
        ];

        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        } catch (ODataException $e) {
            //dd($e->getTraceAsString());
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public static function exampleSingleton()
    {
        return [];
    }
}
