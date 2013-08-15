<?php
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
use ODataProducer\Common\Version;
ODataProducer\Common\ClassAutoLoader::register();
class VersionTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    public function testUrl()
    {
        try {
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
            
        } catch (\Exception $exception) {
            $this->fail('An expected Exception has not been raised' . $exception->getMessage());
        } 
    }

    protected function tearDown()
    {
    }
}
?>