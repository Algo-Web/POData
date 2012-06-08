<?php
use ODataProducer\Providers\Metadata\ResourceAssociationSet;
use ODataProducer\Providers\Metadata\ResourceAssociationSetEnd;
use ODataProducer\Providers\Metadata\ResourceAssociationType;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Providers\Metadata\ResourceAssociationTypeEnd;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\Type\TypeCode;
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
require_once 'PHPUnit\Framework\Assert.php';
require_once 'PHPUnit\Framework\Test.php';
require_once 'PHPUnit\Framework\SelfDescribing.php';
require_once 'PHPUnit\Framework\TestCase.php';
require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'ODataProducer\Common\ClassAutoLoader.php';
require_once (dirname(__FILE__) . "\..\..\Resources\NorthWindMetadata.php");
ODataProducer\Common\ClassAutoLoader::register();
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Metadata\Type\EdmPrimitiveType;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\Type\Int16;
use ODataProducer\Common\InvalidOperationException;
class ResourceClassesTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    /**
     * test ResourceType class
     */
    public function testResourceType()
    {
        try {
            
            $exceptionThrown = false;
            try {
                ResourceType::getPrimitiveResourceType(TypeCode::VOID);
            } catch (\InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->assertStringEndsWith('is not a valid EdmPrimitiveType Enum value', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'EdmPrimitiveType\' has not been raised');
            }
            
            $int16ResType = new ResourceType(new Int16(), ResourceTypeKind::PRIMITIVE, 'Int16', 'Edm');
            $exceptionThrown = false;
            try {
                $int32ResType = new ResourceType(new Int32(), ResourceTypeKind::PRIMITIVE, 'Int32', 'Edm', $int16ResType);
            } catch (\InvalidArgumentException $exception) {
                $this->AssertEquals('Primitive type cannot have base type', $exception->getMessage());
                $exceptionThrown = true; 
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'basetype\' has not been raised');
            }
            
            $exceptionThrown = false;
            try {
                $int32ResType = new ResourceType(new Int32(), ResourceTypeKind::PRIMITIVE, 'Int32', 'Edm', null, true);
            } catch (\InvalidArgumentException $exception) {
                $this->AssertEquals('Primitive type cannot be abstract', $exception->getMessage());
                $exceptionThrown = true; 
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'abstract\' has not been raised');
            }
            
            $exceptionThrown = false;
            try {
                $int32ResType = new ResourceType(null, ResourceTypeKind::PRIMITIVE, 'Int32', 'Edm');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringEndsWith('should be an \'IType\' implementor instance', $exception->getMessage());
                $exceptionThrown = true; 
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'IType\' has not been raised');
            }

            $exceptionThrown = false;
            try {
                $customerResType = new ResourceType(null, ResourceTypeKind::ENTITY, 'Customer', 'Northwind');
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringEndsWith('argument should be an \'ReflectionClass\' instance', $exception->getMessage());
                $exceptionThrown = true;
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'ReflectionClass\' has not been raised');
            }
            
            $customerResType = new ResourceType(new ReflectionClass('Customer2'), ResourceTypeKind::ENTITY, 'Customer', 'Northwind');
            $this->AssertEquals($customerResType->getName(), 'Customer');
            $this->AssertEquals($customerResType->getFullName(), 'Northwind.Customer');
            $this->assertTrue($customerResType->getInstanceType() instanceof \ReflectionClass);
            $this->AssertEquals($customerResType->getNamespace(), 'Northwind');
            $this->AssertEquals($customerResType->getResourceTypeKind(), ResourceTypeKind::ENTITY);
            $this->AssertEquals($customerResType->isMediaLinkEntry(), false);
            $exceptionThrown = false;
            try {
                $customerResType->validateType();
            } catch(InvalidOperationException $exception) {
                $this->assertStringEndsWith('Please make sure the key properties are defined for this entity type', $exception->getMessage());
                $exceptionThrown = true;
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'No key defined\' has not been raised');
            }
            
            $exceptionThrown = false;
            try {
                $int32ResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT32);
                $primitiveResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
                $testProperty = new ResourceProperty('test', null, ResourcePropertyKind::PRIMITIVE, $primitiveResourceType);
                $int32ResourceType->addProperty($testProperty);
                
            } catch (InvalidOperationException $exception) {
                $this->assertStringEndsWith('ResourceType instances with a ResourceTypeKind equal to \'Primitive\'', $exception->getMessage());
                $exceptionThrown = true;
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'property on primitive\' has not been raised');
            }

            $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
            $customerIDPrimProperty   = new ResourceProperty('CustomerID', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $stringResourceType);
            $customerNamePrimProperty = new ResourceProperty('CustomerName', null, ResourcePropertyKind::PRIMITIVE, $stringResourceType);
            $intResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT32);
            $ratingPrimProperty       = new ResourceProperty('Rating', null, ResourcePropertyKind::PRIMITIVE, $intResourceType);
            
            $addressResType = new ResourceType(new ReflectionClass('Address2'), ResourceTypeKind::COMPLEX, 'Address', 'Northwind');
            $exceptionThrown = false;
            try {
                $booleanResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BOOLEAN);
                $isPrimaryPrimProperty   = new ResourceProperty('IsPrimary', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $booleanResourceType);
                $addressResType->addProperty($isPrimaryPrimProperty);
            } catch(InvalidOperationException $exception) {
                $this->assertStringEndsWith('ResourceType instances with a ResourceTypeKind equal to \'EntityType\'', $exception->getMessage());
                $exceptionThrown = true;
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'Key on non-entity\' has not been raised');
            }

            $booleanResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BOOLEAN);
            $isPrimaryPrimProperty   = new ResourceProperty('IsPrimary', null, ResourcePropertyKind::PRIMITIVE, $booleanResourceType);
            $addressResType->addProperty($isPrimaryPrimProperty);
            
            $exceptionThrown = false;
            try {                
                $addressResType->addProperty($isPrimaryPrimProperty);
            } catch(InvalidOperationException $exception) {
                $this->assertStringStartsWith('Property with same name \'IsPrimary\' already exists in type \'Address\'', $exception->getMessage());
                $exceptionThrown = true;
            }    
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'Property duplication\' has not been raised');
            }

            $exceptionThrown = false;
            try {
                $addressResType->setMediaLinkEntry(true);
                
            } catch (InvalidOperationException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('Cannot apply the HasStreamAttribute', $exception->getMessage());
            }

            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'MLE on non-entity\' has not been raised');
            }
            
            $customerAdrComplexType = new ResourceProperty('Address', null, ResourcePropertyKind::COMPLEX_TYPE, $addressResType);
            $customerResType->addProperty($customerIDPrimProperty);
            $customerResType->addProperty($customerNamePrimProperty);
            $customerResType->addProperty($ratingPrimProperty);
            $customerResType->addProperty($customerAdrComplexType);
            $customerResType->validateType();

            $customerProperties = $customerResType->getPropertiesDeclaredOnThisType();
            $this->AssertEquals(count($customerProperties), 4);
            $customerAllProperties = $customerResType->getAllProperties();
            $this->AssertEquals(count($customerProperties), count($customerAllProperties));
            $keys = array('CustomerID', 'CustomerName', 'Rating', 'Address');
            $i = 0;
            foreach ($customerAllProperties as $key => $customerProperty) {
                $this->AssertEquals($key, $keys[$i++]);
            }
            
            $entityKeys = array('CustomerID');
            $customerKeyProperties = $customerResType->getKeyProperties();
            $i = 0;
            foreach ($customerKeyProperties as $key => $customerKeyProperty) {
                $this->AssertEquals($key, $entityKeys[$i++]);
            }

            $this->AssertEquals(count($customerResType->getETagProperties()), 0);
            $this->AssertEquals($customerResType->tryResolvePropertyTypeByName('PropNotExists'), null);
            $property = $customerResType->tryResolvePropertyTypeByName('CustomerName');
            $this->AssertNotEquals($property, null);
            $this->AssertEquals($property->getName(), 'CustomerName');
            
            $employeeResType = new ResourceType(new ReflectionClass('Employee2'), ResourceTypeKind::ENTITY, 'Employee', 'Northwind');
            $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
            $employeeResType->addProperty(new ResourceProperty('EmployeeID', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $stringResourceType));
            $employeeResType->addProperty(new ResourceProperty('Emails', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::BAG, $stringResourceType));
            $employeeResType->setMediaLinkEntry(true);            
            $employeeResType->addNamedStream(new ResourceStreamInfo('ThumNail_64X64'));
            $exceptionThrown = false;
            try {
                $employeeResType->addNamedStream(new ResourceStreamInfo('ThumNail_64X64'));
            } catch (InvalidOperationException $exception) {
                $exceptionThrown = true;
                $this->assertStringStartsWith('Named stream with the name \'ThumNail_64X64\' already exists in type \'Employee\'', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidOperationException for \'named stream duplication\' has not been raised');
            }
            
            $this->AssertEquals($employeeResType->hasNamedStream(), true);
            $b = array();
            $this->AssertEquals($employeeResType->hasBagProperty($b), true);
            
            $namedStreams = $employeeResType->getAllNamedStreams();
            $this->AssertEquals(count($namedStreams), 1);
            $this->AssertTrue(array_key_exists('ThumNail_64X64', $namedStreams));
            $name = $employeeResType->tryResolveNamedStreamByName('ThumNail_64X64')->getName();
            $this->AssertEquals($name, 'ThumNail_64X64');
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    public function testResourceProperty()
    {
        try {
            $addressResType = new ResourceType(new ReflectionClass('Address2'), ResourceTypeKind::COMPLEX, 'Address', 'Northwind');
            $booleanResourcetype = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::BOOLEAN);
            $isPrimaryPrimProperty = new ResourceProperty('IsPrimary', null, ResourcePropertyKind::PRIMITIVE, $booleanResourcetype);
            $addressResType->addProperty($isPrimaryPrimProperty);
            $exceptionThrown = false;
            try {
                $addressComplexProperty = new ResourceProperty('Address', null, ResourcePropertyKind::COMPLEX_TYPE | ResourcePropertyKind::KEY, $addressResType);
            } catch(\InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->AssertStringEndsWith('not a valid ResourcePropertyKind enum value or valid combination of ResourcePropertyKind enum values', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'invalid ResourcePropertyKind\' has not been raised');
            }
            
            $exceptionThrown = false;
            try {
                $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
                $addressComplexProperty = new ResourceProperty('Address', null, ResourcePropertyKind::COMPLEX_TYPE, $stringResourceType);
            } catch(\InvalidArgumentException $exception) {                
                $exceptionThrown = true;
                $this->AssertStringStartsWith('The \'$kind\' parameter does not match with the type of the resource type', $exception->getMessage());
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'Property and ResourceType kind mismatch\' has not been raised');
            }
            
            $customerResType = new ResourceType(new ReflectionClass('Customer2'), ResourceTypeKind::ENTITY, 'Customer', 'Northwind');
            $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
            $customerIDPrimProperty   = new ResourceProperty('CustomerID', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $stringResourceType);
            $customerNamePrimProperty = new ResourceProperty('CustomerName', null, ResourcePropertyKind::PRIMITIVE, $stringResourceType);
            $intResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT32);
            $ratingPrimProperty       = new ResourceProperty('Rating', null, ResourcePropertyKind::PRIMITIVE, $intResourceType);
            $customerResType->addProperty($customerIDPrimProperty);
            $customerResType->addProperty($customerNamePrimProperty);
            $customerResType->addProperty($ratingPrimProperty);
            $this->AssertTrue($customerIDPrimProperty->isKindOf(ResourcePropertyKind::KEY));
            $this->AssertTrue($customerIDPrimProperty->isKindOf(ResourcePropertyKind::PRIMITIVE));
            
            $customerReferenceSetProperty  = new ResourceProperty('Customers', null, ResourcePropertyKind::RESOURCESET_REFERENCE, $customerResType);
            $this->AssertEquals($customerReferenceSetProperty->getName(), 'Customers');
            $this->AssertEquals($customerReferenceSetProperty->getKind(), ResourcePropertyKind::RESOURCESET_REFERENCE);
            $this->AssertEquals($customerReferenceSetProperty->getInstanceType() instanceof \ReflectionClass, true);
            $this->AssertEquals($customerReferenceSetProperty->getResourceType()->getName(), 'Customer');
            $this->AssertEquals($customerReferenceSetProperty->getTypeKind(), ResourceTypeKind::ENTITY);
            $this->AssertFalse($customerReferenceSetProperty->isKindOf(ResourcePropertyKind::RESOURCE_REFERENCE));
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * test ResourceSet class
     */
    public function testResourceSet()
    {
        try {
            
            $exceptionThrown = false;
            try {
                $int64 = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT64);
                $customerResourceSet = new ResourceSet('Customers', $int64);
            } catch(\InvalidArgumentException $exception) {
                $exceptionThrown = true;                
                $this->AssertStringStartsWith('The ResourceTypeKind property of a ResourceType instance associated with a ResourceSet', $exception->getMessage());
            }
            
            $customerResType = $this->_getCustomerResourceType();
            $customerResourceSet = new ResourceSet('Customers', $customerResType);
            $this->AssertEquals($customerResourceSet->getName(), 'Customers');
            $this->AssertEquals($customerResourceSet->getResourceType()->getName(), 'Customer');
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'non-entity type resource type\' has not been raised');
            }
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }
    
    /**
     * Test ResourceAssociationTypeEnd class
     * Note: ResourceAssociationTypeEnd is an internal class used for metadata generation, not suppose to used by the developers 
     */
  public function testResourceAssociationTypeEnd()
    {
        try {
            $customerResType = $this->_getCustomerResourceType();
            $orderResType = $this->_getOrderResourceType();
            //Creates a one-to-many relationship from Customer to  Order entity
            $customerReferenceProperty = new ResourceProperty('Customer', null, ResourcePropertyKind::RESOURCE_REFERENCE, $customerResType);
            $ordersReferenceSetProperty = new ResourceProperty('Orders', null, ResourcePropertyKind::RESOURCESET_REFERENCE, $orderResType);
            $customerResType->addProperty($ordersReferenceSetProperty);
            $orderResType->addProperty($customerReferenceProperty);
            
            $customerToOrderAssoEnd1 = new ResourceAssociationTypeEnd('Orders', $customerResType, $ordersReferenceSetProperty, $customerReferenceProperty);
            $customerToOrderAssoEnd2 = new ResourceAssociationTypeEnd('Customers', $orderResType, $customerReferenceProperty, $ordersReferenceSetProperty);
            
            $this->AssertEquals($customerToOrderAssoEnd1->getName(), 'Orders');
            $this->AssertEquals($customerToOrderAssoEnd1->getResourceType()->getFullName(), 'Northwind.Customer');
            $this->AssertEquals($customerToOrderAssoEnd1->getResourceProperty()->getName(), 'Orders');
            $this->AssertEquals($customerToOrderAssoEnd1->getMultiplicity(), ODataConstants::ZERO_OR_ONE);
            $this->AssertEquals($customerToOrderAssoEnd2->getMultiplicity(), ODataConstants::MANY);
            $this->AssertTrue($customerToOrderAssoEnd1->isBelongsTo($customerResType, $ordersReferenceSetProperty));
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
       }
    }

    /**
     * Test ResourceAssociationType class
     * Note: ResourceAssociationType is an internal class used for metadata generation, not suppose to used by the developers 
     */
    public function testResourceAssociationType()
    {
        try {
            $customerResType = $this->_getCustomerResourceType();
            $orderResType = $this->_getOrderResourceType();
            //Creates a one-to-many relationship from Customer to  Order entity
            $customerReferenceProperty = new ResourceProperty('Customer', null, ResourcePropertyKind::RESOURCE_REFERENCE, $customerResType);
            $ordersReferenceSetProperty = new ResourceProperty('Orders', null, ResourcePropertyKind::RESOURCESET_REFERENCE, $orderResType);
            $customerResType->addProperty($ordersReferenceSetProperty);
            $orderResType->addProperty($customerReferenceProperty);            
            $customerToOrderAssoEnd1 = new ResourceAssociationTypeEnd('Orders', $customerResType, $ordersReferenceSetProperty, $customerReferenceProperty);
            $customerToOrderAssoEnd2 = new ResourceAssociationTypeEnd('Customers', $orderResType, $customerReferenceProperty, $ordersReferenceSetProperty);
            $customerToOrderAssoType = new ResourceAssociationType('FK_Orders_Customers', 'Northwind', $customerToOrderAssoEnd1, $customerToOrderAssoEnd2);
            $this->AssertEquals($customerToOrderAssoType->getName(), 'FK_Orders_Customers');
            $this->AssertEquals($customerToOrderAssoType->getFullName(), 'Northwind.FK_Orders_Customers');
            $this->AssertTrue($customerToOrderAssoType->getResourceAssociationTypeEnd($customerResType, $ordersReferenceSetProperty) === $customerToOrderAssoEnd1);
            $this->AssertTrue($customerToOrderAssoType->getRelatedResourceAssociationSetEnd($customerResType, $ordersReferenceSetProperty) === $customerToOrderAssoEnd2);
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }   
    }

    /**
     * Test ResourceAssociationSetEnd class
     * Note: ResourceAssociationSetEnd is an internal class used for metadata generation, not suppose to used by the developers 
     */
    public function testResourceAssociationSetEnd()
    {
        try {
            $customerResType = $this->_getCustomerResourceType();
            $orderResType = $this->_getOrderResourceType();
            $ordersReferenceSetProperty = new ResourceProperty('Orders', null, ResourcePropertyKind::RESOURCESET_REFERENCE, $orderResType);
            $customerResType->addProperty($ordersReferenceSetProperty);
            $customerResourceSet = new ResourceSet('Customers', $customerResType);
            $exceptionThrown = false;
            try {
                $customerIDPrimProperty = $customerResType->tryResolvePropertyTypeByName('CustomerID');
                $assoSetEnd = new ResourceAssociationSetEnd($customerResourceSet, $customerResType, $customerIDPrimProperty);
            } catch (\InvalidArgumentException $exception) {
                $exceptionThrown = true;
                $this->AssertEquals('The property CustomerID must be a navigation property of the resource type Northwind.Customer', $exception->getMessage());                
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'not valid navigation property\' has not been raised');
            }                      
            
            $exceptionThrown = false;
            try {                
                $assoSetEnd = new ResourceAssociationSetEnd($customerResourceSet, $orderResType, $ordersReferenceSetProperty);
            } catch (\InvalidArgumentException $exception) {
                $exceptionThrown = true;                
                $this->AssertEquals('The property Orders must be a navigation property of the resource type Northwind.Order', $exception->getMessage());                
            }
            
            if (!$exceptionThrown) {
                $this->fail('An expected InvalidArgumentException for \'not valid navigation property\' has not been raised');
            }
            
            $assoSetEnd = new ResourceAssociationSetEnd($customerResourceSet, $customerResType, $ordersReferenceSetProperty);
            $this->AssertEquals($assoSetEnd->getResourceSet()->getName(), 'Customers');
            $this->AssertEquals($assoSetEnd->getResourceType()->getName(), 'Customer');
            $this->AssertEquals($assoSetEnd->getResourceProperty()->getName(), 'Orders');
            $this->AssertTrue($assoSetEnd->isBelongsTo($customerResourceSet, $customerResType, $ordersReferenceSetProperty));
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }
    }

    /**
     * Test ResourceAssociationSet class
     * Note: ResourceAssociationSet is an internal class used for metadata generation, not suppose to used by the developers 
     */
    public function testResourceAssociationSet()
    {
        try {
            $customerResType = $this->_getCustomerResourceType();
            $customerResourceSet = new ResourceSet('Customers', $customerResType);
            $orderResType = $this->_getOrderResourceType();
            $orderResourceSet = new ResourceSet('Orders', $orderResType);
            $customerReferenceProperty = new ResourceProperty('Customer', null, ResourcePropertyKind::RESOURCE_REFERENCE, $customerResType);
            $ordersReferenceSetProperty = new ResourceProperty('Orders', null, ResourcePropertyKind::RESOURCESET_REFERENCE, $orderResType);
            $customerResType->addProperty($ordersReferenceSetProperty);
            $orderResType->addProperty($customerReferenceProperty);
            $assoSetEnd1 = new ResourceAssociationSetEnd($customerResourceSet, $customerResType, $ordersReferenceSetProperty);
            $assoSetEnd2 = new ResourceAssociationSetEnd($orderResourceSet, $orderResType, $customerReferenceProperty);
            $assoSet = new ResourceAssociationSet('Orders_Customers', $assoSetEnd1, $assoSetEnd2);
            $this->AssertEquals($assoSet->getName(), 'Orders_Customers');
            $this->AssertTrue($assoSet->getEnd1() === $assoSetEnd1);
            $this->AssertTrue($assoSet->getEnd2() === $assoSetEnd2);
            $this->AssertTrue($assoSet->getResourceAssociationSetEnd($customerResourceSet, $customerResType, $ordersReferenceSetProperty) === $assoSetEnd1);
            $this->AssertTrue($assoSet->getRelatedResourceAssociationSetEnd($customerResourceSet, $customerResType, $ordersReferenceSetProperty) === $assoSetEnd2);
            $exceptionThrown = false;
            
        } catch (\Exception $exception) {
            $this->fail('An unexpected Exception has been raised' . $exception->getMessage());
        }   
    }
    
    private function _getCustomerResourceType()
    {
        $customerResType = new ResourceType(new ReflectionClass('Customer2'), ResourceTypeKind::ENTITY, 'Customer', 'Northwind');
        $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
        $intResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT32);
        $customerIDPrimProperty   = new ResourceProperty('CustomerID', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $stringResourceType);
        $customerNamePrimProperty = new ResourceProperty('CustomerName', null, ResourcePropertyKind::PRIMITIVE, $stringResourceType);
        $ratingPrimProperty       = new ResourceProperty('Rating', null, ResourcePropertyKind::PRIMITIVE, $intResourceType);
        $customerResType->addProperty($customerIDPrimProperty);
        $customerResType->addProperty($customerNamePrimProperty);
        $customerResType->addProperty($ratingPrimProperty);
        return $customerResType;
    }

    private function _getOrderResourceType()
    {
        $orderResType =  new ResourceType(new ReflectionClass('Order2'), ResourceTypeKind::ENTITY, 'Order', 'Northwind');
        $intResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::INT32);
        $orderIDPrimProperty = new ResourceProperty('OrderID', null, ResourcePropertyKind::PRIMITIVE | ResourcePropertyKind::KEY, $intResourceType);
        $dateTimeResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::DATETIME);
        $orderDatePrimProperty = new ResourceProperty('OrderDate', null, ResourcePropertyKind::PRIMITIVE, $dateTimeResourceType);
        $stringResourceType = ResourceType::getPrimitiveResourceType(EdmPrimitiveType::STRING);
        $orderShipNamePrimProperty = new ResourceProperty('ShipName', null, ResourcePropertyKind::PRIMITIVE, $stringResourceType);
        $orderResType->addProperty($orderIDPrimProperty);
        $orderResType->addProperty($orderDatePrimProperty);
        $orderResType->addProperty($orderShipNamePrimProperty);
        return $orderResType;
    }

    protected function tearDown()
    {
    }
}