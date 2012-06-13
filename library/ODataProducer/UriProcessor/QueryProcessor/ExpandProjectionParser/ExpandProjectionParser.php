<?php
/** 
 * Class used to parse and validate $expand and $select query options and 
 * create a 'Projection Tree' from these options, Syntax of the clause is:
 * 
 * ExpandOrSelectPath : PathSegment [, PathSegment]     
 * PathSegment        : SubPathSegment [\ SubPathSegment]
 * SubPathSegment     : DottedIdentifier
 * SubPathSegment     : * (Only if the SubPathSegment is last segment and 
 *                      belongs to select path)
 * DottedIdentifier   : Identifier [. Identifier]
 * Identifier         : NavigationProperty
 * Identifier         : NonNavigationProperty (Only if if the SubPathSegment 
 *                      is last segment and belongs to select path)
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpandProjectionParser
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
namespace ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Configuration\DataServiceConfiguration;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
/**
 * $expand and $select clause parser.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpandProjectionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ExpandProjectionParser
{
    /**
     * Holds reference to the wrapper class over IDataServiceMetadataProvider 
     * and IDataServiceQueryProvider. 
     * 
     * @var MetadataQueryProviderWrapper
     */
    private $_providerWrapper;

    /**
     * Holds reference to the root of 'Projection Tree'
     * 
     * @var RootProjectionNode
     */
    private $_rootProjectionNode;

    /**
     * Creates new instance of ExpandProjectionParser
     * 
     * @param MetadataQueryProviderWrapper $providerWrapper Reference to 
     * metadata and query provider wrapper.
     */
    private function __construct(MetadataQueryProviderWrapper $providerWrapper)
    {
        $this->_providerWrapper = $providerWrapper;
    }

    /**
     * Parse the given expand and select clause, validate them 
     * and build 'Projection Tree'
     * 
     * @param ResourceSetWrapper           $resourceSetWrapper The resource set
     *                                                         identified by the
     *                                                         resource path uri.
     * @param ResourceType                 $resourceType       The resource type of
     *                                                         entities identified 
     *                                                         by the resource 
     *                                                         path uri.
     * @param InternalOrderByInfo          $internalOrderInfo  The top level sort
     *                                                         information, this 
     *                                                         will be set if the 
     *                                                         $skip, $top is 
     *                                                         specified in the 
     *                                                         request uri or Server 
     *                                                         side paging is
     *                                                         enabled for top level 
     *                                                         resource
     * @param int                          $skipCount          The value of $skip 
     *                                                         option applied to the 
     *                                                         top level resource
     *                                                         set identified by the 
     *                                                         resource path uri 
     *                                                         null means $skip 
     *                                                         option is not present.
     * @param int                          $takeCount          The minimum among the
     *                                                         value of $top option 
     *                                                         applied to and 
     *                                                         page size configured
     *                                                         for the top level
     *                                                         resource 
     *                                                         set identified
     *                                                         by the resource 
     *                                                         path uri.
     *                                                         null means $top option
     *                                                         is not present and/or
     *                                                         page size is not 
     *                                                         configured for top
     *                                                         level resource set.
     * @param string                       $expand             The value of $expand
     *                                                         clause
     * @param string                       $select             The value of $select
     *                                                         clause
     * @param MetadataQueryProviderWrapper $providerWrapper    Reference to metadata
     *                                                         and query provider
     *                                                         wrapper
     * 
     * @return RootProjectionNode Returns root of the 'Projection Tree'
     * 
     * @throws ODataException If any error occur while parsing expand and/or
     *                        select clause
     */
    public static function parseExpandAndSelectClause(ResourceSetWrapper $resourceSetWrapper,
        ResourceType $resourceType, $internalOrderInfo, $skipCount, $takeCount, $expand,
        $select, MetadataQueryProviderWrapper $providerWrapper
    ) {
        $expandSelectParser = new ExpandProjectionParser($providerWrapper);
        $expandSelectParser->_rootProjectionNode 
            = new RootProjectionNode(
                $resourceSetWrapper, $internalOrderInfo, $skipCount, 
                $takeCount, null, $resourceType
            );
        $expandSelectParser->_parseExpand($expand);
        $expandSelectParser->_parseSelect($select);
        return $expandSelectParser->_rootProjectionNode;
    }

    /**
     * Read the given expand clause and build 'Projection Tree', 
     * do nothing if the clause is null
     * 
     * @param string $expand Value of $expand clause.
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while reading expand clause
     *                        or building the projection tree
     */
    private function _parseExpand($expand)
    {
        if (!is_null($expand)) {
            $pathSegments = $this->_readExpandOrSelect($expand, false);
            $this->_buildProjectionTree($pathSegments);
            $this->_rootProjectionNode->setExpansionSpecified();
        }
    }

    /**
     * Read the given select clause and apply selection to the 
     * 'Projection Tree', mark the entire tree as selected if this
     * clause is null
     * Note: _parseExpand should to be called before the invocation 
     * of this function so that basic 'Projection Tree' with expand 
     * information will be ready.
     * 
     * @param string $select Value of $select clause.
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while reading expand clause
     *                        or applying selection to projection tree
     */
    private function _parseSelect($select)
    {
        if (is_null($select)) {
            $this->_rootProjectionNode->markSubtreeAsSelected();
        } else {
            $pathSegments = $this->_readExpandOrSelect($select, true);
            $this->_applySelectionToProjectionTree($pathSegments);
            $this->_rootProjectionNode->setSelectionSpecified();
            $this->_rootProjectionNode->removeNonSelectedNodes();
            $this->_rootProjectionNode->removeNodesAlreadyIncludedImplicitly();
            //TODO: Move sort to parseExpandAndSelectClause function
            $this->_rootProjectionNode->sortNodes();
        }
    }

    /**
     * Build 'Projection Tree' from the given expand path segments
     * 
     * @param array(array(string)) $expandPathSegments Collection of expand 
     *                                                 paths.
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while processing the expand
     *                        path segments.
     */
    private function _buildProjectionTree($expandPathSegments)
    {
        foreach ($expandPathSegments as $expandSubPathSegments) {
            $currentNode = $this->_rootProjectionNode;
            foreach ($expandSubPathSegments as $expandSubPathSegment) {
                $resourceSetWrapper = $currentNode->getResourceSetWrapper();
                $resourceType = $currentNode->getResourceType();
                $resourceProperty 
                    = $resourceType->tryResolvePropertyTypeByName(
                        $expandSubPathSegment
                    );
                if (is_null($resourceProperty)) {
                    ODataException::createSyntaxError(
                        Messages::expandProjectionParserPropertyNotFound(
                            $resourceType->getFullName(), 
                            $expandSubPathSegment, 
                            false
                        )
                    );
                } else if ($resourceProperty->getTypeKind() != ResourceTypeKind::ENTITY) {
                    ODataException::createBadRequestError(
                        Messages::expandProjectionParserExpandCanOnlyAppliedToEntity(
                            $resourceType->getFullName(), 
                            $expandSubPathSegment
                        )
                    );
                }

                $resourceSetWrapper = $this->_providerWrapper
                    ->getResourceSetWrapperForNavigationProperty(
                        $resourceSetWrapper, 
                        $resourceType, 
                        $resourceProperty
                    );
                if (is_null($resourceSetWrapper)) {
                    ODataException::createBadRequestError(
                        Messages::badRequestInvalidPropertyNameSpecified(
                            $resourceType->getFullName(), 
                            $expandSubPathSegment
                        )
                    );
                }

                $singleResult 
                    = $resourceProperty->isKindOf(
                        ResourcePropertyKind::RESOURCE_REFERENCE
                    );
                $resourceSetWrapper->checkResourceSetRightsForRead($singleResult);
                $pageSize = $resourceSetWrapper->getResourceSetPageSize();
                $internalOrderByInfo = null;
                if ($pageSize != 0 && !$singleResult) {
                    $this->_rootProjectionNode->setPagedExpandedResult(true);
                    $rt = $resourceSetWrapper->getResourceType();
                    //assert($rt != null)
                    $keys = array_keys($rt->getKeyProperties());
                    //assert(!empty($keys))
                    $orderBy = null;
                    foreach ($keys as $key) {
                        $orderBy = $orderBy . $key . ', ';
                    }

                    $orderBy = rtrim($orderBy, ', ');
                    try {
                        $internalOrderByInfo = OrderByParser::parseOrderByClause(
                            $resourceSetWrapper, 
                            $rt,
                            $orderBy, 
                            $this->_providerWrapper
                        );            
                    } catch (ODataException $odataException) {
                        throw $odataException;
                    }
                }

                $node = $currentNode->findNode($expandSubPathSegment);
                if (is_null($node)) {
                    $maxResultCount = $this->_providerWrapper
                        ->getConfiguration()->getMaxResultsPerCollection();
                    $node = new ExpandedProjectionNode(
                        $expandSubPathSegment, 
                        $resourceProperty, 
                        $resourceSetWrapper,
                        $internalOrderByInfo,
                        null, 
                        $pageSize == 0 ? null : $pageSize, 
                        $maxResultCount == PHP_INT_MAX ? null : $maxResultCount
                    );
                    $currentNode->addNode($node);
                }

                $currentNode = $node;
            }
        } 
    }

    /**
     * Modify the 'Projection Tree' to include selection details
     * 
     * @param array(array(string)) $selectPathSegments Collection of select 
     *                                                 paths.
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while processing select
     *                        path segments
     */
    private function _applySelectionToProjectionTree($selectPathSegments)
    {
        foreach ($selectPathSegments as $selectSubPathSegments) {
            $currentNode = $this->_rootProjectionNode;
            $subPathCount = count($selectSubPathSegments);
            foreach ($selectSubPathSegments as $index => $selectSubPathSegment) {
                if (!($currentNode instanceof RootProjectionNode) 
                    && !($currentNode instanceof ExpandedProjectionNode)
                ) {
                    ODataException::createBadRequestError(
                        Messages::expandProjectionParserPropertyWithoutMatchingExpand(
                            $currentNode->getPropertyName()
                        )
                    );   
                }

                $currentNode->setSelectionFound();
                $isLastSegment = ($index == $subPathCount - 1);
                if ($selectSubPathSegment === '*') {
                    $currentNode->setSelectAllImmediateProperties();
                    break;
                }

                $currentResourceType = $currentNode->getResourceType();
                $resourceProperty 
                    = $currentResourceType->tryResolvePropertyTypeByName(
                        $selectSubPathSegment
                    );
                if (is_null($resourceProperty)) {
                    ODataException::createSyntaxError(
                        Messages::expandProjectionParserPropertyNotFound(
                            $currentResourceType->getFullName(), 
                            $selectSubPathSegment, 
                            true
                        )
                    );
                }

                if (!$isLastSegment) {
                    if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                        ODataException::createBadRequestError(
                            Messages::expandProjectionParserBagPropertyAsInnerSelectSegment(
                                $currentResourceType->getFullName(), 
                                $selectSubPathSegment
                            )
                        );
                    } else if ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                        ODataException::createBadRequestError(
                            Messages::expandProjectionParserPrimitivePropertyUsedAsNavigationProperty(
                                $currentResourceType->getFullName(), 
                                $selectSubPathSegment
                            )
                        );
                    } else if ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                        ODataException::createBadRequestError(
                            Messages::expandProjectionParserComplexPropertyAsInnerSelectSegment(
                                $currentResourceType->getFullName(), 
                                $selectSubPathSegment
                            )
                        );
                    } else if ($resourceProperty->getKind() != ResourcePropertyKind::RESOURCE_REFERENCE && $resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE) {
                        ODataException::createInternalServerError(
                            Messages::expandProjectionParserUnexpectedPropertyType()
                        );
                    }
                }

                $node = $currentNode->findNode($selectSubPathSegment);
                if (is_null($node)) {
                    if (!$isLastSegment) {
                        ODataException::createBadRequestError(
                            Messages::expandProjectionParserPropertyWithoutMatchingExpand(
                                $selectSubPathSegment
                            )
                        );
                    }

                    $node = new ProjectionNode($selectSubPathSegment, $resourceProperty);
                    $currentNode->addNode($node);
                }

                $currentNode = $node;
                if ($currentNode instanceof ExpandedProjectionNode 
                    && $isLastSegment
                ) {
                    $currentNode->setSelectionFound();
                    $currentNode->markSubtreeAsSelected();
                }
            }
        }
    }

    /**
     * Read expand or select clause.
     * 
     * @param string  $value    expand or select clause to read.
     * @param boolean $isSelect true means $value is value of select clause
     *                          else value of expand clause.
     * 
     * @return array(array) An array of 'PathSegment's, each of which is array
     *                      of 'SubPathSegment's
     */
    private function _readExpandOrSelect($value, $isSelect)
    {
        $pathSegments = array();
        $lexer = new ExpressionLexer($value);
        $i = 0;
        while ($lexer->getCurrentToken()->Id != ExpressionTokenId::END) {
            $lastSegment = false;
            if ($isSelect 
                && $lexer->getCurrentToken()->Id == ExpressionTokenId::STAR
            ) {
                $lastSegment = true;
                $subPathSegment = $lexer->getCurrentToken()->Text;
                $lexer->nextToken();
            } else {
                $subPathSegment = $lexer->readDottedIdentifier();
            }

            if (!array_key_exists($i, $pathSegments)) {
                $pathSegments[$i] = array();
            }

            $pathSegments[$i][] = $subPathSegment;
            $tokenId = $lexer->getCurrentToken()->Id;
            if ($tokenId != ExpressionTokenId::END) {
                if ($lastSegment || $tokenId != ExpressionTokenId::SLASH) {
                    $lexer->validateToken(ExpressionTokenId::COMMA);
                    $i++;
                }

                $lexer->nextToken();
            }
        }

        return $pathSegments;
    }
}
?>