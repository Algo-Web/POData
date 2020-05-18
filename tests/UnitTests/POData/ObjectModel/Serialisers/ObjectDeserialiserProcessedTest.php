<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use Mockery as m;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\TestCase;

class ObjectDeserialiserProcessedTest extends TestCase
{
    public function testNewCreationNotProcessed()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);

        $payload = new ODataEntry();
        $foo     = new CynicDeserialiserDummy($meta, $wrapper);

        $this->assertFalse($foo->isEntryProcessed($payload));
    }

    public function testSingletonProcessed()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;
        $foo         = new CynicDeserialiserDummy($meta, $wrapper);

        $this->assertTrue($foo->isEntryProcessed($payload));
    }

    public function testSingletonChildUnprocessed()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $payload->links = [new ODataLink()];

        $child                             = new ODataEntry();
        $payload->links[0]->setExpandedResult(new ODataExpandedResult($child));

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertFalse($foo->isEntryProcessed($payload));
    }

    public function testSingletonChildProcessed()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $payload->links = [new ODataLink()];

        $child                             = new ODataEntry();
        $child->id                         = $key;
        $payload->links[0]->setExpandedResult(new ODataExpandedResult($child));

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertTrue($foo->isEntryProcessed($payload));
    }

    public function testSingletonWithSingleUnexpandedLink()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $payload->links = [new ODataLink()];

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertTrue($foo->isEntryProcessed($payload));
    }

    public function testSingletonWithEmptyFeedResult()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $payload->links                    = [new ODataLink()];
        $payload->links[0]->setExpandedResult(new ODataExpandedResult(new ODataFeed()));

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertTrue($foo->isEntryProcessed($payload));
    }

    public function testSingletonWithFeedWithSingleUnprocessedResult()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $feed            = new ODataFeed();
        $feed->addEntry(new ODataEntry());

        $payload->links                    = [new ODataLink()];
        $payload->links[0]->setExpandedResult(new ODataExpandedResult($feed));

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertFalse($foo->isEntryProcessed($payload));
    }

    public function testSingletonWithChildWithFeedWithSingleUnprocessedResult()
    {
        $meta    = m::mock(IMetadataProvider::class);
        $wrapper = m::mock(ProvidersWrapper::class);
        $key     = m::mock(KeyDescriptor::class);

        $payload     = new ODataEntry();
        $payload->id = $key;

        $child     = new ODataEntry();
        $child->id = $key;

        $feed            = new ODataFeed();
        $feed->addEntry(new ODataEntry());

        $payload->links                    = [new ODataLink()];
        $payload->links[0]->setExpandedResult(new ODataExpandedResult($child));
        $child->links                      = [new ODataLink()];
        $child->links[0]->setExpandedResult(new ODataExpandedResult($feed));

        $foo = new CynicDeserialiserDummy($meta, $wrapper);
        $this->assertFalse($foo->isEntryProcessed($payload));
    }
}
