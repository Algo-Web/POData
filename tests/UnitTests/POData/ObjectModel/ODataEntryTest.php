<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel;

use AlgoWeb\ODataMetadata\MetadataManager;
use Mockery as m;
use POData\Common\ODataConstants;
use POData\ObjectModel\AtomObjectModel\AtomAuthor;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\EdmPrimitiveType;
use POData\Providers\Metadata\Type\TypeCode;
use ReflectionClass;
use UnitTests\POData\TestCase;

class ODataEntryTest extends TestCase
{
    public function testOkNoContent()
    {
        $foo      = new ODataEntry();
        $expected = 'Property content must be instanceof ODataPropertyContent.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testOkEmptyContent()
    {
        $foo                  = new ODataEntry();
        $foo->propertyContent = new ODataPropertyContent();
        $expected             = 'Must have at least one property present.';

        $actual = null;
        $foo->isOK($actual);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \POData\Common\InvalidOperationException
     * @throws \ReflectionException
     */
    public function testAddODataCategoryWithCustomResourceSetName()
    {
        $typeClass = m::mock(ReflectionClass::class)->makePartial();
        $typeClass->shouldReceive('hasMethod')->withArgs(['__get'])->andReturn(true);
        $typeClass->shouldReceive('isInstance')->andReturn(true);

        $foo  = new SimpleMetadataProvider('string', 'String');
        $type = $foo->addEntityType($typeClass, 'Die', 'Dice', false, null);
        $foo->addKeyProperty($type, 'key', EdmPrimitiveType::STRING());
        $set = $foo->addResourceSet('Dice', $type);
        $this->assertEquals('Dice', $set->getName());

        $setName = MetadataManager::getResourceSetNameFromResourceType('Die');
        $this->assertEquals('Dice', $setName);

        $category = new ODataCategory('App.Die');

        $entry = new ODataEntry();
        $entry->setType($category);
        $this->assertEquals('Dice', $entry->resourceSetName);
    }

    public function testGetAtomContentOfBlankEntry()
    {
        $foo = new ODataEntry();

        $res = $foo->getAtomContent();
        $this->assertEquals('application/xml', $res->type);
        $this->assertNull($res->src);
        $this->assertNull($res->properties);
    }

    public function testGetAtomContentOfMediaLinkEntry()
    {
        $link1 = new ODataMediaLink('edit', null, null, 'Bitz', '', 'edit');

        $foo                   = new ODataEntry();
        $foo->isMediaLinkEntry = true;
        $foo->mediaLink        = $link1;

        $res = $foo->getAtomContent();
        $this->assertEquals('Bitz', $res->type);
    }

    public function testGetMediaLink()
    {
        $link       = m::mock(ODataMediaLink::class)->makePartial();
        $link->name = 'The Launch';

        $foo            = new ODataEntry();
        $foo->mediaLink = $link;

        $result = $foo->getMediaLink();
        $this->assertTrue($result instanceof ODataMediaLink);
        $this->assertEquals('The Launch', $result->name);
    }

    public function testGetSetLinksBlankArrayRoundTrip()
    {
        $foo = new ODataEntry();

        $foo->setLinks([]);

        $expected = [];
        $actual   = $foo->getLinks();

        $this->assertEquals($expected, $actual);
    }

    public function testSetTwoEditLinksLastInBestDressed()
    {
        $link1 = new ODataLink('edit', null, null, 'Bitz');
        $link2 = new ODataLink('edit', null, null, 'Piecez');

        $foo = new ODataEntry();

        $foo->setLinks([$link1, $link2]);

        $editLink = $foo->getEditLink();
        $this->assertEquals('Piecez', $editLink->getUrl());
        $this->assertEquals('Piecez', $foo->resourceSetName);
    }

    public function testSetMediaLinkArray()
    {
        $link1 = new ODataLink('http://schemas.microsoft.com/ado/2007/08/dataservices/related', null, null, 'Bitz');

        $foo = new ODataEntry();

        $foo->setLinks([$link1]);

        $this->assertEquals(1, count($foo->getLinks()));
    }

    public function testSetEmptyMediaLinkArray()
    {
        $foo = new ODataEntry();

        $foo->setMediaLinks([]);

        $this->assertFalse($foo->isMediaLinkEntry);
    }

    public function testFeedInThreeMediaLinks()
    {
        $link1 = new ODataMediaLink('edit', null, null, 'Bitz', '', 'edit');
        $link2 = new ODataMediaLink('edit-media', null, null, 'Bitz', '', 'edit-media');
        $link3 = new ODataMediaLink('name', null, null, '', '', ODataConstants::ATOM_MEDIA_RESOURCE_RELATION_ATTRIBUTE_VALUE);

        $links = [$link1, $link2, $link3];

        $foo = new ODataEntry();
        $foo->setMediaLinks($links);

        $mediaLink = $foo->getMediaLink();
        $this->assertTrue($mediaLink instanceof ODataMediaLink);
        $this->assertEquals('edit-media', $mediaLink->name);

        $mediaLinks = $foo->getMediaLinks();
        $this->assertEquals(1, count($mediaLinks));
        $first = $mediaLinks[0];
        $this->assertEquals('name', $first->name);
        $this->assertEquals('/name', $first->srcLink);
    }

    public function testGetContentWhenNotMediaLinkEntry()
    {
        $foo = new ODataEntry();

        $this->assertNull($foo->getPropertyContent());
    }

    public function testGetContentWhenIsMediaLinkEntry()
    {
        $content = new ODataPropertyContent();

        $foo = new ODataEntry();
        $foo->setPropertyContent($content);
        $foo->isMediaLinkEntry = true;

        $result = $foo->getPropertyContent();
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertEquals(0, count($result->getPropertys()));
    }

    public function testIsOkWhenMPropertyContentNotEmpty()
    {
        $property = new ODataProperty();

        $content               = new ODataPropertyContent();
        $content->properties[] = $property;

        $foo = new ODataEntry();
        $foo->setPropertyContent($content);
        $foo->isMediaLinkEntry = true;

        $result = $foo->getPropertyContent();
        $this->assertTrue($result instanceof ODataPropertyContent);
        $this->assertEquals(1, count($result->getPropertys()));
        $this->assertTrue($foo->isOk());
    }

    public function testGetAtomAuthor()
    {
        $foo = new ODataEntry();

        $result = $foo->getAtomAuthor();

        $this->assertTrue($result instanceof AtomAuthor);
        $this->assertEquals('', $result->name);
    }

    public function testGetType()
    {
        $foo = new ODataEntry();

        $this->assertNull($foo->getType());
    }
}
