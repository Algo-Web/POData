<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\ObjectModelSerializer;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext as OperationContextAdapter;
use Mockery as m;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Query\IQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\ObjectModel\CynicSerialiser as IronicSerialiser;

class SerialiserWritePrimitiveTest extends SerialiserTestBase
{
    public function testCompareWriteNullResourceProperty()
    {
        $request = $this->setUpRequest();
        $request->shouldReceive('prepareRequestUri')->andReturn('/odata.svc/TestModels');
        $request->shouldReceive('fullUrl')->andReturn('http://localhost/odata.svc/TestModels');

        list($host, $meta, $query) = $this->setUpDataServiceDeps($request);
    }


    /**
     * @param $request
     * @return array
     */
    private function setUpDataServiceDeps($request)
    {
        $metadata = [];
        $metadata['id'] = ['type' => 'integer', 'nullable' => false, 'fillable' => false, 'default' => null];
        $metadata['name'] = ['type' => 'string', 'nullable' => false, 'fillable' => true, 'default' => null];
        $metadata['photo'] = ['type' => 'blob', 'nullable' => true, 'fillable' => true, 'default' => null];

        $op = new OperationContextAdapter($request);
        $host = new ServiceHost($op, $request);

        $meta = m::mock(IMetadataProvider::class);
        $query = m::mock(IQueryProvider::class);

        return array($host, $meta, $query);
    }
}
