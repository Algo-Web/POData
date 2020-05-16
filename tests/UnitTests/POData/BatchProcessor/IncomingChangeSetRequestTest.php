<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/05/20
 * Time: 5:33 PM.
 */
namespace UnitTests\POData\BatchProcessor;

use Mockery as m;
use POData\BatchProcessor\IncomingChangeSetRequest;
use UnitTests\POData\TestCase;

class IncomingChangeSetRequestTest extends TestCase
{
    public function testApplyContentID()
    {
        $foo = m::mock(IncomingChangeSetRequestDummy::class)->makePartial();

        $contentId      = "1";
        $contentIdValue = 'WTF';

        $foo->setRawUrl('$1/Orders');

        $foo->applyContentId($contentId, $contentIdValue);

        $expected = 'WTF/Orders';
        $actual   = $foo->getRawUrl();
        $this->assertEquals($expected, $actual);
    }
}
