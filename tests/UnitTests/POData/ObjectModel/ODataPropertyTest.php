<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 14/03/20
 * Time: 8:34 PM.
 */
namespace UnitTests\POData\ObjectModel;

use POData\ObjectModel\ODataProperty;
use UnitTests\POData\TestCase;

class ODataPropertyTest extends TestCase
{
    public function testIsNull()
    {
        $foo = new ODataProperty('','',null);

        $this->assertTrue($foo->isNull());
    }

    public function testIsNotNull()
    {
        $foo        = new ODataProperty('','','var');
        $foo->value = 'var';

        $this->assertNull($foo->isNull());
    }
}
