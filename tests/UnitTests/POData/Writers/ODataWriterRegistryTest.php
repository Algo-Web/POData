<?php

namespace UnitTests\POData\Writers;

use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\Version;
use POData\Writers\IODataWriter;
use POData\Writers\ODataWriterRegistry;
use UnitTests\POData\TestCase;

class ODataWriterRegistryTest extends TestCase
{
    public function testConstructor()
    {
        $registry = new ODataWriterRegistry();
        //the registry should start empty, so there should be no matches
        $this->assertNull($registry->getWriter(Version::v1(), MimeTypes::MIME_APPLICATION_JSON));
    }

    public function testRegister()
    {
        $writer1 = m::mock(IODataWriter::class)->makePartial();
        $writer1->shouldReceive('canHandle')->andReturn(false);
        $writer2 = m::mock(IODataWriter::class)->makePartial();
        $writer2->shouldReceive('canHandle')->withArgs([Version::v2(), MimeTypes::MIME_APPLICATION_ATOM])
            ->andReturn(true);
        $writer2->shouldReceive('canHandle')->andReturn(false);

        $registry = new ODataWriterRegistry();

        $registry->register($writer1);
        $registry->register($writer2);

        $this->assertEquals($writer2, $registry->getWriter(Version::v2(), MimeTypes::MIME_APPLICATION_ATOM));

        $this->assertNull($registry->getWriter(Version::v1(), MimeTypes::MIME_APPLICATION_ATOM));

        //now clear it, should be no matches
        $registry->reset();
        $this->assertNull($registry->getWriter(Version::v2(), MimeTypes::MIME_APPLICATION_ATOM));
    }
}
