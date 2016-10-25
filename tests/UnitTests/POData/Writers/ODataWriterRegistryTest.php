<?php

namespace UnitTests\POData\Writers;

use POData\Common\Version;
use POData\Common\MimeTypes;
use POData\Writers\ODataWriterRegistry;
use POData\Writers\IODataWriter;
use PhockitoUnit\PhockitoUnitTestCase;
use Phockito;

class ODataWriterRegistryTest extends PhockitoUnitTestCase
{
    /** @var IODataWriter */
    protected $mockWriter1;

    /** @var IODataWriter */
    protected $mockWriter2;

    public function testConstructor()
    {
        $registry = new ODataWriterRegistry();
        //the registry should start empty, so there should be no matches
        $this->assertNull($registry->getWriter(Version::v1(), MimeTypes::MIME_APPLICATION_JSON));
    }

    public function testRegister()
    {
        $registry = new ODataWriterRegistry();

        $registry->register($this->mockWriter1);
        $registry->register($this->mockWriter2);

        Phockito::when($this->mockWriter2->canHandle(Version::v2(), MimeTypes::MIME_APPLICATION_ATOM))
            ->return(true);

        $this->assertEquals($this->mockWriter2, $registry->getWriter(Version::v2(), MimeTypes::MIME_APPLICATION_ATOM));

        $this->assertNull($registry->getWriter(Version::v1(), MimeTypes::MIME_APPLICATION_ATOM));

        //now clear it, should be no matches
        $registry->reset();
        $this->assertNull($registry->getWriter(Version::v2(), MimeTypes::MIME_APPLICATION_ATOM));
    }
}
