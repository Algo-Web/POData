<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use Carbon\Carbon;
use Mockery as m;
use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;

class ObjectDeserialiserCreateTest extends SerialiserTestBase
{
    public function testCreateSimpleCustomerModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('POST');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('createResourceforResourceSet')->andReturn($model)->once();

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'Country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $propContent->properties['CustomerID']->name = 'CustomerID';
        $propContent->properties['CustomerID']->typeName = 'Edm.String';
        $propContent->properties['CustomerID']->value = '1';
        $propContent->properties['CustomerGuid']->name = 'CustomerGuid';
        $propContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties['CustomerName']->name = 'CustomerName';
        $propContent->properties['CustomerName']->typeName = 'Edm.String';
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
    }

    public function testUpdateSimpleCustomerModel()
    {
        $known = Carbon::create(2017, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($known);

        $request = $this->setUpRequest();
        $request->setMethod('PUT');
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/Customers');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/Customers');

        list($host, $meta, $prov) = $this->setUpDataServiceDeps($request);

        $model = new Customer2();
        $model->CustomerID = 1;
        $model->CustomerGuid = '123e4567-e89b-12d3-a456-426655440000';
        $model->CustomerName = 'CustomerName';
        $model->Country = 'STRAYA';
        $model->Rating = 11;

        $prov->shouldReceive('getResourceFromResourceSet')->andReturn($model)->once();
        $prov->shouldReceive('updateResource')->andReturn($model)->once();

        $propContent = new ODataPropertyContent();
        $propContent->properties = ['CustomerID' => new ODataProperty(), 'CustomerGuid' => new ODataProperty(),
            'CustomerName' => new ODataProperty(), 'Country' => new ODataProperty(), 'Rating' => new ODataProperty(),
            'Photo' => new ODataProperty(), 'Address' => new ODataProperty()];
        $propContent->properties['CustomerID']->name = 'CustomerID';
        $propContent->properties['CustomerID']->typeName = 'Edm.String';
        $propContent->properties['CustomerID']->value = '1';
        $propContent->properties['CustomerGuid']->name = 'CustomerGuid';
        $propContent->properties['CustomerGuid']->typeName = 'Edm.Guid';
        $propContent->properties['CustomerGuid']->value = '123e4567-e89b-12d3-a456-426655440000';
        $propContent->properties['CustomerName']->name = 'CustomerName';
        $propContent->properties['CustomerName']->typeName = 'Edm.String';
        $propContent->properties['CustomerName']->value = 'CustomerName';
        $propContent->properties['Country']->name = 'Country';
        $propContent->properties['Country']->typeName = 'Edm.String';
        $propContent->properties['Country']->value = 'STRAYA';
        $propContent->properties['Rating']->name = 'Rating';
        $propContent->properties['Rating']->typeName = 'Edm.Int32';
        $propContent->properties['Rating']->value = 11;
        $propContent->properties['Photo']->name = 'Photo';
        $propContent->properties['Photo']->typeName = 'Edm.Binary';
        $propContent->properties['Address']->name = 'Address';
        $propContent->properties['Address']->typeName = 'Address';

        $objectResult = new ODataEntry();
        $objectResult->propertyContent = $propContent;
        $objectResult->resourceSetName = 'Customers';
        $objectResult->type = new ODataCategory('Customer');
        $objectResult->id = 'http://localhost/odata.svc/Customers(CustomerID=\'1\',CustomerGuid'
                            .'=guid\'123e4567-e89b-12d3-a456-426655440000\')';

        $cereal = new CynicDeserialiser($meta, $prov);

        $cereal->processPayload($objectResult);
        $this->assertTrue($objectResult->id instanceof KeyDescriptor);
    }

    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);

        $meta = NorthWindMetadata::Create();
        $prov = m::mock(ProvidersWrapper::class);

        return [$host, $meta, $prov];
    }
}
