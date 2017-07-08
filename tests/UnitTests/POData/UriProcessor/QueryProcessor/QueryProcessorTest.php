<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor;

use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use UnitTests\POData\TestCase;
use Mockery as m;

class QueryProcessorTest extends TestCase
{
    public function testProcessWithNonNullBlankOrderByString()
    {
        $providers = m::mock(ProvidersWrapper::class)->makePartial();

        $rSet = m::mock(ResourceSetWrapper::class)->makePartial();
        $rType = m::mock(ResourceType::class)->makePartial();
        $rType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getQueryStringItem')->withArgs([ODataConstants::HTTPQUERY_STRING_ORDERBY])->andReturn('');
        $host->shouldReceive('getQueryStringItem')->withAnyArgs()->andReturn(null);

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('isSingleResult')->andReturn(false);
        $request->shouldReceive('isLinkUri')->andReturn(false);
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::ENTITY_SET);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::RESOURCE());
        $request->shouldReceive('getTargetResourceType')->andReturn($rType);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn($rSet);
        $request->shouldReceive('setSkipCount')->andReturnNull()->never();
        $request->shouldReceive('setTopOptionCount')->andReturnNull()->never();
        $request->shouldReceive('setTopCount')->andReturnNull()->never();
        $request->shouldReceive('setInternalSkipTokenInfo')->andReturnNull()->never();
        $request->shouldReceive('setFilterInfo')->andReturnNull()->never();
        $request->shouldReceive('setRootProjectionNode')->andReturnNull()->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($providers);

        QueryProcessor::process($request, $service);
    }

    public function testProcessServiceDocCallWithNoArguments()
    {
        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getQueryStringItem')->withAnyArgs()->andReturn('not null');

        $request = m::mock(RequestDescription::class)->makePartial();
        $request->shouldReceive('getTargetSource')->andReturn(TargetSource::NONE);
        $request->shouldReceive('isSingleResult')->andReturn(true);
        $request->shouldReceive('getTargetKind')->andReturn(TargetKind::NOTHING());
        $request->shouldReceive('getTargetResourceType')->andReturn(null);
        $request->shouldReceive('getTargetResourceSetWrapper')->andReturn(null);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);

        $expected = 'Query options $select, $expand, $filter, $orderby, $inlinecount, $skip, $skiptoken and $top'
                    .' are not supported by this request method or cannot be applied to the requested resource.';
        $actual = null;

        try {
            QueryProcessor::process($request, $service);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
