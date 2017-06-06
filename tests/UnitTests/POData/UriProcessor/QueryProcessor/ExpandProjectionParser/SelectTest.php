<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use POData\Common\ODataException;
use POData\Configuration\EntitySetRights;
use POData\Configuration\ServiceConfiguration;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandProjectionParser;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\NorthWindQueryProvider;
use UnitTests\POData\TestCase;

class SelectTest extends TestCase
{
    protected function setUp()
    {
    }

    /**
     * Test applying wild card '*' on root.
     */
    public function testWildCartSelectOnRoot()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType = $customersResourceSetWrapper->getResourceType();

        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            null, // $expand
            '*', // $select
            $providersWrapper
        );
        //expand option is absent
        $this->assertFalse($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //'*' means select all immediate properties
        $this->assertTrue($projectionTreeRoot->canSelectAllImmediateProperties());
        //all properties needs to be included if '*' is there or selectsubtree flag is true
        $this->assertTrue($projectionTreeRoot->canSelectAllProperties());
        //there is no child node for the root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 0);
    }

    /**
     * Application of '*' on a node means select (only) all immediate properties of that node
     * in this case parser should remove any explicitly included nodes if its there
     * this will actually test the function 'ExpandProjectionNode::removeNodesAlreadyIncludedImplicitly'.
     */
    public function testWildCardWithExplicitSelectionOnRoot()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType = $customersResourceSetWrapper->getResourceType();
        //First test with explicit selection and no '*' application
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            null, // $expand
            'CustomerID,CustomerName,Orders', //$select
            $providersWrapper
        );
        //expand option is absent
        $this->assertFalse($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We selected 3 properties (one is a link to 'Orders' navigation property) explicitly, there is no '*'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //there are 3 child nodes for the root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 3);
        //The child nodes are 'ProjectionNode' for CustomerID, CompanyName and Orders
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('CustomerID', $childNodes));
        $this->assertTrue($childNodes['CustomerID'] instanceof ProjectionNode);
        $this->assertTrue(array_key_exists('CustomerName', $childNodes));
        $this->assertTrue($childNodes['CustomerName'] instanceof ProjectionNode);
        $this->assertTrue(array_key_exists('Orders', $childNodes));
        $this->assertTrue($childNodes['Orders'] instanceof ProjectionNode);
        //even though 'Orders' is a navigation property, corresponding node will not be
        //'ExpandedProjectionNode' because 'Orders' is not expanded, its just selected
        //so that only link will be inlcuded in the result
        $this->assertFalse($childNodes['Orders'] instanceof ExpandedProjectionNode);

        //Now test selection with both '*' and explicit property inclusion
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $customersResourceSetWrapper,
            $customerResourceType,
            null,
            null,
            null,
            null, // $expand
            'CustomerID,CustomerName,Orders,*', //$select
            $providersWrapper
        );

        //expand option is absent
        $this->assertFalse($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We applied '*' on root, so flag for selection of all immediate properties must me true
        $this->assertTrue($projectionTreeRoot->canSelectAllImmediateProperties());
        $this->assertTrue($projectionTreeRoot->canSelectAllProperties());
        //Even though we explicity selected 'CustomerID', 'CustomerName' and link to 'Orders'
        //these children will be removed since '*' implcilty select all properties
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 0);
    }

    /**
     * Traversal of navigation property on select clause is allowed only if its expanded
     * We can select navigation property which is not in expand to include links to them
     * in result, but traversal requires expansion
     * $expand=Nav1/Navi2 & $select=Navi1/Navi2/PropertyOFNavi
     *      This is correct, result will include Navi1 and Navi2 with only PropertyOFNavi
     * $expand=Nav1 & $select=Navi1/Navi2
     *     This is correct, result will include Navi1 with link to Navi2
     * $expand=Nav1 & $select=Navi1/Navi2/PropertyOFNavi
     *    This is incorrect, trying to traverse Navi2 that is not expanded.
     */
    public function testTraversalOfNavigationPropertyWhichIsNotExpandedOnRoot()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType = $customersResourceSetWrapper->getResourceType();

        try {
            //Try to traverse 'Orders' on select without expanding
                $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
                    $customersResourceSetWrapper,
                    $customerResourceType,
                    null,
                    null,
                    null,
                    null, // $expand
                    'Orders/OrderID', //$select
                    $providersWrapper
                );
            $this->fail('An expected ODataException for traversal on select without expansion has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Only navigation properties specified in expand option can be travered in select option,In order to treaverse', $odataException->getMessage());
        }
    }

    /**
     * Selection of a parent navigation property causes selection of child navigations
     * for example $expand=A/B/C, A/D/F & $select = A
     * case result to include A and subtree of A (i.e B/C and D/F)
     * with all immediate properties of A, B, C, D and F.
     */
    public function testInclusionOfSubTreeDueToParentInclusion1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $ordersResourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $orderResourceType = $ordersResourceSetWrapper->getResourceType();
        //First test with explicit selection and no '*' application
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $ordersResourceSetWrapper,
            $orderResourceType,
            null,
            null,
            null,
            'Order_Details/Product,Order_Details/Order', // $expand
            'Order_Details', //$select
            $providersWrapper
        );
        //expand option is present
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We did not applied '*' for root resource set i.e. 'Orders'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        //selectSubTree flag for root resource set is false (canSelectAllProperities is true for '*' and selectsubTree
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //there is 1 child nodes for the root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 1);
        //The child nodes are 'ExpandProjectionNode' for 'Order_Details'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //'Order_Details is last sub segment in select path segment, means result should include
        //all properties of 'Order_Details' and the sub-tree Product, Order should be included in the result
        $this->assertTrue($childNodes['Order_Details']->canSelectAllProperties());
        //'Order_Details' has two children
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        $this->assertEquals(count($childNodes), 2);
        //The child nodes of 'Order_Details' are 'Product' and 'Order'
        //In the metadata provider the order of registering of these
        //navigation properties are 'Order' followed by 'Product'
        //Check whether parser sort the nodes accordingly
        $i = 0;
        foreach ($childNodes as $propertyName => $childNode) {
            if ($i == 0) {
                $this->assertEquals($propertyName, 'Order');
            } elseif ($i == 1) {
                $this->assertEquals($propertyName, 'Product');
            }

            ++$i;
        }
    }

    /**
     * Selection of a parent navigation property casues selection of child navigations
     * for example $expand=A/B/C & $select = A/B
     * case result to include A and subtree of A (i.e B/C)
     * but result won't include immediate properties of A, but include immediate
     * properties of B and C.
     */
    public function testInclusionOfSubTreeDueToParentInclusion2()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $ordersResourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $orderResourceType = $ordersResourceSetWrapper->getResourceType();

        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $ordersResourceSetWrapper,
            $orderResourceType,
            null,
            null,
            null,
            'Order_Details/Product/Order_Details/Product, Order_Details/Product/Order_Details/Order', //$expand
            'Order_Details/Product', //$select
            $providersWrapper
        );

        //expand option is present
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We did not applied '*' for root resource set i.e. 'Orders'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        //selectSubTree flag for root resource set is false (canSelectAllProperities is true for '*' and selectsubTree
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //there is 1 child nodes for the root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 1);
        //The child nodes are 'ExpandProjectionNode' for 'Order_Details'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //Properties of 'Order_Details' cannot be selected, its selectSubTree flag is false
        $this->assertFalse($childNodes['Order_Details']->canSelectAllProperties());
        //There is one child node for 'Order_Details', 'Product'
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Product', $childNodes));
        $this->assertTrue($childNodes['Product'] instanceof ExpandedProjectionNode);
        //All properties of 'Product' should be selected, as its selectSubTree flag is true (because it is last segment)
        $this->assertTrue($childNodes['Product']->canSelectAllProperties());
        //Product has one child node 'Order_Details'
        $childNodes = $childNodes['Product']->getChildNodes();
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //This is the Order_Details at level 3 child node of Product
        //Product is last segment means properties of all nodes in the sub-tree should be included in the result
        $this->assertTrue($childNodes['Order_Details']->canSelectAllProperties());
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        //Order_Details at level 3 has 2 child nodes
        $this->assertEquals(count($childNodes), 2);
        $this->assertTrue(array_key_exists('Product', $childNodes));
        $this->assertTrue($childNodes['Product'] instanceof ExpandedProjectionNode);
        $this->assertTrue(array_key_exists('Order', $childNodes));
        $this->assertTrue($childNodes['Order'] instanceof ExpandedProjectionNode);
        //Both child nodes's all properties should be included in the result
        $this->assertTrue($childNodes['Product']->canSelectAllProperties());
        $this->assertTrue($childNodes['Order']->canSelectAllProperties());
    }

    /**
     * Once client applied selection clause, navigation properties specified in the expand clause
     * will included in the result only if they are selected. For example:
     * $expand=A/B, X/Y & select=A
     * The result will include only A and associated B (with all properties). X/Y will be
     * ignored as they are not selected.
     */
    public function testRemovalOfSubTreeWhichIsExpandedButNotSelected1()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $orderDetailsResourceSetWrapper = $providersWrapper->resolveResourceSet('Order_Details');
        $orderDetailsResourceType = $orderDetailsResourceSetWrapper->getResourceType();

        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $orderDetailsResourceSetWrapper,
            $orderDetailsResourceType,
            null,
            null,
            null,
            'Product, Order', //$expand
            'Product', //$select
            $providersWrapper
        );

        //expand option is present
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We did not applied '*' for root resource set i.e. to 'Order_Details'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        //selectSubTree flag for root resource set is false (canSelectAllProperities is true for '*' and selectsubTree
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //there should be only 1 child nodes for the root, 'Orders' will not be there in the result as its not
        //selected
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 1);
        //The child node is 'ExpandProjectionNode' for 'Product'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Product', $childNodes));
        $this->assertTrue($childNodes['Product'] instanceof ExpandedProjectionNode);
        //All properties of 'Product' should be selected as its last node
        $this->assertTrue($childNodes['Product']->canSelectAllProperties());
        //There is no child node for 'Product'
        $childNodes = $childNodes['Product']->getChildNodes();
        $this->assertEquals(count($childNodes), 0);

        //----------------------------------------------------------------------------------------------------

        //Test the same case but with one more level of navigation
        $ordersResourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $orderResourceType = $ordersResourceSetWrapper->getResourceType();
        //Here parser should discard the last expanded node 'Orders' in 'Order_Details/Product/Order_Details/Order'
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $ordersResourceSetWrapper,
            $orderResourceType,
            null,
            null,
            null,
            'Order_Details/Product/Order_Details/Product, Order_Details/Product/Order_Details/Order', //$expand
            'Order_Details/Product/Order_Details/Product', //$select
            $providersWrapper
        );

        //expand option is present
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We did not applied '*' for root resource set i.e. to 'Order_Details'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        //selectSubTree flag for root resource set is false (canSelectAllProperities is true for '*' and selectsubTree
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 1);
        //The child node is 'ExpandProjectionNode' for 'Order_Details'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //All properties of 'Order_Details' should not be selected
        $this->assertFalse($childNodes['Order_Details']->canSelectAllProperties());
        //The child node is 'ExpandProjectionNode' for 'Product'
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        $this->assertTrue($childNodes['Product'] instanceof ExpandedProjectionNode);
        //All properties of 'Product' should not be selected
        $this->assertFalse($childNodes['Product']->canSelectAllProperties());
        //The child node is 'ExpandProjectionNode' for 'Order_Details' this is level 3 segment
        $childNodes = $childNodes['Product']->getChildNodes();
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //In the expand clause there are 2 child segments after level 3 'ORder_Details' segment
        //namely 'Product' and 'Order', but only is selected in select clause i.e. 'Product' so
        //'Order' will be removed from the tree.
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        //count is 1 not 2
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('Product', $childNodes));
        $this->assertTrue($childNodes['Product'] instanceof ExpandedProjectionNode);
    }

    /**
     * Selection of a parent navigation property casues selection of child navigations
     * for example $expand=A/B/C, A/D/F & $select = A
     * case result to include A and subtree of A (i.e B/C and D/F)
     * But selection of immediate properties of A with '*' cause to ignore the
     * child nodes if they are not selected explicitly.
     *  $expand=A/B/C, A/D/F & $select = A/*
     *      cause to ingore B/C and D/F
     *  $expand=A/B/C, A/D/F & $select = A/*, A/B
     *      cuase of include immediate propertises of A, select B and ignore D.
     */
    public function testRemovalOfSubTreeWhichIsExpandedButNotSelected2()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        //Selecting immediate properties of 'Order_Details' will de-select subtree of
        //'Order_Details' if they are not selected explicitly
        $ordersResourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $orderResourceType = $ordersResourceSetWrapper->getResourceType();
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $ordersResourceSetWrapper,
            $orderResourceType,
            null,
            null,
            null,
            'Order_Details/Product, Order_Details/Order', //$expand
            'Order_Details/*', //$select
            $providersWrapper
        );

        //expand option is present
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        //We did not applied '*' for root resource set i.e. to 'Orders'
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        //selectSubTree flag for root resource set is false (canSelectAllProperities is true for '*' and selectsubTree
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //There is a one child node for root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 1);
        //The child node is 'ExpandProjectionNode' for 'Order_Details'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        //All immediate properties of 'Order_Details' should be selected as '*' applied to it
        $this->assertTrue($childNodes['Order_Details']->canSelectAllImmediateProperties());
        $this->assertTrue($childNodes['Order_Details']->canSelectAllProperties());
        //There is no child node for 'Product' because of the application of '*'
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        $this->assertEquals(count($childNodes), 0);
    }

    /**
     * Only navigation property can come as intermediate path segment
     * Primitive/Bag/Complex types should be the last segment.
     */
    public function testPrimitiveBagComplexAsIntermediateSegments()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $customersResourceSetWrapper = $providersWrapper->resolveResourceSet('Customers');
        $customerResourceType = $customersResourceSetWrapper->getResourceType();
        //Test using primitive type as navigation
        try {
            $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
                $customersResourceSetWrapper,
                $customerResourceType,
                null,
                null,
                null,
                'Orders', //$expand
                'Orders/OrderID/*', //$select
                $providersWrapper
            );
            $this->fail('An expected ODataException usage of primitive type as navigation property has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('Property \'OrderID\' on type \'Order\' is of primitive type and cannot be used as a navigation property.', $odataException->getMessage());
        }

        //Test using complex type as navigation
        try {
            $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
                $customersResourceSetWrapper,
                $customerResourceType,
                null,
                null,
                null,
                'Orders', //$expand
                'Address/HouseNumber', //$select
                $providersWrapper
            );
            $this->fail('An expected ODataException usage of complex type as navigation property has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith('select doesn\'t support selection of properties of complex type. The property \'Address\' on type \'Customer\' is a complex type', $odataException->getMessage());
        }

        $employeesResourceSetWrapper = $providersWrapper->resolveResourceSet('Employees');
        $employeeResourceType = $employeesResourceSetWrapper->getResourceType();
        //Test using bag type as navigation
        try {
            $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
                $employeesResourceSetWrapper,
                $employeeResourceType,
                null,
                null,
                null,
                null, //$expand
                'Emails/ABC', //$select
                $providersWrapper
            );
            $this->fail('An expected ODataException usage of bag type as navigation property has not been thrown');
        } catch (ODataException $odataException) {
            $this->assertStringStartsWith(
                'The selection from property \'Emails\' on type \'Employee\' is not valid. The select query option does not support selection items from a bag property',
                $odataException->getMessage()
            );
        }
    }

    /**
     * If last sub path segment specified in the select clause does not appear in the prjection tree,
     * then parser will create 'ProjectionNode' for them.
     */
    public function testProjectionNodeCreation()
    {
        $northWindMetadata = NorthWindMetadata::Create();
        $queryProvider = new NorthWindQueryProvider();
        $configuration = new ServiceConfiguration($northWindMetadata);
        $configuration->setEntitySetAccessRule('*', EntitySetRights::ALL);
        $providersWrapper = new ProvidersWrapper(
            $northWindMetadata, //IMetadataProvider implementation
            $queryProvider, //IDataServiceQueryProvider implementation (set to null)
            $configuration, //Service configuration
            false
        );
        $ordersResourceSetWrapper = $providersWrapper->resolveResourceSet('Orders');
        $orderResourceType = $ordersResourceSetWrapper->getResourceType();
        //test selection of properties which is not included in expand clause
        //1 primitve ('Order_Details/UnitPrice') and 1 link to navigation 'Customer'
        $projectionTreeRoot = ExpandProjectionParser::parseExpandAndSelectClause(
            $ordersResourceSetWrapper,
            $orderResourceType,
            null,
            null,
            null,
            'Order_Details', // $expand
            'Order_Details/UnitPrice, Customer', //$select
            $providersWrapper
        );
        //expand option is absent
        $this->assertTrue($projectionTreeRoot->isExpansionSpecified());
        //select is applied
        $this->assertTrue($projectionTreeRoot->isSelectionSpecified());
        $this->assertFalse($projectionTreeRoot->canSelectAllImmediateProperties());
        $this->assertFalse($projectionTreeRoot->canSelectAllProperties());
        //there are 2 child nodes for the root
        $this->assertEquals(count($projectionTreeRoot->getChildNodes()), 2);
        //The child nodes one 'ProjectionNode' Customer and one 'ExpandedProjectionNode' for 'Order'
        $childNodes = $projectionTreeRoot->getChildNodes();
        $this->assertTrue(array_key_exists('Order_Details', $childNodes));
        $this->assertTrue(array_key_exists('Customer', $childNodes));
        $this->assertTrue($childNodes['Order_Details'] instanceof ExpandedProjectionNode);
        $this->assertTrue($childNodes['Customer'] instanceof ProjectionNode);
        //'Order_Detials' has a child node
        $childNodes = $childNodes['Order_Details']->getChildNodes();
        $this->assertEquals(count($childNodes), 1);
        $this->assertTrue(array_key_exists('UnitPrice', $childNodes));
        $this->assertTrue($childNodes['UnitPrice'] instanceof ProjectionNode);
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
