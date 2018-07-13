<?php

namespace UnitTests\POData\ObjectModel;

use AlgoWeb\ODataMetadata\MetadataManager;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataPropertyContent;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\TypeCode;
use ReflectionClass;
use UnitTests\POData\TestCase;
use Mockery as m;

class ODataEntryTest extends TestCase
{
    public function testOkNoContent()
    {
        $foo = new ODataEntry();
        $expected = 'Property content must be instanceof ODataPropertyContent.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testOkEmptyContent()
    {
        $foo = new ODataEntry();
        $foo->propertyContent = new ODataPropertyContent();
        $expected = 'Must have at least one property present.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testAddODataCategoryWithCustomResourceSetName()
    {
        $typeClass = m::mock(ReflectionClass::class)->makePartial();
        $typeClass->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true);
        $typeClass->shouldReceive('isInstance')->andReturn(true);

        $foo = new SimpleMetadataProvider('string', 'String');
        $type = $foo->addEntityType($typeClass, 'Die', 'Dice', false, null);
        $foo->addKeyProperty($type, 'key', TypeCode::STRING);
        $set = $foo->addResourceSet('Dice', $type);
        $this->assertEquals('Dice', $set->getName());

        $setName = MetadataManager::getResourceSetNameFromResourceType('Die');
        $this->assertEquals('Dice', $setName);

        $category = new ODataCategory('App.Die');

        $entry = new ODataEntry();
        $entry->setType($category);
        $this->assertEquals('Dice', $entry->resourceSetName);
    }
}
