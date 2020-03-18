<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\UriProcessorNew;

use Mockery as m;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceComplexType;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourcePrimitiveType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\Readers\Atom\AtomODataReader;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\UriProcessorNew;
use ReflectionClass;
use UnitTests\POData\TestCase;

class ExecuteGetTest extends TestCase
{
    public function testExecuteGetOnSingleton()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/whoami');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $singleSet = m::mock(ResourceSetWrapper::class);

        $resourceSet = m::mock(ResourceSet::class);
        $resourceSet->shouldReceive('getName')->andReturn('Objects');

        $singleType = m::mock(ResourceType::class);
        $singleType->shouldReceive('getName')->andReturn('Object');
        $singleType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $singleType->shouldReceive('getCustomState')->andReturn($resourceSet);

        $singleResult = new \DateTime('2017-06-10');
        $singleton    = m::mock(ResourceFunctionType::class);
        $singleton->shouldReceive('getResourceType')->andReturn($singleType);
        $singleton->shouldReceive('get')->andReturn($singleResult);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn($singleton);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($singleSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $origSegments = [ new SegmentDescriptor()];
        $origSegments[0]->setResult(new \DateTime('2017-06-10'));

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();

        $segCount = 1;
        $this->assertEquals($segCount, count($origSegments));
        $this->assertEquals($segCount, count($remixSegments));
        $this->assertEquals($singleResult, $origSegments[0]->getResult());
        $this->assertEquals($singleResult, $remixSegments[0]->getResult());
    }

