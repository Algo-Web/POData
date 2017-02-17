<?php

namespace UnitTests\POData\Writers\Json;

use POData\Writers\Json\IndentedTextWriter;
use UnitTests\POData\TestCase;

class IndentedTextWriterTest extends TestCase
{
    public function testWriteLine()
    {
        $writer = new IndentedTextWriter('');

        $result = $writer->writeLine();
        $this->assertSame($writer, $result);
        $this->assertEquals("\n", $writer->getResult());
    }

    public function testWrite()
    {
        $writer = new IndentedTextWriter('');

        $result = $writer->writeValue(' doggy ');

        $this->assertSame($writer, $result);
        $this->assertEquals(' doggy ', $writer->getResult());
    }

    public function testWriteTrimmed()
    {
        $writer = new IndentedTextWriter('');

        $result = $writer->writeTrimmed(' doggy ');

        $this->assertSame($writer, $result);
        $this->assertEquals('doggy', $writer->getResult());
    }

    public function testWriteIndents()
    {
        $writer = new IndentedTextWriter('');

        $result = $writer->increaseIndent();
        $this->assertSame($writer, $result);

        $writer->writeValue('indented1x');
        $writer->writeLine();

        $writer->increaseIndent();
        $writer->writeValue('indented2x');
        $writer->writeLine();

        $result = $writer->decreaseIndent();
        $this->assertSame($writer, $result);
        $writer->writeValue('indented1x');
        $writer->writeTrimmed('  trimmed  ');
        $writer->writeLine();

        $writer->decreaseIndent();
        $writer->decreaseIndent();
        $writer->decreaseIndent();
        $writer->decreaseIndent();
        $writer->decreaseIndent();
        $writer->decreaseIndent();
        $writer->decreaseIndent();

        $writer->writeValue('indented0x');
        $expected = "indented1x\n        indented2x\n    indented1xtrimmed\nindented0x";

        $this->assertEquals($expected, $writer->getResult());
    }
}
