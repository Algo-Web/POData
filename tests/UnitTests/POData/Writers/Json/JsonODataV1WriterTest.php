<?php

namespace UnitTests\POData\Writers\Json;

use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataBagContent;
use POData\Writers\Json\JsonODataV1Writer;

class JsonODataV1WriterTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * 
	 * Testing write url 
	 */
	function testWriteURL()
	{
		$oDataUrl = new ODataURL();
		$oDataUrl->oDataUrl = 'http://services.odata.org/OData/OData.svc/Suppliers(0)';
		$writer = new JsonODataV1Writer();
		$result = $writer->write($oDataUrl);
		$this->assertSame($writer, $result);
		
		//decoding the json string to test, there is no json string comparison in php unit
		$actual = json_decode($writer->getOutput());

		$expected = '{ "d" : {"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)"} }';
		$expected = json_decode($expected);
		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	
	/**
	 * 
	 * Testing write url collection
	 */
	function testWriteURLCollection()
	{
		$oDataUrlCollection = new ODataURLCollection();
		$oDataUrl1 = new ODataURL();
		$oDataUrl1->oDataUrl = 'http://services.odata.org/OData/OData.svc/Products(0)';
		$oDataUrl2 = new ODataURL();
		$oDataUrl2->oDataUrl = 'http://services.odata.org/OData/OData.svc/Products(7)';
		$oDataUrl3 = new ODataURL();
		$oDataUrl3->oDataUrl = 'http://services.odata.org/OData/OData.svc/Products(8)';
		$oDataUrlCollection->oDataUrls = array($oDataUrl1,
		                                       $oDataUrl2,
		                                       $oDataUrl3
		                                      );
		$oDataUrlCollection->count = 3;
		$writer = new JsonODataV1Writer();
		$result = $writer->write($oDataUrlCollection);
		$this->assertSame($writer, $result);


		//decoding the json string to test
		$actual = json_decode($writer->getOutput());

		$expected = '{
		                "d" : [
							{
						        "uri": "http://services.odata.org/OData/OData.svc/Products(0)"
							},
						    {
						        "uri": "http://services.odata.org/OData/OData.svc/Products(7)"
						    },
						    {
						        "uri": "http://services.odata.org/OData/OData.svc/Products(8)"
						    }
						]
					}';
		
		 $expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	

	function testWriteFeed()
	{
		$oDataFeed = new ODataFeed();
		$oDataFeed->id = 'FEED ID';
		$oDataFeed->title = 'FEED TITLE';
		//self link
		$selfLink = new ODataLink();
    	$selfLink->name = "Products";
    	$selfLink->title = "Products";
    	$selfLink->url = "Categories(0)/Products";
		$oDataFeed->selfLink = $selfLink;
		//self link end
		$oDataFeed->rowCount = '3';
		
		//next page link
		$nextPageLink = new ODataLink();
		$nextPageLink->name = "Next Page Link";
    	$nextPageLink->title = "Next Page";
    	$nextPageLink->url = 'http://services.odata.org/OData/OData.svc$skiptoken=12';
		$oDataFeed->nextPageLink = $nextPageLink;
		//feed entries
		
		//entry1
		$entry1 = new ODataEntry();
		$entry1->id = 'http://services.odata.org/OData/OData.svc/Products(0)';
		$entry1->selfLink = 'entry1 self link';
		$entry1->title = 'title of entry 1';
		$entry1->editLink = 'edit link of entry 1';
		$entry1->type = 'DataServiceProviderDemo.Product';
		$entry1->eTag = '';
		//entry 1 property content
		$entry1PropContent = new ODataPropertyContent();
		
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
		$entry1Prop3->value = "2012-09-17T14:17:13";

		$entry1Prop4 = new ODataProperty();
		$entry1Prop4->name = 'DiscontinuedDate';
		$entry1Prop4->typeName = 'Edm.DateTime';
		$entry1Prop4->value = null;

		$entry1Prop5 = new ODataProperty();
		$entry1Prop5->name = 'Price';
		$entry1Prop5->typeName = 'Edm.Double';
		$entry1Prop5->value = 2.5;


		$entry1PropContent = new ODataPropertyContent();
		$entry1PropContent->properties = array(
			$entry1Prop1,
			$entry1Prop2,
			$entry1Prop3,
			$entry1Prop4,
			$entry1Prop5,
		);//entry 1 property content end
		
		$entry1->propertyContent = $entry1PropContent;
		
		$entry1->isExpanded       = false;
		$entry1->isMediaLinkEntry = false;
		
		//entry 1 links
		//link1
		$link1 = new ODataLink();
		$link1->name = "http://services.odata.org/OData/OData.svc/Products(0)/Categories";
		$link1->title = "Categories";
		$link1->url = "http://services.odata.org/OData/OData.svc/Products(0)/Categories";
		
    	$entry1->links = array($link1);
		//entry 1 links end
		
		//entry 1 end
		$oDataFeed->entries = array($entry1);
		
		$writer = new JsonODataV1Writer();
		$result = $writer->write($oDataFeed);
		$this->assertSame($writer, $result);


		//decoding the json string to test
		$actual = json_decode($writer->getOutput());
		$expected = '{
					    "d" : [
				            {
				                "__metadata": {
				                    "uri": "http://services.odata.org/OData/OData.svc/Products(0)",
				                    "type": "DataServiceProviderDemo.Product"
				                },
				                "Categories": {
				                    "__deferred": {
				                        "uri": "http://services.odata.org/OData/OData.svc/Products(0)/Categories"
				                    }
				                },
				                "ID": 100,
				                "Name": "Bread",
				                "ReleaseDate" : "2012-09-17T14:17:13",
				                "DiscontinuedDate" : null,
				                "Price" : 2.5
				            }
				        ]
					}';
		$expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	
	/**
	 * 
	 * Testing write feed function(complex property)
	 */
	function testWriteFeedCompProp()
	{
		$oDataFeed = new ODataFeed();
		$oDataFeed->id = 'FEED ID';
		$oDataFeed->title = 'FEED TITLE';
		//self link
		$selfLink = new ODataLink();
    	$selfLink->name = "Products";
    	$selfLink->title = "Products";
    	$selfLink->url = "Categories(0)/Products";
		$oDataFeed->selfLink = $selfLink;
		//self link end
		$oDataFeed->rowCount = '3';
		
		//next page
		$nextPageLink = new ODataLink();
		$nextPageLink->name = "Next Page Link";
    	$nextPageLink->title = "Next Page";
    	$nextPageLink->url = 'http://services.odata.org/OData/OData.svc$skiptoken=12';
		$oDataFeed->nextPageLink = $nextPageLink;
		//feed entries
		
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
		$entry1Prop1->value = (string) 0 ;
		
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
		
		$compForEntry1Prop3->properties = array($compForEntry1Prop3Prop1,
		                                           $compForEntry1Prop3Prop2, 
		                                           $compForEntry1Prop3Prop3, 
		                                           $compForEntry1Prop3Prop4, 
		                                           $compForEntry1Prop3Prop5);
		
		$entry1Prop3 = new ODataProperty();
		$entry1Prop3->name = 'Address';
		$entry1Prop3->typeName = 'ODataDemo.Address';
		$entry1Prop3->value = $compForEntry1Prop3;
		
		$entry1Prop4 = new ODataProperty();
		$entry1Prop4->name = 'Concurrency';
		$entry1Prop4->typeName = 'Edm.Int16';
		$entry1Prop4->value = (string) 0 ;
		
		$entry1PropContent->properties = array($entry1Prop1, $entry1Prop2, $entry1Prop3, $entry1Prop4);
		//entry 1 property content end
		
		$entry1->propertyContent = $entry1PropContent;
		
		$entry1->isExpanded       = false;
		$entry1->isMediaLinkEntry = false;
		
		//entry 1 links
		//link1
		$link1 = new ODataLink();
		$link1->name = "Products";
    	$link1->title = "Products";
    	$link1->url = "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products";
		
    	$entry1->links = array($link1);
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
		
		$compForEntry2Prop3->properties = array($compForEntry2Prop3Prop1,
		                                           $compForEntry2Prop3Prop2, 
		                                           $compForEntry2Prop3Prop3, 
		                                           $compForEntry2Prop3Prop4, 
		                                           $compForEntry2Prop3Prop5);
		
		$entry2Prop3 = new ODataProperty();
		$entry2Prop3->name = 'Address';
		$entry2Prop3->typeName = 'ODataDemo.Address';
		$entry2Prop3->value = $compForEntry2Prop3;
		
		$entry2Prop4 = new ODataProperty();
		$entry2Prop4->name = 'Concurrency';
		$entry2Prop4->typeName = 'Edm.Int16';
		$entry2Prop4->value = (string) 0 ;
		
		$entry2PropContent->properties = array($entry2Prop1, $entry2Prop2, $entry2Prop3, $entry2Prop4);
		//entry 2 property content end
		
		$entry2->propertyContent = $entry2PropContent;
		
		$entry2->isExpanded       = false;
		$entry2->isMediaLinkEntry = false;
		
		//entry 2 links
		//link1
		$link1 = new ODataLink();
		$link1->name = "Products";
    	$link1->title = "Products";
    	$link1->url = "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products";
		
    	$entry2->links = array($link1);
		//entry 2 links end
		
		//entry 2 end
    	
		$oDataFeed->entries = array($entry1, $entry2);
		
		$writer = new JsonODataV1Writer();
		$result = $writer->write($oDataFeed);
		$this->assertSame($writer, $result);


		//decoding the json string to test
		$actual = json_decode($writer->getOutput());
		$expected = '{
						"d" : [
							{
								"__metadata": {
									"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)",
									"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
								},
								"ID": 0,
								"Name": "Exotic Liquids",
								"Address": {
									"__metadata": {
										"type": "ODataDemo.Address"
									},
									"Street": "NE 228th",
									 "City": "Sammamish",
									 "State": "WA",
									 "ZipCode": "98074",
									 "Country": "USA"
								},
								"Concurrency": 0,
								"Products": {
								        "__deferred": {
											"uri": "http://services.odata.org/OData/OData.svc/Suppliers(0)/Products"
										}
								}
							},
							{
								"__metadata": {
									"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)",
									"etag": "W/\"0\"", "type": "ODataDemo.Supplier"
								},
								"ID": 1,
								"Name": "Tokyo Traders",
								"Address": {
									"__metadata": {
										"type": "ODataDemo.Address"
									},
									"Street": "NE 40th",
									"City": "Redmond",
									"State": "WA",
									"ZipCode": "98052",
									"Country": "USA"
								},
								"Concurrency": 0,
								"Products": {
									"__deferred": {
										"uri": "http://services.odata.org/OData/OData.svc/Suppliers(1)/Products"
									}
								}
							}
						]
					}';
		$expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	
	/**
	 * 
	 * Testing write entry
	 */
	function testWriteEntry()
	{
		//entry
		$entry = new ODataEntry();
		$entry->id = 'http://services.odata.org/OData/OData.svc/Categories(0)';
		$entry->selfLink = 'entry2 self link';
		$entry->title = 'title of entry 2';
		$entry->editLink = 'edit link of entry 2';
		$entry->type = 'ODataDemo.Category';
		$entry->eTag = '';
		
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
		
		$entryPropContent->properties = array($entryProp1, $entryProp2);
		
		$entry->propertyContent = $entryPropContent;
		
		//links
		$link = new ODataLink();
		$link->name = "Products";
    	$link->title = "Products";
    	$link->url = "http://services.odata.org/OData/OData.svc/Categories(0)/Products";
		
    	$entry->links = array($link);
    	
    	$writer = new JsonODataV1Writer();
		$result = $writer->write($entry);
		$this->assertSame($writer, $result);


		//decoding the json string to test
		$actual = json_decode($writer->getOutput());

		$expected = '{
						"d" : {
							"__metadata": {
								"uri": "http://services.odata.org/OData/OData.svc/Categories(0)", "type": "ODataDemo.Category"
							},
							"ID": 0,
							"Name": "Food",
							"Products": {
								"__deferred": {
									"uri": "http://services.odata.org/OData/OData.svc/Categories(0)/Products"
								}
							}
						}
					}';
		$expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
		
	}
	

	function testWriteComplexProperty()
	{
		$propContent = new ODataPropertyContent();
		
		$propContent->isTopLevel = true;

		
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

		//property
		$compProp = new ODataPropertyContent();
		$compProp->properties = array(
			$compProp1,
			$compProp2,
			$compProp3,
			$compProp4,
			$compProp5,
		);

		$prop1 = new ODataProperty();
		$prop1->name = 'Address';
		$prop1->typeName = 'ODataDemo.Address';
		$prop1->value = $compProp;
		
		
		$propContent->properties = array($prop1);
		
		$writer = new JsonODataV1Writer();
		$result = $writer->write($propContent);
		$this->assertSame($writer, $result);


		//decoding the json string to test
		$actual = json_decode($writer->getOutput());

		$expected = '{
						"d" : {
							"Address": {
								"__metadata": {
									"type": "ODataDemo.Address"
								},
								"Street": "NE 228th",
								"City": "Sammamish",
								"State": "WA",
								"ZipCode": "98074",
								"Country": "USA"
								}
						}
					}';
		$expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	
	/**
	 * 
	 * Testing bag property
	 */
	function testBagProtestEntryWithBagPropertyperty()
	{
		//TODO: bags are not available till v3 see https://github.com/balihoo/POData/issues/79

		//entry
		$entry = new ODataEntry();
		$entry->id = 'http://host/service.svc/Customers(1)';
		$entry->selfLink = 'entry2 self link';
		$entry->title = 'title of entry 2';
		$entry->editLink = 'edit link of entry 2';
		$entry->type = 'SampleModel.Customer';
		$entry->eTag = '';

		
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
		
		$bagEntryProp3->propertyContents = array(
    	                              "mike@foo.com",
    	                              "mike2@foo.com");
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
		
		$bagEntryProp4ContentProp1Content->properties = array($bagEntryProp4ContentProp1ContentProp1,
		                                                         $bagEntryProp4ContentProp1ContentProp2);
		
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
		
		$bagEntryProp4ContentProp1Content2->properties = array($bagEntryProp4ContentProp1Content2Prop1,
		                                                         $bagEntryProp4ContentProp1Content2Prop2);
		
		//end property content for bagEntryProp4ContentProp1
		

		                                             
		$bagEntryProp4->propertyContents = array($bagEntryProp4ContentProp1Content, 
		                                         $bagEntryProp4ContentProp1Content2
		                                        );
		$bagEntryProp4->type = 'Bag(SampleModel.Address)'; //TODO: this might not be what really happens in the code..#61
		
		$entryProp4 = new ODataProperty();
		$entryProp4->name = 'Addresses';
		$entryProp4->typeName = 'Bag(SampleModel.Address)';
		$entryProp4->value = $bagEntryProp4;
		//property 4 ends
		
		
		$entryPropContent->properties = array($entryProp1, $entryProp2, $entryProp3, $entryProp4);
		
		$entry->propertyContent = $entryPropContent;
		
		$writer = new JsonODataV1Writer();
		$result = $writer->write($entry);
		$this->assertSame($writer, $result);



		//decoding the json string to test
		$actual = json_decode($writer->getOutput());

		$expected = '{
						"d" : {
							"__metadata": {
								"uri": "http://host/service.svc/Customers(1)",
								"type": "SampleModel.Customer"
							},
							"ID": 1,
							"Name": "mike",
							"EmailAddresses": {
					            "__metadata": {
					                "type": "Bag(Edm.String)"
					            },
					            "results": [
					                "mike@foo.com", "mike2@foo.com"
					            ]
				            },
				            "Addresses": {
				                "__metadata": {
				                    "type": "Bag(SampleModel.Address)"
				                },
				                "results": [
				                    {
				                        "Street": "123 contoso street",
				                        "Apartment": "508"
				                    },
				                    {
				                        "Street": "834 foo street",
				                        "Apartment": "102"
				                    }
				                ]
				            }
					    }
					}';
		$expected = json_decode($expected);

		$this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
	}
	
    /** 
     * test for write top level primitive property.
     */
    function testPrimitiveProperty(){
    	
    	$property = new ODataProperty();
    	$property->name = "Count";
    	$property->typeName = 'Edm.Int16';
    	$property->value = 56;

    	$content = new ODataPropertyContent();
    	$content->properties = array($property);
    	$content->isTopLevel = true;
    	$writer = new JsonODataV1Writer();
    	$result = $writer->write($content);
	    $this->assertSame($writer, $result);


	    //decoding the json string to test
	    $actual = json_decode($writer->getOutput());

	    $expected = '{
						"d" : {
							"Count": 56
						}
					}';
	    $expected = json_decode($expected);

	    $this->assertEquals(array($expected), array($actual), "raw JSON is: " . $writer->getOutput());
    }
     
}