<?php

namespace UnitTests\POData\Common;

use POData\Common\Version;

class VersionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testUrl()
    {

        $version1 = new Version(1, 0);
        $version2 = new Version(1, 0);
        $this->assertEquals($version1->compare($version2), 0);

        $version1 = new Version(1, 0);
        $version2 = new Version(1, 1);
        $this->assertEquals($version1->compare($version2), -1);


        $version1 = new Version(1, 0);
        $version2 = new Version(2, 0);
        $this->assertEquals($version1->compare($version2), -1);

        $version1 = new Version(2, 0);
        $version2 = new Version(1, 1);
        $this->assertEquals($version1->compare($version2), 1);

        $version1 = new Version(2, 0);
        $version1->raiseVersion(1, 5);
        $this->assertEquals($version1->getMajor(), 2);
        $this->assertEquals($version1->getMinor(), 0);
        $version1->raiseVersion(3, 0);
        $this->assertEquals($version1->getMajor(), 3);
        $this->assertEquals($version1->getMinor(), 0);
        $version1->raiseVersion(3, 1);
        $this->assertEquals($version1->getMajor(), 3);
        $this->assertEquals($version1->getMinor(), 1);

    }

    protected function tearDown()
    {
    }
}