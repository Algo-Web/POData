<?php

declare(strict_types=1);

namespace UnitTests\POData\Writers\Json;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\ProvidersWrapper;
use POData\Writers\Json\JsonLightMetadataLevel;
use POData\Writers\Json\JsonLightODataWriter;
use UnitTests\POData\Writers\BaseWriterTest;

/**
 * Class JsonLightODataWriterFullMetadataTest
 * @package UnitTests\POData\Writers\Json
 */
class JsonLightODataWriterFullMetadataTest extends BaseWriterTest
{
    protected $serviceBase = 'http://services.odata.org/OData/OData.svc';

    public function testWriteURL()
    {
        //NOTE: there's no difference for this between fullmetadata and minimalmetadata

        $this->markTestSkipped("see #80 ODataURL doesn't have enough context to get the meta data return result");

        //IE: http://services.odata.org/v3/OData/OData.svc/Products(0)/$links/Supplier?$format=application/json;odata=fullmetadata

        $oDataUrl      = new ODataURL('http://services.odata.org/OData/OData.svc/Suppliers(0)');
        $writer        = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result        = $writer->write($oDataUrl);
        $this->assertSame($writer, $result);

        //decoding the json string to test, there is no json string comparison in php unit
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metdata" : "http://services.odata.org/OData/OData.svc/$metadata#Products/$links/Supplier",
						"url": "http://services.odata.org/OData/OData.svc/Suppliers(0)"
					}';
        $expected = json_decode($expected);
        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteURLCollection()
    {
        //NOTE: there's no difference for this between fullmetadata and minimalmetadata

        $this->markTestSkipped("see #80 ODataURL doesn't have enough context to get the meta data return result");
        //see http://services.odata.org/v3/OData/OData.svc/Categories(1)/$links/Products?$format=application/json;odata=fullmetadata

        $oDataUrlCollection       = new ODataURLCollection(
            [
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(0)'),
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(7)'),
                new ODataURL('http://services.odata.org/OData/OData.svc/Products(8)'),
            ],
            null,
            null //simulate no $inlinecount
        );
        $writer                    = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result                    = $writer->write($oDataUrlCollection);
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());

        $oDataUrlCollection->setCount(44); //simulate an $inlinecount
        $writer                    = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result                    = $writer->write($oDataUrlCollection);
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteFeed()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Categories(0)/Products?$top=2&$format=application/json;odata=fullmetadata

        //entry1
        $entry1 = $this->buildSingleEntry();

        $entry1->isExpanded       = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links NOTE minimalmetadata means this won't be output
        //link1
        $link1        = new ODataLink(
            'http://services.odata.org/OData/OData.svc/Products(0)/Categories',
            'Categories',
            null,
            'http://services.odata.org/OData/OData.svc/Products(0)/Categories'
        );

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end

        $oDataFeed        = new ODataFeed();
        $oDataFeed->id    = 'FEED ID';
        $oDataFeed->title = new ODataTitle('FEED TITLE');
        //self link
        $selfLink            = new ODataLink('Products', 'Products', null, 'Categories(0)/Products');
        $oDataFeed->setSelfLink($selfLink);
        //self link end
        $oDataFeed->setEntries([$entry1]);

        //next page link: NOTE minimalmetadata means this won't be output
        $oDataFeed->setNextPageLink( new ODataLink('Next Page Link', 'Next Page', null, 'http://services.odata.org/OData/OData.svc$skiptoken=12')
    );
        //feed entries

        //Note that even if the top limits the collection the count should not be output unless inline count is specified
        //IE: http://services.odata.org/v3/OData/OData.svc/Categories?$top=1&$inlinecount=allpages&$format=application/json;odata=fullmetadata
        //The feed count will be null unless inlinecount is specified

