<?php
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\Configuration\EntitySetRights;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use ODataProducer\Common\ODataException;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\..\..\..\Resources\NorthWindMetadata.php");
ODataProducer\Common\ClassAutoLoader::register();
class TestSegmentParser extends PHPUnit_Framework_TestCase
{
    private $_metadataProvider;
    private $_metadataProviderWrapper;
    private $_serviceConfiguration;    
    private $_segmentParser;
    
    protected function setUp()
    {
        $this->_metadataProvider = CreateNorthWindMetadata3::Create();
        $this->_serviceConfiguration = new DataServiceConfiguration($this->_metadataProvider);
        $this->_serviceConfiguration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $this->_metadataProviderWrapper = new MetadataQueryProviderWrapper($this->_metadataProvider, null, $this->_serviceConfiguration, false);
    }

    public function testEmptySegments()
    {
        try {
            $segments = array();
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            //No segment means request for service directory
            $this->assertTrue(!empty($segmentDescriptors));
            $this->assertEquals(count($segmentDescriptors), 1);
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::SERVICE_DIRECTORY);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::NONE);
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testExtractionOfIdentiferAndPredicate()
    {
        //test case for SegmentParser::_extractSegmentIdentifierAndKeyPredicate
        try {
            $exceptionThrown = false;
            try {
                $segments = array('Customers(\'ALFKI\'');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax');
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException query syntax error for \'Customers(\ has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateFirstSegment()
    {
        try {       
            //Test for $metadata option
            $segments = array('$metadata');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), '$metadata');  
            $exceptionThrown = false;
            try {
                $segments = array('$metadata(123)');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
 
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax');
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException query synax error for $metadata(123) has not been thrown');
            }
            
            //Test for $batch option
            $segments = array('$batch');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), '$batch');  
            $exceptionThrown = false;
            try {
                $segments = array('$batch(\'XYZ\')');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
 
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax');
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException query synax error for $batch(\'XYZ\') has not been thrown');
            }

            //Test for $links option
            $exceptionThrown = false;
            try {
                $segments = array('$links');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
 
            } catch (ODataException $exception) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('The request URI is not valid, the segment \'$links\' cannot be applied', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException query synax error for \'$links\'');
            }

            //Test for $count option
            $exceptionThrown = false;
            try {
                $segments = array('$count');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
 
            } catch (ODataException $exception) {
                $exceptionThrown = true;                
                $this->assertStringStartsWith('The request URI is not valid, the segment \'$count\' cannot be applied', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException query synax error for \'$count\'');
            }

            //Test for unknown entity set
            $exceptionThrown = false;
            try {
                $segments = array('Customers1(\'ALFKI\')');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
 
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                
                $this->assertEquals('Resource not found for the segment \'Customers1\'', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected Resource not found ODataException for \'Customers1\' has not been thrown');
            }

