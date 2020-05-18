<?php

declare(strict_types=1);

namespace UnitTests\POData\Readers\Atom;

use AlgoWeb\ODataMetadata\MetadataManager;
use PHPUnit\Framework\TestCase;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\Readers\Atom\AtomODataReader;

/**
 * Class AtomODataReaderTest.
 * @package UnitTests\POData\Readers\Atom
 */
class AtomODataReaderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        // Seed MetadataManager mapping so to avoid blowups
        $mng = new MetadataManager();
    }

    public function testParse()
    {
        $xml    = '<?xml version="1.0" encoding="utf-8"?><feed xml:base="https://services.odata.org/V3/OData/OData.svc/" xmlns="http://www.w3.org/2005/Atom" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns:georss="http://www.georss.org/georss" xmlns:gml="http://www.opengis.net/gml"><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails</id><title type="text">ProductDetails</title><updated>2020-03-12T16:26:25Z</updated><link rel="self" title="ProductDetails" href="ProductDetails" /><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(1)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(1)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(1)/Product" /><title /><updated>2020-03-12T16:26:25Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(1)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">1</d:ProductID><d:Details>Details of product 1</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(3)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(3)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(3)/Product" /><title /><updated>2020-03-12T16:26:25Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(3)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">3</d:ProductID><d:Details>Details of product 3</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(4)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(4)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(4)/Product" /><title /><updated>2020-03-12T16:26:25Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(4)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">4</d:ProductID><d:Details>Details of product 4</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(8)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(8)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(8)/Product" /><title /><updated>2020-03-12T16:26:25Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(8)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">8</d:ProductID><d:Details>Details of product 8</d:Details></m:properties></content></entry></feed>';
        $reader = new AtomODataReader();
        $data   = $reader->read($xml);
        // Assert the Feed.
        $this->assertInstanceOf(ODataFeed::class, $data, 'The Xml should have created an odata feed');
        $this->assertEquals(
            'https://services.odata.org/V3/OData/OData.svc/ProductDetails',
            $data->id,
            'feed ID sailed to deserialize correctly'
        );
        $this->assertEquals('text', $data->title->getType(), 'Feed-Title-Type Attribute failed to deserialize correctly');
        $this->assertEquals('ProductDetails', $data->title->getTitle(), 'Feed-Title  failed to deserialize correctly');
        $this->assertEquals(
            '2020-03-12T16:26:25Z',
            $data->updated,
            'the feed updated value failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetails',
            $data->getSelfLink()->getTitle(),
            'the Feed Self Link Title Failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetails',
            $data->getSelfLink()->getUrl(),
            'the Feed Self Link href Failed to deserialise correctly'
        );
        $this->assertEquals('self', $data->getSelfLink()->getName(), 'the Feed Self Link Title Failed to deseralize correctly');
        $this->assertEquals(4, count($data->getEntries()), 'The Feed Deseralized the wrong number of entries');
        $this->assertInstanceOf(
            ODataEntry::class,
            $data->getEntries()[0],
            'an entry was deserialised as something not an odata entry'
        );
        $this->assertInstanceOf(
            ODataEntry::class,
            $data->getEntries()[1],
            'an entry was deserialised as something not an odata entry'
        );
        $this->assertInstanceOf(
            ODataEntry::class,
            $data->getEntries()[2],
            'an entry was deserialised as something not an odata entry'
        );
        $this->assertInstanceOf(
            ODataEntry::class,
            $data->getEntries()[3],
            'an entry was deserialised as something not an odata entry'
        );

        //Assert the first entry
        $entry = $data->getEntries()[0];
        $this->assertEquals(
            'https://services.odata.org/V3/OData/OData.svc/ProductDetails(1)',
            $entry->id,
            'the ID of the first entry deserialised incorrectly'
        );
        $this->assertEquals(
            'edit',
            $entry->editLink->getName(),
            'the name of the edit link failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetail',
            $entry->editLink->getTitle(),
            'The title of the edit link failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetails(1)',
            $entry->editLink->getUrl(),
            'The edit link url failed to deserialise correctly'
        );
        $this->assertEquals(
            'ODataDemo.ProductDetail',
            $entry->type->getTerm(),
            'The Type Term of the entry failed to deserialise correctly'
        );
        $this->assertEquals(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme',
            $entry->type->getScheme(),
            'The Type Scheme of the entry failed to deserialise correctly'
        );

        $this->assertEquals(2, count($entry->links), 'the entry deserialised the wrong number of links');

        if ($entry->links[0]->getName() === 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product') {
            /**
             * @var ODataLink $relatedLink
             */
            $relatedLink = $entry->links[0];
            /**
             * @var ODataLink $associatedLink
             */
            $associatedLink = $entry->links[1];
        }
        $this->assertInstanceOf(
            ODataLink::class,
            $relatedLink,
            'the related link on the entity failed to deserialise to the correct object'
        );
        $this->assertInstanceOf(
            ODataLink::class,
            $associatedLink,
            'the associated link on the entity failed to deserialise to the correct object'
        );

        $this->assertEquals(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product',
            $relatedLink->getName(),
            'The Name of the Related link failed to deserialise correctly'
        );
        $this->assertEquals(
            'Product',
            $relatedLink->getTitle(),
            'the title of the related link failed to deserialise correctly'
        );
        $this->assertEquals(
            'application/atom+xml;type=entry',
            $relatedLink->getType(),
            'the type of the related link failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetails(1)/Product',
            $relatedLink->getUrl(),
            'The url of the related link failed to deserialise correctly'
        );

        $this->assertEquals(
            'http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product',
            $associatedLink->getName(),
            'The Name of the associated link failed to deserialise correctly'
        );
        $this->assertEquals(
            'Product',
            $associatedLink->getTitle(),
            'the title of the associated link failed to deserialise correctly'
        );
        $this->assertEquals(
            'application/xml',
            $associatedLink->getType(),
            'the type of the associated link failed to deserialise correctly'
        );
        $this->assertEquals(
            'ProductDetails(1)/$links/Product',
            $associatedLink->getUrl(),
            'The url of the associated link failed to deserialise correctly'
        );

        $content = $entry->getAtomContent();

        $this->assertEquals(
            'application/xml',
            $content->type,
            'the type of the atom content failed to deserialise correctly'
        );

        $this->assertEquals(2, count($content->properties), 'the entity deserialised the wrong number of properties');
        /**
         * @var ODataProperty[] $properties;
         */
        $properties = $content->properties->getPropertys();
        $this->assertArrayHasKey('ProductID', $properties, 'The properties array failed to deserialise a correct key');
        $this->assertArrayHasKey('Details', $properties, 'The properties array failed to deserialise a correct key');

        $this->assertInstanceOf(
            ODataProperty::class,
            $properties['ProductID'],
            'the property ProductID deseralized to the wrong object type'
        );
        $this->assertInstanceOf(
            ODataProperty::class,
            $properties['Details'],
            'the property Details deseralized to the wrong object type'
        );

        $this->assertEquals(
            'ProductID',
            $properties['ProductID']->getName(),
            'the property ProductID Deseralized the wrong name'
        );
        $this->assertEquals(
            'Edm.Int32',
            $properties['ProductID']->typeName,
            'the property ProductID Deseralized the wrong TypeName'
        );
        $this->assertEquals(
            '1',
            $properties['ProductID']->value,
            'the property ProductID deserialised the wrong value'
        );

        $this->assertEquals(
            'Details',
            $properties['Details']->getName(),
            'the property Details deserialised the wrong name'
        );
        $this->assertEquals(
            null,
            $properties['Details']->typeName,
            'the property Details deserialised the wrong TypeName'
        );
        $this->assertEquals(
            'Details of product 1',
            $properties['Details']->value,
            'the property Details deserialised the wrong value'
        );
    }

    public function testParseWithExpandAndCount()
    {
        $xml    = '<?xml version="1.0" encoding="utf-8"?><feed xml:base="https://services.odata.org/V3/OData/OData.svc/" xmlns="http://www.w3.org/2005/Atom" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns:georss="http://www.georss.org/georss" xmlns:gml="http://www.opengis.net/gml"><m:count>4</m:count><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails</id><title type="text">ProductDetails</title><updated>2020-03-13T03:08:41Z</updated><link rel="self" title="ProductDetails" href="ProductDetails" /><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(1)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(1)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(1)/Product"><m:inline><entry><id>https://services.odata.org/V3/OData/OData.svc/Products(1)</id><category term="ODataDemo.Product" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="Product" href="Products(1)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Categories" type="application/atom+xml;type=feed" title="Categories" href="Products(1)/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Supplier" type="application/atom+xml;type=entry" title="Supplier" href="Products(1)/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/ProductDetail" type="application/atom+xml;type=entry" title="ProductDetail" href="Products(1)/ProductDetail" /><title type="text">Milk</title><summary type="text">Low fat milk</summary><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Categories" type="application/xml" title="Categories" href="Products(1)/$links/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Supplier" type="application/xml" title="Supplier" href="Products(1)/$links/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/ProductDetail" type="application/xml" title="ProductDetail" href="Products(1)/$links/ProductDetail" /><content type="application/xml"><m:properties><d:ID m:type="Edm.Int32">1</d:ID><d:ReleaseDate m:type="Edm.DateTime">1995-10-01T00:00:00</d:ReleaseDate><d:DiscontinuedDate m:null="true" /><d:Rating m:type="Edm.Int16">3</d:Rating><d:Price m:type="Edm.Double">3.5</d:Price></m:properties></content></entry></m:inline></link><title /><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(1)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">1</d:ProductID><d:Details>Details of product 1</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(3)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(3)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(3)/Product"><m:inline><entry><id>https://services.odata.org/V3/OData/OData.svc/Products(3)</id><category term="ODataDemo.Product" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="Product" href="Products(3)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Categories" type="application/atom+xml;type=feed" title="Categories" href="Products(3)/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Supplier" type="application/atom+xml;type=entry" title="Supplier" href="Products(3)/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/ProductDetail" type="application/atom+xml;type=entry" title="ProductDetail" href="Products(3)/ProductDetail" /><title type="text">Havina Cola</title><summary type="text">The Original Key Lime Cola</summary><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Categories" type="application/xml" title="Categories" href="Products(3)/$links/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Supplier" type="application/xml" title="Supplier" href="Products(3)/$links/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/ProductDetail" type="application/xml" title="ProductDetail" href="Products(3)/$links/ProductDetail" /><content type="application/xml"><m:properties><d:ID m:type="Edm.Int32">3</d:ID><d:ReleaseDate m:type="Edm.DateTime">2005-10-01T00:00:00</d:ReleaseDate><d:DiscontinuedDate m:type="Edm.DateTime">2006-10-01T00:00:00</d:DiscontinuedDate><d:Rating m:type="Edm.Int16">3</d:Rating><d:Price m:type="Edm.Double">19.9</d:Price></m:properties></content></entry></m:inline></link><title /><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(3)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">3</d:ProductID><d:Details>Details of product 3</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(4)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(4)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(4)/Product"><m:inline><entry><id>https://services.odata.org/V3/OData/OData.svc/Products(4)</id><category term="ODataDemo.Product" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="Product" href="Products(4)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Categories" type="application/atom+xml;type=feed" title="Categories" href="Products(4)/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Supplier" type="application/atom+xml;type=entry" title="Supplier" href="Products(4)/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/ProductDetail" type="application/atom+xml;type=entry" title="ProductDetail" href="Products(4)/ProductDetail" /><title type="text">Fruit Punch</title><summary type="text">Mango flavor, 8.3 Ounce Cans (Pack of 24)</summary><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Categories" type="application/xml" title="Categories" href="Products(4)/$links/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Supplier" type="application/xml" title="Supplier" href="Products(4)/$links/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/ProductDetail" type="application/xml" title="ProductDetail" href="Products(4)/$links/ProductDetail" /><content type="application/xml"><m:properties><d:ID m:type="Edm.Int32">4</d:ID><d:ReleaseDate m:type="Edm.DateTime">2003-01-05T00:00:00</d:ReleaseDate><d:DiscontinuedDate m:null="true" /><d:Rating m:type="Edm.Int16">3</d:Rating><d:Price m:type="Edm.Double">22.99</d:Price></m:properties></content></entry></m:inline></link><title /><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(4)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">4</d:ProductID><d:Details>Details of product 4</d:Details></m:properties></content></entry><entry><id>https://services.odata.org/V3/OData/OData.svc/ProductDetails(8)</id><category term="ODataDemo.ProductDetail" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="ProductDetail" href="ProductDetails(8)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Product" type="application/atom+xml;type=entry" title="Product" href="ProductDetails(8)/Product"><m:inline><entry><id>https://services.odata.org/V3/OData/OData.svc/Products(8)</id><category term="ODataDemo.Product" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme" /><link rel="edit" title="Product" href="Products(8)" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Categories" type="application/atom+xml;type=feed" title="Categories" href="Products(8)/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/Supplier" type="application/atom+xml;type=entry" title="Supplier" href="Products(8)/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/related/ProductDetail" type="application/atom+xml;type=entry" title="ProductDetail" href="Products(8)/ProductDetail" /><title type="text">LCD HDTV</title><summary type="text">42 inch 1080p LCD with Built-in Blu-ray Disc Player</summary><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Categories" type="application/xml" title="Categories" href="Products(8)/$links/Categories" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Supplier" type="application/xml" title="Supplier" href="Products(8)/$links/Supplier" /><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/ProductDetail" type="application/xml" title="ProductDetail" href="Products(8)/$links/ProductDetail" /><content type="application/xml"><m:properties><d:ID m:type="Edm.Int32">8</d:ID><d:ReleaseDate m:type="Edm.DateTime">2008-05-08T00:00:00</d:ReleaseDate><d:DiscontinuedDate m:null="true" /><d:Rating m:type="Edm.Int16">3</d:Rating><d:Price m:type="Edm.Double">1088.8</d:Price></m:properties></content></entry></m:inline></link><title /><updated>2020-03-13T03:08:41Z</updated><author><name /></author><link rel="http://schemas.microsoft.com/ado/2007/08/dataservices/relatedlinks/Product" type="application/xml" title="Product" href="ProductDetails(8)/$links/Product" /><content type="application/xml"><m:properties><d:ProductID m:type="Edm.Int32">8</d:ProductID><d:Details>Details of product 8</d:Details></m:properties></content></entry></feed>';
        $reader = new AtomODataReader();
        $data   = $reader->read($xml);
    }

    public function testDestructNotBoom()
    {
        $reader = new AtomODataReader();
        $reader->__destruct();
        $this->assertTrue(true);
    }

    public function testCharWithoutTagGoBoom()
    {
        $reader = new AtomODataReader();

        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('encountered character data outside of xml tag');

        $reader->characterData(null, 'anything');
    }

    public function testReadUnknownRootTagOpen()
    {
        $reader = new AtomODataReader();

        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('encountered node tag while not in a feed or a stack');

        $reader->tagOpen('namespace', 'tag', []);
    }

    public function testReadUnknownRootTagClose()
    {
        $reader = new AtomODataReader();

        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('encountered node %s while not in a feed or a stack');

        $reader->tagClose('namespace', 'tag');
    }
}