        $oDataFeed->setRowCount(null);

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
					    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "value" : [
				            {
				                "odata.type": "DataServiceProviderDemo.Product",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Products(0)",
                                "odata.etag":"",
                                "odata.editLink": "edit link of entry 1",
                                "Categories@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Products(0)/Categories",
				                "ID": 100,
				                "Name": "Bread",
				                "ReleaseDate@odata.type": "Edm.DateTime",
				                "ReleaseDate" : "/Date(1346990823000)/",
				                "DiscontinuedDate" : null,
				                "Price" : 2.5
				            }
				        ]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());

        //Now we'll simulate an $inlinecount=allpages by specifying a count
        $oDataFeed->setRowCount(33);

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //TODO: v3 specifies that the count must be before value..how can we test this well?
        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
						"odata.count":"33",
					    "value" : [
				            {
				                "odata.type": "DataServiceProviderDemo.Product",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Products(0)",
                                "odata.etag":"",
                                "odata.editLink": "edit link of entry 1",
                                "Categories@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Products(0)/Categories",
				                "ID": 100,
				                "Name": "Bread",
				                "ReleaseDate@odata.type": "Edm.DateTime",
				                "ReleaseDate" : "/Date(1346990823000)/",
				                "DiscontinuedDate" : null,
				                "Price" : 2.5
				            }
				        ]
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteFeedWithEntriesWithComplexProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers?$top=2&$format=application/json;odata=fullmetadata
        // suppliers have address as a complex property

        //entry1
        $entry1 = $this->buildEntryWithComplexProperties();

        $entry1->isExpanded       = false;
        $entry1->isMediaLinkEntry = false;

        //entry 1 links
        //link1
        $link1        = new ODataLink('Products', 'Products', null, 'http://services.odata.org/OData/OData.svc/Suppliers(0)/Products');

        $entry1->links = [$link1];
        //entry 1 links end

        //entry 1 end

        //entry 2
        $entry2 = $this->buildSecondEntryWithComplexProperties();

        $entry2->isExpanded       = false;
        $entry2->isMediaLinkEntry = false;

        //entry 2 links
        //link1
        $link1        = new ODataLink(
            'Products',
            'Products',
            null,
            'http://services.odata.org/OData/OData.svc/Suppliers(1)/Products'
        );

        $entry2->links = [$link1];
        //entry 2 links end

        //entry 2 end

        $oDataFeed        = new ODataFeed();
        $oDataFeed->id    = 'FEED ID';
        $oDataFeed->title = new ODataTitle('FEED TITLE');
        //self link
        $selfLink            = new ODataLink(
            'Products',
            'Products',
            null,
            'Categories(0)/Products'
        );
        $oDataFeed->setSelfLink($selfLink);
        //self link end

        //next page
        $oDataFeed->setNextPageLink(new ODataLink('Next Page Link', 'Next Page', null, 'http://services.odata.org/OData/OData.svc$skiptoken=12'));
        //feed entries

        $oDataFeed->setEntries([$entry1, $entry2]);

        $oDataFeed->setRowCount(null); //simulate no inline count

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "value": [
							{
								"odata.type": "ODataDemo.Supplier",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
                                "odata.etag":"W/\"0\"",
                                "odata.editLink": "edit link of entry 1",
                                "Products@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products",
                                "ID": 0,
								"Name": "Exotic Liquids",
								"Address": {
									"odata.type": "ODataDemo.Address",
									"Street": "NE 228th",
									 "City": "Sammamish",
									 "State": "WA",
									 "ZipCode": "98074",
									 "Country": "USA"
								},
								"Concurrency": 0
							},
							{
								"odata.type": "ODataDemo.Supplier",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
                                "odata.etag":"W/\"0\"",
                                "odata.editLink": "edit link of entry 2",
                                "Products@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products",
                                "ID": 1,
								"Name": "Tokyo Traders",
								"Address": {
									"odata.type": "ODataDemo.Address",
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());

        $oDataFeed->setRowCount(55); //simulate  $inlinecount=allpages

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($oDataFeed);
        $this->assertSame($writer, $result);

        //TODO: spec says count must be before! need to verify positioning in the test somehow
        //decoding the json string to test
        $actual   = json_decode($writer->getOutput());
        $expected = '{
					    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#FEED TITLE",
					    "odata.count":"55",
					    "value": [
							{
								"odata.type": "ODataDemo.Supplier",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
                                "odata.etag":"W/\"0\"",
                                "odata.editLink": "edit link of entry 1",
                                "Products@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products",
                                "ID": 0,
								"Name": "Exotic Liquids",
								"Address": {
									"odata.type": "ODataDemo.Address",
									"Street": "NE 228th",
									 "City": "Sammamish",
									 "State": "WA",
									 "ZipCode": "98074",
									 "Country": "USA"
								},
								"Concurrency": 0
							},
							{
								"odata.type": "ODataDemo.Supplier",
                                "odata.id": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
                                "odata.etag":"W/\"0\"",
                                "odata.editLink": "edit link of entry 2",
                                "Products@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products",
                                "ID": 1,
								"Name": "Tokyo Traders",
								"Address": {
									"odata.type": "ODataDemo.Address",
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntry()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)?$format=application/json;odata=fullmetadata

        //entry
        $entry = $this->buildTestEntry();

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#resource set name/@Element",
						"odata.type": "ODataDemo.Category",
                        "odata.id": "http://services.odata.org/OData/OData.svc/Categories(0)",
                        "odata.etag":"",
                        "odata.editLink": "edit link of entry 2",
                        "Products@odata.navigationLinkUrl": "http://services.odata.org/OData/OData.svc/Categories(0)/Products",
						"ID": 0,
						"Name": "Food"
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteComplexProperty()
    {
        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)/Address?$format=application/json;odata=fullmetadata


        //property

        $propContent = $this->buildComplexProperty();

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($propContent);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#ODataDemo.Address",
						"odata.type": "ODataDemo.Address",
						"Street": "NE 228th",
						"City": "Sammamish",
						"State": "WA",
						"ZipCode": "98074",
						"Country": "USA"
					}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testEntryWithBagProperty()
    {
        //Intro to bags: http://www.odata.org/2010/09/adding-support-for-bags/
        //TODO: bags were renamed to collection in v3 see https://github.com/balihoo/POData/issues/79
        //see http://docs.oasis-open.org/odata/odata-json-format/v4.0/cs01/odata-json-format-v4.0-cs01.html#_Toc365464701
        //can't find a Collection type in online demo

        //entry
        $entry = $this->buildEntryWithBagProperty();

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
						"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#resource set name/@Element",
						"odata.type": "SampleModel.Customer",
                        "odata.id": "http://host/service.svc/Customers(1)",
                        "odata.etag":"some eTag",
                        "odata.editLink": "edit link of entry 1",
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testPrimitiveProperty()
    {
        //NOTE: there is no different between minimalmetadata and fullmetadata for primitive properties

        //see http://services.odata.org/v3/OData/OData.svc/Suppliers(0)/Address/City?$format=application/json;odata=fullmetadata
        $content             = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Count',
                    'Edm.Int16',
                    56
                )
            ]
        );

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($content);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	                    "odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#Edm.Int16",
	                    "value" :  56
	                 }';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntry()
    {
        //First build up the expanded entry
        $entry = $this->buildEntryWithExpandedEntry();

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
	"odata.type":"Main.Type",
	"odata.id":"Main Entry",
	"odata.etag":"Entry ETag",
	"odata.editLink":"Edit Link URL",
	"Expanded Property@odata.navigationLinkUrl":"ExpandedURL",
    "Expanded Property":{
        "odata.type":"Expanded.Type",
        "odata.id":"Expanded Entry 1",
        "odata.etag":"Entry ETag",
        "odata.editLink":"Edit Link URL",
        "Expanded Entry Complex Property":{
            "odata.type":"Full Name",
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedEntryThatIsNull()
    {

        //build up the main entry

        $entry             = new ODataEntry();
        $entry->id         = 'Main Entry';
        $entry->title      = new ODataTitle('Entry Title');
        $entry->type       = 'Main.Type';
        $entry->editLink   = 'Edit Link URL';
        $entry->setSelfLink(new ODataLink('Self Link URL'));
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

        $entry->eTag             = 'Entry ETag';
        $entry->isMediaLinkEntry = false;
        $entry->propertyContent             = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Main Entry Property 1',
                    'string',
                    'Yash'
                ),
                new ODataProperty(
                    'Main Entry Property 2',
                    'string',
                    'Kothari'
                )
            ]
        );
        //End of main entry

        //Now link the expanded entry to the main entry
        $expandLink                 = new ODataLink(
            null,
            'Expanded Property',
            null,
            'ExpandedURL',
            false,
            null, //<--key part
            true
        );
        $entry->links               = [$expandLink];

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
	"odata.type":"Main.Type",
	"odata.id":"Main Entry",
	"odata.etag":"Entry ETag",
	"odata.editLink":"Edit Link URL",
	"Expanded Property@odata.navigationLinkUrl":"ExpandedURL",
    "Expanded Property":null,
    "Main Entry Property 1":"Yash",
    "Main Entry Property 2":"Kothari"
}';
        $expected = json_decode($expected);

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    public function testWriteEntryWithExpandedFeed()
    {
        //First build up the expanded entry 1
        $entry = $this->buildEntryWithExpandedFeed();

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        //decoding the json string to test
        $actual = json_decode($writer->getOutput());

        $expected = '{
	"odata.metadata":"http://services.odata.org/OData/OData.svc/$metadata#/@Element",
	"odata.type":"Main.Type",
	"odata.id":"Main Entry",
	"odata.etag":"Entry ETag",
	"odata.editLink":"Edit Link URL",
	"SubCollection@odata.navigationLinkUrl" : "SubCollectionURL",
	"SubCollection" : [
	    {
	        "odata.type":"Expanded.Type",
            "odata.id":"Expanded Entry 1",
            "odata.etag":"Entry ETag",
            "odata.editLink":"Edit Link URL",
	        "Expanded Entry Complex Property":{
	            "odata.type" : "Full Name",
	            "first":"Entry 1 Name First",
	            "last":"Entry 1 Name Last"
	        },
	        "Expanded Entry City Property":"Entry 1 City Value",
	        "Expanded Entry State Property":"Entry 1 State Value"
	    },
	    {
	        "odata.type":"Expanded.Type",
            "odata.id":"Expanded Entry 2",
            "odata.etag":"Entry ETag",
            "odata.editLink":"Edit Link URL",
            "Expanded Entry Complex Property":{
                "odata.type" : "Full Name",
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

        $this->assertEquals([$expected], [$actual], 'raw JSON is: ' . $writer->getOutput());
    }

    /**
     * @var ProvidersWrapper
     */
    protected $mockProvider;

    public function testGetOutputNoResourceSets()
    {
        $this->mockProvider->shouldReceive('getResourceSets')->andReturn([]);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n\n        ]\n    }\n}";

        $this->assertJsonStringEqualsJsonString($expected, $actual);
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

        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "{\n    \"d\":{\n        \"EntitySet\":[\n            \"Name 1\",\"XML escaped stuff \\\" ' <> & ?\"\n        ]\n    }\n}";

        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    /**
     * @dataProvider canHandleProvider
     * @param mixed $id
     * @param mixed $version
     * @param mixed $contentType
     * @param mixed $expected
     */
    public function testCanHandle($id, $version, $contentType, $expected)
    {
        $writer = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), $this->serviceBase);

        $actual = $writer->canHandle($version, $contentType);

        $this->assertEquals($expected, $actual, strval($id));
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
            [302, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, false],

            [400, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],
            [401, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],
            [402, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_NO_META, false],

            [500, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],
            [501, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, false],
            [502, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_FULL_META, true],

            [600, Version::v1(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false], //this one seems especially wrong
            [601, Version::v2(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false],
            [602, Version::v3(), MimeTypes::MIME_APPLICATION_JSON_VERBOSE, false],
        ];
    }

    public function testConstructorWithBadServiceUri()
    {
        $level      = JsonLightMetadataLevel::FULL();
        $serviceUri = '';

        $expected = 'absoluteServiceUri must not be empty or null';
        $actual   = null;

        try {
            new JsonLightODataWriter(PHP_EOL, true, $level, $serviceUri);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWritePropertyContentWithFirstPropertyHavingNullValue()
    {
        $level      = JsonLightMetadataLevel::FULL();
        $serviceUri = 'http://localhost/odata.svc';

        $foo = new JsonLightODataWriter(PHP_EOL, true, $level, $serviceUri);

        $property           = new ODataProperty('','Edm.String', null);

        $model               = new ODataPropertyContent([$property]);

        $expected = '{' . PHP_EOL;
        $expected .= '    "odata.metadata":"http://localhost/odata.svc/$metadata#Edm.String","value":null' . PHP_EOL;
        $expected .= '}';
        $foo->write($model);
        $actual   = $foo->getOutput();
        $expected = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $expected);
        $actual   = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $actual);
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testWritePropertyContentWithFirstPropertyHavingBagValue()
    {
        $level      = JsonLightMetadataLevel::FULL();
        $serviceUri = 'http://localhost/odata.svc';

        $foo = new JsonLightODataWriter(PHP_EOL, true, $level, $serviceUri);

        $bag                   = new ODataBagContent();
        $bag->setPropertyContents([]);

        $property           = new ODataProperty('','Edm.String', $bag);

        $model               = new ODataPropertyContent([$property]);

        $expected = '{' . PHP_EOL;
        $expected .= '    "odata.metadata":"http://localhost/odata.svc/$metadata#Edm.String","value":[' . PHP_EOL;
        $expected .= PHP_EOL . '    ]' . PHP_EOL;
        $expected .= '}';
        $foo->write($model);
        $actual   = $foo->getOutput();
        $expected = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $expected);
        $actual   = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $actual);
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testWriteEmptyODataEntry()
    {
        $entry                  = new ODataEntry();
        $entry->resourceSetName = 'Foobars';

        $foo = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), 'http://localhost/odata.svc');

        $actual   = $foo->write($entry)->getOutput();
        $expected = '{' . PHP_EOL . '    "odata.metadata":"http://localhost/odata.svc/$metadata#Foobars/@Element"'
                    . ',"odata.type":"","odata.id":"","odata.etag":"","odata.editLink":""' . PHP_EOL . '}';
        $this->assertJsonStringEqualsJsonString($actual, $expected);
    }

    public function testWriteEmptyODataFeed()
    {
        $feed                  = new ODataFeed();
        $feed->id              = 'http://localhost/odata.svc/feedID';
        $feed->title           = new ODataTitle('title');
        $feed->setSelfLink(new ODataLink(
            ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE,
            'Feed Title',
            null,
            'feedID'
        ));

        $foo      = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), 'http://localhost/odata.svc');
        $expected = '{' . PHP_EOL
                    . '    "odata.metadata":"http://localhost/odata.svc/$metadata#title","value":['
                    . PHP_EOL . PHP_EOL . '    ]' . PHP_EOL . '}';
        $actual = $foo->write($feed)->getOutput();
        $this->assertTrue(false !== strpos($actual, $expected));
    }
}