            //test with single positional value
            $segments = array("Employees('AF123')");
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 1);
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
            $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();
            $namedkeys = $keyDescriptor->getValidatedNamedValues();
            $this->assertEquals(count($namedkeys), 1);
            $this->assertTrue(array_key_exists('EmployeeID', $namedkeys));
            $this->assertEquals($namedkeys['EmployeeID'][0], '\'AF123\'');
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            
            //test with multiple positional values
            $exceptionThrown = false;
            try {
                $segments = array("Customers('ALFKI', guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')");
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('Segments with multiple key values must specify them in \'name=value\' form', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for multiple positional values has not been thrown');
            }
            
            //test with multiple named values
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')");
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);         
            $this->assertEquals(count($segmentDescriptors), 1);
            $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
            $this->assertNotNull($resourceSetWrapper);
            $this->assertEquals($resourceSetWrapper->getName(), 'Customers');
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();
            $namedkeys = $keyDescriptor->getValidatedNamedValues();
            $this->assertEquals(count($namedkeys), 2);
            $this->assertTrue(array_key_exists('CustomerGuid', $namedkeys));            
            $this->assertEquals($namedkeys['CustomerGuid'][0], '\'15b242e7-52eb-46bd-8f0e-6568b72cd9a6\'');
            //test for multiple results
            $segments = array("Orders");
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);         
            $this->assertEquals(count($segmentDescriptors), 1);
            $this->assertFalse($segmentDescriptors[0]->isSingleResult());
            //test for multiple results, Orders(   ) is also valid segment for all Orders
            $segments = array("Orders(   )");
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);         
            $this->assertEquals(count($segmentDescriptors), 1);
            $this->assertFalse($segmentDescriptors[0]->isSingleResult());
            //test for FORBIDDEN
            $metadataProvider = CreateNorthWindMetadata3::Create();
            $serviceConfiguration = new DataServiceConfiguration($metadataProvider);
            //HIDING ALL RESOURCE SET
            $serviceConfiguration->setEntitySetAccessRule('*', EntitySetRights::NONE); 
            $metadataProviderWrapper = new MetadataQueryProviderWrapper($metadataProvider, null, $serviceConfiguration, false);
            $segments = array("Employees('AF123')");
            $exceptionThrown = false;
            try {
                SegmentParser::parseRequestUriSegements($segments, $metadataProviderWrapper);
            } catch (ODataException $exception) {                
                $this->assertEquals('Resource not found for the segment \'Employees\'', $exception->getMessage());
                $exceptionThrown = true;
            }

            if (!$exceptionThrown) {
                $this->fail('An expected Forbidden ODataException has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_MetadataAndBatchLeafSegment()
    {
        $exceptionThrown = false;
        try {
            $segments = array('$metadata', "Customers('ALFKI')");
            try {
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('The request URI is not valid. The segment \'$metadata\' must be the last segment in the URI', $exception->getMessage());                
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for invalid uri ($metadata/Customers(\'ALFKI\')) has not been thrown');
            }

            $segments = array('$batch', "Employees('ID234')");
            try {
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('The request URI is not valid. The segment \'$batch\' must be the last segment in the URI', $exception->getMessage());                
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for invalid uri ($metadata/Employees(\'ID234\')) has not been thrown');
            }
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_PrimitiveSegment()
    {
        try {
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              "CustomerName");
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 2);
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'CustomerName');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::PRIMITIVE);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            //Test with a non $value followed by primitve type
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  "CustomerName",
                                  "CustomerID");
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('Since the segment \'CustomerName\' refers to a primitive type property, the only supported value from the next segment is \'$value\'.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException that indicates only allowed segment \'$value\' after primitve property has not been thrown');
            }
            //Test with $value followed by primitve type
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              "CustomerName",
                              '$value');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 3);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'CustomerName');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::PRIMITIVE);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$value');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::PRIMITIVE_VALUE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNull($segmentDescriptors[2]->getTargetResourceSetWrapper());  
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_LinksSegment()
    {
        
        try {
            //test $links as last segment
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                
                $this->assertStringEndsWith('must a segment specified after the \'$links\' segment and the segment must refer to a entity resource.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException missing segment after $links segment has not been thrown');
            }

             //test for post-post link
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links',
                                  'Orders',
                                  'OrderID');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                
                $this->assertStringEndsWith('The segment \'OrderID\' is not valid. Since the uri contains the $links segment, there must be only one segment specified after that.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException post post segment has not been thrown');
            }
            
             //test for $links with predicate         
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links(123)',
                                  'Orders');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;        
                $this->assertEquals('Bad Request - Error in query syntax', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException bad request for $links(123) has not been thrown');
            }

            //test for $links with non-navigation property         
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links',
                                  'CustomerName');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                        
                $this->assertStringEndsWith('The segment \'CustomerName\' must refer to a navigation property since the previous segment identifier is \'$links\'.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for non-navigation followed by $links has not been thrown');
            }

            //test for $links with non-navigation property         
            $exceptionThrown = false;
            try {
                $segments = array("Customers",
                                  '$links',
                                  'Orders');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                        
                $this->assertStringEndsWith('Since the segment \'Customers\' refers to a collection, this must be the last segment in the request URI. All intermediate segments must refer to a single resource.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException intermediate collection in uri not thrown');
            }
            
            //test a valid links segment followed by multiple result
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links',
                                  'Orders');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::LINK);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
            $this->assertFalse($segmentDescriptors[2]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());

            //test a valid links segment followed by single result
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links',
                                  'Orders(123)');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 3);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::LINK);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
            $this->assertNotNull($segmentDescriptors[2]->getKeyDescriptor());
            $ordersKeyDescriptor = $segmentDescriptors[2]->getKeyDescriptor();
            $namedKeyValues = $ordersKeyDescriptor->getValidatedNamedValues();
            $this->assertTrue(array_key_exists('OrderID', $namedKeyValues));
            $this->assertEquals($namedKeyValues['OrderID'][0], 123);

            //$count followed by post segment is valid
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                                  '$links',
                                  'Orders',
                                  '$count');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 4);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::LINK);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
            $this->assertFalse($segmentDescriptors[2]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
            $this->assertNull($segmentDescriptors[2]->getKeyDescriptor());
            /** check fourth segment */
            $this->assertEquals($segmentDescriptors[3]->getIdentifier(), '$count');
            $this->assertEquals($segmentDescriptors[3]->getTargetKind(), RequestTargetKind::PRIMITIVE_VALUE);
            $this->assertEquals($segmentDescriptors[3]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNull($segmentDescriptors[3]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[3]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[3]->getTargetResourceSetWrapper());
            $this->assertNull($segmentDescriptors[3]->getKeyDescriptor());  
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_CountSegment()
    {
        try {
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Orders',
                              '$count');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 3);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Orders');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
            $this->assertFalse($segmentDescriptors[1]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$count');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::PRIMITIVE_VALUE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $this->assertNull($segmentDescriptors[2]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
            $this->assertNull($segmentDescriptors[2]->getKeyDescriptor());
            
            //$count cannot be applied for singleton resource
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Orders(123)',
                              '$count');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                                        
                $this->assertStringEndsWith('since the segment \'Orders\' refers to a singleton, and the segment \'$count\' can only follow a resource collection.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException singleton followed by $count has not been thrown');
            }
            
            //$count cannot be applied to primitive only $vlaue is allowed for primitive
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'CustomerID',
                              '$count');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                
                $this->assertStringEndsWith('Since the segment \'CustomerID\' refers to a primitive type property, the only supported value from the next segment is \'$value\'.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException primitive followed by non $value segment has not been thrown');
            }

            //$count cannot be applied to non-resource
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Address',
                              '$count');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                
                $this->assertStringEndsWith('$count cannot be applied to the segment \'Address\' since $count can only follow a resource segment.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException non resource followed by $count segment has not been thrown');
            }

            //No segments allowed after $count segment
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Orders',
                              '$count',
                              'OrderID');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('The request URI is not valid. The segment \'$count\' must be the last segment in the URI because it is one of the following:', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException non resource followed by $count segment has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_ComplexSegment()
    {
        try {
            //Test complex segment
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Address');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 2);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Address');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::COMPLEX_OBJECT);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
            $this->assertFalse(is_null($projectedProperty));
            $this->assertEquals($projectedProperty->getName(), 'Address');
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            //Test property of complex
            $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Address',
            				  'StreetName');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 3);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Address');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::COMPLEX_OBJECT);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
            $this->assertFalse(is_null($projectedProperty));
            $this->assertEquals($projectedProperty->getName(), 'Address');
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            /** check third segment */
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'StreetName');
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::PRIMITIVE);
            $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
            $projectedProperty = $segmentDescriptors[2]->getProjectedProperty();
            $this->assertFalse(is_null($projectedProperty));
            $this->assertEquals($projectedProperty->getName(), 'StreetName');
            $resourceType = $projectedProperty->getResourceType();
            $this->assertFalse(is_null($resourceType));            
            $this->assertEquals($resourceType->getName(), 'String');
            $this->assertTrue($segmentDescriptors[2]->isSingleResult());
            $this->assertNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
            //Test $value followed by complex, only primitive and MLE can followed by $value
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Address',
                              '$value');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                
                $this->assertEquals('Resource not found for the segment \'$value\'', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException resource not found for $value segment has no been thrown');
            }
            //Test $count followed by complex
            $exceptionThrown = false;
            try {
                $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              'Address',
                              '$count');
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                
                $this->assertStringEndsWith('$count cannot be applied to the segment \'Address\' since $count can only follow a resource segment.', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for $count followed by non-resource has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_BagSegment()
    {
        try {
            //Test bag segment
            $segments = array("Employees('ABC')",
                              'Emails');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 2);
            /** check first segment */
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
            $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
            $this->assertTrue($segmentDescriptors[0]->isSingleResult());
            $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
            /** check second segment */
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Emails');
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::BAG);
            $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
            $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
            $this->assertFalse(is_null($projectedProperty));
            $this->assertEquals($projectedProperty->getName(), 'Emails');
            $resourceType = $projectedProperty->getResourceType();
            $this->assertEquals($resourceType->getName(), 'String');
            $this->assertTrue($segmentDescriptors[1]->isSingleResult());
            $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
            //Test anything followed by bag segment (not allowed as bag is a leaf segment)
            $exceptionThrown = false;
            try {
                $segments = array("Employees('ABC')",
                                  'Emails',
                                  'AB');
                $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
                
            } catch (ODataException $exception) {
                $exceptionThrown = true;                                               
                $this->assertStringStartsWith('The request URI is not valid. The segment \'Emails\' must be the last segment in the URI', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for using bag property as non-leaf segment has not been thrown');
            }
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_NavigationSegment()
    {
        try {
        //Test navigation segment followed by primitve property
        $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",          
                          'Orders(789)',
                          'Customer',
                          'CustomerName');
        $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
        $this->assertEquals(count($segmentDescriptors), 4);
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Orders');
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Customer');
        $this->assertEquals($segmentDescriptors[3]->getIdentifier(), 'CustomerName');
        
        $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();
        $this->assertFalse(is_null($keyDescriptor));
        $keyDescriptor = $segmentDescriptors[1]->getKeyDescriptor();
        $this->assertFalse(is_null($keyDescriptor));
        $keyDescriptor = $segmentDescriptors[2]->getKeyDescriptor();
        $this->assertTrue(is_null($keyDescriptor));
        $keyDescriptor = $segmentDescriptors[3]->getKeyDescriptor();
        $this->assertTrue(is_null($keyDescriptor));
        
        $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();
        $this->assertEquals($keyDescriptor->valueCount(), 2);
        $namedKeyValues = $keyDescriptor->getValidatedNamedValues();
        $this->assertTrue(array_key_exists('CustomerID', $namedKeyValues));
        $this->assertTrue(array_key_exists('CustomerGuid', $namedKeyValues));
        $this->assertEquals($namedKeyValues['CustomerID'][0], '\'ALFKI\'');
        $this->assertEquals($namedKeyValues['CustomerGuid'][0], '\'15b242e7-52eb-46bd-8f0e-6568b72cd9a6\'');
        $keyDescriptor = $segmentDescriptors[1]->getKeyDescriptor();
        $this->assertEquals($keyDescriptor->valueCount(), 1);
        $namedKeyValues = $keyDescriptor->getValidatedNamedValues();
        $this->assertTrue(array_key_exists('OrderID', $namedKeyValues));
        $this->assertEquals($namedKeyValues['OrderID'][0], 789);
        
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::RESOURCE);
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::RESOURCE);
        $this->assertEquals($segmentDescriptors[3]->getTargetKind(), RequestTargetKind::PRIMITIVE);
        
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), RequestTargetSource::ENTITY_SET);
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), RequestTargetSource::PROPERTY);
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), RequestTargetSource::PROPERTY);
        $this->assertEquals($segmentDescriptors[3]->getTargetSource(), RequestTargetSource::PROPERTY);
        
        $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourcesetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[1]->getTargetResourcesetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[2]->getTargetResourcesetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[3]->getTargetResourcesetWrapper();
        $this->assertTrue(is_null($resourceSetWrapper));

        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        
        //Test invisible navigation segment
        //Creates a provider wrapper for NorthWind service with 'Orders' entity set as invisible
        $metadataProvider = CreateNorthWindMetadata3::Create();
        $serviceConfiguration = new DataServiceConfiguration($this->_metadataProvider);
        $serviceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::READ_ALL);
        $serviceConfiguration->setEntitySetAccessRule('Orders', EntitySetRights::NONE);
        $metadataProviderWrapper = new MetadataQueryProviderWrapper($metadataProvider, null, $serviceConfiguration, false);
        $segments = array("Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",          
                          'Orders(789)',
                          'OrderID');
        $exceptionThrown = false;
        try {
            SegmentParser::parseRequestUriSegements($segments, $metadataProviderWrapper);
        } catch (ODataException $exception) {
            $exceptionThrown = true;
            $this->assertEquals( 'Resource not found for the segment \'Orders\'', $exception->getMessage());
        }
  
        if (!$exceptionThrown) {
                $this->fail('An expected ODataException for \'Orders\' resource not found error has not been thrown');
        }
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testCreateSegments_MLEAndNamedStream()
    {
        try {
            //Test MLE
            $segments = array("Employees('JKT')",          
                          '$value');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 2);
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$value');
            
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::MEDIA_RESOURCE);
            
            $resourceType = $segmentDescriptors[0]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            $resourceType = $segmentDescriptors[1]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            
            $resouceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
            $this->assertFalse(is_null($resouceSetWrapper));
            $resouceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
            $this->assertTrue(is_null($resouceSetWrapper));
            
            $this->assertEquals($segmentDescriptors[0]->isSingleResult(), true);
            $this->assertEquals($segmentDescriptors[1]->isSingleResult(), true);
                        
            $segments = array("Employees('JKT')",
                          'Manager',          
                          '$value');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 3);
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Manager');
            $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$value');
            
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[2]->getTargetKind(), RequestTargetKind::MEDIA_RESOURCE);
            
            $resourceType = $segmentDescriptors[0]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            $resourceType = $segmentDescriptors[1]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            $resourceType = $segmentDescriptors[2]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            
            $resouceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
            $this->assertFalse(is_null($resouceSetWrapper));
            $resouceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
            $this->assertFalse(is_null($resouceSetWrapper));
            $resouceSetWrapper = $segmentDescriptors[2]->getTargetResourceSetWrapper();
            $this->assertTrue(is_null($resouceSetWrapper));

            //Test Named Stream
            $segments = array("Employees('JKT')",
                          'TumbNail_48X48');
            $segmentDescriptors = SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            $this->assertEquals(count($segmentDescriptors), 2);
            
            $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
            $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'TumbNail_48X48');
            
            $this->assertEquals($segmentDescriptors[0]->getTargetKind(), RequestTargetKind::RESOURCE);
            $this->assertEquals($segmentDescriptors[1]->getTargetKind(), RequestTargetKind::MEDIA_RESOURCE);
            
            $resourceType = $segmentDescriptors[0]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            $resourceType = $segmentDescriptors[1]->getTargetResourceType();
            $this->assertFalse(is_null($resourceType));
            
            $resouceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
            $this->assertFalse(is_null($resouceSetWrapper));
            $resouceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
            $this->assertTrue(is_null($resouceSetWrapper));
            
            //No more segments after namedstream or MLE
            $segments = array("Employees('JKT')",
                          'TumbNail_48X48',
                          'anything');
            $exceptionThrown = false;
            try {
                SegmentParser::parseRequestUriSegements($segments, $this->_metadataProviderWrapper);
            } catch (ODataException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('The request URI is not valid. The segment \'TumbNail_48X48\' must be the last segment in the', $exception->getMessage());               
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected ODataException for segments specifed after named stream has not been thrown');
            }
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    protected function tearDown()
    {
    }
}
?>