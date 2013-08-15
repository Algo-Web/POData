<?php
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\Common\ODataException;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\..\..\..\Resources\NorthWind2\NorthWindMetadata2.php");
require_once (dirname(__FILE__) . "\..\..\..\Resources\NorthWind2\NorthWindQueryProvider.php");
ODataProducer\Common\ClassAutoLoader::register();
class TestSkipTokenParser2 extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * Test will null as resultSet and empty array as resultSet     
     */
    public function testGetIndexOfFirstEntryInNextPage1()
    {
        try {
                $northWindMetadata = CreateNorthWindMetadata1::Create();
                $configuration = new DataServiceConfiguration($northWindMetadata);
                $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
                $metaQueryProverWrapper = new MetadataQueryProviderWrapper(
                                  $northWindMetadata, //IDataServiceMetadataProvider implementation 
                                  null, //IDataServiceQueryProvider implementation (set to null)
                                  $configuration, //Service configuuration
                                  false
                                 );

                $resourceSetWrapper = $metaQueryProverWrapper->resolveResourceSet('Orders');
                $resourceType = $resourceSetWrapper->getResourceType();
                $orderBy = 'OrderID';                
                $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $metaQueryProverWrapper);
                $skipToken = "10365";                
                $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
                $exceptionThrown = false;
                try {
                    $internalSkipTokenInfo->getIndexOfFirstEntryInTheNextPage($m);
                } catch (\InvalidArgumentException $exception) {
                    $this->assertStringStartsWith("The argument 'searchArray' should be an array to perfrom binary search", $exception->getMessage());
                    $exceptionThrown = true;
                }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for non-array param type has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised.' . $exception->getMessage());
        }
    }

    /**
     * Test search InternalSkipToeknInfo::GetIndexOfFirstEntryInNextPage function
     */
    public function testGetIndexOfFirstEntryInNextPage2()
    {
         try {
                $northWindMetadata = CreateNorthWindMetadata1::Create();
                $configuration = new DataServiceConfiguration($northWindMetadata);
                $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
                $metaQueryProverWrapper = new MetadataQueryProviderWrapper(
                                  $northWindMetadata, //IDataServiceMetadataProvider implementation 
                                  null, //IDataServiceQueryProvider implementation (set to null)
                                  $configuration, //Service configuuration
                                  false
                                 );

                $resourceSetWrapper = $metaQueryProverWrapper->resolveResourceSet('Orders');
                $resourceType = $resourceSetWrapper->getResourceType();
                $orderBy = 'ShipName asc, Freight';
                //Note: library will add prim key as last sort key
                $orderBy .= ', OrderID';
                $qp = new NorthWindQueryProvider1();
                $orders = $qp->getResourceSet($resourceSetWrapper->getResourceSet());        
                $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $metaQueryProverWrapper);
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
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised.' . $exception->getMessage());
        }
    }

    protected function tearDown()
    {
    }
}
?>