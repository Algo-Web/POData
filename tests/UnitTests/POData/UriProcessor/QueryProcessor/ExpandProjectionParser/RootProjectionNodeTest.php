<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use Mockery as m;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandProjectionParser;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\NorthWindQueryProvider;
use UnitTests\POData\TestCase;

class RootProjectionNodeTest extends TestCase
{
    public function testGetEagerListWhenExpansionNotSpecified()
    {
        $node = m::mock(RootProjectionNode::class)->makePartial();
        $node->shouldReceive('isExpansionSpecified')->andReturn(false)->once();

        $expected = [];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEagerListWhenExpansionSpecifiedButNothingToDo()
    {
        $node = m::mock(RootProjectionNode::class)->makePartial();
        $node->shouldReceive('isExpansionSpecified')->andReturn(true)->once();
        $node->shouldReceive('getChildNodes')->andReturn([])->once();

        $expected = [];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEagerListWithSingleFirstLevelExpansion()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider     = new NorthWindQueryProvider();
        $configuration     = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType        = $customersResourceSetWrapper->getResourceType();

        $node = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            'Orders', // $expand
            null, // $select
            $providersWrapper
        );

        $this->assertTrue($node instanceof RootProjectionNode);
        $this->assertTrue($node->isExpansionSpecified());
        $expected = ['Orders'];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEagerListWithDuplicatedFirstLevelExpansion()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider     = new NorthWindQueryProvider();
        $configuration     = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType        = $customersResourceSetWrapper->getResourceType();

        $node = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            'Orders,Orders', // $expand
            null, // $select
            $providersWrapper
        );

        $this->assertTrue($node instanceof RootProjectionNode);
        $this->assertTrue($node->isExpansionSpecified());
        $expected = ['Orders'];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals($expected, $actual);
    }

    public function testGetEagerListWithSingleMultiLevelExpansion()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider     = new NorthWindQueryProvider();
        $configuration     = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType        = $customersResourceSetWrapper->getResourceType();

        $node = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            'Orders/Order_Details/Product', // $expand
            null, // $select
            $providersWrapper
        );

        $this->assertTrue($node instanceof RootProjectionNode);
        $this->assertTrue($node->isExpansionSpecified());
        $expected = ['Orders/Order_Details/Product', 'Orders', 'Orders/Order_Details'];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $test) {
            $this->assertTrue(in_array($test, $actual));
        }
        foreach ($actual as $test) {
            $this->assertTrue(in_array($test, $expected));
        }
    }

    public function testGetEagerListingWithTwoMultilevelExpansions()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider     = new NorthWindQueryProvider();
        $configuration     = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $customerResourceType        = $customersResourceSetWrapper->getResourceType();

        $node = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            'Order/Customer, Product/Order_Details', // $expand
            null, // $select
            $providersWrapper
        );

        $this->assertTrue($node instanceof RootProjectionNode);
        $this->assertTrue($node->isExpansionSpecified());
        $expected = ['Order', 'Product', 'Order/Customer', 'Product/Order_Details'];
        $actual   = $node->getEagerLoadList();
        $this->assertEquals(count($expected), count($actual));
        foreach ($expected as $test) {
            $this->assertTrue(in_array($test, $actual));
        }
        foreach ($actual as $test) {
            $this->assertTrue(in_array($test, $expected));
        }
    }
}
