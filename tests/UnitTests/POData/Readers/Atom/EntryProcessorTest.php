<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/03/20
 * Time: 11:56 PM
 */

namespace UnitTests\POData\Readers\Atom;

use POData\Common\ODataConstants;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataMediaLink;
use POData\Readers\Atom\Processors\EntryProcessor;
use UnitTests\POData\TestCase;

/**
 * Class EntryProcessorTest
 * @package UnitTests\POData\Readers\Atom
 */
class EntryProcessorTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testHandleODataMediaLinkWithActualMediaLink()
    {
        $foo = new EntryProcessor();

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('handleLink');
        $method->setAccessible(true);
        $prop = $reflec->getProperty('oDataEntry');
        $prop->setAccessible(true);
        /** @var ODataEntry $propValue */
        $propValue = $prop->getValue($foo);

        $this->assertFalse($propValue->isMediaLinkEntry);
        $this->assertNull($propValue->mediaLink);
        $this->assertEquals(0, count($propValue->getMediaLinks()));

        $link1 = new ODataMediaLink(
            ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE,
            null,
            null,
            'Bitz',
            '',
            'edit'
        );

        $method->invokeArgs($foo, [$link1]);

        $propValue = $prop->getValue($foo);
        $this->assertTrue($propValue->isMediaLinkEntry);
        $this->assertNotNull($propValue->mediaLink);
        $this->assertEquals(0, count($propValue->getMediaLinks()));
    }

    /**
     * @throws \ReflectionException
     */
    public function testHandleODataMediaLinkWithActualNonMediaLink()
    {
        $foo = new EntryProcessor();

        $reflec = new \ReflectionClass($foo);
        $method = $reflec->getMethod('handleLink');
        $method->setAccessible(true);
        $prop = $reflec->getProperty('oDataEntry');
        $prop->setAccessible(true);
        /** @var ODataEntry $propValue */
        $propValue = $prop->getValue($foo);

        $this->assertFalse($propValue->isMediaLinkEntry);
        $this->assertNull($propValue->mediaLink);
        $this->assertEquals(0, count($propValue->getMediaLinks()));

        $link1 = new ODataMediaLink(
            'foo',
            null,
            null,
            'Bitz',
            '',
            'edit'
        );

        $method->invokeArgs($foo, [$link1]);

        $propValue = $prop->getValue($foo);
        $this->assertFalse($propValue->isMediaLinkEntry);
        $this->assertNull($propValue->mediaLink);
        $this->assertEquals(1, count($propValue->getMediaLinks()));
    }

    public function testEntryProcessorBoomOnBadTag()
    {
        $processor = new EntryProcessor();

        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('FeedProcessor encountered Atom Start Tag with name unknown Tag that we don\'t know how to process');

        $processor->handleStartNode(ODataConstants::ATOM_NAMESPACE, 'unknown Tag', []);
    }
}
