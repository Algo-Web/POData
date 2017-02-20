<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\SkipTokenParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
use UnitTests\POData\Facets\NorthWind2\NorthWindMetadata;
use UnitTests\POData\TestCase;

class SkipTokenParser2Test extends TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    public function setUp()
    {
        $this->mockQueryProvider = m::mock(IQueryProvider::class)->makePartial();
    }

    /**
     * Test will null as resultSet and empty array as resultSet.
     */
    public function testGetIndexOfFirstEntryInNextPage1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause(
            $resourceSetWrapper,
            $resourceType,
            $orderBy,
            $providersWrapper
        );
        $skipToken = '10365';
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);

        try {
            $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($m);
            $this->fail('An expected ODataException for non-array param type has not been thrown');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringStartsWith(
                "The argument 'searchArray' should be an array to perfrom binary search",
                $exception->getMessage()
            );
        }
    }

    /**
     * Test search InternalSkipTokenInfo::GetIndexOfFirstEntryInNextPage function.
     */
    public function testGetIndexOfFirstEntryInNextPage2()
    {
        $this->markTestSkipped("Skipped because it depends on a query provider that isn't mocked");

        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Freight';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $qp = new NorthWindQueryProvider1();
        $orders = $qp->getResourceSet($resourceSetWrapper->getResourceSet());
        $internalOrderByInfo = OrderByParser::parseOrderByClause(
            $resourceSetWrapper,
            $resourceType,
            $orderBy,
            $providersWrapper
        );
        $compFun = $internalOrderByInfo->getSorterFunction();
        $fun = $compFun->getReference();
        usort($orders, $fun);
        $numRecords = count($orders);

        //-----------------------------------------------------------------
        //Search with a key that exactly matches
        $skipToken = utf8_decode(urldecode("'Antonio%20Moreno%20Taquer%C3%ADa',22.0000M,10365"));
        $skipToken = urldecode($skipToken);
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $nextIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($orders);
        $this->assertTrue($nextIndex > 1);
        $this->assertTrue($nextIndex < $numRecords);

        //$nextIndex is the index of order record next to the searched record
        $this->assertEquals($orders[$nextIndex - 1]->OrderID, 10365);
        $this->assertEquals($orders[$nextIndex - 1]->Freight, 22.0000);

        //-----------------------------------------------------------------
        //Search with a key that partially matches, in the DB there is no
        //order with ShipName 'An', but there are records start with
        //'An', so partial match, since its a parial match other two
        //key wont be used for comparsion
        $skipToken = "'An',22.0000M,10365";
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $nextIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($orders);
        $this->assertTrue($nextIndex > 1);
        $this->assertTrue($nextIndex < $numRecords);
        //Make sure this is the most matching record by comparing with previous record
        $prevOrder = $orders[$nextIndex - 1];
        $r = strcmp($prevOrder->ShipName, $orders[$nextIndex]->ShipName);
        $this->assertTrue($r < 0);
        //Make sure this is the most matching record by comparing with next record
        $nextOrder = $orders[$nextIndex + 1];
        $r = strcmp($nextOrder->ShipName, $orders[$nextIndex]->ShipName);
        $this->assertTrue($r >= 0);
        //-----------------------------------------------------------------
        //Search with a key that does not exists
        $skipToken = "'XXX',11,10365";
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $nextIndex = $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($orders);
        $this->assertTrue($nextIndex == -1);
        //-----------------------------------------------------------------
    }
}
