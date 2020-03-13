<?php

declare(strict_types=1);

namespace UnitTests\POData;

use POData\ObjectModel\ModelDeserialiser;
use POData\Providers\Metadata\SimpleMetadataProvider;

/**
 * Class TestCase.
 * @package UnitTests\POData
 */
class TestCase extends \PHPUnit\Framework\TestCase
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
        \Mockery::close();
    }
}
