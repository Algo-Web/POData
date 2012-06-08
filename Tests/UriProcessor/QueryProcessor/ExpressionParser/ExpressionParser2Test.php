<?php
use ODataProducer\Providers\Metadata\ResourceProperty;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\..\..\..\Resources\NorthWindMetadata.php");
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use ODataProducer\Common\ODataException;
ODataProducer\Common\ClassAutoLoader::register();
class PHPExpressionProviderTest extends PHPUnit_Framework_TestCase
{
    private $_northWindMetadata;
    
    protected function setUp()
    {        
        $this->_northWindMetadata = CreateNorthWindMetadata3::Create();
    }

    public function testparseExpression2()
    {
        try {
              $odataUriExpression = 'UnitPrice ge 6';
              $resourceType = $this->_northWindMetadata->resolveResourceSet('Order_Details')->getResourceType();
              $internalFilterInfo = ExpressionParser2::parseExpression2($odataUriExpression, $resourceType, null);
              $this->assertTrue(!is_null($internalFilterInfo));
              $filterInfo = $internalFilterInfo->getFilterInfo();
              $this->assertTrue(!is_null($filterInfo));
              $navigationsUsed = $filterInfo->getNavigationPropertiesUsed();
              //Ther is no navigation in the expression so should be null.
              $this->assertTrue(is_null($navigationsUsed));
              $filterFunction = $internalFilterInfo->getFilterFunction();
              $whereCode = $filterFunction->getCode();
              $this->assertEquals($whereCode, 'if((!(is_null($lt->UnitPrice)) && ($lt->UnitPrice >= 6))) { return true; } else { return false;}');

              $odataUriExpression = 'Order/Customer/CustomerID eq \'ANU\' or Product/ProductID gt 123 and UnitPrice ge 6';
              $internalFilterInfo = ExpressionParser2::parseExpression2($odataUriExpression, $resourceType, null);
              $this->assertTrue(!is_null($internalFilterInfo));
              $filterInfo = $internalFilterInfo->getFilterInfo();
              $this->assertTrue(!is_null($filterInfo));
              $navigationsUsed = $filterInfo->getNavigationPropertiesUsed();
              $this->assertTrue(!is_null($navigationsUsed));
              $this->assertTrue(is_array($navigationsUsed));
              $this->assertEquals(count($navigationsUsed), 2);
              //Order/Customer
              $this->assertTrue(is_array($navigationsUsed[0]));
              $this->assertEquals(count($navigationsUsed[0]), 2);
              //Product
              $this->assertTrue(is_array($navigationsUsed[1]));
              $this->assertEquals(count($navigationsUsed[1]), 1);              
              //Verify 'Order/Customer'
              $this->assertTrue(is_object($navigationsUsed[0][0]));
              $this->assertTrue(is_object($navigationsUsed[0][1]));
              $this->assertTrue($navigationsUsed[0][0] instanceof ResourceProperty);
              $this->assertTrue($navigationsUsed[0][1] instanceof ResourceProperty);
              $this->assertEquals($navigationsUsed[0][0]->getName(), 'Order');
              $this->assertEquals($navigationsUsed[0][1]->getName(), 'Customer');
              //Verify 'Product'
              $this->assertTrue(is_object($navigationsUsed[1][0]));              
              $this->assertTrue($navigationsUsed[1][0] instanceof ResourceProperty);              
              $this->assertEquals($navigationsUsed[1][0]->getName(), 'Product');

              $odataUriExpression = 'Customer/Address/LineNumber add 4 eq 8';
              $resourceType = $this->_northWindMetadata->resolveResourceSet('Orders')->getResourceType();
              $internalFilterInfo = ExpressionParser2::parseExpression2($odataUriExpression, $resourceType, null);
              $this->assertTrue(!is_null($internalFilterInfo));
              $filterInfo = $internalFilterInfo->getFilterInfo();
              $this->assertTrue(!is_null($filterInfo));
              $navigationsUsed = $filterInfo->getNavigationPropertiesUsed();
              //Customer
              $this->assertTrue(!is_null($navigationsUsed));
              $this->assertTrue(is_array($navigationsUsed));
              $this->assertEquals(count($navigationsUsed), 1);
              $this->assertTrue(is_array($navigationsUsed[0]));
              $this->assertEquals(count($navigationsUsed[0]), 1);
              //Verify 'Customer'
              $this->assertTrue(is_object($navigationsUsed[0][0]));
              $this->assertTrue($navigationsUsed[0][0] instanceof ResourceProperty);
              $this->assertEquals($navigationsUsed[0][0]->getName(), 'Customer');

              //Test with property acess expression in function call
              $odataUriExpression = 'replace(Customer/CustomerID, \'LFK\', \'RTT\') eq \'ARTTI\'';              
              $internalFilterInfo = ExpressionParser2::parseExpression2($odataUriExpression, $resourceType, null);
              $this->assertTrue(!is_null($internalFilterInfo));
              $filterInfo = $internalFilterInfo->getFilterInfo();
              $this->assertTrue(!is_null($filterInfo));
              $navigationsUsed = $filterInfo->getNavigationPropertiesUsed();
              //Customer
              $this->assertTrue(!is_null($navigationsUsed));
              $this->assertTrue(is_array($navigationsUsed));
              $this->assertEquals(count($navigationsUsed), 1);
              $this->assertTrue(is_array($navigationsUsed[0]));
              $this->assertEquals(count($navigationsUsed[0]), 1);
              //Verify 'Customer'
              $this->assertTrue(is_object($navigationsUsed[0][0]));
              $this->assertTrue($navigationsUsed[0][0] instanceof ResourceProperty);
              $this->assertEquals($navigationsUsed[0][0]->getName(), 'Customer');
              
        } catch(ODataException $exception){
            $this->fail('An unexpected ODataException has been raised');
        }
    }

    protected function tearDown()
    {    
    }
}
?>