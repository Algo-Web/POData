<?php

namespace UnitTests\POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use Mockery as m;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\ObjectModel\ODataProperty;
use POData\Providers\Metadata\Type\Int32;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\TestCase;

class KeyDescriptorTest extends TestCase
{
    public function testKeyPredicateParsing()
    {
        $keyDescriptor = null;
        $keyPredicate = '  ';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $this->assertTrue($keyDescriptor->isEmpty());
        $this->assertEquals($keyDescriptor->valueCount(), 0);

        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $this->assertFalse($keyDescriptor->isEmpty());
        $this->assertTrue($keyDescriptor->areNamedValues());
        $this->assertEquals($keyDescriptor->valueCount(), 2);
        $namedValues = $keyDescriptor->getNamedValues();
        $this->assertFalse(empty($namedValues));
        $this->assertTrue(array_key_exists('ProductID', $namedValues));
        $this->assertTrue(array_key_exists('OrderID', $namedValues));
        $productVal = $namedValues['ProductID'];
        $orderVal = $namedValues['OrderID'];
        $this->assertEquals($productVal[0], 11);
        $this->assertEquals($orderVal[0], 2546);

        try {
            $keyDescriptor->getValidatedNamedValues();
            $this->fail('An expected InvalidOperationException has not been raised');
        } catch (InvalidOperationException $exception) {
            $exceptionThrown = true;
        }

        $keyDescriptor = null;
        $keyPredicate = '11, 2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $this->assertFalse($keyDescriptor->isEmpty());
        $this->assertFalse($keyDescriptor->areNamedValues());
        $this->assertEquals($keyDescriptor->valueCount(), 2);
        $positionalValues = $keyDescriptor->getPositionalValues();
        $this->assertFalse(empty($positionalValues));
        $productVal = $positionalValues[0];
        $orderVal = $positionalValues[1];
        $this->assertEquals($productVal[0], 11);
        $this->assertEquals($orderVal[0], 2546);

        try {
            $keyDescriptor->getValidatedNamedValues();
            $this->fail('An expected InvalidOperationException has not been raised');
        } catch (InvalidOperationException $exception) {
            $exceptionThrown = true;
        }

        //Test Mixing of Named and Positional values
        $keyDescriptor = null;
        $keyPredicate = '11, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Test Mixing of Named and Positional values
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11, 2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Syntax of single key should be keyName=keyValue
        $keyDescriptor = null;
        $keyPredicate = 'ProductID, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Syntax of single key should be keyName=keyValue
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Null is not a valid key value
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=null, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Identifer is not a valid key value
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=OrderID';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Key name duplication not allowed
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11, OrderID=2546, ProductID=11';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //comma cannot be terminating char
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11, OrderID=2546,';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);

        //Guid is a valid key value
        $keyDescriptor = null;
        $keyPredicate = 'Id=guid\'05b242e7-52eb-46bd-8f0e-6568b72cd9a5\', PlaceName=\'Ktym\'';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $namedValues = $keyDescriptor->getNamedValues();
        $this->assertFalse(empty($namedValues));
        $this->assertTrue(array_key_exists('Id', $namedValues));
        $idVal = $namedValues['Id'];
        $this->assertEquals($idVal[0], '\'05b242e7-52eb-46bd-8f0e-6568b72cd9a5\'');

        //Test invalid guid
        $keyDescriptor = null;
        $keyPredicate = 'Id=guid\'05b242e7---52eb-46bd-8f0e-6568b72cd9a5\', PlaceName=\'Ktym\'';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertFalse($validPredicateSyntax);
    }

    public function testKeyDescriptorValidation()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $orderDetailsResourceType = $northWindMetadata->resolveResourceType('Order_Details');
        $this->assertFalse(null === $orderDetailsResourceType);

        $keyDescriptor = null;
        //Test with a valid named value key predicate
        $keyPredicate = 'ProductID=11, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $keyDescriptor->validate('Order_Details(ProductID=11, OrderID=2546)', $orderDetailsResourceType);
        $validatedNamedValues = $keyDescriptor->getValidatedNamedValues();
        $this->assertTrue(array_key_exists('ProductID', $validatedNamedValues));
        $this->assertTrue(array_key_exists('OrderID', $validatedNamedValues));
        $productVal = $validatedNamedValues['ProductID'];
        $orderVal = $validatedNamedValues['OrderID'];
        $this->assertEquals($productVal[0], 11);
        $this->assertEquals($orderVal[0], 2546);

