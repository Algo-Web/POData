<?php

namespace UnitTests\POData\UriProcessor;

use \Mockery\Mockery;

use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\Configuration\ProtocolVersion;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\Url;
use POData\Common\Version;
use POData\Common\ODataException;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\Facets\ServiceHostTestFake;
use UnitTests\POData\Facets\NorthWind1\NorthWindService2;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV1;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV3;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\ResourceProperty;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IOperationContext;

class UriProcessorTestMockery extends \PHPUnit_Framework_TestCase
{
    public function testUriProcessorWithNoSuppliedOperationContext()
    {
        $service = \Mockery::mock(\POData\IService::class);
        $service->shouldReceive('getOperationContext')->andReturnNull();

        $foo = \Mockery::mock(\POData\UriProcessor\UriProcessor::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('executeBase')->andReturnNull()->once();
        $foo->shouldReceive('executeGet')->andReturnNull()->never();
        $foo->shouldReceive('executePost')->andReturnNull()->never();
        $foo->shouldReceive('executePut')->andReturnNull()->never();
        $foo->shouldReceive('executePatch')->andReturnNull()->never();
        $foo->shouldReceive('executeDelete')->andReturnNull()->never();
        $foo->shouldReceive('execute')->passthru();

        $foo->execute();
    }

    public function testUriProcessorWithSuppliedHttpGetOperationContext()
    {
        $opcon = \Mockery::mock(IOperationContext::class);
        $opcon->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::GET());

        $service = \Mockery::mock(\POData\IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($opcon);

        $foo = \Mockery::mock(\POData\UriProcessor\UriProcessor::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('executeBase')->andReturnNull()->never();
        $foo->shouldReceive('executeGet')->andReturnNull()->once();
        $foo->shouldReceive('executePost')->andReturnNull()->never();
        $foo->shouldReceive('executePut')->andReturnNull()->never();
        $foo->shouldReceive('executePatch')->andReturnNull()->never();
        $foo->shouldReceive('executeDelete')->andReturnNull()->never();
        $foo->shouldReceive('execute')->passthru();

        $foo->execute();
    }

    public function testUriProcessorWithSuppliedHttpPutOperationContext()
    {
        $opcon = \Mockery::mock(IOperationContext::class);
        $opcon->shouldReceive('incomingRequest->getMethod')->andReturn(HTTPRequestMethod::PUT());

        $service = \Mockery::mock(\POData\IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($opcon);

        $foo = \Mockery::mock(\POData\UriProcessor\UriProcessor::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $foo->shouldReceive('getService')->andReturn($service);
        $foo->shouldReceive('executeBase')->andReturnNull()->never();
        $foo->shouldReceive('executeGet')->andReturnNull()->never();
        $foo->shouldReceive('executePost')->andReturnNull()->never();
        $foo->shouldReceive('executePut')->andReturnNull()->once();
        $foo->shouldReceive('executePatch')->andReturnNull()->never();
        $foo->shouldReceive('executeDelete')->andReturnNull()->never();
        $foo->shouldReceive('execute')->passthru();

        $foo->execute();
    }
}
