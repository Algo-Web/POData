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
//These are in the file loaded by above use statement
//TODO: move to own class files
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\TestCase;

class SkipTokenParserTest extends TestCase
{
    /** @var IQueryProvider */
    protected $mockQueryProvider;

    protected function setUp()
    {
        $this->mockQueryProvider = m::mock('POData\Providers\Query\IQueryProvider');
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Named values are not allowed in skip token.
     */
    public function testNamedValuesInSkipToken()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "'Antonio%20Moreno%20Taquer%C3%ADa',Price=22.0000M,10365";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised (because of named value)');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Bad Request - Error in the syntax of skiptoken', $odataException->getMessage());
        }
    }

    /**
     * Skiptoken should not contain leading and trailing commas, use
     * commas only to separate token sub-values.
     */
    public function testSkipTokenWithLeadingAndTrailingCommas()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);

        //Test with trailing comma
        $skipToken = "'Antonio%20Moreno%20Taquer%C3%ADa',22.000,10365,";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised (because of trailing comma)');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Bad Request - Error in the syntax of skiptoken', $odataException->getMessage());
        }

        //Test with leading comma
        $skipToken = ",'Antonio%20Moreno%20Taquer%C3%ADa',22.000,10365,";
        $skipToken = urldecode($skipToken);
        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised (because of leading comma)');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Bad Request - Error in the syntax of skiptoken', $odataException->getMessage());
        }

        //Test with extra comma between values
        $skipToken = "'Antonio%20Moreno%20Taquer%C3%ADa',,22.000,10365,";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised (because of extra comma)');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Bad Request - Error in the syntax of skiptoken', $odataException->getMessage());
        }
    }

    /**
     * Number of sub-values in skiptoken should match with number of
     * ordering expressions.
     */
    public function testSkipTokenHavingCountMismatchWithOrderBy()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        //zero skiptoken values
        $skipToken = '';
        $thrownException = false;
        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised for skiptoken and ordering constraint count mismatch');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('The number of keys \'0\' in skip token with value \'\' did not match the number of ordering constraints \'3\'', $odataException->getMessage());
        }

        //two skiptoken values
        $skipToken = "'Antonio%20Moreno%20Taquer%C3%ADa',22.000";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for syntax error has not been raised for skiptoken and ordering constraint count mismatch');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('The number of keys \'2\' in skip token with value', $odataException->getMessage());
        }
    }

    /**
     * test skiptoken sub-values where type does not match with
     * corrseponding type of orderby path.
     */
    public function testSkipTokenTypeInCompatibility()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        //ShipName is String, but in skiptoken uses datetime
        $skipToken = "datetime'1996-07-12T03%3A58%3A58', 22.00, 1234";
        //do decoding so token become "datetime'1996-07-12T03:58:58', 22.00, 1234";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for type mismatch (datetime and string) ');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('The skiptoken value \'datetime\'1996-07-12T03:58:58\', 22.00, 1234\' contain a value of type \'Edm.DateTime\' at position 0 which is not compatible with the type \'Edm.String\' of corresponding orderby constraint', $odataException->getMessage());
        }

        //Price is Double, but in skiptoken uses true
        $skipToken = "'ANS', true, 1234";
        $skipToken = urldecode($skipToken);

        try {
            $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
            $this->fail('An expected ODataException for type mismatch (boolean and double) ');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('The skiptoken value \'\'ANS\', true, 1234\' contain a value of type \'Edm.Boolean\' at position 1 which is not compatible with the type \'Edm.Double\' of corresponding orderby constraint', $odataException->getMessage());
        }

        //null is allowed in skiptoken and compactable with all types
        $skipToken = 'null, null, null';
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
    }

    /**
     * Test the initialzation of orderbyinfo's dummy object from the skiptoken value.
     * The orderby parser will create the dummy object, using the orderby path,
     * we will do a negative testing on GetKeyObject, by explicitly unsetting property
     * set by orderby parser.
     */
    public function testInitializationOfKeyObject1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Order/Customer/Rating';
        //Note: library will add primary key as last sort key
        $orderBy .= ', ProductID, OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = '12, 1234, 4567';
        $dummyObject = $internalOrderByInfo->getDummyObject();
        //we will remove Customer object created by orderby parser, so that getKeyObject function will
        //fail to access the property 'Rating', since parent ($dummyObject->Order->Customer) is null
        $this->assertTrue(!is_null($dummyObject));
        $this->assertTrue(!is_null($dummyObject->Order));
        $this->assertTrue(!is_null($dummyObject->Order->Customer));
        $this->assertTrue(is_null($dummyObject->Order->Customer->Rating));
        $dummyObject->Order->Customer = null;
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);

        try {
            $o = $internalSkipTokenInfo->getKeyObject();
            $this->fail('An expected ODataException for failure of ReflectionProperty on dummy object');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('internalSkipTokenInfo failed to access or initialize the property Rating', $odataException->getMessage());
        }
    }

    /**
     * Test the initialization of orderby info's dummy object from the skiptoken value.
     * 1. The string properties in the key object should be utf8 decoded.
     * 2. The lexer will consider all token text as string, but when the we populate the
     *    dummy object with these values, it should be converted to appropriate type.
     */
    public function testInitializationOfKeyObject2()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "'Antonio%20Moreno%20Taquer%C3%ADa',22.001,10365";
        //urldecode convert the skip token to - 'Antonio Moreno Taquería',22.000,10365
        $skipToken = urldecode($skipToken);
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        //The getKeyObject should do utf8 encoding on Antonio Moreno Taquería
        $keyObject = $internalSkipTokenInfo->getKeyObject();
        $this->assertTrue(!is_null($keyObject->ShipName));
        $shipName = (urldecode('Antonio%20Moreno%20Taquer%C3%ADa'));
        $this->assertEquals($shipName, $keyObject->ShipName);

        $this->assertTrue(!is_null($keyObject->Price));
        $this->assertTrue(is_float($keyObject->Price));

        $this->assertTrue(!is_null($keyObject->OrderID));
        $this->assertTrue(is_int($keyObject->OrderID));
    }

    /**
     * Test the initialization of orderbyinfo's dummy object from the skiptoken value.
     * 1. Complex navigation can be also used in the skiptoken
     * 2. The lexer will consider all token text as string, but when the we populate the
     *    dummy object with these values, it should be converted to appropriate type.
     */
    public function testInitializationOfKeyObject3()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Address/IsValid';
        //Note: library will add primery key as last sort key
        $orderBy .= ',CustomerID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "true, 'ALFKI'";
        $dummyObject = $internalOrderByInfo->getDummyObject();
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $keyObject = $internalSkipTokenInfo->getKeyObject();
        $this->assertTrue(!is_null($keyObject));
        $this->assertTrue(!is_null($keyObject->Address));
        $this->assertTrue(!is_null($keyObject->Address->IsValid));
        $this->assertTrue($keyObject->Address->IsValid);

        //test with other values, double, guid, int, datetime
    }

    /**
     * test the creation of nextlink from an object.
     * Test whether the buildNextPageLink function set skiptoken sub value as null when
     * it found one of the ancestor of corresponding ordering key property(ies) as null.
     */
    public function testCreationOfNextLink1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'Address/Address2/IsPrimary';
        //Note: library will add primery key as last sort key
        $orderBy .= ',CustomerID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "true, 'ALFKI'";
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        //Here the Address2 property is null, the sort key property IsPrimary is a property of Address2
        //so should emit null
        $lastObject = new Customer2();
        $lastObject->CustomerID = 'ALFKI';
        $lastObject->Address = new Address4();
        $lastObject->Address->Address2 = null;
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertTrue(!is_null($nextLink));
        $this->assertEquals($nextLink, "null, 'ALFKI'");

        $lastObject = null;
        try {
            $internalSkipTokenInfo->buildNextPageLink($lastObject);
            $this->fail('An expected ODataException for failure of ReflectionProperty on dummy object while building next link');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('internalSkipTokenInfo failed to access or initialize the property Address', $odataException->getMessage());
        }
    }

    /**
     * test the creation of nextlink from an object.
     * Test building of link with string sub-value.
     */
    public function testCreationOfNextLink2()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'ShipName asc, Price';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "'ABY',22.000,10365";
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $lastObject = new Order2();
        //The string that is fetched from DB can be in utf8 format, so we are building
        //such a string with utf8_decode
        $lastObject->ShipName = utf8_decode(urldecode('Antonio%20Moreno%20Taquer%C3%ADa'));
        $lastObject->Price = 23.56;
        $lastObject->OrderID = 3456;
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertTrue(!is_null($nextLink));
        $thrownException = false;
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertEquals($nextLink, "'Antonio+Moreno+Taquer%C3%ADa', 23.56D, 3456");
    }

    /**
     * test the creation of nextlink from an object.
     * Test building of link with datetime sub-value.
     */
    public function testCreationOfNextLink3()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'OrderDate';
        //Note: library will add prim key as last sort key
        $orderBy .= ', OrderID';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = 'null,10365';
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $lastObject = new Order2();
        $lastObject->OrderDate = '1996-07-12T00:00:00';
        $lastObject->OrderID = 3456;
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertTrue(!is_null($nextLink));
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertEquals($nextLink, "datetime'1996-07-12T00%3A00%3A00', 3456");
    }

    /**
     * test the creation of nextlink from an object.
     * Test building of link with guid sub-value.
     */
    public function testCreationOfNextLink4()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $this->mockQueryProvider,
            $configuration, //Service configuuration
            false
        );

        $resourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $resourceType = $resourceSetWrapper->getResourceType();
        $orderBy = 'CustomerID, CustomerGuid';
        $internalOrderByInfo = OrderByParser::parseOrderByClause($resourceSetWrapper, $resourceType, $orderBy, $providersWrapper);
        $skipToken = "null, guid'05b242e752eb46bd8f0e6568b72cd9a5'";
        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause($resourceType, $internalOrderByInfo, $skipToken);
        $keyObject = $internalSkipTokenInfo->getKeyObject();
        $lastObject = new Customer2();
        $lastObject->CustomerID = 'ABC';
        $lastObject->CustomerGuid = '{05b242e7-52eb-46bd-8f0e-6568b72cd9a5}';
        $nextLink = $internalSkipTokenInfo->buildNextPageLink($lastObject);
        $this->assertEquals($nextLink, "'ABC', guid'%7B05b242e7-52eb-46bd-8f0e-6568b72cd9a5%7D'");
    }
}
