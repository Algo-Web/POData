<?php

namespace UnitTests\POData\Providers\Metadata;

use AlgoWeb\ODataMetadata\MetadataManager;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JMS\Serializer\Serializer;
use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceAssociationSet;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\TypeCode;
use ReflectionClass;
use ReflectionException;
use UnitTests\POData\ObjectModel\reusableEntityClass1;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class SimpleMetadataProviderTest extends TestCase
{
    public function testGetContainerNameAndNamespace()
    {
        $foo = new SimpleMetadataProvider('string', 'number');
        $this->assertEquals('string', $foo->getContainerName());
        $this->assertEquals('number', $foo->getContainerNamespace());
    }

    public function testAddResourceSetThenGoAroundAgainAndThrowException()
    {
        $foo = new SimpleMetadataProvider('string', 'String');
        $name = 'Customers';
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getFullName')->andReturn('Customer')->twice();
        $type->shouldReceive('setCustomState')->andReturnNull()->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('validateType')->andReturnNull()->once();

        $result = $foo->addResourceSet($name, $type);
        $this->assertEquals($name, $result->getName());

        $expected = 'Resource Set already added';
        $actual = null;

        try {
            $foo->addResourceSet($name, $type);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetsNotArrayThrowException()
    {
        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Input parameter must be absent, null, string or array';
        $actual = null;

        try {
            $foo->getResourceSets(new \StdClass());
        } catch (\ErrorException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceSetsOnlyOneExists()
    {
        $foo = new SimpleMetadataProvider('string', 'String');
        $name = 'Customers';
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getFullName')->andReturn('Customer')->once();
        $type->shouldReceive('setCustomState')->andReturnNull()->once();
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('validateType')->andReturnNull()->once();

        $foo->addResourceSet($name, $type);

        $parms = ['Hammer', 'Time', 'Customers'];
        $result = $foo->getResourceSets($parms);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($name, $result[0]->getName());
    }

    public function testGetResourceSetsByStringNoneExist()
    {
        $foo = new SimpleMetadataProvider('string', 'String');
        $parms = 'Hammer';
        $result = $foo->getResourceSets($parms);
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testAddResourceSetWithoutExplicitName()
    {
        $type = m::mock(ResourceEntityType::class)->makePartial();
        $type->shouldReceive('getFullName')->andReturn('Supplier');

        $foo = new SimpleMetadataProvider('string', 'String');
        $foo->addResourceSet(null, $type);

        $result = $foo->resolveResourceSet('Suppliers');
        $this->assertTrue($result instanceof ResourceSet);
        $this->assertEquals('Supplier', $result->getResourceType()->getFullName());
    }

    public function testAddResourceSetContainingTypeName()
    {
        $type = m::mock(ResourceEntityType::class)->makePartial();
        $type->shouldReceive('getFullName')->andReturn('Supplier');

        $foo = new SimpleMetadataProvider('string', 'String');
        $foo->addResourceSet('App\Models\Supplier', $type);

        $result = $foo->resolveResourceSet('Suppliers');
        $this->assertTrue($result instanceof ResourceSet);
        $this->assertEquals('Supplier', $result->getResourceType()->getFullName());
    }

    public function testAddResourceSetWithCustomSetName()
    {
        // as in one six-sided die, two six-sided dice
        $type = m::mock(ResourceEntityType::class)->makePartial();
        $type->shouldReceive('getFullName')->andReturn('Die');

        $foo = new SimpleMetadataProvider('string', 'String');
        $foo->addResourceSet('Dice', $type);

        $result = $foo->resolveResourceSet('Dice');
        $this->assertTrue($result instanceof ResourceSet);
        $this->assertEquals('Die', $result->getResourceType()->getFullName());
    }

    public function testGetTypesOnEmpty()
    {
        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->getTypes();
        $this->assertTrue(is_array($result));
        $this->assertEquals(0, count($result));
    }

    public function testResolveResourceTypeOnEmpty()
    {
        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->resolveResourceType('Hammer');
        $this->assertNull($result);
    }

    public function testResolveResourceSetOnEmpty()
    {
        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->resolveResourceSet('Hammer');
        $this->assertNull($result);
    }

    public function testResolveAssociationSetOnEmpty()
    {
        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->resolveAssociationSet('Hammer');
        $this->assertNull($result);
    }

    public function testHasDerivedTypes()
    {
        $type = m::mock(ResourceEntityType::class);
        $this->assertTrue($type instanceof ResourceType);

        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->hasDerivedTypes($type);
        $this->assertFalse($result);
    }

    public function testGetResourceAssociationSetCustomStateNullThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceEntityType::class);
        $targType = m::mock(ResourceEntityType::class);
        $targType->shouldReceive('getCustomState')->andReturnNull()->once();
        $targType->shouldReceive('getName')->andReturn('Hammer');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getResourceType')->andReturn($targType);

        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Failed to retrieve the custom state from Hammer';
        $actual = null;

        try {
            $foo->getResourceAssociationSet($set, $type, $property);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetResourceAssociationSetWhenEmpty()
    {
        $set = m::mock(ResourceSet::class);
        $targSet = m::mock(ResourceSet::class);
        $targSet->shouldReceive('getResourceType->getName')->andReturn('M.C.');
        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getName')->andReturn('Hawking');
        $targType = m::mock(ResourceType::class);
        $targType->shouldReceive('getCustomState')->andReturn($targSet)->once();
        $targType->shouldReceive('getName')->andReturn('Hammer');
        $property = m::mock(ResourceProperty::class);
        $property->shouldReceive('getResourceType')->andReturn($targType);
        $property->shouldReceive('getName')->andReturn('Hammer');

        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->getResourceAssociationSet($set, $type, $property);
        $this->assertNull($result);
    }

    public function testAddResourceTypeThenGoAroundAgainAndThrowException()
    {
        $set = m::mock(ResourceSet::class);
        $type = m::mock(ResourceType::class);
        $property = m::mock(ResourceProperty::class);

        $orig = new reusableEntityClass2('foo', 'bar');
        $entity = new \ReflectionClass($orig);

        $foo = new SimpleMetadataProvider('string', 'String');

        $result = $foo->addEntityType($entity, 'Hammer');
        $this->assertTrue($result instanceof ResourceType);
        $this->assertEquals('Hammer', $result->getName());

        $expected = 'Type with same name already added';
        $actual = null;

        try {
            $foo->addEntityType($entity, 'Hammer');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddComplexPropertyBadEntityTypeThrowException()
    {
        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $complexType = m::mock(ResourceComplexType::class);
        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Complex property can be added to an entity or another complex type';
        $actual = null;

        try {
            $foo->addComplexProperty($type, 'Time', $complexType);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddComplexPropertyWithMissingPropertyTypeThowException()
    {
        $deflect = m::mock(ReflectionClass::class);
        $deflect->shouldReceive('isInstance')->andReturn(false);
        $deflect->shouldReceive('getProperty')->andThrow(new ReflectionException('OH NOES!'));
        $deflect->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(false)->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('getInstanceType')->andReturn($deflect);
        $type->shouldReceive('getName')->andReturn('outaTime');

        $complexType = m::mock(ResourceComplexType::class);
        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Can\'t add a property which does not exist on the instance type.';
        $actual = null;

        try {
            $foo->addComplexProperty($type, 'Time', $complexType);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddComplexPropertyWithResourceNameCollision()
    {
        $deflect = m::mock(ReflectionClass::class);
        $deflect->shouldReceive('getProperty')->andThrow(new ReflectionException('OH NOES!'));
        $deflect->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true)->never();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('getInstanceType')->andReturn($deflect);
        $type->shouldReceive('getName')->andReturn('time');

        $complexType = m::mock(ResourceComplexType::class);
        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Property name must be different from resource name.';
        $actual = null;

        try {
            $foo->addComplexProperty($type, 'time', $complexType);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    public function testAddKeyPropertyWithMagicGetterMissingPropertyThrowsInvalidArgException()
    {
        $orig = new reusableEntityClass2('foo', 'bar');
        $entity = new \ReflectionClass($orig);

        $foo = new SimpleMetadataProvider('string', 'String');

        $keyName = 'id';
        $complex = $foo->addEntityType(new \ReflectionClass(get_class($orig)), 'table');

        $expected = 'The argument \'$typeCode\' to getPrimitiveResourceType is not' .
            ' a valid EdmPrimitiveType Enum value.';
        $actual = null;

        try {
            $foo->addKeyProperty($complex, $keyName, EdmPrimitiveType::OBJECT());
        } catch (InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddKeyPropertyWithoutMagicGetterMissingPropertyThrowsInvalidOpException()
    {
        $orig = new reusableEntityClass1('foo', 'bar');
        $entity = new \ReflectionClass($orig);

        $foo = new SimpleMetadataProvider('string', 'String');

        $keyName = 'id';
        $complex = $foo->addEntityType(new \ReflectionClass(get_class($orig)), 'table');

        $expected = 'Can\'t add a property which does not exist on the instance type.';
        $actual = null;

        try {
            $foo->addKeyProperty($complex, $keyName, EdmPrimitiveType::OBJECT());
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddPrimitivePropertyWithNameCollision()
    {
        $deflect = m::mock(ReflectionClass::class);
        $deflect->shouldReceive('isInstance')->andReturn(false);
        $deflect->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true)->once();

        $type = m::mock(ResourceType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('getInstanceType')->andReturn($deflect);
        $type->shouldReceive('getName')->andReturn('time');

        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Property name must be different from resource name.';
        $actual = null;

        try {
            $foo->addPrimitiveProperty($type, 'time', EdmPrimitiveType::OBJECT());
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceNameCollision()
    {
        $deflect = m::mock(ReflectionClass::class);
        $deflect->shouldReceive('isInstance')->andReturn(false);
        $deflect->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true)->once();

        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('getInstanceType')->andReturn($deflect);
        $type->shouldReceive('getName')->andReturn('time');

        $resourceSet = m::mock(ResourceSet::class);

        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Property name must be different from resource name.';
        $actual = null;

        try {
            $foo->addResourceReferenceProperty($type, 'time', $resourceSet);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceBadCustomState()
    {
        $deflect = m::mock(ReflectionClass::class);
        $deflect->shouldReceive('isInstance')->andReturn(false);
        $deflect->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true)->once();

        $type = m::mock(ResourceEntityType::class);
        $type->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $type->shouldReceive('getInstanceType')->andReturn($deflect);
        $type->shouldReceive('getName')->andReturn('time');
        $type->shouldReceive('addProperty')->andReturn(null)->once();
        $type->shouldReceive('getCustomState')->andReturn(null)->once();

        $resourceSet = m::mock(ResourceSet::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($type)->once();

        $foo = new SimpleMetadataProvider('string', 'String');

        $expected = 'Failed to retrieve the custom state from time';
        $actual = null;

        try {
            $foo->addResourceReferenceProperty($type, 'date', $resourceSet);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceCheckSane()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceReferenceProperty($fore, 'relation', $aftSet);
        $foo->addResourceReferenceProperty($aft, 'backRelation', $foreSet);

        // now dig out expected results
        $firstExpectedKey = 'fore_relation_aft';
        $secondExpectedKey = 'aft_backRelation_fore';

        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $this->assertNotNull($result, 'First association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($firstExpectedKey, $result->getName());
        $result = $foo->resolveAssociationSet($secondExpectedKey);
        $this->assertNotNull($result, 'Second association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($secondExpectedKey, $result->getName());
    }

    public function testAddResourceReferenceBidirectionalBadPropertyNames()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Source and target properties must both be strings';
        $actual = null;

        try {
            $foo->addResourceReferencePropertyBidirectional($fore, $aft, null, null);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceSetReferenceBidirectionalFirstBadPropertyName()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Source and target properties must both be strings';
        $actual = null;

        try {
            $foo->addResourceSetReferencePropertyBidirectional($fore, $aft, null, 'property');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceSingleBidirectionalSecondBadPropertyName()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Source and target properties must both be strings';
        $actual = null;

        try {
            $foo->addResourceReferenceSinglePropertyBidirectional($fore, $aft, 'property', null);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceBidirectionalFirstPropertyNameCollision()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Source property name must be different from source resource name.';
        $actual = null;

        try {
            $foo->addResourceReferencePropertyBidirectional($fore, $aft, $fore->getName(), 'property');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceBidirectionalSecondPropertyNameCollision()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Target property name must be different from target resource name.';
        $actual = null;

        try {
            $foo->addResourceReferencePropertyBidirectional($fore, $aft, 'property', $aft->getName());
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceSetReferenceBidirectionalFirstNoCustomStage()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $expected = 'Failed to retrieve the custom state from fore';
        $actual = null;

        try {
            $foo->addResourceSetReferencePropertyBidirectional($fore, $aft, 'property', 'property');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceSetReferenceBidirectionalSecondNoCustomStage()
    {
        $forwardSet = m::mock(ResourceSet::class);

        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);
        $fore->setCustomState($forwardSet);

        $expected = 'Failed to retrieve the custom state from aft';
        $actual = null;

        try {
            $foo->addResourceSetReferencePropertyBidirectional($fore, $aft, 'property', 'property');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAddResourceReferenceBidirectionalCheckSane()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceEntityType);
        $this->assertTrue($aft instanceof ResourceEntityType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceReferencePropertyBidirectional($fore, $aft, 'relation', 'backRelation');

        // now dig out expected results
        $firstExpectedKey = 'fore_relation_aft';
        $secondExpectedKey = 'aft_backRelation_fore';

        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $this->assertNotNull($result, 'First association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($firstExpectedKey, $result->getName());
        $result = $foo->resolveAssociationSet($secondExpectedKey);
        $this->assertNotNull($result, 'Second association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($secondExpectedKey, $result->getName());

        // now dig out ends and check resource property types
        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $end1 = $result->getEnd1();
        $property = $end1->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCE_REFERENCE, $property);
        $end2 = $result->getEnd2();
        $property = $end2->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCESET_REFERENCE, $property);

        // fianlly, to check multiplicities, dig out from metadata manager
        $assoc = $foo->getMetadataManager()->getEdmx()->getDataServiceType()->getSchema()[0]->getAssociation();
        $this->assertEquals(1, count($assoc));
        $assoc = $assoc[0];
        $ends = $assoc->getEnd();
        $this->assertEquals('*', $ends[0]->getMultiplicity());
        $this->assertEquals('1', $ends[1]->getMultiplicity());
    }

    public function testAddResourceReferenceBidirectionalCheckSaneWhenParentNullable()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceEntityType);
        $this->assertTrue($aft instanceof ResourceEntityType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceReferencePropertyBidirectional($fore, $aft, 'relation', 'backRelation', true);

        // check multiplicities - dig out from metadata manager
        $assoc = $foo->getMetadataManager()->getEdmx()->getDataServiceType()->getSchema()[0]->getAssociation();
        $this->assertEquals(1, count($assoc));
        $assoc = $assoc[0];
        $ends = $assoc->getEnd();
        $this->assertEquals('*', $ends[0]->getMultiplicity());
        $this->assertEquals('0..1', $ends[1]->getMultiplicity());
    }

    public function testAddResourceSetReferenceBidirectionalCheckSane()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceSetReferencePropertyBidirectional($fore, $aft, 'relation', 'backRelation');

        // now dig out expected results
        $firstExpectedKey = 'fore_relation_aft';
        $secondExpectedKey = 'aft_backRelation_fore';

        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $this->assertNotNull($result, 'First association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($firstExpectedKey, $result->getName());
        $result = $foo->resolveAssociationSet($secondExpectedKey);
        $this->assertNotNull($result, 'Second association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($secondExpectedKey, $result->getName());

        // now dig out ends and check resource property types
        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $end1 = $result->getEnd1();
        $property = $end1->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCESET_REFERENCE, $property);
        $end2 = $result->getEnd2();
        $property = $end2->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCESET_REFERENCE, $property);
    }

    public function testAddResourceReferenceSingleBidirectionalCheckSane()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceReferenceSinglePropertyBidirectional($fore, $aft, 'relation', 'backRelation');

        // now dig out expected results
        $firstExpectedKey = 'fore_relation_aft';
        $secondExpectedKey = 'aft_backRelation_fore';

        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $this->assertNotNull($result, 'First association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($firstExpectedKey, $result->getName());
        $result = $foo->resolveAssociationSet($secondExpectedKey);
        $this->assertNotNull($result, 'Second association set is null');
        $this->assertTrue($result instanceof ResourceAssociationSet, get_class($result));
        $this->assertEquals($secondExpectedKey, $result->getName());

        // now dig out ends and check resource property types
        $result = $foo->resolveAssociationSet($firstExpectedKey);
        $end1 = $result->getEnd1();
        $property = $end1->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCE_REFERENCE, $property);
        $end2 = $result->getEnd2();
        $property = $end2->getResourceProperty()->getKind();
        $this->assertEquals(ResourcePropertyKind::RESOURCE_REFERENCE, $property);
    }

    public function testAddResourceReferenceSingleBidirectionalForwardAndReverse()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $this->assertEquals(0, $foo->getAssociationCount());
        $foo->addResourceReferenceSinglePropertyBidirectional($fore, $aft, 'relation', 'backRelation');
        $this->assertEquals(2, $foo->getAssociationCount());
        $foo->addResourceReferenceSinglePropertyBidirectional($aft, $fore, 'backRelation', 'relation');
        $this->assertEquals(2, $foo->getAssociationCount());
    }

    public function testAddEntityTypeAbstractTest()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore', null, true);
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft', null, false, $fore);
        $this->assertTrue($fore->isAbstract());
        $this->assertFalse($aft->isAbstract());
        $this->assertEquals($fore, $aft->getBaseType());
        $this->assertTrue($foo->hasDerivedTypes($fore));
        $this->assertEquals([$aft], $foo->getDerivedTypes($fore));
    }

    public function testAddResourceSetReferenceWithOtherEndSingle()
    {
        $forward = new reusableEntityClass4('foo', 'bar');
        $back = new reusableEntityClass5('foo', 'bar');

        $foo = new SimpleMetadataProvider('string', 'String');

        $fore = $foo->addEntityType(new \ReflectionClass(get_class($forward)), 'fore');
        $aft = $foo->addEntityType(new \ReflectionClass(get_class($back)), 'aft');
        $foo->addKeyProperty($fore, 'key', EdmPrimitiveType::INT32());
        $foo->addKeyProperty($aft, 'key', EdmPrimitiveType::INT32());
        $this->assertTrue($fore instanceof ResourceType);
        $this->assertTrue($aft instanceof ResourceType);

        $foreSet = $foo->addResourceSet('foreSet', $fore);
        $aftSet = $foo->addResourceSet('aftSet', $aft);
        $this->assertTrue($foreSet instanceof ResourceSet);
        $this->assertTrue($aftSet instanceof ResourceSet);

        $foo->addResourceSetReferenceProperty($fore, 'fore_aft', $aftSet, null, true);
        $xml = $foo->getXML();
        $assocName = '<Association Name="fore_fore_aft_aft">';
        $fwdEnd = '<End Type="String.fore" Role="fores_fore_aft" Multiplicity="*"/>';
        $revEnd = '<End Type="String.aft" Role="afts" Multiplicity="1"/>';
        $navProp = '<NavigationProperty Name="fore_aft" Relationship="String.fore_fore_aft_aft" ToRole="afts" '
                   .'FromRole="fores_fore_aft" cg:GetterAccess="Public" cg:SetterAccess="Public"/>';

        $this->assertTrue(false !== strpos($xml, $assocName));
        $this->assertTrue(false !== strpos($xml, $fwdEnd));
        $this->assertTrue(false !== strpos($xml, $revEnd));
        $this->assertTrue(false !== strpos($xml, $navProp));
    }

    public function testGetXML()
    {
        $cereal = m::mock(Serializer::class);
        $meta = m::mock(MetadataManager::class)->makePartial();
        $meta->shouldReceive('getEdmxXML')->andReturn($cereal);

        $foo = m::mock(SimpleMetadataProvider::class)->makePartial();
        $foo->shouldReceive('getMetadataManager')->andReturn($meta);
        $result = $foo->getXML();
        $this->assertTrue($result instanceof Serializer);
    }

    public function testBagAndEtagException()
    {
        $foo = new SimpleMetadataProvider('string', 'String');
        $reflector = new \ReflectionObject($foo);
        $method = $reflector->getMethod('addPrimitivePropertyInternal');
        $method->setAccessible(true);
        try {
            $method->invoke($foo, null, null, null, true, true, true);
            $this->fail('expected exception not fired');
        } catch (InvalidOperationException $e) {
            $this->assertEquals(
                'Only primitive property can be etag property, bag property cannot be etag property.',
                $e->getMessage()
            );
        }
    }
}


class reusableEntityClass4
{
    private $name;
    private $type;
    private $relation;

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

class reusableEntityClass5
{
    private $name;
    private $type;
    private $backRelation;

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
