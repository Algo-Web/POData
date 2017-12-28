<?php

namespace UnitTests\POData;

use POData\ObjectModel\ModelDeserialiser;
use POData\Providers\Metadata\SimpleMetadataProvider;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        // clean up static caches, lookups, etc, to break coupling between tests
        $decereal = new ModelDeserialiser();
        $decereal->reset();
        $bar = new SimpleMetadataProvider('Data', 'Data');
        unset($bar);
    }

    public function tearDown()
    {
        if (class_exists('Mockery')) {
            \Mockery::close();
        }
    }
}
