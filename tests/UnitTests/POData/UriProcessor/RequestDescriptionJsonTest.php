<?php

namespace UnitTests\POData\UriProcessor;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\OperationContext\Web\Illuminate\IncomingIlluminateRequest;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\Writers\Json\JsonLightMetadataLevel;
use POData\Writers\Json\JsonLightODataWriter;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use UnitTests\POData\TestCase;

class RequestDescriptionJsonTest extends TestCase
{
    public function testWriteOdataEntryOverJsonV1()
    {
        $expected = '{
    "d":{
        "__metadata":{
            "uri":"http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","type":"Customer"
        },"CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
    }
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonODataV1Writer();
        $actual = $jsonWriter->write($payload)->getOutput();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonV2()
    {
        $expected = '{
    "d":{
        "__metadata":{
            "uri":"http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","type":"Customer"
        },"CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
    }
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonODataV2Writer();
        $actual = $jsonWriter->write($payload)->getOutput();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithFullMetadata()
    {
        $expected = '{
    "odata.metadata":"http://localhost/odata.svc/$metadata#Customers/@Element","odata.type":"Customer","odata.id":"http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","odata.etag":"","odata.editLink":"Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(JsonLightMetadataLevel::FULL(), 'http://localhost/odata.svc');
        $actual = $jsonWriter->write($payload)->getOutput();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithMinimalMetadata()
    {
        $expected = '{
    "odata.metadata":"http://localhost/odata.svc/$metadata#Customers/@Element","CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(JsonLightMetadataLevel::MINIMAL(), 'http://localhost/odata.svc');
        $actual = $jsonWriter->write($payload)->getOutput();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithNoMetadata()
    {
        $expected = '{
    "CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(JsonLightMetadataLevel::NONE(), 'http://localhost/odata.svc');
        $actual = $jsonWriter->write($payload)->getOutput();
        $this->assertEquals($expected, $actual);
    }

    private function generateODataEntry()
    {
        $propContent = new ODataPropertyContent();
        $propContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $propContent->properties['CustomerID']->name = 'CustomerID';
        $propContent->properties['CustomerID']->typeName = 'Edm.String';
        $propContent->properties['CustomerID']->value = '1';
        $propContent->properties['CustomerGuid']->name = 'CustomerGuid';
        $propContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties['CustomerName']->name = 'CustomerName';
        $propContent->properties['CustomerName']->typeName = 'Edm.String';
        $propContent->properties['CustomerName']->value = ' MakeItPhunkee ';
        $propContent->properties['country']->name = 'country';
        $propContent->properties['country']->typeName = 'Edm.String';
        $propContent->properties['country']->value = ' Oop North ';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->title = new ODataTitle('Customer');
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->editLink = new ODataLink();
        $objectResult->editLink->url = 'Customers(CustomerID=\'1\',CustomerGuid'
                                       .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->editLink->name = 'edit';
        $objectResult->editLink->title = 'Customer';
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->updated = '2017-01-01T00:00:00+00:00';
        $objectResult->isMediaLinkEntry = false;

        return $objectResult;
    }

    private function generateODataURL()
    {

    }
}