<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/03/20
 * Time: 12:33 AM
 */

namespace UnitTests\POData\Readers\Atom;

use PHPUnit\Framework\TestCase;
use POData\ObjectModel\ODataEntry;
use POData\Readers\Atom\Processors\Entry\PropertyProcessor;

/**
 * Class ProcessPropertyTest
 * @package UnitTests\POData\Readers\Atom
 */
class ProcessPropertyTest extends TestCase
{
    public function testDummyChildComplete()
    {
        $foo = new PropertyProcessor();

        $model = new ODataEntry();
        $foo->handleChildComplete($model);
        $this->assertTrue(true);
    }
}
