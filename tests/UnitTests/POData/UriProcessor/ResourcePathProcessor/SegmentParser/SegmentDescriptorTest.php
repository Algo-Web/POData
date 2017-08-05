<?php

namespace UnitTests\POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use Mockery as m;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use UnitTests\POData\TestCase;

class SegmentDescriptorTest extends TestCase
{
    public function testNewCreationDoesNotHaveKeyValues()
    {
        $foo = new SegmentDescriptor();
        $this->assertFalse($foo->hasKeyValues());
    }
}
