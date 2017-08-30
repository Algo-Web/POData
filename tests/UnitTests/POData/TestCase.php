<?php

namespace UnitTests\POData;

use POData\ObjectModel\ModelDeserialiser;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $decereal = new ModelDeserialiser();
        $decereal->reset();
    }

    public function tearDown()
    {
        if (class_exists('Mockery')) {
            \Mockery::close();
        }
    }
}
