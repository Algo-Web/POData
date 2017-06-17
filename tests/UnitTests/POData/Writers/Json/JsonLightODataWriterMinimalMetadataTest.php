<?php

namespace UnitTests\POData\Writers\Json;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\Version;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\ProvidersWrapper;
use POData\Writers\Json\JsonLightMetadataLevel;
use POData\Writers\Json\JsonLightODataWriter;
use UnitTests\POData\TestCase;

class JsonLightODataWriterMinimalMetadataTest extends TestCase
{
    protected $serviceBase = 'http://services.odata.org/OData/OData.svc';

    public function setUp()
    {
        $this->mockProvider = m::mock(ProvidersWrapper::class)->makePartial();
    }

    public function testWriteURL()
    {
        $this->markTestSkipped("see #80 ODataURL doesn't have enough context to get the meta data return result");

        //IE: http://services.odata.org/v3/OData/OData.svc/Products(0)/$links/Supplier?$format=application/json;odata=minimalmetadata

        $oDataUrl = new ODataURL();
        $oDataUrl->url = 'http://services.odata.org/OData/OData.svc/Suppliers(0)';
        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataUrl);
        $this->assertSame($writer, $result);

        //decoding the json string to test, there is no json string comparison in php unit
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metdata" : "http://services.odata.org/OData/OData.svc/$metadata#Products/$links/Supplier",
						"url": "http://services.odata.org/OData/OData.svc/Suppliers(0)"
					}';
        $expected = json_decode($expected);
        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteURLCollection()
    {
        $this->markTestSkipped("see #80 ODataURL doesn't have enough context to get the meta data return result");
        // see http://services.odata.org/v3/OData/OData.svc/Categories(1)/$links/Products?$format=application/json;odata=minimalmetadata

        $oDataUrlCollection = new ODataURLCollection();
        $oDataUrl1 = new ODataURL();
        $oDataUrl1->url = 'http://services.odata.org/OData/OData.svc/Products(0)';
        $oDataUrl2 = new ODataURL();
        $oDataUrl2->url = 'http://services.odata.org/OData/OData.svc/Products(7)';
        $oDataUrl3 = new ODataURL();
        $oDataUrl3->url = 'http://services.odata.org/OData/OData.svc/Products(8)';
        $oDataUrlCollection->urls = [$oDataUrl1,
                                               $oDataUrl2,
                                               $oDataUrl3,
                                              ];

        $oDataUrlCollection->count = null; //simulate no $inlinecount
        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataUrlCollection);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metdata" : "http://services.odata.org/OData/OData.svc/$metadata#Products/$links/Products",
		                "value" : [
							{
						        "url": "http://services.odata.org/OData/OData.svc/Products(0)"
							},
						    {
						        "url": "http://services.odata.org/OData/OData.svc/Products(7)"
						    },
						    {
						        "url": "http://services.odata.org/OData/OData.svc/Products(8)"
						    }
						]
					}';

        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());

        $oDataUrlCollection->count = 44; //simulate an $inlinecount
        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataUrlCollection);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
		                "odata.count" : "44",
		                "odata.metdata" : "http://services.odata.org/OData/OData.svc/$metadata#Products/$links/Products",
		                "value" : [
							{
						        "url": "http://services.odata.org/OData/OData.svc/Products(0)"
							},
						    {
						        "url": "http://services.odata.org/OData/OData.svc/Products(7)"
						    },
						    {
						        "url": "http://services.odata.org/OData/OData.svc/Products(8)"
						    }
						]
					}';

        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteFeed()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Categories(0)/Products?$top=2&$format=application/json;odata=minimalmetadata

        //entry1
        $entry1 = new ODataEntry();
        $entry1->id = 'http://services.odata.org/OData/OData.svc/Products(0)';
        $entry1->selfLink = 'entry1 self link';
        $entry1->title = 'title of entry 1';
        $entry1->editLink = 'edit link of entry 1';
        $entry1->type = 'DataServiceProviderDemo.Product';
        $entry1->eTag = '';

        //entry 1 property content
        $entry1Prop1 = new ODataProperty();
        $entry1Prop1->name = 'ID';
        $entry1Prop1->typeName = 'Edm.Int16';
        $entry1Prop1->value = (string) 100;

        $entry1Prop2 = new ODataProperty();
        $entry1Prop2->name = 'Name';
        $entry1Prop2->typeName = 'Edm.String';
        $entry1Prop2->value = 'Bread';

        $entry1Prop3 = new ODataProperty();
        $entry1Prop3->name = 'ReleaseDate';
        $entry1Prop3->typeName = 'Edm.DateTime';
        $entry1Prop3->value = '2012-09-17T14:17:13';

        $entry1Prop4 = new ODataProperty();
        $entry1Prop4->name = 'DiscontinuedDate';
        $entry1Prop4->typeName = 'Edm.DateTime';
        $entry1Prop4->value = null;

        $entry1Prop5 = new ODataProperty();
        $entry1Prop5->name = 'Price';
        $entry1Prop5->typeName = 'Edm.Double';
        $entry1Prop5->value = 2.5;

        $entry1PropContent = new ODataPropertyContent();
        $entry1PropContent->properties = [
            $entry1Prop1,
            $entry1Prop2,
            $entry1Prop3,
            $entry1Prop4,
            $entry1Prop5,
        ];
        //entry 1 property content end

        $entry1->propertyContent = $entry1PropContent;

        $entry1->isExpanded = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links NOTE minimalmetadata means this won't be output
        //link1
        $link1 = new ODataLink();
        $link1->name = 'http://services.odata.org/OData/OData.svc/Products(0)/Categories';
        $link1->title = 'Categories';
        $link1->url = 'http://services.odata.org/OData/OData.svc/Products(0)/Categories';

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end

        $oDataFeed = new ODataFeed();
        $oDataFeed->id = 'FEED ID';
        $oDataFeed->title = 'FEED TITLE';
        //self link
        $selfLink = new ODataLink();
        $selfLink->name = 'Products';
        $selfLink->title = 'Products';
        $selfLink->url = 'Categories(0)/Products';
        $oDataFeed->selfLink = $selfLink;
        //self link end
        $oDataFeed->entries = [$entry1];

        //next page link: NOTE minimalmetadata means this won't be output
        $nextPageLink = new ODataLink();
        $nextPageLink->name = 'Next Page Link';
        $nextPageLink->title = 'Next Page';
        $nextPageLink->url = 'http://services.odata.org/OData/OData.svc$skiptoken=12';
        $oDataFeed->nextPageLink = $nextPageLink;
        //feed entries

        //Note that even if the top limits the collection the count should not be output unless inline count is specified
        //IE: http://services.odata.org/v3/OData/OData.svc/Categories?$top=1&$inlinecount=allpages&$format=application/json;odata=minimalmetadata
        //The feed count will be null unless inlinecount is specified

        $oDataFeed->rowCount = null;

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());
        $expected = '{
					    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "value" : [
				            {
				                "ID": 100,
				                "Name": "Bread",
				                "ReleaseDate" : "/Date(1347891433000)/",
				                "DiscontinuedDate" : null,
				                "Price" : 2.5
				            }
				        ]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());

        //Now we'll simulate an $inlinecount=allpages by specifying a count
        $oDataFeed->rowCount = 33;

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //TODO: v3 specifies that the count must be before value..how can we test this well?
        //decoding the json string to test
        $actual = json_decode($writer->getOutput());
        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
						"odata.count":"33",
					    "value" : [
				            {
				                "ID": 100,
				                "Name": "Bread",
				                "ReleaseDate" : "/Date(1347891433000)/",
				                "DiscontinuedDate" : null,
				                "Price" : 2.5
				            }
				        ]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteFeedWithEntriesWithComplexProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers?$top=2&$format=application/json;odata=minimalmetadata
        // suppliers have address as a complex property

        //entry1
        $entry1 = new ODataEntry();
        $entry1->id = 'http://services.odata.org/OData/OData.svc/Suppliers(0)';
        $entry1->selfLink = 'entry1 self link';
        $entry1->title = 'title of entry 1';
        $entry1->editLink = 'edit link of entry 1';
        $entry1->type = 'ODataDemo.Supplier';
        $entry1->eTag = 'W/"0"';
        //entry 1 property content
        $entry1PropContent = new ODataPropertyContent();

        $entry1Prop1 = new ODataProperty();
        $entry1Prop1->name = 'ID';
        $entry1Prop1->typeName = 'Edm.Int16';
        $entry1Prop1->value = (string) 0;

        $entry1Prop2 = new ODataProperty();
        $entry1Prop2->name = 'Name';
        $entry1Prop2->typeName = 'Edm.String';
        $entry1Prop2->value = 'Exotic Liquids';
        //complex type
        $compForEntry1Prop3 = new ODataPropertyContent();

        $compForEntry1Prop3Prop1 = new ODataProperty();
        $compForEntry1Prop3Prop1->name = 'Street';
        $compForEntry1Prop3Prop1->typeName = 'Edm.String';
        $compForEntry1Prop3Prop1->value = 'NE 228th';

        $compForEntry1Prop3Prop2 = new ODataProperty();
        $compForEntry1Prop3Prop2->name = 'City';
        $compForEntry1Prop3Prop2->typeName = 'Edm.String';
        $compForEntry1Prop3Prop2->value = 'Sammamish';

        $compForEntry1Prop3Prop3 = new ODataProperty();
        $compForEntry1Prop3Prop3->name = 'State';
        $compForEntry1Prop3Prop3->typeName = 'Edm.String';
        $compForEntry1Prop3Prop3->value = 'WA';

        $compForEntry1Prop3Prop4 = new ODataProperty();
        $compForEntry1Prop3Prop4->name = 'ZipCode';
        $compForEntry1Prop3Prop4->typeName = 'Edm.String';
        $compForEntry1Prop3Prop4->value = '98074';

        $compForEntry1Prop3Prop5 = new ODataProperty();
        $compForEntry1Prop3Prop5->name = 'Country';
        $compForEntry1Prop3Prop5->typeName = 'Edm.String';
        $compForEntry1Prop3Prop5->value = 'USA';

        $compForEntry1Prop3->properties = [$compForEntry1Prop3Prop1,
                                                   $compForEntry1Prop3Prop2,
                                                   $compForEntry1Prop3Prop3,
                                                   $compForEntry1Prop3Prop4,
                                                   $compForEntry1Prop3Prop5, ];

        $entry1Prop3 = new ODataProperty();
        $entry1Prop3->name = 'Address';
        $entry1Prop3->typeName = 'ODataDemo.Address';
        $entry1Prop3->value = $compForEntry1Prop3;

        $entry1Prop4 = new ODataProperty();
        $entry1Prop4->name = 'Concurrency';
        $entry1Prop4->typeName = 'Edm.Int16';
        $entry1Prop4->value = (string) 0;

        $entry1PropContent->properties = [$entry1Prop1, $entry1Prop2, $entry1Prop3, $entry1Prop4];
        //entry 1 property content end

        $entry1->propertyContent = $entry1PropContent;

        $entry1->isExpanded = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links
        //link1
        $link1 = new ODataLink();
        $link1->name = 'Products';
        $link1->title = 'Products';
        $link1->url = 'http://services.odata.org/OData/OData.svc/Suppliers(0)/Products';

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end

        //entry 2
        $entry2 = new ODataEntry();
        $entry2->id = 'http://services.odata.org/OData/OData.svc/Suppliers(1)';
        $entry2->selfLink = 'entry2 self link';
        $entry2->title = 'title of entry 2';
        $entry2->editLink = 'edit link of entry 2';
        $entry2->type = 'ODataDemo.Supplier';
        $entry2->eTag = 'W/"0"';
        //entry 2 property content
        $entry2PropContent = new ODataPropertyContent();

        $entry2Prop1 = new ODataProperty();
        $entry2Prop1->name = 'ID';
        $entry2Prop1->typeName = 'Edm.Int16';
        $entry2Prop1->value = 1;

        $entry2Prop2 = new ODataProperty();
        $entry2Prop2->name = 'Name';
        $entry2Prop2->typeName = 'Edm.String';
        $entry2Prop2->value = 'Tokyo Traders';
        //complex type
        $compForEntry2Prop3 = new ODataPropertyContent();

        $compForEntry2Prop3Prop1 = new ODataProperty();
        $compForEntry2Prop3Prop1->name = 'Street';
        $compForEntry2Prop3Prop1->typeName = 'Edm.String';
        $compForEntry2Prop3Prop1->value = 'NE 40th';

        $compForEntry2Prop3Prop2 = new ODataProperty();
        $compForEntry2Prop3Prop2->name = 'City';
        $compForEntry2Prop3Prop2->typeName = 'Edm.String';
        $compForEntry2Prop3Prop2->value = 'Redmond';

        $compForEntry2Prop3Prop3 = new ODataProperty();
        $compForEntry2Prop3Prop3->name = 'State';
        $compForEntry2Prop3Prop3->typeName = 'Edm.String';
        $compForEntry2Prop3Prop3->value = 'WA';

        $compForEntry2Prop3Prop4 = new ODataProperty();
        $compForEntry2Prop3Prop4->name = 'ZipCode';
        $compForEntry2Prop3Prop4->typeName = 'Edm.String';
        $compForEntry2Prop3Prop4->value = '98052';

        $compForEntry2Prop3Prop5 = new ODataProperty();
        $compForEntry2Prop3Prop5->name = 'Country';
        $compForEntry2Prop3Prop5->typeName = 'Edm.String';
        $compForEntry2Prop3Prop5->value = 'USA';

        $compForEntry2Prop3->properties = [$compForEntry2Prop3Prop1,
                                                   $compForEntry2Prop3Prop2,
                                                   $compForEntry2Prop3Prop3,
                                                   $compForEntry2Prop3Prop4,
                                                   $compForEntry2Prop3Prop5, ];

        $entry2Prop3 = new ODataProperty();
        $entry2Prop3->name = 'Address';
        $entry2Prop3->typeName = 'ODataDemo.Address';
        $entry2Prop3->value = $compForEntry2Prop3;

        $entry2Prop4 = new ODataProperty();
        $entry2Prop4->name = 'Concurrency';
        $entry2Prop4->typeName = 'Edm.Int16';
        $entry2Prop4->value = (string) 0;

        $entry2PropContent->properties = [$entry2Prop1, $entry2Prop2, $entry2Prop3, $entry2Prop4];
        //entry 2 property content end

        $entry2->propertyContent = $entry2PropContent;

        $entry2->isExpanded = false;
        $entry2->isMediaLinkEntry = false;

        //entry 2 links
        //link1
        $link1 = new ODataLink();
        $link1->name = 'Products';
        $link1->title = 'Products';
        $link1->url = 'http://services.odata.org/OData/OData.svc/Suppliers(1)/Products';

        $entry2->links = [$link1];
        //entry 2 links end

        //entry 2 end

        $oDataFeed = new ODataFeed();
        $oDataFeed->id = 'FEED ID';
        $oDataFeed->title = 'FEED TITLE';
        //self link
        $selfLink = new ODataLink();
        $selfLink->name = 'Products';
        $selfLink->title = 'Products';
        $selfLink->url = 'Categories(0)/Products';
        $oDataFeed->selfLink = $selfLink;
        //self link end

        //next page
        $nextPageLink = new ODataLink();
        $nextPageLink->name = 'Next Page Link';
        $nextPageLink->title = 'Next Page';
        $nextPageLink->url = 'http://services.odata.org/OData/OData.svc$skiptoken=12';
        $oDataFeed->nextPageLink = $nextPageLink;
        //feed entries

        $oDataFeed->entries = [$entry1, $entry2];

        $oDataFeed->rowCount = null; //simulate no inline count

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());
        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "value": [
							{
								"ID": 0,
								"Name": "Exotic Liquids",
								"Address": {
									"Street": "NE 228th",
									 "City": "Sammamish",
									 "State": "WA",
									 "ZipCode": "98074",
									 "Country": "USA"
								},
								"Concurrency": 0
							},
							{
								"ID": 1,
								"Name": "Tokyo Traders",
								"Address": {
									"Street": "NE 40th",
									"City": "Redmond",
									"State": "WA",
									"ZipCode": "98052",
									"Country": "USA"
								},
								"Concurrency": 0
							}
						]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());

        $oDataFeed->rowCount = 55; //simulate  $inlinecount=allpages

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //TODO: spec says count must be before! need to verify positioning in the test somehow
        //decoding the json string to test
        $actual = json_decode($writer->getOutput());
        $expected = '{
					    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "odata.count":"55",
					    "value": [
							{
								"ID": 0,
								"Name": "Exotic Liquids",
								"Address": {
									"Street": "NE 228th",
									 "City": "Sammamish",
									 "State": "WA",
									 "ZipCode": "98074",
									 "Country": "USA"
								},
								"Concurrency": 0
							},
							{
								"ID": 1,
								"Name": "Tokyo Traders",
								"Address": {
									"Street": "NE 40th",
									"City": "Redmond",
									"State": "WA",
									"ZipCode": "98052",
									"Country": "USA"
								},
								"Concurrency": 0
							}
						]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteEntry()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)?$format=application/json;odata=minimalmetadata

        //entry
        $entry = new ODataEntry();
        $entry->id = 'http://services.odata.org/OData/OData.svc/Categories(0)';
        $entry->selfLink = 'entry2 self link';
        $entry->title = 'title of entry 2';
        $entry->editLink = 'edit link of entry 2';
        $entry->type = 'ODataDemo.Category';
        $entry->eTag = '';
        $entry->resourceSetName = 'resource set name';

        $entryPropContent = new ODataPropertyContent();
        //entry property
        $entryProp1 = new ODataProperty();
        $entryProp1->name = 'ID';
        $entryProp1->typeName = 'Edm.Int16';
        $entryProp1->value = (string) 0;

        $entryProp2 = new ODataProperty();
        $entryProp2->name = 'Name';
        $entryProp2->typeName = 'Edm.String';
        $entryProp2->value = 'Food';

        $entryPropContent->properties = [$entryProp1, $entryProp2];

        $entry->propertyContent = $entryPropContent;

        //links
        $link = new ODataLink();
        $link->name = 'Products';
        $link->title = 'Products';
        $link->url = 'http://services.odata.org/OData/OData.svc/Categories(0)/Products';

        $entry->links = [$link];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#resource set name/@Element",
						"ID": 0,
						"Name": "Food"
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteComplexProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)/Address?$format=application/json;odata=minimalmetadata

        $propContent = new ODataPropertyContent();

        //property
        $compProp = new ODataPropertyContent();

        $compProp1 = new ODataProperty();
        $compProp1->name = 'Street';
        $compProp1->typeName = 'Edm.String';
        $compProp1->value = 'NE 228th';

        $compProp2 = new ODataProperty();
        $compProp2->name = 'City';
        $compProp2->typeName = 'Edm.String';
        $compProp2->value = 'Sammamish';

        $compProp3 = new ODataProperty();
        $compProp3->name = 'State';
        $compProp3->typeName = 'Edm.String';
        $compProp3->value = 'WA';

        $compProp4 = new ODataProperty();
        $compProp4->name = 'ZipCode';
        $compProp4->typeName = 'Edm.String';
        $compProp4->value = '98074';

        $compProp5 = new ODataProperty();
        $compProp5->name = 'Country';
        $compProp5->typeName = 'Edm.String';
        $compProp5->value = 'USA';

        $compProp->properties = [$compProp1,
                                         $compProp2,
                                         $compProp3,
                                         $compProp4,
                                         $compProp5, ];

        $prop1 = new ODataProperty();
        $prop1->name = 'Address';
        $prop1->typeName = 'ODataDemo.Address';
        $prop1->value = $compProp;

        $propContent->properties = [$prop1];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($propContent);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#ODataDemo.Address",
						"Street": "NE 228th",
						"City": "Sammamish",
						"State": "WA",
						"ZipCode": "98074",
						"Country": "USA"
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testEntryWithBagProperty()
    {
        //Intro to bags: http://www.odata.org/2010/09/adding-support-for-bags/
        //TODO: bags were renamed to collection in v3 see https://github.com/balihoo/POData/issues/79
        //see http://docs.oasis-open.org/odata/odata-json-format/v4.0/cs01/odata-json-format-v4.0-cs01.html#_Toc365464701
        //can't find a Collection type in online demo

        //entry
        $entry = new ODataEntry();
        $entry->id = 'http://host/service.svc/Customers(1)';
        $entry->selfLink = 'entry2 self link';
        $entry->title = 'title of entry 2';
        $entry->editLink = 'edit link of entry 2';
        $entry->type = 'SampleModel.Customer';
        $entry->eTag = '';
        $entry->resourceSetName = 'resource set name';

        $entryPropContent = new ODataPropertyContent();
        //entry property
        $entryProp1 = new ODataProperty();
        $entryProp1->name = 'ID';
        $entryProp1->typeName = 'Edm.Int16';
        $entryProp1->value = 1;

        $entryProp2 = new ODataProperty();
        $entryProp2->name = 'Name';
        $entryProp2->typeName = 'Edm.String';
        $entryProp2->value = 'mike';

        //property 3 starts
        //bag property for property 3
        $bagEntryProp3 = new ODataBagContent();

        $bagEntryProp3->propertyContents = [
                                      'mike@foo.com',
                                      'mike2@foo.com', ];
        $bagEntryProp3->type = 'Bag(Edm.String)'; //TODO: this might not be what really happens in the code..#61

        $entryProp3 = new ODataProperty();
        $entryProp3->name = 'EmailAddresses';
        $entryProp3->typeName = 'Bag(Edm.String)';
        $entryProp3->value = $bagEntryProp3;
        //property 3 ends

        //property 4 starts
        $bagEntryProp4 = new ODataBagContent();

        //property content for bagEntryProp4ContentProp1
        $bagEntryProp4ContentProp1Content = new ODataPropertyContent();

        $bagEntryProp4ContentProp1ContentProp1 = new ODataProperty();
        $bagEntryProp4ContentProp1ContentProp1->name = 'Street';
        $bagEntryProp4ContentProp1ContentProp1->typeName = 'Edm.String';
        $bagEntryProp4ContentProp1ContentProp1->value = '123 contoso street';

        $bagEntryProp4ContentProp1ContentProp2 = new ODataProperty();
        $bagEntryProp4ContentProp1ContentProp2->name = 'Apartment';
        $bagEntryProp4ContentProp1ContentProp2->typeName = 'Edm.String';
        $bagEntryProp4ContentProp1ContentProp2->value = '508';

        $bagEntryProp4ContentProp1Content->properties = [$bagEntryProp4ContentProp1ContentProp1,
                                                                 $bagEntryProp4ContentProp1ContentProp2, ];

        //end property content for bagEntryProp4ContentProp1

        //property content2 for bagEntryProp4ContentProp1
        $bagEntryProp4ContentProp1Content2 = new ODataPropertyContent();

        $bagEntryProp4ContentProp1Content2Prop1 = new ODataProperty();
        $bagEntryProp4ContentProp1Content2Prop1->name = 'Street';
        $bagEntryProp4ContentProp1Content2Prop1->typeName = 'Edm.String';
        $bagEntryProp4ContentProp1Content2Prop1->value = '834 foo street';

        $bagEntryProp4ContentProp1Content2Prop2 = new ODataProperty();
        $bagEntryProp4ContentProp1Content2Prop2->name = 'Apartment';
        $bagEntryProp4ContentProp1Content2Prop2->typeName = 'Edm.String';
        $bagEntryProp4ContentProp1Content2Prop2->value = '102';

        $bagEntryProp4ContentProp1Content2->properties = [$bagEntryProp4ContentProp1Content2Prop1,
                                                                 $bagEntryProp4ContentProp1Content2Prop2, ];

        //end property content for bagEntryProp4ContentProp1

        $bagEntryProp4->propertyContents = [$bagEntryProp4ContentProp1Content,
                                                 $bagEntryProp4ContentProp1Content2,
                                                ];
        $bagEntryProp4->type = 'Bag(SampleModel.Address)'; //TODO: this might not be what really happens in the code..#61

        $entryProp4 = new ODataProperty();
        $entryProp4->name = 'Addresses';
        $entryProp4->typeName = 'Bag(SampleModel.Address)';
        $entryProp4->value = $bagEntryProp4;
        //property 4 ends

        $entryPropContent->properties = [$entryProp1, $entryProp2, $entryProp3, $entryProp4];

        $entry->propertyContent = $entryPropContent;

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#resource set name/@Element",
						"ID": 1,
						"Name": "mike",
						"EmailAddresses": [
							"mike@foo.com", "mike2@foo.com"
				        ],
			            "Addresses": [
		                    {
		                        "Street": "123 contoso street",
		                        "Apartment": "508"
		                    },
		                    {
		                        "Street": "834 foo street",
		                        "Apartment": "102"
		                    }
		                ]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testPrimitiveProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)/Address/City?$format=application/json;odata=minimalmetadata
        $property = new ODataProperty();
        $property->name = 'Count';
        $property->typeName = 'Edm.Int16';
        $property->value = 56;

        $content = new ODataPropertyContent();
        $content->properties = [$property];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($content);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	                    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#Edm.Int16",
	                    "value" :  56
	                 }';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntry()
    {
        //First build up the expanded entry
        $expandedEntry = new ODataEntry();
        $expandedEntry->id = 'Expanded Entry 1';
        $expandedEntry->title = 'Expanded Entry Title';
        $expandedEntry->type = 'Expanded.Type';
        $expandedEntry->editLink = 'Edit Link URL';
        $expandedEntry->selfLink = 'Self Link URL';

        $expandedEntry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry->links = [];
        $expandedEntry->eTag = 'Entry ETag';
        $expandedEntry->isMediaLinkEntry = false;

        $pr1 = new ODataProperty();
        $pr1->name = 'fname';
        $pr1->typeName = 'string';
        $pr1->value = 'Yash';

        $pr2 = new ODataProperty();
        $pr2->name = 'lname';
        $pr2->typeName = 'string';
        $pr2->value = 'Kothari';

        $propCon1 = new ODataPropertyContent();
        $propCon1->properties = [$pr1, $pr2];

        $expandedEntryComplexProperty = new ODataProperty();
        $expandedEntryComplexProperty->name = 'Expanded Entry Complex Property';
        $expandedEntryComplexProperty->typeName = 'Full Name';
        $expandedEntryComplexProperty->value = $propCon1;

        $expandedEntryProperty1 = new ODataProperty();
        $expandedEntryProperty1->name = 'Expanded Entry City Property';
        $expandedEntryProperty1->typeName = 'string';
        $expandedEntryProperty1->value = 'Ahmedabad';

        $expandedEntryProperty2 = new ODataProperty();
        $expandedEntryProperty2->name = 'Expanded Entry State Property';
        $expandedEntryProperty2->typeName = 'string';
        $expandedEntryProperty2->value = 'Gujarat';

        $expandedEntry->propertyContent = new ODataPropertyContent();
        $expandedEntry->propertyContent->properties = [
            $expandedEntryComplexProperty,
            $expandedEntryProperty1,
            $expandedEntryProperty2,
        ];
        //End the expanded entry

        //build up the main entry

        $entry = new ODataEntry();
        $entry->id = 'Main Entry';
        $entry->title = 'Entry Title';
        $entry->type = 'Main.Type';
        $entry->editLink = 'Edit Link URL';
        $entry->selfLink = 'Self Link URL';
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag = 'Entry ETag';
        $entry->isMediaLinkEntry = false;

        $entryProperty1 = new ODataProperty();
        $entryProperty1->name = 'Main Entry Property 1';
        $entryProperty1->typeName = 'string';
        $entryProperty1->value = 'Yash';

        $entryProperty2 = new ODataProperty();
        $entryProperty2->name = 'Main Entry Property 2';
        $entryProperty2->typeName = 'string';
        $entryProperty2->value = 'Kothari';

        $entry->propertyContent = new ODataPropertyContent();
        $entry->propertyContent->properties = [$entryProperty1, $entryProperty2];
        //End of main entry

        //Now link the expanded entry to the main entry
        $expandLink = new ODataLink();
        $expandLink->isCollection = false;
        $expandLink->isExpanded = true;
        $expandLink->title = 'Expanded Property';
        $expandLink->url = 'ExpandedURL';
        $expandLink->expandedResult = $expandedEntry;
        $entry->links = [$expandLink];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
    "Expanded Property":{
        "Expanded Entry Complex Property":{
            "fname":"Yash",
            "lname":"Kothari"
        },
        "Expanded Entry City Property":"Ahmedabad",
        "Expanded Entry State Property":"Gujarat"
    },
    "Main Entry Property 1":"Yash",
    "Main Entry Property 2":"Kothari"
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntryThatIsNull()
    {

        //build up the main entry

        $entry = new ODataEntry();
        $entry->id = 'Main Entry';
        $entry->title = 'Entry Title';
        $entry->type = 'Main.Type';
        $entry->editLink = 'Edit Link URL';
        $entry->selfLink = 'Self Link URL';
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag = 'Entry ETag';
        $entry->isMediaLinkEntry = false;

        $entryProperty1 = new ODataProperty();
        $entryProperty1->name = 'Main Entry Property 1';
        $entryProperty1->typeName = 'string';
        $entryProperty1->value = 'Yash';

        $entryProperty2 = new ODataProperty();
        $entryProperty2->name = 'Main Entry Property 2';
        $entryProperty2->typeName = 'string';
        $entryProperty2->value = 'Kothari';

        $entry->propertyContent = new ODataPropertyContent();
        $entry->propertyContent->properties = [$entryProperty1, $entryProperty2];
        //End of main entry

        //Now link the expanded entry to the main entry
        $expandLink = new ODataLink();
        $expandLink->isCollection = false;
        $expandLink->isExpanded = true;
        $expandLink->title = 'Expanded Property';
        $expandLink->url = 'ExpandedURL';
        $expandLink->expandedResult = null; //<--key part
        $entry->links = [$expandLink];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
    "Expanded Property":null,
    "Main Entry Property 1":"Yash",
    "Main Entry Property 2":"Kothari"
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    public function testWriteEntryWithExpandedFeed()
    {
        //First build up the expanded entry 1
        $expandedEntry1 = new ODataEntry();
        $expandedEntry1->id = 'Expanded Entry 1';
        $expandedEntry1->title = 'Expanded Entry 1 Title';
        $expandedEntry1->type = 'Expanded.Type';
        $expandedEntry1->editLink = 'Edit Link URL';
        $expandedEntry1->selfLink = 'Self Link URL';

        $expandedEntry1->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry1->links = [];
        $expandedEntry1->eTag = 'Entry ETag';
        $expandedEntry1->isMediaLinkEntry = false;

        $pr1 = new ODataProperty();
        $pr1->name = 'first';
        $pr1->typeName = 'string';
        $pr1->value = 'Entry 1 Name First';

        $pr2 = new ODataProperty();
        $pr2->name = 'last';
        $pr2->typeName = 'string';
        $pr2->value = 'Entry 1 Name Last';

        $expandedEntry1ComplexProperty = new ODataProperty();
        $expandedEntry1ComplexProperty->name = 'Expanded Entry Complex Property';
        $expandedEntry1ComplexProperty->typeName = 'Full Name';
        $expandedEntry1ComplexProperty->value = new ODataPropertyContent();
        $expandedEntry1ComplexProperty->value->properties = [$pr1, $pr2];

        $expandedEntry1Property1 = new ODataProperty();
        $expandedEntry1Property1->name = 'Expanded Entry City Property';
        $expandedEntry1Property1->typeName = 'string';
        $expandedEntry1Property1->value = 'Entry 1 City Value';

        $expandedEntry1Property2 = new ODataProperty();
        $expandedEntry1Property2->name = 'Expanded Entry State Property';
        $expandedEntry1Property2->typeName = 'string';
        $expandedEntry1Property2->value = 'Entry 1 State Value';

        $expandedEntry1->propertyContent = new ODataPropertyContent();
        $expandedEntry1->propertyContent->properties = [
            $expandedEntry1ComplexProperty,
            $expandedEntry1Property1,
            $expandedEntry1Property2,
        ];
        //End the expanded entry 1

        //First build up the expanded entry 2
        $expandedEntry2 = new ODataEntry();
        $expandedEntry2->id = 'Expanded Entry 2';
        $expandedEntry2->title = 'Expanded Entry 2 Title';
        $expandedEntry2->type = 'Expanded.Type';
        $expandedEntry2->editLink = 'Edit Link URL';
        $expandedEntry2->selfLink = 'Self Link URL';

        $expandedEntry2->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry2->links = [];
        $expandedEntry2->eTag = 'Entry ETag';
        $expandedEntry2->isMediaLinkEntry = false;

        $pr1 = new ODataProperty();
        $pr1->name = 'first';
        $pr1->typeName = 'string';
        $pr1->value = 'Entry 2 Name First';

        $pr2 = new ODataProperty();
        $pr2->name = 'last';
        $pr2->typeName = 'string';
        $pr2->value = 'Entry 2 Name Last';

        $expandedEntry2ComplexProperty = new ODataProperty();
        $expandedEntry2ComplexProperty->name = 'Expanded Entry Complex Property';
        $expandedEntry2ComplexProperty->typeName = 'Full Name';
        $expandedEntry2ComplexProperty->value = new ODataPropertyContent();
        $expandedEntry2ComplexProperty->value->properties = [$pr1, $pr2];

        $expandedEntry2Property1 = new ODataProperty();
        $expandedEntry2Property1->name = 'Expanded Entry City Property';
        $expandedEntry2Property1->typeName = 'string';
        $expandedEntry2Property1->value = 'Entry 2 City Value';

        $expandedEntry2Property2 = new ODataProperty();
        $expandedEntry2Property2->name = 'Expanded Entry State Property';
        $expandedEntry2Property2->typeName = 'string';
        $expandedEntry2Property2->value = 'Entry 2 State Value';

        $expandedEntry2->propertyContent = new ODataPropertyContent();
        $expandedEntry2->propertyContent->properties = [
            $expandedEntry2ComplexProperty,
            $expandedEntry2Property1,
            $expandedEntry2Property2,
        ];
        //End the expanded entry 2

        //build up the main entry

        $entry = new ODataEntry();
        $entry->id = 'Main Entry';
        $entry->title = 'Entry Title';
        $entry->type = 'Main.Type';
        $entry->editLink = 'Edit Link URL';
        $entry->selfLink = 'Self Link URL';
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag = 'Entry ETag';
        $entry->isMediaLinkEntry = false;

        $entryProperty1 = new ODataProperty();
        $entryProperty1->name = 'Main Entry Property 1';
        $entryProperty1->typeName = 'string';
        $entryProperty1->value = 'Yash';

        $entryProperty2 = new ODataProperty();
        $entryProperty2->name = 'Main Entry Property 2';
        $entryProperty2->typeName = 'string';
        $entryProperty2->value = 'Kothari';

        $entry->propertyContent = new ODataPropertyContent();
        $entry->propertyContent->properties = [$entryProperty1, $entryProperty2];
        //End of main entry

        //Create a the expanded feed
        $expandedFeed = new ODataFeed();
        $expandedFeed->id = 'expanded feed id';
        $expandedFeed->title = 'SubCollection';
        $expandedFeed->entries = [$expandedEntry1, $expandedEntry2];

        $expandedFeedSelfLink = new ODataLink();
        $expandedFeedSelfLink->name = 'self';
        $expandedFeedSelfLink->title = 'SubCollection';
        $expandedFeedSelfLink->url = 'SubCollection Self URL';

        $expandedFeed->selfLink = $expandedFeedSelfLink;

        //Now link the expanded entry to the main entry
        $expandLink = new ODataLink();
        $expandLink->isCollection = true;
        $expandLink->isExpanded = true;
        $expandLink->title = 'SubCollection';
        $expandLink->url = 'SubCollectionURL';
        $expandLink->expandedResult = $expandedFeed;
        $entry->links = [$expandLink];

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
	"SubCollection" : [
	    {
	        "Expanded Entry Complex Property":{
	            "first":"Entry 1 Name First",
	            "last":"Entry 1 Name Last"
	        },
	        "Expanded Entry City Property":"Entry 1 City Value",
	        "Expanded Entry State Property":"Entry 1 State Value"
	    },
	    {
	        "Expanded Entry Complex Property":{
	            "first":"Entry 2 Name First",
	            "last":"Entry 2 Name Last"
	        },
	        "Expanded Entry City Property":"Entry 2 City Value",
	        "Expanded Entry State Property":"Entry 2 State Value"
	    }
	],
    "Main Entry Property 1":"Yash",
    "Main Entry Property 2":"Kothari"
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: '.$writer->getOutput());
    }

    /**
     * @var ProvidersWrapper
     */
    protected $mockProvider;

    public function testGetOutputNoResourceSets()
    {
        $this->mockProvider->shouldReceive('getResourceSets')->andReturn([]);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n\n        ]\n    }\n}";

        $this->assertEquals($expected, $actual);
    }

    public function testGetOutputTwoResourceSets()
    {
        $fakeResourceSet1 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        $fakeResourceSet1->shouldReceive('getName')->andReturn('Name 1');

        $fakeResourceSet2 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        //TODO: this certainly doesn't seem right...see #73
        $fakeResourceSet2->shouldReceive('getName')->andReturn("XML escaped stuff \" ' <> & ?");

        $fakeResourceSets = [
            $fakeResourceSet1,
            $fakeResourceSet2,
        ];

        $this->mockProvider->shouldReceive('getResourceSets')->andReturn($fakeResourceSets);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n            \"Name 1\",\"XML escaped stuff \\\" ' <> & ?\"\n        ]\n    }\n}";

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider canHandleProvider
     */
    public function testCanHandle($id, $version, $contentType, $expected)
    {
        $writer = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), $this->serviceBase);

        $actual = $writer->canHandle($version, $contentType);

        $this->assertEquals($expected, $actual, $id);
    }

    public function canHandleProvider()
    {
        return [
            [100, Version::v1(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],
            [101, Version::v2(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],
            [102, Version::v3(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, false],

            [200, Version::v1(), MimeTypes::MIME_APPLICATION_JSON, false],
            [201, Version::v2(), MimeTypes::MIME_APPLICATION_JSON, false],
            [202, Version::v3(), MimeTypes::MIME_APPLICATION_JSON, false],

            //TODO: is this first one right?  this should NEVER come up, but should we claim to handle this format when
            //it's invalid for V1? Ditto first of the next sections
            [300, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, false],
            [301, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, false],
            [302, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, true],

            [400, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],
            [401, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],
            [402, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],

            [500, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],
            [501, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],
            [502, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],

            [600, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false], //this one seems especially wrong
            [601, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false],
            [602, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false],
        ];
    }
}
