<?php

namespace UnitTests\POData\ObjectModel;

use POData\Common\ODataConstants;
use POData\Common\InvalidOperationException;
use POData\ObjectModel\ObjectModelSerializer;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\RequestDescription;
use POData\IService;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\Binary;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\Type\StringType;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\ODataException;
use POData\Common\Messages;
use Mockery as m;

class ObjectModelSerializerTest extends \PHPUnit_Framework_TestCase
{
/*    public function testDoNothingTest()
    {
        $service = m::mock(IService::class);
        $request = m::mock(RequestDescription::class)->makePartial();
        $foo = new ObjectModelSerializer($service, $request);
        $this->assertTrue(true);
    }*/
}
