<?php
/**
 * Class to parse $orderby query option and perform syntax validation 
 * and build 'OrderBy Tree' along with next level of validation, the 
 * created tree is used for building sort functions and 'OrderByInfo' structure.
 * 
 * The syntax of orderby clause is:
 * 
 * OrderByClause         : OrderByPathSegment [, OrderByPathSegment]*
 * OrderByPathSegment    : OrderBySubPathSegment[/OrderBySubPathSegment]*[asc|desc]?
 * OrderBySubPathSegment : identifier
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\UriProcessor\QueryProcessor\AnonymousFunction;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\Metadata\Type\Binary;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
/**
 * $orderby parser.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class OrderByParser
{
    /**
     * Collection of anonymous sorter function corrosponding to 
     * each orderby path segment.
     * 
     * @var array(AnonymousFunction)
     */
    private $_comparisonFunctions = array();

    /**
     * The top level sorter function generated from orderby path 
     * segments.
     * 
     * @var AnonymousFunction
     */
    private $_topLevelComparisonFunction;

    /**
     * The structure holds information about the navigation properties 
     * used in the orderby clause (if any) and orderby path if IDSQP 
     * implementor want to perform sorting.
     * 
     * @var OrderByInfo
     */
    private $_orderByInfo;

    /**
     * Reference to metadata and query provider wrapper
     * 
     * @var MetadataQueryProviderWrapper
     */
    private $_providerWrapper;

    /**
     * This object will be of type of the resource set identified by the 
     * request uri.
     * 
     * @var mixed
     */
    private $_dummyObject;

    /**
     * Creates new instance of OrderByParser
     * 
     * @param MetadataQueryProviderWrapper $providerWrapper Reference to metadata
     *                                                      and query provider 
     *                                                      wrapper
     */
    private function __construct(MetadataQueryProviderWrapper $providerWrapper)
    {
        $this->_providerWrapper = $providerWrapper;
    }

    /**
     * This function perform the following tasks with the help of internal helper
     * functions
     * (1) Read the orderby clause and perform basic syntax errors
     * (2) Build 'Order By Tree', creates anonymous sorter function for each leaf 
     *     node and check for error
     * (3) Build 'OrderInfo' structure, holds information about the navigation 
     *     properties used in the orderby clause (if any) and orderby path if 
     *     IDSQP implementor want to perform sorting
     * (4) Build top level anonymous sorter function
     * (4) Release resources hold by the 'Order By Tree'
     * (5) Create 'InternalOrderInfo' structure, which wraps 'OrderInfo' and top 
     *     level sorter function 
     * 
     * @param ResourceSetWrapper           $resourceSetWrapper ResourceSetWrapper for
     *                                                         the resource targetted
     *                                                         by resource path.
     * @param ResourceType                 $resourceType       ResourceType for the 
     *                                                         resource targetted
     *                                                         by resource path.
     * @param string                       $orderBy            The orderby clause.
     * @param MetadataQueryProviderWrapper $providerWrapper    Reference to the 
     *                                                         wrapper for IDSQP
     *                                                         and IDSMP impl.
     * 
     * @return InternalOrderByInfo
     * 
     * @throws ODataException If any error occur while parsing orderby clause
     */
    public static function parseOrderByClause(ResourceSetWrapper $resourceSetWrapper, 
        ResourceType $resourceType, $orderBy, 
        MetadataQueryProviderWrapper $providerWrapper
    ) {
        $orderByParser = new OrderByParser($providerWrapper);
        try {
            $orderByParser->_dummyObject 
                = $resourceType->getInstanceType()->newInstance();
        } catch (\ReflectionException $reflectionException) {
            throw ODataException::createInternalServerError(
                Messages::orderByParserFailedToCreateDummyObject()
            );
        }
        $orderByParser->_rootOrderByNode 
            = new OrderByRootNode($resourceSetWrapper, $resourceType);
        $orderByPathSegments = $orderByParser->_readOrderBy($orderBy);
        $orderByParser->_buildOrderByTree($orderByPathSegments);
        $orderByParser->_createOrderInfo($orderByPathSegments);
        $orderByParser->_generateTopLevelComparisonFunction();
        //Recursively release the resources
        $orderByParser->_rootOrderByNode->free();
        //creates internal order info wrapper 
        $internalOrderInfo = new InternalOrderByInfo(
            $orderByParser->_orderByInfo, 
            $orderByParser->_comparisonFunctions, 
            $orderByParser->_topLevelComparisonFunction, 
            $orderByParser->_dummyObject
        );
        unset($orderByParser->_orderByInfo);
        unset($orderByParser->_topLevelComparisonFunction);
        return $internalOrderInfo;        
    }

    /**
     * Build 'OrderBy Tree' from the given orderby path segments, also build 
     * comparsion function for each path segment.
     * 
     * @param array(array) &$ordeyByPathSegments Collection of orderby path segments,
     *                                           this is passed by reference
     *                                           since we need this function to 
     *                                           modify this array in two cases:
     *                                           1. if asc or desc present, then the 
     *                                              corrosponding sub path segment 
     *                                              should be removed
     *                                           2. remove duplicate orderby path 
     *                                              segment
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while processing the orderby path 
     *                        segments
     */
    private function _buildOrderByTree(&$ordeyByPathSegments)
    {
        foreach ($ordeyByPathSegments as $index1 => &$ordeyBySubPathSegments) {
            $currentNode = $this->_rootOrderByNode;
            $currentObject = $this->_dummyObject;
            $ascending = true;
            $subPathCount = count($ordeyBySubPathSegments);
            // Check sort order is specified in the path, if so set a 
            // flag and remove that segment
            if ($subPathCount > 1) {
                if ($ordeyBySubPathSegments[$subPathCount - 1] === '*desc') {
                    $ascending = false;
                    unset($ordeyBySubPathSegments[$subPathCount - 1]);
                    $subPathCount--;
                } else if ($ordeyBySubPathSegments[$subPathCount - 1] === '*asc') {
                    unset($ordeyBySubPathSegments[$subPathCount - 1]);
                    $subPathCount--;
                }
            }

            $ancestors = array(
                $this->_rootOrderByNode->getResourceSetWrapper()->getName()
            );
            foreach ($ordeyBySubPathSegments as $index2 => $orderBySubPathSegment) {
                $isLastSegment = ($index2 == $subPathCount - 1);
                $resourceSetWrapper = null;
                $resourceType = $currentNode->getResourceType();
                $resourceProperty 
                    = $resourceType->tryResolvePropertyTypeByName(
                        $orderBySubPathSegment
                    );
                if (is_null($resourceProperty)) {
                    ODataException::createSyntaxError(
                        Messages::orderByParserPropertyNotFound(
                            $resourceType->getFullName(), $orderBySubPathSegment
                        )
                    );
                }

                if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                    ODataException::createBadRequestError(
                        Messages::orderByParserBagPropertyNotAllowed(
                            $resourceProperty->getName()
                        )
                    );
                } else if ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                    if (!$isLastSegment) {
                        ODataException::createBadRequestError(
                            Messages::orderByParserPrimitiveAsIntermediateSegment(
                                $resourceProperty->getName()
                            )
                        );
                    }

                    $type = $resourceProperty->getInstanceType();
                    if ($type instanceof Binary) {
                        ODataException::createBadRequestError(Messages::orderbyParserSortByBinaryPropertyNotAllowed($resourceProperty->getName()));
                    }
                } else if ($resourceProperty->getKind() == ResourcePropertyKind::RESOURCESET_REFERENCE 
                    || $resourceProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE
                ) {
                    $this->_assertion(
                        $currentNode instanceof OrderByRootNode 
                        || $currentNode instanceof OrderByNode
                    );
                    $resourceSetWrapper = $currentNode->getResourceSetWrapper();
                    $this->_assertion(!is_null($resourceSetWrapper));
                    $resourceSetWrapper 
                        = $this->_providerWrapper->getResourceSetWrapperForNavigationProperty(
                            $resourceSetWrapper, $resourceType, $resourceProperty
                        );
                    if (is_null($resourceSetWrapper)) {
                        ODataException::createBadRequestError(
                            Messages::badRequestInvalidPropertyNameSpecified(
                                $resourceType->getFullName(), $orderBySubPathSegment
                            )
                        );
                    }

                    if ($resourceProperty->getKind() == ResourcePropertyKind::RESOURCESET_REFERENCE) {
                        ODataException::createBadRequestError(
                            Messages::orderbyParserResourceSetReferenceNotAllowed(
                                $resourceProperty->getName(), $resourceType->getFullName()
                            )
                        );
                    }

                    $resourceSetWrapper->checkResourceSetRightsForRead(true);
                    if ($isLastSegment) {
                        ODataException::createBadRequestError(
                            Messages::orderByParserSortByNavigationPropertyIsNotAllowed(
                                $resourceProperty->getName()
                            )
                        );
                    }

                    $ancestors[] = $orderBySubPathSegment;
                } else if ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                    if ($isLastSegment) {
                        ODataException::createBadRequestError(
                            Messages::orderByParserSortByComplexPropertyIsNotAllowed(
                                $resourceProperty->getName()
                            )
                        );
                    }

                    $ancestors[] = $orderBySubPathSegment;
                } else {
                    ODataException::createInternalServerError(
                        Messages::orderByParserUnexpectedPropertyType()
                    );
                }

                $node = $currentNode->findNode($orderBySubPathSegment);
                if (is_null($node)) {
                    if ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                        $node = new OrderByLeafNode(
                            $orderBySubPathSegment, $resourceProperty, 
                            $ascending
                        );                        
                        $this->_comparisonFunctions[] 
                            = $node->buildComparisonFunction($ancestors);
                    } else if ($resourceProperty->getKind() == ResourcePropertyKind::RESOURCE_REFERENCE) {
                        $node = new OrderByNode(
                            $orderBySubPathSegment, $resourceProperty, 
                            $resourceSetWrapper
                        );
                        // Initialize this member variable (identified by 
                        // $resourceProperty) of parent object. 
                        try {
                            $dummyProperty 
                                = new \ReflectionProperty(
                                    $currentObject, $resourceProperty->getName()
                                );
                            $object = $resourceProperty->getInstanceType()->newInstance();
                            $dummyProperty->setValue($currentObject, $object);
                            $currentObject = $object;
                        } catch (\ReflectionException $reflectionException) {
                            throw ODataException::createInternalServerError(
                                Messages::orderByParserFailedToAccessOrInitializeProperty(
                                    $resourceProperty->getName(), $resourceType->getName()
                                )
                            );
                        }
                    } else if ($resourceProperty->getKind() == ResourcePropertyKind::COMPLEX_TYPE) {
                        $node = new OrderByNode($orderBySubPathSegment, $resourceProperty, null);
                        // Initialize this member variable
                        // (identified by $resourceProperty)of parent object. 
                        try {
                            $dummyProperty 
                                = new \ReflectionProperty(
                                    $currentObject, $resourceProperty->getName()
                                );
                            $object = $resourceProperty->getInstanceType()->newInstance();
                            $dummyProperty->setValue($currentObject, $object);
                            $currentObject = $object;
                        } catch (\ReflectionException $reflectionException) {
                            throw ODataException::createInternalServerError(
                                Messages::orderByParserFailedToAccessOrInitializeProperty(
                                    $resourceProperty->getName(), $resourceType->getName()
                                )
                            );
                        }
                    }

                    $currentNode->addNode($node);
                } else {
                    try {
                        $dummyProperty = new \ReflectionProperty(
                            $currentObject, $resourceProperty->getName()
                        );
                        $currentObject = $dummyProperty->getValue($currentObject);
                    } catch (\ReflectionException $reflectionException) {
                            throw ODataException::createInternalServerError(
                                Messages::orderByParserFailedToAccessOrInitializeProperty(
                                    $resourceProperty->getName(), 
                                    $resourceType->getName()
                                )
                            );
                    }

                    if ($node instanceof OrderByLeafNode) {
                        //remove duplicate orderby path
                        unset($ordeyByPathSegments[$index1]);
                    }
                }

                $currentNode = $node;
            }
        }
    }

    /**
     * Traverse 'Order By Tree' and create 'OrderInfo' structure
     * 
     * @param array(array) $ordeyByPaths The orderby paths.
     * 
     * @return OrderInfo
     * 
     * @throws ODataException In case parser found any tree inconsisitent 
     *                        state, throws unexpected state error 
     */
    private function _createOrderInfo($ordeyByPaths)
    {
        $orderByPathSegments = array();
        $navigationPropertiesInThePath = array();
        foreach ($ordeyByPaths as $index => $ordeyBySubPaths) {
            $currentNode = $this->_rootOrderByNode;
            $orderBySubPathSegments = array();
            foreach ($ordeyBySubPaths as $ordeyBySubPath) {
                $node = $currentNode->findNode($ordeyBySubPath);
                $this->_assertion(!is_null($node));
                $resourceProperty = $node->getResourceProperty();
                if ($node instanceof OrderByNode && !is_null($node->getResourceSetWrapper())) {
                    if (!array_key_exists($index, $navigationPropertiesInThePath)) {
                        $navigationPropertiesInThePath[$index] = array();
                    }

                    $navigationPropertiesInThePath[$index][] = $resourceProperty;
                }

                $orderBySubPathSegments[] = new OrderBySubPathSegment($resourceProperty);
                $currentNode = $node;
            }

            $this->_assertion($currentNode instanceof OrderByLeafNode);
            $orderByPathSegments[] = new OrderByPathSegment($orderBySubPathSegments, $currentNode->isAscending());
            unset($orderBySubPathSegments);
        }

        $this->_orderByInfo = new OrderByInfo($orderByPathSegments, empty($navigationPropertiesInThePath) ? null : $navigationPropertiesInThePath);
    }

    /**
     * Generates top tevel comparison function from sub comparison functions. 
     * 
     * @return void
     */
    private function _generateTopLevelComparisonFunction()
    {
        $comparsionFunctionCount = count($this->_comparisonFunctions);
        $this->_assertion($comparsionFunctionCount > 0);
        $parameters = $this->_comparisonFunctions[0]->getParameters();
        //$parameters[] = '&$matchLevel = 0';
        if ($comparsionFunctionCount == 1) {
            $this->_topLevelComparisonFunction = $this->_comparisonFunctions[0];
        } else {
            $code = null;
            for ($i = 0; $i < $comparsionFunctionCount; $i++) {
                $subComparsionFunctionName = substr($this->_comparisonFunctions[$i]->getReference(), 1);
                $code .= "\$result = call_user_func_array(chr(0) . '$subComparsionFunctionName', array($parameters[0], $parameters[1]));";
                $code .= "
                         if (\$result != 0) {
                            return \$result;
                         }
                         ";
            }

            $code .= "return \$result;";
            $this->_topLevelComparisonFunction = new AnonymousFunction($parameters, $code);
        }        
    }

    /**
     * Read orderby clause.
     * 
     * @param string $value orderby clause to read.
     * 
     * @return array(array) An array of 'OrderByPathSegment's, each of which 
     *                      is array of 'OrderBySubPathSegment's
     * 
     * @throws ODataException If any syntax error found while reading the clause
     */
    private function _readOrderBy($value)
    {
        $orderByPathSegments = array();
        $lexer = new ExpressionLexer($value);
        $i = 0;
        while ($lexer->getCurrentToken()->Id != ExpressionTokenId::END) {
            $orderBySubPathSegment = $lexer->readDottedIdentifier();
            if (!array_key_exists($i, $orderByPathSegments)) {
                $orderByPathSegments[$i] = array();
            }

            $orderByPathSegments[$i][] = $orderBySubPathSegment;
            $tokenId = $lexer->getCurrentToken()->Id;
            if ($tokenId != ExpressionTokenId::END) {
                if ($tokenId != ExpressionTokenId::SLASH) {
                    if ($tokenId != ExpressionTokenId::COMMA) {
                        $lexer->validateToken(ExpressionTokenId::IDENTIFIER);
                        $identifier = $lexer->getCurrentToken()->Text;
                        if ($identifier !== 'asc' && $identifier !== 'desc') {
                            // force lexer to throw syntax error as we found 
                            // unexpected identifier
                            $lexer->validateToken(ExpressionTokenId::DOT);
                        }

                        $orderByPathSegments[$i][] = '*' . $identifier;
                        $lexer->nextToken();
                        $tokenId = $lexer->getCurrentToken()->Id;
                        if ($tokenId != ExpressionTokenId::END) {
                            $lexer->validateToken(ExpressionTokenId::COMMA);
                            $i++;
                        }
                    } else {
                        $i++;
                    }
                }

                $lexer->nextToken();
            }
        }

        return $orderByPathSegments;
    }

    /**
     * Assert that the given condition is true, if false throw 
     * ODataException for unexpected state
     * 
     * @param boolean $condition The condition to assert
     * 
     * @return void
     * 
     * @throws ODataException
     */
    private function _assertion($condition)
    {
        if (!$condition) {
            ODataException::createInternalServerError(
                Messages::orderByParserUnExpectedState()
            );
        }
    }
}
?>