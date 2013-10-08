<?php

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\ProvidersWrapper;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;

/**
 * Class SegmentParser
 *
 * A parser to parse the segments in OData URI, Uri is made up of bunch of segments,
 * each segment is separated by '/' character
 * e.g. Customers('ALFKI')/Orders(2134)/Order_Details/Product
 *
 * Syntax of an OData segment is:
 * Segment       : identifier[(keyPredicate)]?            : e.g. Customers, Customers('ALFKI'), Order_Details(OrderID=123, ProductID=11)
 * keyPredicate  : keyValue | NamedKeyValue
 * NamedKeyValue : keyName=keyValue [, keyName=keyValue]* : e.g. OrderID=123, ProductID=11
 * keyValue      : quotedValue | unquotedValue            : e.g. 'ALFKI'
 * quotedValue   : "'" nqChar "'"
 * unquotedValue : [.*]                                   : Any character
 * nqChar        : [^\']                                  : Character other than quotes
 *
 * @package POData\UriProcessor\ResourcePathProcessor\SegmentParser
 */
class SegmentParser
{
    /**
     * The wrapper of IMetadataProvider and IQueryProvider
     *
     * @var ProvidersWrapper
     */
    private $providerWrapper;

    /**
     * Array of SegmentDescriptor describing each segment in the request Uri
     * 
     * @var SegmentDescriptor[]
     */
    private $_segmentDescriptors = array();

    /**
     * Constructs a new instance of SegmentParser
     * 
     * @param ProvidersWrapper $providerWrapper Reference to metadata and query provider wrapper
     *
     */
    private function __construct(ProvidersWrapper $providerWrapper ) {
        $this->providerWrapper = $providerWrapper;
    }

    /**
     * Parse the given Uri segments
     * 
     * @param string[] $segments Array of segments in the request Uri
     *
     * @param ProvidersWrapper $providerWrapper Reference to metadata and query provider wrapper
     * @param boolean $checkForRights  Whether to check for rights on the resource sets in the segments
     *
     * @return SegmentDescriptor[]
     * 
     * @throws ODataException If any error occurs while processing segment
     */
    public static function parseRequestUriSegments($segments, ProvidersWrapper $providerWrapper, $checkForRights = true) {
        $segmentParser = new SegmentParser($providerWrapper);
        $segmentParser->_createSegmentDescriptors($segments, $checkForRights);
        return $segmentParser->_segmentDescriptors;
    }

    /**
     * Extract identifier and key predicate from a segment
     * 
     * @param string $segment The segment from which identifier and key
     * @param string &$identifier   On return, this parameter will contain identifier part of the segment
     * @param string &$keyPredicate On return, this parameter will contain key predicate part of the segment, null if predicate is absent
     *
     * @throws ODataException If any error occurs while processing segment
     */
    private function _extractSegmentIdentifierAndKeyPredicate($segment, &$identifier, &$keyPredicate) {
        $predicateStart = strpos($segment, '(');
        if ($predicateStart === false) {
            $identifier = $segment;
            $keyPredicate = null;
            return;
        }

        $segmentLength = strlen($segment);
        if (strrpos($segment, ')') !== $segmentLength - 1) {
            ODataException::createSyntaxError(Messages::syntaxError());
        }

        $identifier = substr($segment, 0, $predicateStart);
        $predicateStart++;
        $keyPredicate = substr($segment, $predicateStart, $segmentLength - $predicateStart - 1);
    }

