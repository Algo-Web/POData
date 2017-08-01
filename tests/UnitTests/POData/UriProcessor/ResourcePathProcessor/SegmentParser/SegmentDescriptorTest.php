<?php

namespace UnitTests\POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use Mockery as m;
use UnitTests\POData\TestCase;

class SegmentDescriptorTest extends TestCase
{
    public function testNewCreationDoesNotHaveKeyValues()
    {
        $foo = new SegmentDescriptor();
        $this->assertFalse($foo->hasKeyValues());
    }
}
