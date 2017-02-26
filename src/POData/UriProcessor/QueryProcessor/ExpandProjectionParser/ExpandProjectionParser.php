<?php

namespace POData\UriProcessor\QueryProcessor\ExpandProjectionParser;

use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionLexer;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionTokenId;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;

/**
 * Class ExpandProjectionParser.
 *
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
 */
class ExpandProjectionParser
{
    /**
     * The wrapper of IMetadataProvider and IQueryProvider
     * .
     *
     * @var ProvidersWrapper
     */
    private $_providerWrapper;

    /**
     * Holds reference to the root of 'Projection Tree'.
     *
     * @var RootProjectionNode
     */
    private $_rootProjectionNode;

    /**
     * Creates new instance of ExpandProjectionParser.
     *
     * @param ProvidersWrapper $providerWrapper Reference to metadata and query provider wrapper
     */
    private function __construct(ProvidersWrapper $providerWrapper)
    {
        $this->_providerWrapper = $providerWrapper;
    }

    /**
     * Parse the given expand and select clause, validate them
     * and build 'Projection Tree'.
     *
     * @param ResourceSetWrapper  $resourceSetWrapper The resource set identified by the resource path uri
     * @param ResourceType        $resourceType       The resource type of entities identified by the resource path uri
     * @param InternalOrderByInfo $internalOrderInfo  The top level sort information, this will be set if the $skip, $top is
     *                                                specified in the
     *                                                request uri or Server
     *                                                side paging is
     *                                                enabled for top level
     *                                                resource
     * @param int                 $skipCount          The value of $skip option applied to the top level resource
     *                                                set identified by the
     *                                                resource path uri
     *                                                null means $skip
     *                                                option is not present
     * @param int                 $takeCount          The minimum among the value of $top option applied to and
     *                                                page size configured
     *                                                for the top level
     *                                                resource
     *                                                set identified
     *                                                by the resource
     *                                                path uri.
     *                                                null means $top option
     *                                                is not present and/or
     *                                                page size is not
     *                                                configured for top
     *                                                level resource set
     * @param string              $expand             The value of $expand clause
     * @param string              $select             The value of $select clause
     * @param ProvidersWrapper    $providerWrapper    Reference to metadata and query provider wrapper
     *
     * @throws ODataException If any error occur while parsing expand and/or select clause
     *
     * @return RootProjectionNode Returns root of the 'Projection Tree'
     */
    public static function parseExpandAndSelectClause(
        ResourceSetWrapper $resourceSetWrapper,
        ResourceType $resourceType,
        $internalOrderInfo,
        $skipCount,
        $takeCount,
        $expand,
        $select,
        ProvidersWrapper $providerWrapper
    ) {
        $parser = new self($providerWrapper);
        $parser->_rootProjectionNode = new RootProjectionNode(
            $resourceSetWrapper,
            $internalOrderInfo,
            $skipCount,
            $takeCount,
            null,
            $resourceType
        );
        $parser->_parseExpand($expand);
        $parser->_parseSelect($select);

        return $parser->_rootProjectionNode;
    }

    /**
     * Read the given expand clause and build 'Projection Tree',
     * do nothing if the clause is null.
     *
     * @param string $expand Value of $expand clause
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
     * @param string $select Value of $select clause
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
     * Build 'Projection Tree' from the given expand path segments.
     *
     * @param array(array(string)) $expandPathSegments Collection of expand paths
     *
     * @throws ODataException If any error occurs while processing the expand path segments
     */
    private function _buildProjectionTree($expandPathSegments)
    {
        foreach ($expandPathSegments as $expandSubPathSegments) {
            $currentNode = $this->_rootProjectionNode;
            foreach ($expandSubPathSegments as $expandSubPathSegment) {
                $resourceSetWrapper = $currentNode->getResourceSetWrapper();
                $resourceType = $currentNode->getResourceType();
                $resourceProperty
                    = $resourceType->resolveProperty(
                        $expandSubPathSegment
                    );
                if (is_null($resourceProperty)) {
                    throw ODataException::createSyntaxError(
                        Messages::expandProjectionParserPropertyNotFound(
                            $resourceType->getFullName(),
                            $expandSubPathSegment,
                            false
                        )
                    );
                } elseif ($resourceProperty->getTypeKind() != ResourceTypeKind::ENTITY) {
                    throw ODataException::createBadRequestError(
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
                    throw ODataException::createBadRequestError(
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
                    $internalOrderByInfo = OrderByParser::parseOrderByClause(
                        $resourceSetWrapper,
                        $rt,
                        $orderBy,
                        $this->_providerWrapper
                    );
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
     * Modify the 'Projection Tree' to include selection details.
     *
     * @param array(array(string)) $selectPathSegments Collection of select
     *                                                 paths
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
                    throw ODataException::createBadRequestError(
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
                    = $currentResourceType->resolveProperty(
                        $selectSubPathSegment
                    );
                if (is_null($resourceProperty)) {
                    throw ODataException::createSyntaxError(
                        Messages::expandProjectionParserPropertyNotFound(
                            $currentResourceType->getFullName(),
                            $selectSubPathSegment,
                            true
                        )
                    );
                }

                if (!$isLastSegment) {
                    if ($resourceProperty->isKindOf(ResourcePropertyKind::BAG)) {
                        throw ODataException::createBadRequestError(
                            Messages::expandProjectionParserBagPropertyAsInnerSelectSegment(
                                $currentResourceType->getFullName(),
                                $selectSubPathSegment
                            )
                        );
                    } elseif ($resourceProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                        throw ODataException::createBadRequestError(
                            Messages::expandProjectionParserPrimitivePropertyUsedAsNavigationProperty(
                                $currentResourceType->getFullName(),
                                $selectSubPathSegment
                            )
                        );
                    } elseif ($resourceProperty->isKindOf(ResourcePropertyKind::COMPLEX_TYPE)) {
                        throw ODataException::createBadRequestError(
                            Messages::expandProjectionParserComplexPropertyAsInnerSelectSegment(
                                $currentResourceType->getFullName(),
                                $selectSubPathSegment
                            )
                        );
                    } elseif ($resourceProperty->getKind() != ResourcePropertyKind::RESOURCE_REFERENCE && $resourceProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE) {
                        throw ODataException::createInternalServerError(
                            Messages::expandProjectionParserUnexpectedPropertyType()
                        );
                    }
                }

                $node = $currentNode->findNode($selectSubPathSegment);
                if (is_null($node)) {
                    if (!$isLastSegment) {
                        throw ODataException::createBadRequestError(
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
     * @param string $value    expand or select clause to read
     * @param bool   $isSelect true means $value is value of select clause
     *                         else value of expand clause
     *
     * @return array(array) An array of 'PathSegment's, each of which is array
     *                      of 'SubPathSegment's
     */
    private function _readExpandOrSelect($value, $isSelect)
    {
        $pathSegments = [];
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
                $pathSegments[$i] = [];
            }

            $pathSegments[$i][] = $subPathSegment;
            $tokenId = $lexer->getCurrentToken()->Id;
            if ($tokenId != ExpressionTokenId::END) {
                if ($lastSegment || $tokenId != ExpressionTokenId::SLASH) {
                    $lexer->validateToken(ExpressionTokenId::COMMA);
                    ++$i;
                }

                $lexer->nextToken();
            }
        }

        return $pathSegments;
    }
}