    /**
     * Create SegmentDescriptors for a set of given segments, optionally 
     * check for rights.
     * 
     * @param string[] $segments array of segments strings to parse
     * @param boolean $checkRights Whether to check for rights or not
     * 
     * @return void
     * 
     * @throws ODataException Exception in case of any error found while precessing segments
     */
    private function _createSegmentDescriptors($segments, $checkRights)
    {        
        if (empty($segments)) {
	        $descriptor = new SegmentDescriptor();
	        $descriptor->setTargetKind(TargetKind::SERVICE_DIRECTORY());
            $this->_segmentDescriptors[] = $descriptor;
            return;
        }

        $segmentCount = count($segments);
        $identifier = $keyPredicate = null;
        $this->_extractSegmentIdentifierAndKeyPredicate($segments[0], $identifier, $keyPredicate);
	    $previous = $this->_createFirstSegmentDescriptor(
            $identifier, $keyPredicate, $checkRights
        );
        $this->_segmentDescriptors[0] = $previous;

        for ($i = 1; $i < $segmentCount; $i++) {
	        $kind = $previous->getTargetKind() ;
            if ($kind == TargetKind::METADATA()
                || $kind == TargetKind::BATCH()
                || $kind == TargetKind::PRIMITIVE_VALUE()
                || $kind == TargetKind::BAG()
                || $kind == TargetKind::MEDIA_RESOURCE()
            ) {
                ODataException::resourceNotFoundError(
                    Messages::segmentParserMustBeLeafSegment($previous->getIdentifier())
                );
            }

            $identifier = $keyPredicate = null;
            $this->_extractSegmentIdentifierAndKeyPredicate($segments[$i], $identifier, $keyPredicate);
            $hasPredicate = !is_null($keyPredicate);
            $current = null;
            if ($kind == TargetKind::PRIMITIVE()) {
                if ($identifier !== ODataConstants::URI_VALUE_SEGMENT) {
                    ODataException::resourceNotFoundError(
                        Messages::segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment(
                            $identifier, $previous->getIdentifier()
                        )
                    );
                }
                
                $this->_assertion(!$hasPredicate);
                $current = SegmentDescriptor::createFrom($previous);
                $current->setIdentifier(ODataConstants::URI_VALUE_SEGMENT);
                $current->setTargetKind(TargetKind::PRIMITIVE_VALUE());
                $current->setSingleResult(true);
            } else if (!is_null($previous->getPrevious()) && $previous->getPrevious()->getIdentifier() === ODataConstants::URI_LINK_SEGMENT && $identifier !== ODataConstants::URI_COUNT_SEGMENT) {
                ODataException::createBadRequestError(
                    Messages::segmentParserNoSegmentAllowedAfterPostLinkSegment($identifier)
                );
            } else if ($kind == TargetKind::RESOURCE()
                && $previous->isSingleResult() 
                && $identifier === ODataConstants::URI_LINK_SEGMENT
            ) {
                $this->_assertion(!$hasPredicate);
                $current = SegmentDescriptor::createFrom($previous);
                $current->setIdentifier(ODataConstants::URI_LINK_SEGMENT);
                $current->setTargetKind(TargetKind::LINK());
            } else {                
                //Do a sanity check here
                if ($kind != TargetKind::COMPLEX_OBJECT()
                    && $kind != TargetKind::RESOURCE()
                    && $kind != TargetKind::LINK()
                ) {
                    ODataException::createInternalServerError(
                        Messages::segmentParserInconsistentTargetKindState()
                    );
                }

                if (!$previous->isSingleResult() && $identifier !== ODataConstants::URI_COUNT_SEGMENT) {
                    ODataException::createBadRequestError(
                        Messages::segmentParserCannotQueryCollection($previous->getIdentifier())
                    );
                }

                $current = new SegmentDescriptor();
                $current->setIdentifier($identifier);
                $current->setTargetSource(TargetSource::PROPERTY);
                $projectedProperty = $previous->getTargetResourceType()->tryResolvePropertyTypeByName($identifier);
                $current->setProjectedProperty($projectedProperty);

	            if ($identifier === ODataConstants::URI_COUNT_SEGMENT) {
                    if ($kind != TargetKind::RESOURCE()) {
                        ODataException::createBadRequestError(
                            Messages::segmentParserCountCannotBeApplied($previous->getIdentifier())
                        );
                    }

                    if ($previous->isSingleResult()) {
                        ODataException::createBadRequestError(
                            Messages::segmentParserCountCannotFollowSingleton($previous->getIdentifier())
                        );
                    }
                    
                    $current->setTargetKind(TargetKind::PRIMITIVE_VALUE());
                    $current->setSingleResult(true);
                    $current->setTargetResourceSetWrapper(
                        $previous->getTargetResourceSetWrapper()
                    );
                    $current->setTargetResourceType(
                        $previous->getTargetResourceType()
                    );
                } else if ($identifier === ODataConstants::URI_VALUE_SEGMENT 
                    && $kind == TargetKind::RESOURCE()
                ) {
                    $current->setSingleResult(true);
                    $current->setTargetResourceType(
                        $previous->getTargetResourceType()
                    );
                    $current->setTargetKind(TargetKind::MEDIA_RESOURCE());
                } else if (is_null($projectedProperty)) {
                    if (!is_null($previous->getTargetResourceType()) 
                        && !is_null($previous->getTargetResourceType()->tryResolveNamedStreamByName($identifier))
                    ) {
                        $current->setTargetKind(TargetKind::MEDIA_RESOURCE());
                        $current->setSingleResult(true);
                        $current->setTargetResourceType(
                            $previous->getTargetResourceType()
                        );
                    } else {
                        ODataException::createResourceNotFoundError($identifier);
                    }
                } else {
                    $current->setTargetResourceType($projectedProperty->getResourceType());
                    $current->setSingleResult($projectedProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE);
                    if ($kind == TargetKind::LINK()
                        && $projectedProperty->getTypeKind() != ResourceTypeKind::ENTITY
                    ) {
                        ODataException::createBadRequestError(
                            Messages::segmentParserLinkSegmentMustBeFollowedByEntitySegment(
                                $identifier
                            )
                        );
                    }

                    switch($projectedProperty->getKind()) {
	                    case ResourcePropertyKind::COMPLEX_TYPE:
	                        $current->setTargetKind(TargetKind::COMPLEX_OBJECT());
	                        break;
	                    case ResourcePropertyKind::BAG | ResourcePropertyKind::PRIMITIVE:
	                    case ResourcePropertyKind::BAG | ResourcePropertyKind::COMPLEX_TYPE:
	                        $current->setTargetKind(TargetKind::BAG());
	                        break;
	                    case ResourcePropertyKind::RESOURCE_REFERENCE:
	                    case ResourcePropertyKind::RESOURCESET_REFERENCE:
	                        $current->setTargetKind(TargetKind::RESOURCE());
	                        $resourceSetWrapper = $this->providerWrapper->getResourceSetWrapperForNavigationProperty($previous->getTargetResourceSetWrapper(), $previous->getTargetResourceType(), $projectedProperty);
	                        if (is_null($resourceSetWrapper)) {
	                            ODataException::createResourceNotFoundError($projectedProperty->getName());
	                        }

	                        $current->setTargetResourceSetWrapper($resourceSetWrapper);
	                        break;
	                    default:
	                        if (!$projectedProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
	                            ODataException::createInternalServerError(
	                                Messages::segmentParserUnExpectedPropertyKind(
	                                    'Primitive'
	                                )
	                            );
	                        }

	                        $current->setTargetKind(TargetKind::PRIMITIVE());
	                        break;
                    }

                    if ($hasPredicate) {
                        $this->_assertion(!$current->isSingleResult());
                        $keyDescriptor = $this->_createKeyDescriptor(
                            $identifier . '(' . $keyPredicate . ')',
                            $projectedProperty->getResourceType(),
                            $keyPredicate
                        );
                        $current->setKeyDescriptor($keyDescriptor);
                        if (!$keyDescriptor->isEmpty()) {
                            $current->setSingleResult(true);
                        }
                    }

                    if ($checkRights 
                        && !is_null($current->getTargetResourceSetWrapper())
                    ) {
                        $current->getTargetResourceSetWrapper()
                            ->checkResourceSetRightsForRead(
                                $current->isSingleResult()
                            );
                    }
                }
            } 
            
            $current->setPrevious($previous);
            $previous->setNext($current);
            $this->_segmentDescriptors[] = $current;
            $previous = $current;
        }

        if ($previous->getTargetKind() == TargetKind::LINK()) {
            ODataException::createBadRequestError(Messages::segmentParserMissingSegmentAfterLink());
        }
    }

