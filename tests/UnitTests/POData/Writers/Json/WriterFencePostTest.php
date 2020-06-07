<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 7/06/20
 * Time: 1:22 PM
 */

namespace UnitTests\POData\Writers\Json;

use POData\ObjectModel\IOData;
use POData\Writers\Atom\AtomODataWriter;
use POData\Writers\IODataWriter;
use POData\Writers\Json\JsonLightODataWriter;
use POData\Writers\Json\JsonODataV1Writer;
use POData\Writers\Json\JsonODataV2Writer;
use UnitTests\POData\TestCase;
use Mockery as m;

/**
 * Class WriterFencePostTest
 * @package UnitTests\POData\Writers\Json
 */
class WriterFencePostTest extends TestCase
{
    /**
     * @return array
     * @throws \Exception
     */
    public function writerProvider(): array
    {
        $result = [];
        $result[] = [new AtomODataWriter(' ', true, 'http://localhost')];
        $result[] = [new JsonODataV1Writer(' ', true)];
        $result[] = [new JsonODataV2Writer(' ', true)];
        $result[] = [new JsonLightODataWriter(' ', true, null, 'http://localhost')];

        return $result;
    }

    /**
     * @dataProvider writerProvider
     *
     * @param IODataWriter $foo
     */
    public function testWriteFencepost(IODataWriter $foo)
    {
        $payload = m::mock(IOData::class)->makePartial();

        $result = $foo->write($payload);
    }
}
