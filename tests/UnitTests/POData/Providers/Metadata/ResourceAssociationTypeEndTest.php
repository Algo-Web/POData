<?php

namespace UnitTests\POData\Providers\Metadata;

use Mockery as m;
use POData\Providers\Metadata\ResourceAssociationTypeEnd;
use POData\Providers\Metadata\ResourceType;

class ResourceAssociationTypeEndTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithBothPropertiesNullThrowException()
    {
        $type = m::mock(ResourceType::class);

        $expected = 'Both to and from property argument to ResourceAssociationTypeEnd constructor cannot be null.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, null, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorFromPropertyBadTypeThrowException()
    {
        $type = m::mock(ResourceType::class);

        $expected = 'The argument \'$resourceProperty\' must be either null or instance of \'ResourceProperty\'.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, $type, null);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testConstructorToPropertyBadTypeThrowException()
    {
        $type = m::mock(ResourceType::class);

        $expected = 'The argument \'$fromProperty\' must be either null or instance of \'ResourceProperty\'.';
        $actual = null;

        try {
            new ResourceAssociationTypeEnd('name', $type, null, $type);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}