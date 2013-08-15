<?php
use ODataProducer\ObjectModel\ODataURL;
use ODataProducer\ObjectModel\ODataURLCollection;
use ODataProducer\ObjectModel\ODataFeed;
use ODataProducer\ObjectModel\ODataEntry;
use ODataProducer\ObjectModel\ODataLink;
use ODataProducer\ObjectModel\ODataMediaLink;
use ODataProducer\ObjectModel\OdataPropertyContent;
use ODataProducer\ObjectModel\OdataProperty;
use ODataProducer\ObjectModel\OdataBagContent;
use ODataProducer\Writers\Atom\AtomODataWriter;
use ODataProducer\Writers\Common\ODataWriter;
use ODataProducer\Common\InvalidOperationException;
use ODataProducer\Common\ODataException;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
ODataProducer\Common\ClassAutoLoader::register();

class TestAtomODataWriter extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {        
    }
	/**
	 * Test for write top level URI item.
	 */
    public function testODataURLItem()
    {
    	$url = "http://www.odata.org/developers/protocols/atom-format";
		$odataURLItem = new ODataURL();
		$odataURLItem->oDataUrl = $url;
		$oWriter= new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataURLItem);
		$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<uri xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices">http://www.odata.org/developers/protocols/atom-format</uri>';
		//$this->assertXmlStringEqualsXmlString($result, $expected);		
    }
    
	/**
	 * Test for write top level Collection of URL item.
	 */
    public function testODataURLCollectionItem()
    {
    	$url1 = new ODataURL();
    	$url1->oDataUrl = 'http://www.odata.org/developers/protocols/atom-format';
    	$url2 = new ODataURL();
    	$url2->oDataUrl = 'http://www.odata.org/developers/protocols/json-format';
    	
    	$urls = array($url1, $url2);
		$odataURLItem = new ODataURLCollection();
		$odataURLItem->oDataUrls = $urls;
		
		$nextPageLink = new ODataLink ();
    	$nextPageLink->name = "Next";
    	$nextPageLink->title = "";
    	$nextPageLink->type = "";
    	$nextPageLink->url = "Next Link Url";
    	
    	$odataURLItem->nextPageLink = $nextPageLink;
		$odataURLItem->count = 10;
		$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataURLItem);
		$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<links xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices" 
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <m:count>10</m:count>
 <uri>http://www.odata.org/developers/protocols/atom-format</uri>
 <uri>http://www.odata.org/developers/protocols/json-format</uri>
 <link rel="Next" href="Next Link Url"/>
