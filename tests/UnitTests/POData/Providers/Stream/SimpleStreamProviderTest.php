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
use POData\OperationContext\Web\WebOperationContext;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Stream\IStreamProvider;
use POData\Providers\Stream\IStreamProvider2;
use POData\Providers\Stream\SimpleStreamProvider;
use POData\Providers\Stream\StreamProviderWrapper;
use UnitTests\POData\ObjectModel\reusableEntityClass2;
use UnitTests\POData\TestCase;

class SimpleStreamProviderTest extends TestCase
{
    public function testGetReadStreamWithNullResourceInfo()
    {
        $entity = new \DateTime();
        $resourceStreamInfo = null;
        $checkETag = true;
        $eTag = 'eTag';
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = 'stream for DateTime';
        $actual = $foo->getReadStream2($entity, $resourceStreamInfo, $eTag, $checkETag, $context);
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamWithNonNullResourceInfo()
    {
        $entity = new \stdClass();
        $entity->TheStreamWithNoName = 'ELEVATION!';
        $resourceStreamInfo = m::mock(ResourceStreamInfo::class);
        $resourceStreamInfo->shouldReceive('getName')->andReturn('TheStreamWithNoName');
        $checkETag = true;
        $eTag = 'eTag';
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = 'ELEVATION!';
        $actual = $foo->getReadStream2($entity, $resourceStreamInfo, $eTag, $checkETag, $context);
        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultStreamEditMediaUriWithNullResourceInfo()
    {
        $entity = new \DateTime();
        $rType = m::mock(ResourceType::class);
        $resourceStreamInfo = null;
        $context = m::mock(IOperationContext::class)->makePartial();
        $relativeUri = 'all/your/base';

        $foo = new SimpleStreamProvider();

        $expected = 'all/your/base/$value';
        $actual = $foo->getDefaultStreamEditMediaUri($entity, $rType, $resourceStreamInfo, $context, $relativeUri);
        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultStreamEditMediaUriWithNonNullResourceInfo()
    {
        $entity = new \DateTime();
        $rType = m::mock(ResourceType::class);
        $resourceStreamInfo = m::mock(ResourceStreamInfo::class);
        $resourceStreamInfo->shouldReceive('getName')->andReturn('TheStreamWithNoName');
        $context = m::mock(IOperationContext::class)->makePartial();
        $relativeUri = 'all/your/base';

        $foo = new SimpleStreamProvider();

        $expected = 'all/your/base/TheStreamWithNoName';
        $actual = $foo->getDefaultStreamEditMediaUri($entity, $rType, $resourceStreamInfo, $context, $relativeUri);
        $this->assertEquals($expected, $actual);
    }

    public function testgetStreamContentType2WithNullResourceInfo()
    {
        $entity = new \DateTime();
        $resourceStreamInfo = null;
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = '*/*';
        $actual = $foo->getStreamContentType2($entity, $resourceStreamInfo, $context);
        $this->assertEquals($expected, $actual);
    }

    public function testgetStreamContentType2WithNonNullResourceInfo()
    {
        $entity = new \DateTime();
        $resourceStreamInfo = m::mock(ResourceStreamInfo::class);
        $resourceStreamInfo->shouldReceive('getName')->andReturn('TheStreamWithNoName');
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = 'application/octet-stream';
        $actual = $foo->getStreamContentType2($entity, $resourceStreamInfo, $context);
        $this->assertEquals($expected, $actual);
    }

    public function testGetStreamETag2WithNullResourceInfo()
    {
        $entity = new \stdClass();
        $entity->TheStreamWithNoName = 'HorseWithNoName';
        $resourceStreamInfo = null;
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = '65b1447e80fa8ab1dd3c5b31410390a68f42899b';
        $actual = $foo->getStreamETag2($entity, $resourceStreamInfo, $context);
        $this->assertNotEquals($expected, $actual);
    }

    public function testGetStreamETag2WithNonNullResourceInfo()
    {
        $entity = new \stdClass();
        $entity->TheStreamWithNoName = 'HorseWithNoName';
        $resourceStreamInfo = m::mock(ResourceStreamInfo::class);
        $resourceStreamInfo->shouldReceive('getName')->andReturn('TheStreamWithNoName');
        $context = m::mock(IOperationContext::class)->makePartial();

        $foo = new SimpleStreamProvider();

        $expected = '65b1447e80fa8ab1dd3c5b31410390a68f42899b';
        $actual = $foo->getStreamETag2($entity, $resourceStreamInfo, $context);
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamUri2WithNullResourceInfo()
    {
        $entity = new \stdClass();
        $entity->TheStreamWithNoName = 'HorseWithNoName';
        $resourceStreamInfo = null;
        $context = m::mock(IOperationContext::class)->makePartial();
        $relativeUri = 'all/your/base';

        $foo = new SimpleStreamProvider();

        $expected = 'all/your/base/$value';
        $actual = $foo->getReadStreamUri2($entity, $resourceStreamInfo, $context, $relativeUri);
        $this->assertEquals($expected, $actual);
    }

    public function testGetReadStreamUri2WithNonNullResourceInfo()
    {
        $entity = new \stdClass();
        $entity->TheStreamWithNoName = 'HorseWithNoName';
        $resourceStreamInfo = m::mock(ResourceStreamInfo::class);
        $resourceStreamInfo->shouldReceive('getName')->andReturn('TheStreamWithNoName');
        $context = m::mock(IOperationContext::class)->makePartial();
        $relativeUri = 'all/your/base';

        $foo = new SimpleStreamProvider();

        $expected = 'all/your/base/TheStreamWithNoName';
        $actual = $foo->getReadStreamUri2($entity, $resourceStreamInfo, $context, $relativeUri);
        $this->assertEquals($expected, $actual);
    }
}
