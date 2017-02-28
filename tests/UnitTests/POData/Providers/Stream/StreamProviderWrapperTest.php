<?php

namespace UnitTests\POData\Providers\Stream;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\Configuration\IServiceConfiguration;
use POData\IService;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Stream\IStreamProvider;
use POData\Providers\Stream\IStreamProvider2;
use POData\Providers\Stream\StreamProviderWrapper;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class StreamProviderWrapperTest extends TestCase
{
    public function testGetDefaultMediaStreamUriWithNullStreamInfo()
    {
        $mediaUrl = 'https://www.example.org/media/';
        $foo = new StreamProviderWrapper();
        $streamInfo = null;

        $expected = 'https://www.example.org/media/$value';
        $actual = $foo->getDefaultStreamEditMediaUri($mediaUrl, $streamInfo);
        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultMediaStreamUriWithNonNullStreamInfo()
    {
        $mediaUrl = 'https://www.example.org/media/';
        $foo = new StreamProviderWrapper();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $streamInfo->shouldReceive('getName')->andReturn('$size');

        $expected = 'https://www.example.org/media/$size';
        $actual = $foo->getDefaultStreamEditMediaUri($mediaUrl, $streamInfo);
        $this->assertEquals($expected, $actual);
    }

    public function testIsNullETagValid()
    {
        $etag = null;
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertTrue($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertTrue($result);
    }

    public function testIsStarETagValid()
    {
        $etag = '*';
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertTrue($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertTrue($result);
    }

    public function testWeakETagValid()
    {
        $etag = 'W/"Uplink"';
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertTrue($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertTrue($result);
    }

    public function testWeakETagWithoutLastDubQuoteInvalid()
    {
        $etag = 'W/"Uplink';
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertFalse($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertFalse($result);
    }

    public function testStrongETagValid()
    {
        $etag = '"Uplink"';
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertFalse($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertTrue($result);
    }

    public function testStrongETagWithInternalDubQuoteInvalid()
    {
        $etag = '"Up"link"';
        $result = StreamProviderWrapper::isETagValueValid($etag, false);
        $this->assertFalse($result);
        $result = StreamProviderWrapper::isETagValueValid($etag, true);
        $this->assertFalse($result);
    }

    public function testGetStreamContentTypeWithNullResourceStreamInfoV2()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(2, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn('electric-rave')->once();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = null;
        $foo->setService($service);

        $expected = 'return \'IServiceProvider.GetService\' for IStreamProvider2 returns invalid object.';
        $actual = null;

        try {
            $result = $foo->getStreamContentType($data, $streamInfo);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamContentTypeWithNullResourceStreamInfoV3()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn('electric-rave')->once();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = null;
        $foo->setService($service);

        $expected = 'return \'IServiceProvider.GetService\' for IStreamProvider2 returns invalid object.';
        $actual = null;

        try {
            $result = $foo->getStreamContentType($data, $streamInfo);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamContentTypeNullStreamInfoFallbackToV2AndReturnsNullContent()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = null;
        $foo->setService($service);

        $expected = 'The method \'IStreamProvider.GetStreamContentType\' must not return a null or empty string.';
        $actual = null;

        try {
            $result = $foo->getStreamContentType($data, $streamInfo);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamContentTypeHasStreamInfoV3AndReturnsNullContent()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturnNull()->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getStreamContentType($data, $streamInfo);
        $this->assertNull($result);
    }

    public function testGetStreamContentTypeHasStreamInfoV3AndReturnsNullProvider()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn(null)->times(1);
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = 'To support named streams, the data service must implement IServiceProvider.GetService() to'
                    .' return an implementation of IStreamProvider2 or the data source must implement IStreamProvider2.';
        $actual = null;

        try {
            $result = $foo->getStreamContentType($data, $streamInfo);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamContentTypeHasStreamInfoFallbackToV2AndReturnsNullContent()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType')->andReturnNull()->never();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $config = m::mock(IServiceConfiguration::class);
        $config->shouldReceive('getMaxDataServiceVersion')->andReturn(new Version(2, 0));

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration')->andReturn($config);
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->never();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = 'To support named streams, the MaxProtocolVersion of the data service must be set '.
                    'to ProtocolVersion.V3 or above.';
        $actual = null;

        try {
            $result = $foo->getStreamContentType($data, $streamInfo);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamContentTypeHasStreamInfoV3AndReturnsActualData()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $opContext = m::mock(IOperationContext::class);

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->once();

        $host = m::mock(ServiceHost::class)->makePartial();
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');

        $service = m::mock(IService::class);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();
        $service->shouldReceive('getOperationContext')->andReturn($opContext)->once();

        $foo = new StreamProviderWrapper();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getStreamContentType($data, $streamInfo);
        $this->assertEquals('electric-rave', $result);
    }

    public function testGetReadStreamAndThrowODataExceptionCode304()
    {
        $data = new reusableEntityClass2('hammer', 'time!');
        $exception = new ODataException('Inner universe', 304);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->andReturn('W/"electric-rave"')->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andThrow($exception)->once();
        $service->shouldReceive('getHost')->andReturn($host);

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getStreamETag')->andReturn('W/"electric-rave"')->once();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = 'Inner universe';
        $actual = null;

        try {
            $result = $foo->getReadStream($data, null);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamNullStreamNoStreamInfoThrowException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->never();
        $streamProv->shouldReceive('getReadStream2')->andReturnNull()->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProvider2')->andReturn($streamProv)->never();
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getStreamETag')->andReturn('W/"electric-rave"')->never();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = 'IStreamProvider.GetReadStream() must return a valid readable stream.';
        $actual = null;

        try {
            $result = $foo->getReadStream($data, null);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamNullStreamNoStreamInfoReturnNotNull()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->once();
        $streamProv->shouldReceive('getReadStream2')->andReturn($streamProv)->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();
        $host->shouldReceive('setResponseStatusCode')->withArgs([204])->andReturnNull()->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getStreamETag')->andReturn('W/"electric-rave"')->never();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getReadStream($data, $streamInfo);
        $this->assertEquals('electric-rave', $result->getStreamContentType2(null, $streamInfo, $context));
    }

    public function testGetReadStreamNullStreamHasStreamInfoReturnNull()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->never();
        $streamProv->shouldReceive('getReadStream2')->andReturnNull()->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();
        $host->shouldReceive('setResponseStatusCode')->withArgs([204])->andReturnNull()->once();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getStreamETag')->andReturn('W/"electric-rave"')->never();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getReadStream($data, $streamInfo);
        $this->assertNull($result);
    }

    public function testGetStreamETagNoStreamInfoAndGetsNullStreamProviderThrowsException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->never();
        $streamProv->shouldReceive('getReadStream2')->andReturn($streamProv)->never();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn(null)->times(1);

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = Messages::streamProviderWrapperMustImplementIStreamProviderToSupportStreaming();
        $actual = null;

        try {
            $result = $foo->getStreamETag($data, null);
        } catch (ODataException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamETagNoStreamInfoValidStreamProviderBadETagThrowsException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->never();
        $streamProv->shouldReceive('getReadStream2')->andReturn($streamProv)->never();
        $streamProv->shouldReceive('getStreamETag2')->andReturn('W/"elect"ric-rave"');
        $streamProv->shouldReceive('setStreamETag')->andReturn('W/"elect"ric-rave"');

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"elect"ric-rave"');
        $host->shouldReceive('setResponseETag')->andReturn('W/"elect"ric-rave"')->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = Messages::streamProviderWrapperGetStreamETagReturnedInvalidETagFormat();
        $actual = null;

        try {
            $result = $foo->getStreamETag($data, null);
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamETagHasStreamInfoValidStreamProviderGoodETag()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getStreamContentType2')->andReturn('electric-rave')->never();
        $streamProv->shouldReceive('getReadStream2')->andReturn($streamProv)->never();
        $streamProv->shouldReceive('getStreamETag2')->andReturn('W/"electric-rave"');
        $streamProv->shouldReceive('setStreamETag2')->andReturn('W/"electric-rave"');

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context)->once();
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getStreamETag($data, $streamInfo);
        $this->assertEquals('W/"electric-rave"', $result);
    }

    public function testGetReadStreamUriNoStreamInfo()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getReadStreamUri2')->andReturn('https://www.example.org')->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getReadStreamUri($data, null, 'http://www.example.com');
        $this->assertEquals('https://www.example.org', $result);
    }

    public function testGetReadStreamUriNoStreamInfoBadUriThrowException()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getReadStreamUri2')->andReturn('FAIL')->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $expected = Messages::streamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull();
        $actual = null;

        try {
            $result = $foo->getReadStreamUri($data, null, 'http://www.example.com');
        } catch (InvalidOperationException $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamUriHasStreamInfoFallBackToDefault()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getReadStreamUri2')->andReturn(null)->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->andReturn('W/"electric-rave"')->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getDefaultStreamEditMediaUri')->andReturn('https://www.example.org')->never();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getReadStreamUri($data, $streamInfo, 'http://www.example.com');
        $this->assertNull($result);
    }

    public function testGetReadStreamUriNoStreamInfoFallBackToDefault()
    {
        $data = new reusableEntityClass2('hammer', 'time!');

        $streamProv = m::mock(IStreamProvider2::class);
        $streamProv->shouldReceive('getReadStreamUri2')->andReturn(null)->once();

        $context = m::mock(IOperationContext::class);

        $host = m::mock(ServiceHost::class);
        $host->shouldReceive('getRequestIfMatch')->andReturn(null);
        $host->shouldReceive('getRequestIfNoneMatch')->andReturn('electric-rave');
        $host->shouldReceive('getResponseContentType')->andReturn('application/json');
        $host->shouldReceive('getResponseETag')->andReturn('W/"electric-rave"');
        $host->shouldReceive('setResponseETag')->withArgs(['W/"electric-rave"'])->never();

        $service = m::mock(IService::class);
        $service->shouldReceive('getOperationContext')->andReturn($context);
        $service->shouldReceive('getHost')->andReturn($host);
        $service->shouldReceive('getConfiguration->getMaxDataServiceVersion')->andReturn(new Version(3, 0));
        $service->shouldReceive('getStreamProviderX')->andReturn($streamProv)->once();

        $foo = m::mock(StreamProviderWrapper::class)->makePartial();
        $foo->shouldReceive('getDefaultStreamEditMediaUri')->andReturn('https://www.example.org')->once();
        $streamInfo = m::mock(ResourceStreamInfo::class);
        $foo->setService($service);

        $result = $foo->getReadStreamUri($data, null, 'http://www.example.com');
        $this->assertEquals('https://www.example.org', $result);
    }
}
