<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 15/05/20
 * Time: 5:33 PM.
 */
namespace UnitTests\POData;

use POData\Pluralizer;

/**
 * Class PluralizerTest.
 * @package UnitTests\POData
 */
class PluralizerTest extends TestCase
{
    public function testPluraliseSingular()
    {
        $single = 'single';

        $expected = 'single';

        $actual = Pluralizer::plural($single, 1);
        $this->assertEquals($expected, $actual);
    }

    public function testPluraliseUncountable()
    {
        $single = 'sheep';

        $expected = 'sheep';

        $actual = Pluralizer::plural($single, 2);
        $this->assertEquals($expected, $actual);
    }
}
