<?php
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\..\Resources\NorthWindMetadata.php");
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\Configuration\DataServiceProtocolVersion;
use ODataProducer\Common\InvalidOperationException;
ODataProducer\Common\ClassAutoLoader::register();
class DataServiceConfigurationTest extends PHPUnit_Framework_TestCase
{
    private $_northWindMetadata;
    private $_dataServiceConfiguration;
    
    protected function setUp()
    {
        $this->_northWindMetadata = CreateNorthWindMetadata3::Create();
        $this->_dataServiceConfiguration = new DataServiceConfiguration($this->_northWindMetadata);
    }

    public function testConfiguration1() 
    {
        try {
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setMaxExpandCount(-123);
            } catch (\InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('should be non-negative, negative value \'-123\' passed', $exception->getMessage());            
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'non-negative parameter\' was not thrown for month');
            }
        
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setMaxExpandDepth('ABCS');
            } catch (\InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('should be integer, non-integer value \'ABCS\' passed', $exception->getMessage());            
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'non-integer parameter\' was not thrown for month');
            }

            $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandCount(), PHP_INT_MAX);
            $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandDepth(), PHP_INT_MAX);            
            $this->_dataServiceConfiguration->setMaxExpandCount(6);
            $this->_dataServiceConfiguration->setMaxExpandDepth(10);
            $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandCount(), 6);
            $this->assertEquals($this->_dataServiceConfiguration->getMaxExpandDepth(), 10);
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    public function testConfiguration2()
    {
        try {
            $this->assertEquals($this->_dataServiceConfiguration->getMaxResultsPerCollection(), PHP_INT_MAX);
            $this->_dataServiceConfiguration->setMaxResultsPerCollection(10);
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setEntitySetPageSize('Customers', 5);
            } catch (InvalidOperationException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('mutually exclusive with the specification of \'maximum result per collection\' in configuration', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
            }
            
            $this->assertEquals($this->_dataServiceConfiguration->getMaxResultsPerCollection(), 10);
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());
        }
    }

    public function testConfiguration3()
    {
        try {
            $customersResourceSet = $this->_northWindMetadata->resolveResourceSet('Customers');
            $this->assertNotNull($customersResourceSet);            
            $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 0);
            $this->_dataServiceConfiguration->setEntitySetPageSize('Customers', 5);
            $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 5);
            $this->_dataServiceConfiguration->setEntitySetPageSize('*', 4);
            $ordersResourceSet = $this->_northWindMetadata->resolveResourceSet('Orders');
            $this->assertNotNull($ordersResourceSet);
            $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($ordersResourceSet), 4);
            $this->assertEquals($this->_dataServiceConfiguration->getEntitySetPageSize($customersResourceSet), 5);
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setEntitySetPageSize('NonExistEntitySet', 7);
            } catch(InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->AssertEquals('The given name \'NonExistEntitySet\' was not found in the entity sets', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'non-exist entity set name\' was not thrown for month');
            }
            
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setMaxResultsPerCollection(5);
            } catch (InvalidOperationException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('mutually exclusive with the specification of \'maximum result per collection\' in configuration', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
            }
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());            
        }
    }

    public function testConfiguration4()
    {
        try {
            $customersResourceSet = $this->_northWindMetadata->resolveResourceSet('Customers');
            $this->assertNotNull($customersResourceSet); 
            $this->AssertEquals($this->_dataServiceConfiguration->getEntitySetAccessRule($customersResourceSet), EntitySetRights::NONE);
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::ALL + 1);
            } catch (InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->AssertEquals('The argument \'$rights\' of \'setEntitySetAccessRule\' should be EntitySetRights enum value', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'page size and max result per collection mutual exclusion\' was not thrown for month');
            }
            
            $this->_dataServiceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::READ_ALL);
            $this->AssertEquals($this->_dataServiceConfiguration->getEntitySetAccessRule($customersResourceSet), EntitySetRights::READ_ALL);
            
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->setEntitySetAccessRule('NonExistEntitySet', EntitySetRights::READ_MULTIPLE);
            } catch(InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->AssertEquals('The given name \'NonExistEntitySet\' was not found in the entity sets', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'non-exist entity set name\' was not thrown for month');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());            
        }
    }

    public function testConfiguration5()
    {
        try {
            $this->_dataServiceConfiguration->setAcceptCountRequests(true);
            $this->_dataServiceConfiguration->setAcceptProjectionRequests(true);
            $this->_dataServiceConfiguration->setMaxDataServiceVersion(DataServiceProtocolVersion::V1);
            $this->AssertTrue($this->_dataServiceConfiguration->getAcceptCountRequests());
            $this->AssertTrue($this->_dataServiceConfiguration->getAcceptProjectionRequests());
            $exceptionThrown = false;
            try {
                $this->_dataServiceConfiguration->validateConfigAganistVersion();
                
            } catch (InvalidOperationException $exception) {
                $exceptionThrown = true;
               $this->AssertEquals('The feature \'projection and count request\' is supported only for OData version \'V2\' or greater', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'feature not supported for version\' was not thrown for month');
            }
            
            $this->_dataServiceConfiguration->setMaxDataServiceVersion(DataServiceProtocolVersion::V2);
            $this->_dataServiceConfiguration->validateConfigAganistVersion();
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised . ' . $exception->getMessage());            
        }       
    }

    protected function tearDown()
    {
        unset($this->_dataServiceConfiguration);
        unset($this->_northWindMetadata);
    }
}