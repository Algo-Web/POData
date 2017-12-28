<?php

namespace UnitTests\POData\ObjectModel;

use AlgoWeb\ODataMetadata\MetadataManager;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\SimpleMetadataProvider;
use POData\Providers\Metadata\Type\TypeCode;
use ReflectionClass;
use UnitTests\POData\TestCase;
use Mockery as m;

class ODataFeedTest extends TestCase
{
    public function testSetNextPageLink()
    {
        $foo = new ODataFeed();
        $bar = new ODataLink();
        $bar->url = 'http://localhost/odata.svc';

        $foo->setNextPageLink($bar);
        $this->assertNotNull($foo->getNextPageLink());
    }
}