    public function testExecuteGetOnResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn([])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);

        $result = ['eins', 'zwei', 'polizei'];

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $origSegments = [ new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 1;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnResourceSingle()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $origSegments = [ new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 1;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testGetOnResourceSingleWithExpansion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)?expand=orders');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $rPropType = new Int32();

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(1);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->withArgs(['customers'])->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(1);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $origSegments = [ new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 1;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnMediaResourceBadRequestVersion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/photo');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $rPropType = new Int32();

        $photoStream = m::mock(ResourceStreamInfo::class);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['photo'])->andReturn(null);
        $resourceType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['photo'])->andReturn($photoStream);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(true);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(0);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected = 'Request version \'1.0\' is not supported for the request payload. The only supported'
                    . ' version is \'3.0\'.';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnMediaResourceGoodRequestVersion()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/photo');

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn('3.0');
        $host->shouldReceive('getRequestMaxVersion')->andReturn('3.0');
        $host->shouldReceive('getQueryStringItem')->andReturn(null);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $rPropType = new Int32();

        $photoStream = m::mock(ResourceStreamInfo::class);

        $rProp = m::mock(ResourceProperty::class);
        $rProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE])->andReturn(true);
        $rProp->shouldReceive('isKindOf')->withAnyArgs()->andReturn(false);
        $rProp->shouldReceive('getInstanceType')->andReturn($rPropType);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($rProp)->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['photo'])->andReturn(null);
        $resourceType->shouldReceive('tryResolveNamedStreamByName')->withArgs(['photo'])->andReturn($photoStream);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(true);

        $result = ['eins'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->times(1);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $foo = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $foo);
        $foo->validate('customers(id=1)', $resourceType);

        $origSegments = [ new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setKeyDescriptor($foo);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[1]->setTargetResourceType($resourceType);
        $origSegments[1]->setIdentifier('photo');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetKind(TargetKind::MEDIA_RESOURCE());
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setResult($result);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnFirstSegmentLink()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/' . ODataConstants::URI_COUNT_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $wrapper = m::mock(ProvidersWrapper::class);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected      = 'The request URI is not valid, the segment \'$count\' cannot be applied to the root of the service';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnCountAfterSingleResource()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/' . ODataConstants::URI_COUNT_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->andReturn(null)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected = 'The request URI is not valid, since the segment \'customers\' refers to a singleton,'
                    . ' and the segment \'$count\' can only follow a resource collection.';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnCountAfterResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers/' . ODataConstants::URI_COUNT_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl, '2.0', '3.0');

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getAcceptCountRequests')->andReturn(true)->atLeast(2);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->andReturn(null)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);

        $result = ['eins', 'zwei', 'polizei'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result)->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('customers(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(false);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setTargetKind(TargetKind::PRIMITIVE_VALUE());
        $origSegments[1]->setIdentifier('$count');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[1]->setTargetResourceType($resourceType);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;
        $this->assertEquals($origSegments[0], $remixSegments[0]);
        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnNonterminalCountAfterResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers/' . ODataConstants::URI_COUNT_SEGMENT . '/orders');

        $host = $this->setUpMockHost($reqUrl, $baseUrl, '2.0', '3.0');

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $config->shouldReceive('getAcceptCountRequests')->andReturn(true)->atLeast(2);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->andReturn(null)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getName')->andReturn('Customers');
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);

        $result = ['eins', 'zwei', 'polizei'];

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceSet')->andReturn($result)->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected = 'The request URI is not valid. The segment \'$count\' must be the last segment in the URI'
                    . ' because it is one of the following: $batch, $value, $metadata, $count, a bag property, a'
                    . ' named media resource, or a service operation that does not return a value.';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnComplexType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/address');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $complexType = m::mock(ResourceComplexType::class);

        $complexProp = m::mock(ResourceProperty::class);
        $complexProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::COMPLEX_TYPE())->atLeast(1);
        $complexProp->shouldReceive('getResourceType')->andReturn($complexType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['address'])->andReturn($complexProp)->atLeast(1);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(false);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('customers(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setTargetKind(TargetKind::COMPLEX_OBJECT());
        $origSegments[1]->setIdentifier('address');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setTargetResourceType($complexType);
        $origSegments[1]->setProjectedProperty($complexProp);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnBagOfPrimitivesType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/addresses');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $bagType = m::mock(ResourceComplexType::class);

        $bagProp = m::mock(ResourceProperty::class);
        $bagProp->shouldReceive('getKind')
            ->andReturn(new ResourcePropertyKind(ResourcePropertyKind::BAG | ResourcePropertyKind::PRIMITIVE))
            ->atLeast(1);
        $bagProp->shouldReceive('getResourceType')->andReturn($bagType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['addresses'])->andReturn($bagProp)->atLeast(1);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('customers(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setTargetKind(TargetKind::BAG());
        $origSegments[1]->setIdentifier('addresses');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setTargetResourceType($bagType);
        $origSegments[1]->setProjectedProperty($bagProp);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnBagOfComplexType()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/addresses');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $bagType = m::mock(ResourceComplexType::class);

        $bagProp = m::mock(ResourceProperty::class);
        $bagProp->shouldReceive('getKind')
            ->andReturn(new ResourcePropertyKind(ResourcePropertyKind::BAG | ResourcePropertyKind::COMPLEX_TYPE))
            ->atLeast(1);
        $bagProp->shouldReceive('getResourceType')->andReturn($bagType);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(1);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(1);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(1);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['addresses'])->andReturn($bagProp)->atLeast(1);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(1);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->once();

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('customers(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setTargetKind(TargetKind::BAG());
        $origSegments[1]->setIdentifier('addresses');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setTargetResourceType($bagType);
        $origSegments[1]->setProjectedProperty($bagProp);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnBatchFirstSegment()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/' . ODataConstants::URI_BATCH_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected      = null;
        $expectedClass = null;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnUnallocatedLinks()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/' . ODataConstants::URI_LINK_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected      = 'The request URI is not valid, the segment \'$links\' cannot be applied to the root of the service';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnDanglingLinks()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/' . ODataConstants::URI_LINK_SEGMENT);

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $expected = 'The request URI is not valid. There must a segment specified after the \'$links\' segment'
                    . ' and the segment must refer to a entity resource.';
        $expectedClass = ODataException::class;
        $actual        = null;
        $actualClass   = null;

        try {
            UriProcessorNew::process($service);
        } catch (\Exception $e) {
            $actualClass = get_class($e);
            $actual      = $e->getMessage();
        }
        $this->assertEquals($expectedClass, $actualClass);
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testExecuteGetOnNonDanglingLinks()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/' . ODataConstants::URI_LINK_SEGMENT . '/orders');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('isKindOf')->passthru();
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());

        $ordersProp = m::mock(ResourceProperty::class);
        $ordersProp->shouldReceive('isKindOf')->with(m::on(function (ResourcePropertyKind $arg) {
            return ResourcePropertyKind::PRIMITIVE == $arg->getValue();
        }))->andReturn(true);
        $ordersProp->shouldReceive('isKindOf')->andReturn(false);
        $ordersProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());
        $ordersProp->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);

        $ordersSet = m::mock(ResourceSetWrapper::class);
        $ordersSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $ordersSet->shouldReceive('getResourceSetPageSize')->andReturn(200);
        $ordersSet->shouldReceive('getName')->andReturn('Orders');

        $rClass = m::mock(ReflectionClass::class);
        $rClass->shouldReceive('newInstanceArgs')->andReturn(new \stdClass())->atLeast(4);

        $ordersType = m::mock(ResourceEntityType::class);
        $ordersType->shouldReceive('getName')->andReturn('Order');
        $ordersType->shouldReceive('getFullName')->andReturn('Data.Order');
        $ordersType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $ordersType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $ordersType->shouldReceive('getInstanceType')->andReturn($rClass)->atLeast(2);
        $ordersType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($ordersProp)->atLeast(2);
        $ordersType->shouldReceive('resolveProperty')->withAnyArgs()->andReturn(null)->atLeast(2);

        $bagProp = m::mock(ResourceProperty::class);
        $bagProp->shouldReceive('getResourceType')->andReturn($ordersType);
        $bagProp->shouldReceive('getTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $bagProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE());

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType')->andReturn($rClass)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['orders'])->andReturn($bagProp)->atLeast(2);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $result = 'eins';

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceSetWrapperForNavigationProperty')->andReturn($ordersSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->atLeast(2);
        $wrapper->shouldReceive('getResourceSet')->andReturn('foobar')->atLeast(2);
        $wrapper->shouldReceive('getRelatedResourceReference')->andReturn('mosh around the world')->atLeast(2);
        $wrapper->shouldReceive('getRelatedResourceSet')->andReturn('foobar')->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('customers(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setIdentifier('$links');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[1]->setTargetKind(TargetKind::LINK());
        $origSegments[1]->setResult('eins');
        $origSegments[1]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[1]->setTargetResourceType($resourceType);
        $origSegments[1]->setKeyDescriptor($bar);
        $origSegments[1]->setNext($origSegments[2]);
        $origSegments[1]->setPrevious($origSegments[0]);
        $origSegments[2]->setIdentifier('orders');
        $origSegments[2]->setSingleResult(false);
        $origSegments[2]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[2]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[2]->setTargetResourceSetWrapper($ordersSet);
        $origSegments[2]->setTargetResourceType($ordersType);
        $origSegments[2]->setProjectedProperty($bagProp);
        $origSegments[2]->setResult('foobar');
        $origSegments[2]->setPrevious($origSegments[1]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 3;
        $this->assertEquals($origSegments[2], $remixSegments[2]);
        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnPrimitiveValueOfEntity()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/customers(id=1)/id/$value');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $primType = m::mock(ResourcePrimitiveType::class);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('isKindOf')->with(m::on(function (ResourcePropertyKind $arg) {
            return ResourcePropertyKind::PRIMITIVE == $arg->getValue();
        }))->andReturn(true)->atLeast(2);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $keyProp->shouldReceive('getResourceType')->andReturn($primType);
        $keyProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Customer');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($keyProp)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $result = 'eins';

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('orders(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('customers');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setIdentifier('id');
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setTargetKind(TargetKind::PRIMITIVE());
        $origSegments[1]->setProjectedProperty($keyProp);
        $origSegments[1]->setTargetResourceType($primType);
        $origSegments[1]->setNext($origSegments[2]);
        $origSegments[1]->setPrevious($origSegments[0]);
        $origSegments[2]->setIdentifier('$value');
        $origSegments[2]->setTargetResourceType($primType);
        $origSegments[2]->setSingleResult(true);
        $origSegments[2]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[2]->setTargetKind(TargetKind::PRIMITIVE_VALUE());
        $origSegments[2]->setProjectedProperty($keyProp);
        $origSegments[2]->setPrevious($origSegments[1]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 3;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetWhenHeadingUpToSingleResult()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/orders(id=1)/customer');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $custType = m::mock(ResourceEntityType::class);
        $custType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());

        $custSet = m::mock(ResourceSetWrapper::class);
        $custSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $custSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $custSet->shouldReceive('hasBagProperty')->andReturn(true);

        $primType = m::mock(ResourcePrimitiveType::class);

        $custProp = m::mock(ResourceProperty::class);
        $custProp->shouldReceive('getResourceType')->andReturn($custType);
        $custProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCE_REFERENCE());

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE()])->andReturn(true)->atLeast(2);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $keyProp->shouldReceive('getResourceType')->andReturn($primType);
        $keyProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Order');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($keyProp)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['customer'])->andReturn($custProp)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $result = 'eins';

        $relatedResult = 'zwei';

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceSetWrapperForNavigationProperty')->andReturn($custSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->atLeast(2);
        $wrapper->shouldReceive('getRelatedResourceReference')->andReturn($relatedResult)->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('orders(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('orders');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setIdentifier('customer');
        $origSegments[1]->setResult($relatedResult);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[1]->setTargetResourceSetWrapper($custSet);
        $origSegments[1]->setTargetResourceType($custType);
        $origSegments[1]->setProjectedProperty($custProp);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    public function testExecuteGetOnResourceFromRelatedResourceSet()
    {
        $baseUrl = new Url('http://localhost/odata.svc');
        $reqUrl  = new Url('http://localhost/odata.svc/orders(id=1)/customer(id=1)');

        $host = $this->setUpMockHost($reqUrl, $baseUrl);

        $request = $this->setUpMockRequest();

        $context = m::mock(IOperationContext::class);
        $context->shouldReceive('incomingRequest')->andReturn($request);

        $primType = m::mock(ResourcePrimitiveType::class);

        $iType = m::mock(IType::class);
        $iType->shouldReceive('isCompatibleWith')->andReturn(true)->atLeast(2);

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(3, 0));

        $keyProp = m::mock(ResourceProperty::class);
        $keyProp->shouldReceive('isKindOf')->withArgs([ResourcePropertyKind::PRIMITIVE()])->andReturn(true)->atLeast(2);
        $keyProp->shouldReceive('getInstanceType')->andReturn($iType);
        $keyProp->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::PRIMITIVE());
        $keyProp->shouldReceive('getResourceType')->andReturn($primType);
        $keyProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::PRIMITIVE());

        $custType = m::mock(ResourceEntityType::class);
        $custType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $custType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);

        $custSet = m::mock(ResourceSetWrapper::class);
        $custSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $custSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $custSet->shouldReceive('hasBagProperty')->andReturn(true);

        $custProp = m::mock(ResourceProperty::class);
        $custProp->shouldReceive('getResourceType')->andReturn($custType);
        $custProp->shouldReceive('getKind')->andReturn(ResourcePropertyKind::RESOURCESET_REFERENCE());

        $resourceType = m::mock(ResourceEntityType::class);
        $resourceType->shouldReceive('getName')->andReturn('Order');
        $resourceType->shouldReceive('getResourceTypeKind')->andReturn(ResourceTypeKind::ENTITY());
        $resourceType->shouldReceive('getKeyProperties')->andReturn(['id' => $keyProp])->atLeast(2);
        $resourceType->shouldReceive('getInstanceType->newInstance')->andReturn(new \stdClass())->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['id'])->andReturn($keyProp)->atLeast(2);
        $resourceType->shouldReceive('resolveProperty')->withArgs(['customer'])->andReturn($custProp)->atLeast(2);

        $resourceSet = m::mock(ResourceSetWrapper::class);
        $resourceSet->shouldReceive('getResourceType')->andReturn($resourceType);
        $resourceSet->shouldReceive('checkResourceSetRightsForRead')->andReturnNull()->atLeast(2);
        $resourceSet->shouldReceive('hasNamedStreams')->andReturn(false);
        $resourceSet->shouldReceive('hasBagProperty')->andReturn(true);
        $resourceSet->shouldReceive('getResourceSetPageSize')->andReturn(200);

        $result = 'eins';

        $relatedResult = 'zwei';

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('resolveSingleton')->andReturn(null);
        $wrapper->shouldReceive('resolveResourceSet')->andReturn($resourceSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceSetWrapperForNavigationProperty')->andReturn($custSet)->atLeast(2);
        $wrapper->shouldReceive('getResourceFromResourceSet')->andReturn($result)->atLeast(2);
        $wrapper->shouldReceive('getResourceFromRelatedResourceSet')->andReturn($relatedResult)->atLeast(2);

        $service = $this->setUpMockService($host, $wrapper, $context, $config);

        $remix = UriProcessorNew::process($service);

        $bar = null;
        $foo = null;
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $foo);
        $foo->validate('customers(id=1)', $resourceType);
        $key = KeyDescriptor::tryParseKeysFromKeyPredicate('id=1', $bar);
        $bar->validate('orders(id=1)', $resourceType);

        $origSegments = [new SegmentDescriptor(), new SegmentDescriptor()];
        $origSegments[0]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[0]->setResult($result);
        $origSegments[0]->setSingleResult(true);
        $origSegments[0]->setIdentifier('orders');
        $origSegments[0]->setTargetSource(TargetSource::ENTITY_SET());
        $origSegments[0]->setTargetResourceSetWrapper($resourceSet);
        $origSegments[0]->setTargetResourceType($resourceType);
        $origSegments[0]->setKeyDescriptor($bar);
        $origSegments[0]->setNext($origSegments[1]);
        $origSegments[1]->setIdentifier('customer');
        $origSegments[1]->setResult($relatedResult);
        $origSegments[1]->setTargetSource(TargetSource::PROPERTY());
        $origSegments[1]->setSingleResult(true);
        $origSegments[1]->setTargetKind(TargetKind::RESOURCE());
        $origSegments[1]->setTargetResourceSetWrapper($custSet);
        $origSegments[1]->setTargetResourceType($custType);
        $origSegments[1]->setKeyDescriptor($foo);
        $origSegments[1]->setProjectedProperty($custProp);
        $origSegments[1]->setPrevious($origSegments[0]);

        $remix->execute();
        $remixSegments = $remix->getRequest()->getSegments();
        $segCount      = 2;

        $this->checkSegmentEquality($segCount, $origSegments, $remixSegments);
    }

    /**
     * @param $host
     * @param $wrapper
     * @param $context
     * @param $config
     * @return m\MockInterface
     */
    private function setUpMockService($host, $wrapper, $context, $config)
    {
        $meta = m::mock(IMetadataProvider::class);

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getProvidersWrapper')->andReturn($wrapper);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getMetadataProvider')->andReturn($meta);
        $readerRegistery = new ODataReaderRegistry();
        $readerRegistery->register(new AtomODataReader());
        $service->shouldReceive('getODataReaderRegistry')->andReturn($readerRegistery);
        return $service;
    }

    /**
     * @param $reqUrl
     * @param $baseUrl
     * @param  mixed           $requestVer
     * @param  mixed           $maxVer
     * @return m\MockInterface
     */
    private function setUpMockHost($reqUrl, $baseUrl, $requestVer = '1.0', $maxVer = '3.0')
    {
        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getAbsoluteRequestUri')->andReturn($reqUrl);
        $host->shouldReceive('getAbsoluteServiceUri')->andReturn($baseUrl);
        $host->shouldReceive('getRequestVersion')->andReturn($requestVer);
        $host->shouldReceive('getRequestMaxVersion')->andReturn($maxVer);
        $host->shouldReceive('getQueryStringItem')->andReturn(null);
        return $host;
    }

    /**
     * @return m\MockInterface
     */
    private function setUpMockRequest()
    {
        $request = m::mock(IHTTPRequest::class);
        $request->shouldReceive('getMethod')->andReturn(HTTPRequestMethod::GET());
        $request->shouldReceive('getAllInput')->andReturn(null);
        return $request;
    }

    /**
     * @param $segCount
     * @param $origSegments
     * @param $remixSegments
     */
    private function checkSegmentEquality($segCount, $origSegments, $remixSegments)
    {
        $this->assertEquals($segCount, count($origSegments));
        $this->assertEquals($segCount, count($remixSegments));

        for ($i = 0; $i < $segCount; $i++) {
            $strI = strval($i);
            $this->assertEquals($origSegments[$i]->getTargetKind(), $remixSegments[$i]->getTargetKind(), $strI);
            $this->assertEquals($origSegments[$i]->getResult(), $remixSegments[$i]->getResult(), $strI);
            $this->assertEquals($origSegments[$i]->isSingleResult(), $remixSegments[$i]->isSingleResult(), $strI);
            $this->assertEquals($origSegments[$i]->getNext(), $remixSegments[$i]->getNext(), $strI);
            $this->assertEquals($origSegments[$i]->getPrevious(), $remixSegments[$i]->getPrevious(), $strI);
        }
    }
}