    /**
     * Create SegmentDescriptor for the first segment
     * 
     * @param string  $segmentIdentifier The identifier part of the 
     *                                   first segment
     * @param string  $keyPredicate      The predicate part of the first
     *                                   segment if any else NULL     
     * @param boolean $checkRights       Whether to check the rights on 
     *                                   this segment
     * 
     * @return SegmentDescriptor Descriptor for the first segment
     * 
     * @throws ODataException Exception if any validation fails
     */
    private function _createFirstSegmentDescriptor($segmentIdentifier, 
        $keyPredicate, $checkRights
    ) {
        $descriptor = new SegmentDescriptor();
        $descriptor->setIdentifier($segmentIdentifier);
        if ($segmentIdentifier === ODataConstants::URI_METADATA_SEGMENT) {
            $this->_assertion(is_null($keyPredicate));            
            $descriptor->setTargetKind(TargetKind::METADATA());
            return $descriptor;
        }

        if ($segmentIdentifier === ODataConstants::URI_BATCH_SEGMENT) {
            $this->_assertion(is_null($keyPredicate));
            $descriptor->setTargetKind(TargetKind::BATCH());
            return $descriptor;
        }

        if ($segmentIdentifier === ODataConstants::URI_COUNT_SEGMENT) {
            ODataException::createBadRequestError(
                Messages::segmentParserSegmentNotAllowedOnRoot(
                    ODataConstants::URI_COUNT_SEGMENT
                )
            );
        }

        if ($segmentIdentifier === ODataConstants::URI_LINK_SEGMENT) {
            ODataException::createBadRequestError(
                Messages::segmentParserSegmentNotAllowedOnRoot(
                    ODataConstants::URI_LINK_SEGMENT
                )
            );
        }

        $resourceSetWrapper = $this->providerWrapper->resolveResourceSet($segmentIdentifier);
        if ($resourceSetWrapper === null) {
            ODataException::createResourceNotFoundError($segmentIdentifier);
        }

        $descriptor->setTargetResourceSetWrapper($resourceSetWrapper);
        $descriptor->setTargetResourceType($resourceSetWrapper->getResourceType());
        $descriptor->setTargetSource(TargetSource::ENTITY_SET);
        $descriptor->setTargetKind(TargetKind::RESOURCE());
        if ($keyPredicate !== null) {
            $keyDescriptor = $this->_createKeyDescriptor(
                $segmentIdentifier . '(' . $keyPredicate . ')', 
                $resourceSetWrapper->getResourceType(), 
                $keyPredicate
            );
            $descriptor->setKeyDescriptor($keyDescriptor);
            if (!$keyDescriptor->isEmpty()) {
                $descriptor->setSingleResult(true); 
            }
        }

        if ($checkRights) {
            $resourceSetWrapper->checkResourceSetRightsForRead(
                $descriptor->isSingleResult()
            );
        }

        return $descriptor;
    }

