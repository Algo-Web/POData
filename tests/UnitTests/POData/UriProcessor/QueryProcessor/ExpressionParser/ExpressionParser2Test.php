<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Providers\Expression\PHPExpressionProvider;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\TestCase;

class ExpressionParser2Test extends TestCase
{
    /**
     * @var IMetadataProvider
     */
    private $_northWindMetadata;

    protected function setUp()
    {
        $this->_northWindMetadata = NorthWindMetadata::Create();
    }

    public function testParseExpression2()
    {
        $expressionProvider = new PHPExpressionProvider('$lt');

        $filterExpression = 'UnitPrice ge 6';
        $resourceType = $this->_northWindMetadata->resolveResourceSet('Order_Details')->getResourceType();
        $filterInfo = ExpressionParser2::parseExpression2($filterExpression, $resourceType, $expressionProvider);
        $this->assertTrue(!is_null($filterInfo));

        //There are no navigation properties in the expression so should be empty.
        $this->assertEquals([], $filterInfo->getNavigationPropertiesUsed());
        $this->assertEquals('(!(is_null($lt->UnitPrice)) && ($lt->UnitPrice >= 6))', $filterInfo->getExpressionAsString());

        $filterExpression = 'Order/Customer/CustomerID eq \'ANU\' or Product/ProductID gt 123 and UnitPrice ge 6';
        $filterInfo = ExpressionParser2::parseExpression2($filterExpression, $resourceType, $expressionProvider);
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

        $filterExpression = 'Customer/Address/LineNumber add 4 eq 8';
        $resourceType = $this->_northWindMetadata->resolveResourceSet('Orders')->getResourceType();
        $filterInfo = ExpressionParser2::parseExpression2($filterExpression, $resourceType, $expressionProvider);
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
        $filterExpression = 'replace(Customer/CustomerID, \'LFK\', \'RTT\') eq \'ARTTI\'';
        $filterInfo = ExpressionParser2::parseExpression2($filterExpression, $resourceType, $expressionProvider);
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
    }
}