</links>';
		//$this->assertXmlStringEqualsXmlString($result, $expected);  		
    }
    
	/**
	 * Test for write top level feed item. 
	 */
	public function testWriteFeed()
    {
    	$odataFeedItem = new ODataFeed();
    	$odataFeedItem->id = 'Feed Id';
    	$odataFeedItem->rowCount = 'Count';
    	
    	$selfLink = new ODataLink ();
    	$selfLink->name = "Self Link Name";
    	$selfLink->tytle = "Self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link Url";
    	
    	$odataFeedItem->selfLink = $selfLink;

    	$nextPageLink = new ODataLink ();
    	$nextPageLink->name = "Next";
    	$nextPageLink->tytle = "";
    	$nextPageLink->type = "";
    	$nextPageLink->url = "Next Link Url";
    	
    	$odataFeedItem->nextPageLink = $nextPageLink;
    	$odataFeedItem->title = 'Feed Title';
    	$odataFeedItem->isTopLevel = true;

    	
    	 // Entry 1
    	
    	$odataEntryItem1 = new ODataEntry();
    	$odataEntryItem1->id = 'Entry 1';
    	$odataEntryItem1->title = 'Entry Title';

    	$editLink = new ODataLink();
    	$editLink->name = "edit";
    	$editLink->tytle = "Edit Link Title";
    	$editLink->type = "Edit link type";
    	$editLink->url = "Edit Link URL";
    	
    	$odataEntryItem1->editLink = $editLink;
    	
    	$selfLink = new ODataLink();
    	$selfLink->name = "self";
    	$selfLink->tytle = "self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link URL";
                                                
    	$odataEntryItem1->selfLink = $selfLink;
        $odataEntryItem1->mediaLinks = array(new ODataMediaLink('Media Link Name', 
                                                      'Edit Media link', 
                                                      'Src Media Link', 
                                                      'Media Content Type', 
                                                      'Media ETag'));
        $link = new ODataLink();
        $link->name = "Link Name";
    	$link->tytle = "Link Title";
    	$link->type = "Link Type";
    	$link->url = "Link URL";
        
        $odataEntryItem1->links = array();
        $odataEntryItem1->eTag = 'Entry ETag';
        $link->isExpanded       = false;
        $odataEntryItem1->isMediaLinkEntry = false;
        
        $bagProp1 = new ODataBagContent ();
        
        $propCont1 = new ODataPropertyContent();
        $propCont1_1 = new ODataPropertyContent();
        
        $pr1 = new ODataProperty();
        $pr1->name = 'fname';
        $pr1->typeName = 'string';
        $pr1->value = 'Yash';
        
        $pr2 = new ODataProperty();
        $pr2->name = 'lname';
        $pr2->typeName = 'string';
        $pr2->value = 'Kothari';
        
        $propCont1_1->odataProperty = array($pr1, $pr2);
		$propCont1_1_1 = new ODataPropertyContent();
		
		$pr3 = new ODataProperty();
        $pr3->name = 'fname';
        $pr3->typeName = 'string';
        $pr3->value = 'Anu';
        
        $pr4 = new ODataProperty();
        $pr4->name = 'lname';
        $pr4->typeName = 'string';
        $pr4->value = 'Chandy';
        
        $pr5 = new ODataProperty();
        $pr5->name = 'name';
        $pr5->typeName = null;
        $pr5->value = $propCont1_1;
        
        $pr6 = new ODataProperty();
        $pr6->name = 'name';
        $pr6->typeName = null;
        $pr6->value = $propCont1_1;
        
		$propCont1_1_1->odataProperty = array ($pr3,$pr4);
        $propCont1->odataProperty = array($pr5, $pr6);
        
        $bagProp1->propertyContents = array($propCont1);
        
        $pr7 = new ODataProperty();
        $pr7->name = 'name';
        $pr7->typeName = 'Bag(Name)';
        $pr7->value = $bagProp1;
        
        $prop1 = $pr7;
        
        $propCont = new ODataPropertyContent ();
        $propCont->odataProperty = array ($prop1);
        $odataEntryItem1->propertyContent = $propCont;
    	
        $odataFeedItem->entries = array (
            $odataEntryItem1
            );
		$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataFeedItem);		
    }
    
    /**
	 * Test for top level Entry Item with media link.
	 */
	public function testWriteMediaEntry()
    {
    	$odataEntryItem = new ODataEntry();
    	$odataEntryItem->id = 'Entry 1';
    	$odataEntryItem->title = 'Entry Title';
    	
    	$editLink = new ODataLink();
    	$editLink->name = "edit";
    	$editLink->tytle = "Edit Link Title";
    	$editLink->type = "Edit link type";
    	$editLink->url = "Edit Link URL";
    	
    	$odataEntryItem->editLink = $editLink;
    	
    	$selfLink = new ODataLink();
    	$selfLink->name = "self";
    	$selfLink->tytle = "self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link URL";
                                                
    	$odataEntryItem->selfLink = $selfLink;
    	$odataEntryItem->mediaLink = new ODataMediaLink("Thumbnail_600X450", "http://storage.live.com/123/christmas-tree-with-presents.jpg", "http://cdn-8.nflximg.com/US/boxshots/large/5632678.jpg", "image/jpg", time());
    	$odataEntryItem->mediaLinks = array(new ODataMediaLink('Media Link Name', 
                                                      'Edit Media link', 
                                                      'Src Media Link', 
                                                      'Media Content Type', 
                                                      'Media ETag'),
    	                                    new ODataMediaLink('Media Link Name2', 
                                                      'Edit Media link2', 
                                                      'Src Media Link2', 
                                                      'Media Content Type2', 
                                                      'Media ETag2'));
        
        
        $odataEntryItem->links = array();
        
        $odataEntryItem->eTag = 'Entry ETag';
        $odataEntryItem->isMediaLinkEntry = true;
        
        $propCont = new ODataPropertyContent ();
        $propCont->odataProperty = array (); 
        $odataEntryItem->propertyContent = $propCont;                     
		$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataEntryItem);
    }
    
	/**
	 * Test for top level Entry Item.
	 */
	public function testWriteEntry()
    {
    	$odataEntryItem = new ODataEntry();
    	$odataEntryItem->id = 'Entry 1';
    	$odataEntryItem->title = 'Entry Title';
    	
    	$editLink = new ODataLink();
    	$editLink->name = "edit";
    	$editLink->tytle = "Edit Link Title";
    	$editLink->type = "Edit link type";
    	$editLink->url = "Edit Link URL";
    	
    	$odataEntryItem->editLink = $editLink;
    	
    	$selfLink = new ODataLink();
    	$selfLink->name = "self";
    	$selfLink->tytle = "self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link URL";
                                                
    	$odataEntryItem->selfLink = $selfLink;
    	$odataEntryItem->mediaLink = new ODataMediaLink("Thumbnail_600X450", "http://storage.live.com/123/christmas-tree-with-presents.jpg", null, "image/jpg", time());
    	$odataEntryItem->mediaLinks = array(new ODataMediaLink('Media Link Name', 
                                                      'Edit Media link', 
                                                      'Src Media Link', 
                                                      'Media Content Type', 
                                                      'Media ETag'),
    	                                    new ODataMediaLink('Media Link Name2', 
                                                      'Edit Media link2', 
                                                      'Src Media Link2', 
                                                      'Media Content Type2', 
                                                      'Media ETag2'));
        
        
        $link = new ODataLink();
        $link->name = "Link Name";
    	$link->tytle = "Link Title";
    	$link->type = "Link Type";
    	$link->url = "Link URL";
        $link->isExpanded       = false;
        
        $odataEntryItem->links = array($link);
        
        $odataEntryItem->eTag = 'Entry ETag';
        $odataEntryItem->isMediaLinkEntry = true;
        
        $bagProp1 = new ODataBagContent ();
        
        $propCont3 = new ODataPropertyContent ();
        $propCont3_1 = new ODataPropertyContent ();
        
        $pr1 = new ODataProperty();
        $pr1->name = 'fname';
        $pr1->typeName = 'string';
        $pr1->value = 'Yash';
        
        $pr2 = new ODataProperty();
        $pr2->name = 'lname';
        $pr2->typeName = 'string';
        $pr2->value = 'Kothari';

        $propCont3_1->odataProperty = array($pr1, $pr2);
		$propCont3_2 = new ODataPropertyContent();
		
		$pr3 = new ODataProperty();
        $pr3->name = 'fname';
        $pr3->typeName = 'string';
        $pr3->value = 'Anu';
        
        $pr4 = new ODataProperty();
        $pr4->name = 'lname';
        $pr4->typeName = 'string';
        $pr4->value = 'Chandy';
		
		$propCont3_2->odataProperty = array ($pr3, $pr4);
		
        $pr5 = new ODataProperty();
        $pr5->name = 'name';
        $pr5->typeName = null;
        $pr5->value = $propCont3_1;
        
        $pr6 = new ODataProperty();
        $pr6->name = 'name';
        $pr6->typeName = null;
        $pr6->value = $propCont3_2;
		
        $propCont3->odataProperty = array($pr5, $pr6);
        $bagProp1->propertyContents = array($propCont3);
        
        $pr7 = new ODataProperty();
        $pr7->name = 'name';
        $pr7->typeName = 'Bag(Name)';
        $pr7->value = $bagProp1;
		
        $prop1 = $pr7;
        
        $propCont4 = new ODataPropertyContent ();
        
        $pr8 = new ODataProperty();
        $pr8->name = 'House_num';
        $pr8->typeName = 'Int';
        $pr8->value = '31';
        
        $pr9= new ODataProperty();
        $pr9->name = 'Street_name';
        $pr9->typeName = 'String';
        $pr9->value = 'Ankur Road';
        
        $propCont4->odataProperty = array ($pr8,$pr9);
        
        $pr9= new ODataProperty();
        $pr9->name = 'Address';
        $pr9->typeName = 'Address';
        $pr9->value = $propCont4;
        
        $prop3 = $pr9;
        
        $pr10= new ODataProperty();
        $pr10->name = 'Pin_Num';
        $pr10->typeName = 'Int';
        $pr10->value = '380013';
        
        $prop4 = $pr10;
        
        $pr11= new ODataProperty();
        $pr11->name = 'Phon_num';
        $pr11->typeName = 'Int';
        $pr11->value = '9665-043-347';
        
        $prop5 = $pr11;
        
        $bagProp3 = new ODataBagContent();
        
        $propCont5 = new ODataPropertyContent();
        $propCont5_1 = new ODataPropertyContent();
        
        $pr12= new ODataProperty();
        $pr12->name = 'Flat_no';
        $pr12->typeName = '';
        $pr12->value = '31';
        
        $pr13= new ODataProperty();
        $pr13->name = 'Street_name';
        $pr13->typeName = '';
        $pr13->value = 'Ankur';
        
        $pr14= new ODataProperty();
        $pr14->name = 'City';
        $pr14->typeName = '';
        $pr14->value = 'Ahmedabad';
        
        $propCont5_1->odataProperty = array($pr12, $pr13, $pr14);
        
        $propCont5_2 = new ODataPropertyContent();

        $pr15= new ODataProperty();
        $pr15->name = 'Flat_no';
        $pr15->typeName = '';
        $pr15->value = '101';
        
        $pr16= new ODataProperty();
        $pr16->name = 'Street_name';
        $pr16->typeName = '';
        $pr16->value = 'Nal Stop';
        
        $pr17= new ODataProperty();
        $pr17->name = 'City';
        $pr17->typeName = '';
        $pr17->value = 'Pune';
        
        $propCont5_2->odataProperty = array ($pr15, $pr16, $pr17);
        
        $pr18= new ODataProperty();
        $pr18->name = 'Address';
        $pr18->typeName = '';
        $pr18->value = $propCont5_1;
        
        $pr19= new ODataProperty();
        $pr19->name = 'Address';
        $pr19->typeName = '';
        $pr19->value = $propCont5_2;
        
        $propCont5->odataProperty = array ($pr18, $pr19);
        $bagProp3->propertyContents = array ($propCont5);

        $pr20= new ODataProperty();
        $pr20->name = 'Addresses';
        $pr20->typeName = 'Bag(Address)';
        $pr20->value = $bagProp3;
        
        $prop6 = $pr20;
        
        $bagProp4 = new ODataBagContent();
        
        $propCont6 = new ODataPropertyContent();
        $propCont6_1 = new ODataPropertyContent();
        $propCont6_1_1 = new ODataPropertyContent();

        $pr21= new ODataProperty();
        $pr21->name = 'apartment1';
        $pr21->typeName = 'String';
        $pr21->value = 'taj residency';
        
        $pr22= new ODataProperty();
        $pr22->name = 'apartment2';
        $pr22->typeName = 'String';
        $pr22->value = 'le-merdian';
        
        $propCont6_1_1->odataProperty = array($pr21, $pr22);

        $pr23= new ODataProperty();
        $pr23->name = 'Street';
        $pr23->typeName = 'String';
        $pr23->value = '123 contoso street';
        
        $pr24= new ODataProperty();
        $pr24->name = 'Appartments';
        $pr24->typeName = '';
        $pr24->value = $propCont6_1_1;
        
        
        $propCont6_1->odataProperty = array($pr23, $pr24);
        $propCont6_2 = new ODataPropertyContent();

        $pr25= new ODataProperty();
        $pr25->name = 'Street';
        $pr25->typeName = 'String';
        $pr25->value = '834 foo street';
        
        $pr26= new ODataProperty();
        $pr26->name = 'Appartment';
        $pr26->typeName = '';
        $pr26->value = '';
        
        $propCont6_2->odataProperty = array ($pr25, $pr26);

        $pr27= new ODataProperty();
        $pr27->name = 'Addresses';
        $pr27->typeName = '';
        $pr27->value = $propCont6_1;
        
        $pr28= new ODataProperty();
        $pr28->name = 'Address';
        $pr28->typeName = '';
        $pr28->value = $propCont6_2;
        
        $propCont6->odataProperty = array ($pr27, $pr28);
        $bagProp4->propertyContents = array ($propCont6);
        
        $pr29= new ODataProperty();
        $pr29->name = 'Addresses';
        $pr29->typeName = 'Bag(SampleModel.Address)';
        $pr29->value = $bagProp4;
        
        $prop_address = $pr29;
        
        $propCont = new ODataPropertyContent ();
        $propCont->odataProperty = array (
            $prop1,
            //$prop2,
            $prop3,
            $prop4,
            $prop5,
            $prop6,
            $prop_address
        ); 
        $odataEntryItem->propertyContent = $propCont;                     
		$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataEntryItem);
    }
    
	/**
	 * Test for top level Entry Item with Expand.
	 */
	public function testWriteExpandEntry()
    {
    	$odataEntryItem = new ODataEntry();
    	$odataEntryItem->id = 'Expand Entry';
    	$odataEntryItem->title = 'Entry Title';

    	$editLink = new ODataLink();
    	$editLink->name = "edit";
    	$editLink->tytle = "Edit Link Title";
    	$editLink->type = "Edit link type";
    	$editLink->url = "Edit Link URL";
    	
    	$odataEntryItem->editLink = $editLink;

    	$selfLink = new ODataLink();
    	$selfLink->name = "self";
    	$selfLink->tytle = "self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link URL";
    	
    	$odataEntryItem->selfLink = $selfLink;
        $odataEntryItem->mediaLinks = array(new ODataMediaLink('Media Link Name', 
                                                      'Edit Media link', 
                                                      'Src Media Link', 
                                                      'Media Content Type', 
                                                      'Media ETag'),new ODataMediaLink('Media Link Name2', 
                                                      'Edit Media link2', 
                                                      'Src Media Link2', 
                                                      'Media Content Type2', 
                                                      'Media ETag2'));
        
        $odataEntryItem->isTopLevel = true;
        $odataLink = new ODataLink();
        $odataLink->isCollection = false;
        $odataLink->isExpanded = true;
        $odataExpandEntry = new ODataEntry();
        
        $odataExpandEntry->id = 'Entry 1';
    	$odataExpandEntry->title = 'Entry Title';
    	
    	$editLink = new ODataLink();
    	$editLink->name = "edit";
    	$editLink->tytle = "Edit Link Title";
    	$editLink->type = "Edit link type";
    	$editLink->url = "Edit Link URL";
    	
    	$odataExpandEntry->editLink = $editLink;
    	
    	$selfLink = new ODataLink();
    	$selfLink->name = "self";
    	$selfLink->tytle = "self Link Title";
    	$selfLink->type = "";
    	$selfLink->url = "Self Link URL";
                                                
    	$odataExpandEntry->selfLink = $selfLink;

        $odataExpandEntry->mediaLinks = array(new ODataMediaLink('Media Link Name', 
                                                      'Edit Media link', 
                                                      'Src Media Link', 
                                                      'Media Content Type', 
                                                      'Media ETag'),new ODataMediaLink('Media Link Name2', 
                                                      'Edit Media link2', 
                                                      'Src Media Link2', 
                                                      'Media Content Type2', 
                                                      'Media ETag2'));
        
        $odataExpandEntry->isTopLevel       = false;
        
        $link = new ODataLink();
        $link->name = "Link Name";
    	$link->tytle = "Link Title";
    	$link->type = "Link Type";
    	$link->url = "Link URL";
        $link->isExpanded       = false;
        
        $odataExpandEntry->links = array();
        $odataExpandEntry->eTag = 'Entry ETag';
        $odataExpandEntry->isMediaLinkEntry = false;
        
        $propCon1 = new ODataPropertyContent ();
        
        $pr1 = new ODataProperty();
        $pr1->name = 'fname';
        $pr1->typeName = 'string';
        $pr1->value = 'Yash';
        
        $pr2 = new ODataProperty();
        $pr2->name = 'lname';
        $pr2->typeName = 'string';
        $pr2->value = 'Kothari';
        
        $propCon1->odataProperty = array ($pr1, $pr2);

        $pr3 = new ODataProperty();
        $pr3->name = 'name';
        $pr3->typeName = 'string';
        $pr3->value = $propCon1;
        
        $prop3 = $pr3;

        $prop4 = new ODataProperty ();
        $prop4->name = 'city';
        $prop4->typeName = 'string';
        $prop4->value = 'Ahmedabad';
        
        $prop5 = new ODataProperty ();
        $prop5->name = 'state';
        $prop5->typeName = 'string';
        $prop5->value = 'Gujarat';
        
        $propCon = new ODataPropertyContent ();
        $propCon->odataProperty = array (
            $prop3,
            $prop4,
            $prop5
        );
        $odataExpandEntry->propertyContent = $propCon;
        
        $odataLink->expandedResult = $odataExpandEntry;
        
        $odataEntryItem->links = array($odataLink);
        $odataEntryItem->eTag = 'Entry ETag';
        $odataEntryItem->isMediaLinkEntry = false;
		
        $bagProp1 = new ODataBagContent ();
        
        $propCont1 = new ODataPropertyContent ();
        $propCont1_1 = new ODataPropertyContent ();
        
        $pr6 = new ODataProperty();
        $pr6->name = 'fname';
        $pr6->typeName = 'string';
        $pr6->value = 'Yash';
        
        $pr7 = new ODataProperty();
        $pr7->name = 'lname';
        $pr7->typeName = 'string';
        $pr7->value = 'Kothari';
        
        $propCont1_1->odataProperty = array($pr6, $pr7);

        $propCont1_2 = new ODataPropertyContent();

        $pr8 = new ODataProperty();
        $pr8->name = 'fname';
        $pr8->typeName = 'string';
        $pr8->value = 'Anu';
        
        $pr9 = new ODataProperty();
        $pr9->name = 'lname';
        $pr9->typeName = 'string';
        $pr9->value = 'Chandy';
        
        $propCont1_2->odataProperty = array ($pr8, $pr9);

        $pr10 = new ODataProperty();
        $pr10->name = 'name';
        $pr10->typeName = null;
        $pr10->value = $propCont1_1;
        
        $pr11 = new ODataProperty();
        $pr11->name = 'name';
        $pr11->typeName = null;
        $pr11->value = $propCont1_2;
        
        $propCont1->odataProperty = array($pr10, $pr11);
        $bagProp1->propertyContents = array($propCont1);

        $pr12 = new ODataProperty();
        $pr12->name = 'name';
        $pr12->typeName = 'Bag(Name)';
        $pr12->value = $bagProp1;
        
        $prop1 = $pr12;
        
        $propCont = new ODataPropertyContent ();
        $propCont->odataProperty = array ($prop1);
        $odataEntryItem->propertyContent = $propCont;             
        
        $oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($odataEntryItem);
    }
    
    /** 
     * test for write top level primitive property.
     */
    public function testPrimitiveProperty(){
    	
    	$odataProperty = new ODataProperty();
    	$odataProperty->name = "Count";
    	$odataProperty->typeName = null;
    	$odataProperty->value = "56";

    	$propCont = new ODataPropertyContent();
    	$propCont->odataProperty = array($odataProperty);
    	$propCont->isTopLevel = true;
    	$odataPropertyContent = $propCont;
    	$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
    	$result = $oWriter->writeRequest($odataPropertyContent);
    	$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Count xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata"
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices"
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">56</d:Count>';
    	//$this->assertXmlStringEqualsXmlString($result, $expected);
    }

	/**
     * test for write top level Complex property.
     */
    public function testComplexProperty(){
    	$propCont1 = new ODataPropertyContent();
    	
        $pr1 = new ODataProperty();
    	$pr1->name = "FlatNo.";
    	$pr1->typeName = null;
    	$pr1->value = "31";
    	 
        $pr2 = new ODataProperty();
    	$pr2->name = "StreetName";
    	$pr2->typeName = null;
    	$pr2->value = "Ankur";
    	
        $pr3 = new ODataProperty();
    	$pr3->name = "City";
    	$pr3->typeName = null;
    	$pr3->value = "Ahmedabad";
    	
    	$propCont1->odataProperty = array($pr1, $pr2, $pr3);
    	
    	$odataProperty = new ODataProperty();
    	$odataProperty->name = "Address";
    	$odataProperty->typeName = "Complex.Address";
    	$odataProperty->value = $propCont1;

    	$propCont = new ODataPropertyContent();
    	$propCont->odataProperty = array($odataProperty);
    	$propCont->isTopLevel = true;
    	$odataPropertyContent = $propCont;
    	$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
    	$result = $oWriter->writeRequest($odataPropertyContent);
    	$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Address m:type="Complex.Address" xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata"
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices"
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <d:FlatNo.>31</d:FlatNo.>
 <d:StreetName>Ankur</d:StreetName>
 <d:City>Ahmedabad</d:City>
</d:Address>';
    	//$this->assertXmlStringEqualsXmlString($result, $expected);
    }
    
