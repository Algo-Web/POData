<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/03/20
 * Time: 12:08 AM.
 */
namespace UnitTests\POData\Readers\Atom;

use POData\Common\ODataConstants;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataMediaLink;
use POData\Readers\Atom\Processors\Entry\LinkProcessor;
use UnitTests\POData\TestCase;

/**
 * Class LinkProcessorTest.
 * @package UnitTests\POData\Readers\Atom
 */
class LinkProcessorTest extends TestCase
{
    public function testHandleChildCompleteBlankAttributes()
    {
        $foo = new LinkProcessor([]);

        $obj = new ODataFeed();

        $foo->handleChildComplete($obj);

        $result = $foo->getObjetModelObject()->getExpandResult();
        $this->assertTrue($result->feed instanceof ODataFeed);
        $this->assertNull($result->entry);
    }

    public function testConstructorWithEditMediaRelation()
    {
        $parms =
            [strtoupper(ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME) =>
                ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE];

        $foo    = new LinkProcessor($parms);
        $result = $foo->getObjetModelObject();
        $this->assertTrue($result instanceof ODataMediaLink);
    }
}