        $keyDescriptor = null;
        //Test with a valid positional value key predicate
        $keyPredicate = '11, 2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $keyDescriptor->validate('Order_Details(11, 2546)', $orderDetailsResourceType);
        $validatedNamedValues = $keyDescriptor->getValidatedNamedValues();
        $this->assertEquals(count($validatedNamedValues), 2);
        $this->assertTrue(array_key_exists('ProductID', $validatedNamedValues));
        $this->assertTrue(array_key_exists('OrderID', $validatedNamedValues));
        $productVal = $validatedNamedValues['ProductID'];
        $orderVal = $validatedNamedValues['OrderID'];
        $this->assertEquals($productVal[0], 11);
        $this->assertEquals($orderVal[0], 2546);

        //Test for key count
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);

        try {
            $keyDescriptor->validate('Order_Details(ProductID=11)', $orderDetailsResourceType);
            $this->fail('An expected ODataException for predicate key count has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith(' expect 2 keys but 1 provided', $exception->getMessage());
        }

        //test for missing key
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11, OrderID1=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);

        try {
            $keyDescriptor->validate('Order_Details(ProductID=11, OrderID1=2546)', $orderDetailsResourceType);
            $this->fail('An expected ODataException for missing of keys in the predicate has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('The key predicate expects the keys \'ProductID, OrderID\'', $exception->getMessage());
        }

        //test for key type
        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11.12, OrderID=2546';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);

        try {
            $keyDescriptor->validate('Order_Details(ProductID=11.12, OrderID=2546)', $orderDetailsResourceType);
            $this->fail('An expected ODataException for type incompactibility has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('The value of key property \'ProductID\' should be of type Edm.Int32, given Edm.Double', $exception->getMessage());
        }

        //test for key type
        $keyDescriptor = null;
        $keyPredicate = '11, \'ABCD\'';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);

        try {
            $keyDescriptor->validate('Order_Details(11, \'ABCD\')', $orderDetailsResourceType);
            $this->fail('An expected ODataException for type incompactibility has not been thrown');
        } catch (ODataException $exception) {
            $this->assertStringEndsWith('The value of key property \'OrderID\' at position 1 should be of type Edm.Int32, given Edm.String', $exception->getMessage());
        }
    }

    public function testSingleNamedValuePredicate()
    {
        $keyDescriptor = null;
        $keyPredicate = 'GroupNo=11';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $this->assertFalse(null === $keyDescriptor);
        $this->assertTrue($keyDescriptor instanceof KeyDescriptor, get_class($keyDescriptor));
        $this->assertFalse($keyDescriptor->isEmpty());
        $this->assertTrue($keyDescriptor->areNamedValues());
        $this->assertEquals($keyDescriptor->valueCount(), 1);
        $namedValues = $keyDescriptor->getNamedValues();
        $this->assertFalse(empty($namedValues));
        $this->assertTrue(array_key_exists('GroupNo', $namedValues));
        $groupVal = $namedValues['GroupNo'];
        $this->assertEquals($groupVal[0], 11);
    }

    public function testSingleValuePredicateRelativeUrlGeneration()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $orderResourceType = $northWindMetadata->resolveResourceType('Order');
        $this->assertFalse(null === $orderResourceType);
        $orderResourceSet = $northWindMetadata->resolveResourceSet('Orders');
        $this->assertFalse(null === $orderResourceSet);

        $expected = 'Orders(OrderID=42)';

        $keyDescriptor = null;
        $keyPredicate = 'OrderID=42';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $keyDescriptor->validate($keyPredicate, $orderResourceType);

        $actual = $keyDescriptor->generateRelativeUri($orderResourceSet);
        $this->assertEquals($expected, $actual);
    }

    public function testMultipleValuePredicateRelativeUrlGeneration()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $orderResourceType = $northWindMetadata->resolveResourceType('Order_Details');
        $this->assertFalse(null === $orderResourceType);
        $orderResourceSet = $northWindMetadata->resolveResourceSet('Order_Details');
        $this->assertFalse(null === $orderResourceSet);

        $expected = 'Order_Details(ProductID=11,OrderID=42)';

        $keyDescriptor = null;
        $keyPredicate = 'ProductID=11,OrderID=42';
        $validPredicateSyntax = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor);
        $this->assertTrue($validPredicateSyntax);
        $keyDescriptor->validate($keyPredicate, $orderResourceType);

        $actual = $keyDescriptor->generateRelativeUri($orderResourceSet);
        $this->assertEquals($expected, $actual);
    }

    public function testMismatchNumberOfKeyValues()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $orderResourceType = $northWindMetadata->resolveResourceType('Order');
        $this->assertFalse(null === $orderResourceType);
        $orderResourceSet = $northWindMetadata->resolveResourceSet('Orders');
        $this->assertFalse(null === $orderResourceSet);

        $expected = 'Mismatch between supplied key predicates and number of keys defined on resource set';
        $actual = null;

        $keyDescriptor = m::mock(KeyDescriptor::class)->makePartial();
        $keyDescriptor->shouldReceive('getNamedValues')->andReturn([]);

        try {
            $keyDescriptor->generateRelativeUri($orderResourceSet);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testMismatchMissingKeyPredicate()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $orderResourceType = $northWindMetadata->resolveResourceType('Order');
        $this->assertFalse(null === $orderResourceType);
        $orderResourceSet = $northWindMetadata->resolveResourceSet('Orders');
        $this->assertFalse(null === $orderResourceSet);

        $expected = 'Key predicate OrderID not present in named values';
        $actual = null;

        $keyDescriptor = m::mock(KeyDescriptor::class)->makePartial();
        $keyDescriptor->shouldReceive('getNamedValues')->andReturn(['foo' => 11]);

        try {
            $keyDescriptor->generateRelativeUri($orderResourceSet);
        } catch (\InvalidArgumentException $e) {
            $actual = $e->getMessage();
        }
        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetPropertiesFromValidatedValues()
    {
        $validated = [ 'id' => [ '2', new Int32()]];

        $payload = new ODataProperty();
        $payload->name = 'id';
        $payload->typeName = 'Edm.Int32';
        $payload->value = 2;
        $expected = ['id' => $payload];

        $keyDescriptor = m::mock(KeyDescriptor::class)->makePartial();
        $keyDescriptor->shouldReceive('getValidatedNamedValues')->andReturn($validated)->once();

        $actual = $keyDescriptor->getODataProperties();
        $this->assertEquals($expected, $actual);
        $this->assertTrue(2 === $actual['id']->value);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetTypeAndValidateKeyValueForDecimalLiteral()
    {
        $reflec = new \ReflectionClass(KeyDescriptor::class);

        $method = $reflec->getMethod('getTypeAndValidateKeyValue');
        $method->setAccessible(true);

        $value = "1.0m";
        $tokenId = ExpressionTokenId::DECIMAL_LITERAL();
        $outVal = null;
        $outType = null;

        $result = $method->invokeArgs(null, [$value, $tokenId, &$outVal, &$outType]);
        $this->assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetTypeAndValidateKeyValueForInt64Literal()
    {
        $reflec = new \ReflectionClass(KeyDescriptor::class);

        $method = $reflec->getMethod('getTypeAndValidateKeyValue');
        $method->setAccessible(true);

        $value = "10L";
        $tokenId = ExpressionTokenId::INT64_LITERAL();
        $outVal = null;
        $outType = null;

        $result = $method->invokeArgs(null, [$value, $tokenId, &$outVal, &$outType]);
        $this->assertTrue($result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetTypeAndValidateKeyValueForSingleLiteral()
    {
        $reflec = new \ReflectionClass(KeyDescriptor::class);

        $method = $reflec->getMethod('getTypeAndValidateKeyValue');
        $method->setAccessible(true);

        $value = "1.0f";
        $tokenId = ExpressionTokenId::SINGLE_LITERAL();
        $outVal = null;
        $outType = null;

        $result = $method->invokeArgs(null, [$value, $tokenId, &$outVal, &$outType]);
        $this->assertTrue($result);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
