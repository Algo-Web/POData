<?php

namespace UnitTests\POData\Common;

use POData\Common\Version;
use UnitTests\POData\TestCase;

class VersionTest extends TestCase
{
    public function testCompareSame()
    {
        $version1 = new Version(1, 0);
        $version2 = new Version(1, 0);
        $this->assertEquals(0, $version1->compare($version2));
    }

    public function testCompareMajorSameMinorLess()
    {
        $version1 = new Version(1, 0);
        $version2 = new Version(1, 1);
        $this->assertEquals(-1, $version1->compare($version2));
    }

    public function testCompareMajorSameMinorMore()
    {
        $version1 = new Version(1, 1);
        $version2 = new Version(1, 0);
        $this->assertEquals(1, $version1->compare($version2));
    }

    public function testCompareMajorLessMinorSame()
    {
        $version1 = new Version(1, 0);
        $version2 = new Version(2, 0);
        $this->assertEquals(-1, $version1->compare($version2));
    }

    public function testCompareMajorMoreMinorLess()
    {
        $version1 = new Version(2, 0);
        $version2 = new Version(1, 1);
        $this->assertEquals(1, $version1->compare($version2));
    }

    public function testRaiseVersion()
    {
        $version = new Version(2, 0);

        $version->raiseVersion(1, 5);
        $this->assertEquals(2, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());

        $version->raiseVersion(3, 0);
        $this->assertEquals(3, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());

        $version->raiseVersion(3, 1);
        $this->assertEquals(3, $version->getMajor());
        $this->assertEquals(1, $version->getMinor());
    }

    public function testStaticFunctionV1()
    {
        $version = Version::v1();
        $this->assertEquals(1, $version->getMajor());
        $this->assertEquals(0, $version->getMinor());
    }
}