    /**
     * Creates an instance of KeyDescriptor by parsing a key predicate, also 
     * validates the KeyDescriptor
     * 
     * @param string       $segment      The uri segment in the form identifier
     *                                   (keyPredicate)
     * @param ResourceType $resourceType The Resource type whose keys need to 
     *                                   be parsed
     * @param string       $keyPredicate The key predicate to parse and generate 
     *                                   KeyDescriptor for
     * 
     * @return KeyDescriptor Describes the key values in the $keyPredicate
     * 
     * @throws ODataException Exception if any error occurs while parsing and 
     *                                  validating the key predicate
     */
    private function _createKeyDescriptor($segment, ResourceType 
        $resourceType, $keyPredicate
    ) {
        /**
         * @var KeyDescriptor $keyDescriptor
         */
        $keyDescriptor = null;
        if (!KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor)) {
            ODataException::createSyntaxError(Messages::syntaxError());
        }
        
        // Note: Currently WCF Data Service does not support multiple
        // 'Positional values' so Order_Details(10248, 11) is not valid
        if (!$keyDescriptor->isEmpty() 
            && !$keyDescriptor->areNamedValues() 
            && $keyDescriptor->valueCount() > 1
        ) {
            ODataException::createSyntaxError(
                Messages::segmentParserKeysMustBeNamed($segment)
            );
        }


        $keyDescriptor->validate($segment, $resourceType);
            

        return $keyDescriptor;
    }

    /**
     * Assert that the given condition is true, if false throw 
     * ODataException for syntax error
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
            ODataException::createSyntaxError(Messages::syntaxError());
        }
    }
}