/**
	 * 
	 * Testing bag property
	 */
	function testBagProperty()
	{
		//entry
		$entry = new ODataEntry();
		$entry->id = 'http://host/service.svc/Customers(1)';
		$entry->selfLink = 'entry2 self link';
		$entry->title = 'title of entry 2';
		$entry->editLink = 'edit link of entry 2';
		$entry->type = 'SampleModel.Customer';
		$entry->eTag = '';
		$entry->isTopLevel = true;
		
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
		
		$bagEntryProp4ContentProp1Content->odataProperty = array($bagEntryProp4ContentProp1ContentProp1,
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
		
		$bagEntryProp4ContentProp1Content2->odataProperty = array($bagEntryProp4ContentProp1Content2Prop1,
		                                                         $bagEntryProp4ContentProp1Content2Prop2);
		
		//end property content for bagEntryProp4ContentProp1
		

		                                             
		$bagEntryProp4->propertyContents = array($bagEntryProp4ContentProp1Content, 
		                                         $bagEntryProp4ContentProp1Content2
		                                        );
		
		$entryProp4 = new ODataProperty();
		$entryProp4->name = 'Addresses';
		$entryProp4->typeName = 'Bag(SampleModel.Address)';
		$entryProp4->value = $bagEntryProp4;
		//property 4 ends
		
		
		$entryPropContent->odataProperty = array($entryProp1, $entryProp2, $entryProp3, $entryProp4);
		
		$entry->propertyContent = $entryPropContent;
		
		$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
		$result = $oWriter->writeRequest($entry);
		$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/NorthWind.svc" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom" m:etag="">
 <id>http://host/service.svc/Customers(1)</id>
 <title type="text">title of entry 2</title>
 <updated>2011-05-24T15:01:23+05:30</updated>
 <author>
  <name/>
 </author>
 <category term="SampleModel.Customer" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
 <content type="application/xml">
  <m:properties>
   <d:ID m:type="Edm.Int16">1</d:ID>
   <d:Name m:type="Edm.String">mike</d:Name>
   <d:EmailAddresses m:type="Bag(Edm.String)">
    <d:element>mike@foo.com</d:element>
    <d:element>mike2@foo.com</d:element>
   </d:EmailAddresses>
   <d:Addresses m:type="Bag(SampleModel.Address)">
    <d:element>
     <d:Street m:type="Edm.String">123 contoso street</d:Street>
     <d:Apartment m:type="Edm.String">508</d:Apartment>
    </d:element>
    <d:element>
     <d:Street m:type="Edm.String">834 foo street</d:Street>
     <d:Apartment m:type="Edm.String">102</d:Apartment>
    </d:element>
   </d:Addresses>
  </m:properties>
 </content>
</entry>';
		
		//$this->assertXmlStringEqualsXmlString($result, $expected);
	}
    
   

    /**
     * test for write top level Bag of Primitive Property.
     */
   Public function testPrimitiveBagProperty(){ 	
    	$odataProperty = new ODataProperty ();
    	
    	$odataBag = new ODataBagContent();
    	
    	$odataProperty->name = 'Emails';
    	$odataProperty->typeName = 'Bag(edm.String)';
    	$odataProperty->value =  $odataBag;
    	
    	$odataBag->propertyContents = array(
    	                              "yash_kothari@persistent.co.in",
    	                              "v-yashk@microsoft.com",
    	                              "yash2712@gmail.com",
    	                              "y2k2712@yahoo.com");
    	
    	$propCont = new ODataPropertyContent();
    	$propCont->odataProperty = array($odataProperty);
    	$propCont->isTopLevel = true;
    	$odataPropertyContent = $propCont;
    	$oWriter = new ODataWriter('http://localhost/NorthWind.svc', true, 'atom');
    	$result = $oWriter->writeRequest($odataPropertyContent);
    	$expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Emails m:type="Bag(edm.String)" xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" 
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" 
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <d:element>yash_kothari@persistent.co.in</d:element>
 <d:element>v-yashk@microsoft.com</d:element>
 <d:element>yash2712@gmail.com</d:element>
 <d:element>y2k2712@yahoo.com</d:element>
</d:Emails>';
		//$this->assertXmlStringEqualsXmlString($result, $expected);
    }
    
    protected function tearDown()
    {
    }
}
?>