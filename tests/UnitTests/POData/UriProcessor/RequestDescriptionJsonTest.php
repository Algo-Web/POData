<?php

declare(strict_types=1);

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

        $jsonWriter = new JsonODataV1Writer(PHP_EOL, true);
        $actual     = $jsonWriter->write($payload)->getOutput();
        $this->assertJsonStringEqualsJsonString($expected, $actual);
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

        $jsonWriter = new JsonODataV2Writer(PHP_EOL, true);
        $actual     = $jsonWriter->write($payload)->getOutput();
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithFullMetadata()
    {
        $expected = '{
    "odata.metadata":"http://localhost/odata.svc/$metadata#Customers/@Element","odata.type":"Customer","odata.id":"http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","odata.etag":"","odata.editLink":"Customers(CustomerID=\'1\',CustomerGuid=guid\'123e4567-e89b-12d3-a456-426655440000\')","CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::FULL(), 'http://localhost/odata.svc');
        $actual     = $jsonWriter->write($payload)->getOutput();
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithMinimalMetadata()
    {
        $expected = '{
    "odata.metadata":"http://localhost/odata.svc/$metadata#Customers/@Element","CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::MINIMAL(), 'http://localhost/odata.svc');
        $actual     = $jsonWriter->write($payload)->getOutput();
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    public function testWriteOdataEntryOverJsonLightWithNoMetadata()
    {
        $expected = '{
    "CustomerID":"1","CustomerGuid":"123e4567-e89b-12d3-a456-426655440000","CustomerName":" MakeItPhunkee ","country":" Oop North ","Rating":null,"Photo":null,"Address":null
}';
        $payload = $this->generateODataEntry();

        $jsonWriter = new JsonLightODataWriter(PHP_EOL, true, JsonLightMetadataLevel::NONE(), 'http://localhost/odata.svc');
        $actual     = $jsonWriter->write($payload)->getOutput();
        $this->assertJsonStringEqualsJsonString($expected, $actual);
    }

    private function generateODataEntry()
    {
        $propContent             = new ODataPropertyContent(
            [
                'CustomerID' => new ODataProperty(),
                'CustomerGuid' => new ODataProperty(),
                'CustomerName' => new ODataProperty(),
                'country' => new ODataProperty(),
                'Rating' => new ODataProperty(),
                'Photo' => new ODataProperty(),
                'Address' => new ODataProperty()]
        );
        $propContent['CustomerID']->name       = 'CustomerID';
        $propContent['CustomerID']->typeName   = 'Edm.String';
        $propContent['CustomerID']->value      = '1';
        $propContent['CustomerGuid']->name     = 'CustomerGuid';
        $propContent['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent['CustomerGuid']->value    = '123e4567-e89b-12d3-a456-426655440000';
        $propContent['CustomerName']->name     = 'CustomerName';
        $propContent['CustomerName']->typeName = 'Edm.String';
        $propContent['CustomerName']->value    = ' MakeItPhunkee ';
        $propContent['country']->name          = 'country';
        $propContent['country']->typeName      = 'Edm.String';
        $propContent['country']->value         = ' Oop North ';
        $propContent['Rating']->name           = 'Rating';
        $propContent['Rating']->typeName       = 'Edm.Int32';
        $propContent['Photo']->name            = 'Photo';
        $propContent['Photo']->typeName        = 'Edm.Binary';
        $propContent['Address']->name          = 'Address';
        $propContent['Address']->typeName      = 'Address';

        $objectResult     = new ODataEntry();
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')';
        $objectResult->title         = new ODataTitle('Customer');
        $objectResult->type          = new ODataCategory('Customer');
        $objectResult->editLink      = new ODataLink('edit', 'Customer', null, 'Customers(CustomerID=\'1\',CustomerGuid'
            . '=guid\'123e4567-e89b-12d3-a456-426655440000\')');
        $objectResult->propertyContent  = $propContent;
        $objectResult->resourceSetName  = 'Customers';
        $objectResult->updated          = '2017-01-01T00:00:00+00:00';
        $objectResult->isMediaLinkEntry = false;

        return $objectResult;
    }
}
