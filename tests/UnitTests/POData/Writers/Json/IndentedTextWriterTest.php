<?php

declare(strict_types=1);

namespace UnitTests\POData\Writers\Json;

use POData\Writers\Json\IndentedTextWriter;
use UnitTests\POData\Writers\BaseWriterTest;

/**
 * Class IndentedTextWriterTest
 * @package UnitTests\POData\Writers\Json
 */
class IndentedTextWriterTest extends BaseWriterTest
{
    public function testWriteLine()
    {
        $writer = new IndentedTextWriter('', PHP_EOL, true);

        $result = $writer->writeLine();
        $this->assertSame($writer, $result);
        $this->assertEquals(PHP_EOL, $writer->getResult());
    }

    public function testWrite()
    {
        $writer = new IndentedTextWriter('', PHP_EOL, true);

        $result = $writer->writeValue(' doggy ');

        $this->assertSame($writer, $result);
        $this->assertEquals(' doggy ', $writer->getResult());
    }

    public function testWriteTrimmed()
    {
        $writer = new IndentedTextWriter('', PHP_EOL, true);

        $result = $writer->writeTrimmed(' doggy ');

        $this->assertSame($writer, $result);
        $this->assertEquals('doggy', $writer->getResult());
    }

    public function testWriteIndents()
    {
        $writer = new IndentedTextWriter('', PHP_EOL, true);

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
        $expected = 'indented1x' . PHP_EOL . '        indented2x' . PHP_EOL . '    indented1xtrimmed' . PHP_EOL . 'indented0x';

        $this->assertEquals($expected, $writer->getResult());
    }
}
