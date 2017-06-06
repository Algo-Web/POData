<?php

namespace UnitTests\POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use Mockery as m;
use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\TestCase;

class SegmentParserTest extends TestCase
{
    private $_metadataProvider;
    private $providersWrapper;
    private $_serviceConfiguration;
    private $_segmentParser;

    /** @var IQueryProvider */
    protected $mockQueryProvider;

    protected function setUp()
    {
        $this->_metadataProvider = NorthWindMetadata::Create();
        $this->_serviceConfiguration = new ServiceConfiguration($this->_metadataProvider);
        $this->_serviceConfiguration->setEntitySetAccessRule('*', EntitySetRights::ALL);

        $this->mockQueryProvider = m::mock('POData\Providers\Query\IQueryProvider');

        $this->providersWrapper = new ProvidersWrapper(
            $this->_metadataProvider,
            $this->mockQueryProvider,
            $this->_serviceConfiguration,
            false
        );
    }

    public function testEmptySegments()
    {
        $segments = [];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        //No segment means request for service directory
        $this->assertTrue(!empty($segmentDescriptors));
        $this->assertEquals(count($segmentDescriptors), 1);
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::SERVICE_DIRECTORY());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::NONE);
    }

    public function testExtractionOfIdentifierAndPredicate()
    {
        //test case for SegmentParser::_extractSegmentIdentifierAndKeyPredicate
        $segments = ['Customers(\'ALFKI\''];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException query syntax error for \'Customers(\ has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax.');
        }
    }

    public function testCreateFirstSegment()
    {
        //Test for $metadata option
        $segments = ['$metadata'];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), '$metadata');

        $segments = ['$metadata(123)'];

        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException query syntax error for $metadata(123) has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax.');
        }

        //Test for $batch option
        $segments = ['$batch'];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), '$batch');
        $segments = ['$batch(\'XYZ\')'];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException query syntax error for $batch(\'XYZ\') has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals($exception->getMessage(), 'Bad Request - Error in query syntax.');
        }

        //Test for $links option
        $segments = ['$links'];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException query syntax error for \'$links\'');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith(
                'The request URI is not valid, the segment \'$links\' cannot be applied',
                $exception->getMessage()
            );
        }

        //Test for $count option
        $segments = ['$count'];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException query syntax error for \'$count\'');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith(
                'The request URI is not valid, the segment \'$count\' cannot be applied',
                $exception->getMessage()
            );
        }

        //Test for unknown entity set
        $segments = ['Customers1(\'ALFKI\')'];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected Resource not found ODataException for \'Customers1\' has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Resource not found for the segment \'Customers1\'.', $exception->getMessage());
        }

        //test with single positional value
        $segments = ["Employees('AF123')"];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 1);
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
        $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();

        $namedKeys = $keyDescriptor->getValidatedNamedValues();
        $this->assertEquals(count($namedKeys), 1);
        $this->assertTrue(array_key_exists('EmployeeID', $namedKeys));
        $this->assertEquals($namedKeys['EmployeeID'][0], '\'AF123\'');
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());

        //test with multiple positional values
        $segments = ["Customers('ALFKI', guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')"];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for multiple positional values has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith(
                'Segments with multiple key values must specify them in \'name=value\' form',
                $exception->getMessage()
            );
        }

        //test with multiple named values
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')"];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 1);
        $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
        $this->assertNotNull($resourceSetWrapper);
        $this->assertEquals('Customers', $resourceSetWrapper->getName());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertEquals('Customers', $segmentDescriptors[0]->getIdentifier());
        $keyDescriptor = $segmentDescriptors[0]->getKeyDescriptor();
        $namedKeys = $keyDescriptor->getValidatedNamedValues();
        $this->assertEquals(count($namedKeys), 2);
        $this->assertTrue(array_key_exists('CustomerGuid', $namedKeys));
        $this->assertEquals($namedKeys['CustomerGuid'][0], '\'15b242e7-52eb-46bd-8f0e-6568b72cd9a6\'');
        //test for multiple results
        $segments = ['Orders'];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 1);
        $this->assertFalse($segmentDescriptors[0]->isSingleResult());
        //test for multiple results, Orders(   ) is also valid segment for all Orders
        $segments = ['Orders(   )'];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 1);
        $this->assertFalse($segmentDescriptors[0]->isSingleResult());
        //test for FORBIDDEN
        $metadataProvider = NorthWindMetadata::Create();
        $serviceConfiguration = new ServiceConfiguration($metadataProvider);
        //HIDING ALL RESOURCE SET
        $serviceConfiguration->setEntitySetAccessRule('*', EntitySetRights::NONE);
        $providersWrapper = new ProvidersWrapper($metadataProvider, $this->mockQueryProvider, $serviceConfiguration, false);
        $segments = ["Employees('AF123')"];

        try {
            SegmentParser::parseRequestUriSegments($segments, $providersWrapper);
            $this->fail('An expected Forbidden ODataException has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Resource not found for the segment \'Employees\'.', $exception->getMessage());
        }
    }

    public function testCreateSegments_MetadataAndBatchLeafSegment()
    {
        $segments = ['$metadata', "Customers('ALFKI')"];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for invalid uri ($metadata/Customers(\'ALFKI\')) has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The request URI is not valid. The segment \'$metadata\' must be the last segment in the URI', $exception->getMessage());
        }

        $segments = ['$batch', "Employees('ID234')"];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for invalid uri ($metadata/Employees(\'ID234\')) has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The request URI is not valid. The segment \'$batch\' must be the last segment in the URI', $exception->getMessage());
        }
    }

    public function testCreateSegments_PrimitiveSegment()
    {
        $segments = [
            "Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'CustomerName',
        ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 2);
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'CustomerName');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::PRIMITIVE());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());

        //Test with a non $value followed by primitive type
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'CustomerName',
            'CustomerID', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException that indicates only allowed segment \'$value\' after primitve property has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('Since the segment \'CustomerName\' refers to a primitive type property, the only supported value from the next segment is \'$value\'.', $exception->getMessage());
        }

        //Test with $value followed by primitive type
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'CustomerName',
                          '$value', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 3);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'CustomerName');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::PRIMITIVE());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$value');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::PRIMITIVE_VALUE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
    }

    public function testCreateSegments_LinksSegment()
    {

        //test $links as last segment
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            '$links', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException missing segment after $links segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('must a segment specified after the \'$links\' segment and the segment must refer to a entity resource.', $exception->getMessage());
        }

         //test for post-post link
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            '$links',
            'Orders',
            'OrderID', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException post post segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('The segment \'OrderID\' is not valid. Since the uri contains the $links segment, there must be only one segment specified after that.', $exception->getMessage());
        }

         //test for $links with predicate
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            '$links(123)',
            'Orders', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException bad request for $links(123) has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Bad Request - Error in query syntax.', $exception->getMessage());
        }

        //test for $links with non-navigation property
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            '$links',
            'CustomerName', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for non-navigation followed by $links has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('The segment \'CustomerName\' must refer to a navigation property since the previous segment identifier is \'$links\'.', $exception->getMessage());
        }

        //test for $links with non-navigation property
        $segments = ['Customers',
            '$links',
            'Orders', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException intermediate collection in uri not thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('Since the segment \'Customers\' refers to a collection, this must be the last segment in the request URI. All intermediate segments must refer to a single resource.', $exception->getMessage());
        }

        //test a valid links segment followed by multiple result
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              '$links',
                              'Orders', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::LINK());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
        $this->assertFalse($segmentDescriptors[2]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());

        //test a valid links segment followed by single result
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              '$links',
                              'Orders(123)', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 3);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::LINK());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
        $this->assertNotNull($segmentDescriptors[2]->getKeyDescriptor());
        $ordersKeyDescriptor = $segmentDescriptors[2]->getKeyDescriptor();
        $namedKeyValues = $ordersKeyDescriptor->getValidatedNamedValues();
        $this->assertTrue(array_key_exists('OrderID', $namedKeyValues));
        $this->assertEquals($namedKeyValues['OrderID'][0], 123);

        //$count followed by post segment is valid
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                              '$links',
                              'Orders',
                              '$count', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 4);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$links');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::LINK());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'Orders');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[2]->getProjectedProperty());
        $this->assertFalse($segmentDescriptors[2]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
        $this->assertNull($segmentDescriptors[2]->getKeyDescriptor());
        /* check fourth segment */
        $this->assertEquals($segmentDescriptors[3]->getIdentifier(), '$count');
        $this->assertEquals($segmentDescriptors[3]->getTargetKind(), TargetKind::PRIMITIVE_VALUE());
        $this->assertEquals($segmentDescriptors[3]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNull($segmentDescriptors[3]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[3]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[3]->getTargetResourceSetWrapper());
        $this->assertNull($segmentDescriptors[3]->getKeyDescriptor());
    }

    public function testCreateSegments_CountSegment()
    {
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'Orders',
                          '$count', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 3);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Orders');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNotNull($segmentDescriptors[1]->getProjectedProperty());
        $this->assertFalse($segmentDescriptors[1]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$count');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::PRIMITIVE_VALUE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertNull($segmentDescriptors[2]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[2]->getTargetResourceSetWrapper());
        $this->assertNull($segmentDescriptors[2]->getKeyDescriptor());

        //$count cannot be applied for singleton resource
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Orders(123)',
            '$count', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException singleton followed by $count has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('since the segment \'Orders\' refers to a singleton, and the segment \'$count\' can only follow a resource collection.', $exception->getMessage());
        }

        //$count cannot be applied to primitive only $vlaue is allowed for primitive
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'CustomerID',
            '$count', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException primitive followed by non $value segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('Since the segment \'CustomerID\' refers to a primitive type property, the only supported value from the next segment is \'$value\'.', $exception->getMessage());
        }

        //$count cannot be applied to non-resource
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Address',
            '$count', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException non resource followed by $count segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('$count cannot be applied to the segment \'Address\' since $count can only follow a resource segment.', $exception->getMessage());
        }

        //No segments allowed after $count segment
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Orders',
            '$count',
            'OrderID', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException non resource followed by $count segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The request URI is not valid. The segment \'$count\' must be the last segment in the URI because it is one of the following:', $exception->getMessage());
        }
    }

    public function testCreateSegments_ComplexSegment()
    {
        //Test complex segment
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'Address', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 2);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Address');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::COMPLEX_OBJECT());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
        $this->assertFalse(is_null($projectedProperty));
        $this->assertEquals($projectedProperty->getName(), 'Address');
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        //Test property of complex
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'Address',
                          'StreetName', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 3);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Customers');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Address');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::COMPLEX_OBJECT());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
        $this->assertFalse(is_null($projectedProperty));
        $this->assertEquals($projectedProperty->getName(), 'Address');
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());
        /* check third segment */
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), 'StreetName');
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::PRIMITIVE());
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $projectedProperty = $segmentDescriptors[2]->getProjectedProperty();
        $this->assertFalse(is_null($projectedProperty));
        $this->assertEquals($projectedProperty->getName(), 'StreetName');
        $resourceType = $projectedProperty->getResourceType();
        $this->assertFalse(is_null($resourceType));
        $this->assertEquals($resourceType->getName(), 'String');
        $this->assertTrue($segmentDescriptors[2]->isSingleResult());
        $this->assertNull($segmentDescriptors[2]->getTargetResourceSetWrapper());

        //Test $value followed by complex, only primitive and MLE can followed by $value
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Address',
            '$value', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException resource not found for $value segment has no been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Resource not found for the segment \'$value\'.', $exception->getMessage());
        }

        //Test $count followed by complex
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
            'Address',
            '$count', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for $count followed by non-resource has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('$count cannot be applied to the segment \'Address\' since $count can only follow a resource segment.', $exception->getMessage());
        }
    }

    public function testCreateSegments_BagSegment()
    {
        //Test bag segment
        $segments = ["Employees('ABC')",
                          'Emails', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 2);
        /* check first segment */
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertNull($segmentDescriptors[0]->getProjectedProperty());
        $this->assertTrue($segmentDescriptors[0]->isSingleResult());
        $this->assertNotNull($segmentDescriptors[0]->getTargetResourceSetWrapper());
        /* check second segment */
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Emails');
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::BAG());
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $projectedProperty = $segmentDescriptors[1]->getProjectedProperty();
        $this->assertFalse(is_null($projectedProperty));
        $this->assertEquals($projectedProperty->getName(), 'Emails');
        $resourceType = $projectedProperty->getResourceType();
        $this->assertEquals($resourceType->getName(), 'String');
        $this->assertTrue($segmentDescriptors[1]->isSingleResult());
        $this->assertNull($segmentDescriptors[1]->getTargetResourceSetWrapper());

        //Test anything followed by bag segment (not allowed as bag is a leaf segment)
        $segments = ["Employees('ABC')",
            'Emails',
            'AB', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for using bag property as non-leaf segment has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith(
                'The request URI is not valid. The segment \'Emails\' must be the last segment in the URI',
                $exception->getMessage()
            );
        }
    }

    public function testCreateSegments_NavigationSegment()
    {
        //Test navigation segment followed by primitve property
        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'Orders(789)',
                          'Customer',
                          'CustomerName', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
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

        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[3]->getTargetKind(), TargetKind::PRIMITIVE());

        $this->assertEquals($segmentDescriptors[0]->getTargetSource(), TargetSource::ENTITY_SET);
        $this->assertEquals($segmentDescriptors[1]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertEquals($segmentDescriptors[2]->getTargetSource(), TargetSource::PROPERTY);
        $this->assertEquals($segmentDescriptors[3]->getTargetSource(), TargetSource::PROPERTY);

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
        $metadataProvider = NorthWindMetadata::Create();
        $serviceConfiguration = new ServiceConfiguration($this->_metadataProvider);
        $serviceConfiguration->setEntitySetAccessRule('Customers', EntitySetRights::READ_ALL);
        $serviceConfiguration->setEntitySetAccessRule('Orders', EntitySetRights::NONE);
        $providersWrapper = new ProvidersWrapper(
            $metadataProvider,
            $this->mockQueryProvider,
            $serviceConfiguration,
            false
        );

        $segments = ["Customers(CustomerID='ALFKI', CustomerGuid=guid'15b242e7-52eb-46bd-8f0e-6568b72cd9a6')",
                          'Orders(789)',
                          'OrderID', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $providersWrapper);
            $this->fail('An expected ODataException for \'Orders\' resource not found error has not been thrown');
        } catch (ODataException $exception) {
            $this->assertEquals('Resource not found for the segment \'Orders\'.', $exception->getMessage());
        }
    }

    public function testCreateSegments_MLEAndNamedStream()
    {
        //Test MLE
        $segments = ["Employees('JKT')",
                      '$value', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 2);
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), '$value');

        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::MEDIA_RESOURCE());

        $resourceType = $segmentDescriptors[0]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));
        $resourceType = $segmentDescriptors[1]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));

        $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
        $this->assertTrue(is_null($resourceSetWrapper));

        $this->assertEquals($segmentDescriptors[0]->isSingleResult(), true);
        $this->assertEquals($segmentDescriptors[1]->isSingleResult(), true);

        $segments = ["Employees('JKT')",
                      'Manager',
                      '$value', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 3);
        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'Manager');
        $this->assertEquals($segmentDescriptors[2]->getIdentifier(), '$value');

        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[2]->getTargetKind(), TargetKind::MEDIA_RESOURCE());

        $resourceType = $segmentDescriptors[0]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));
        $resourceType = $segmentDescriptors[1]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));
        $resourceType = $segmentDescriptors[2]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));

        $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[2]->getTargetResourceSetWrapper();
        $this->assertTrue(is_null($resourceSetWrapper));

        //Test Named Stream
        $segments = ["Employees('JKT')",
                      'TumbNail_48X48', ];
        $segmentDescriptors = SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
        $this->assertEquals(count($segmentDescriptors), 2);

        $this->assertEquals($segmentDescriptors[0]->getIdentifier(), 'Employees');
        $this->assertEquals($segmentDescriptors[1]->getIdentifier(), 'TumbNail_48X48');

        $this->assertEquals($segmentDescriptors[0]->getTargetKind(), TargetKind::RESOURCE());
        $this->assertEquals($segmentDescriptors[1]->getTargetKind(), TargetKind::MEDIA_RESOURCE());

        $resourceType = $segmentDescriptors[0]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));
        $resourceType = $segmentDescriptors[1]->getTargetResourceType();
        $this->assertFalse(is_null($resourceType));

        $resourceSetWrapper = $segmentDescriptors[0]->getTargetResourceSetWrapper();
        $this->assertFalse(is_null($resourceSetWrapper));
        $resourceSetWrapper = $segmentDescriptors[1]->getTargetResourceSetWrapper();
        $this->assertTrue(is_null($resourceSetWrapper));

        //No more segments after namedstream or MLE
        $segments = ["Employees('JKT')",
                      'TumbNail_48X48',
                      'anything', ];
        try {
            SegmentParser::parseRequestUriSegments($segments, $this->providersWrapper);
            $this->fail('An expected ODataException for segments specifed after named stream has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringStartsWith('The request URI is not valid. The segment \'TumbNail_48X48\' must be the last segment in the', $exception->getMessage());
        }
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
