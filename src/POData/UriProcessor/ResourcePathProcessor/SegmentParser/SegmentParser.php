<?php

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\ProvidersWrapper;

/**
 * Class SegmentParser.
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
 */
class SegmentParser
{
    /**
     * The wrapper of IMetadataProvider and IQueryProvider.
     *
     * @var ProvidersWrapper
     */
    private $providerWrapper;

    /**
     * Array of SegmentDescriptor describing each segment in the request Uri.
     *
     * @var SegmentDescriptor[]
     */
    private $_segmentDescriptors = [];

    /**
     * Constructs a new instance of SegmentParser.
     *
     * @param ProvidersWrapper $providerWrapper Reference to metadata and query provider wrapper
     */
    private function __construct(ProvidersWrapper $providerWrapper)
    {
        $this->providerWrapper = $providerWrapper;
    }

    /**
     * Parse the given Uri segments.
     *
     * @param string[]         $segments        Array of segments in the request Uri
     * @param ProvidersWrapper $providerWrapper Reference to metadata and query provider wrapper
     * @param bool             $checkForRights  Whether to check for rights on the resource sets in the segments
     *
     * @throws ODataException If any error occurs while processing segment
     *
     * @return SegmentDescriptor[]
     */
    public static function parseRequestUriSegments($segments, ProvidersWrapper $providerWrapper, $checkForRights = true)
    {
        $segmentParser = new self($providerWrapper);
        $segmentParser->createSegmentDescriptors($segments, $checkForRights);

        return $segmentParser->_segmentDescriptors;
    }

    /**
     * Extract identifier and key predicate from a segment.
     *
     * @param string $segment       The segment from which identifier and key
     * @param string &$identifier   On return, this parameter will contain identifier part of the segment
     * @param string &$keyPredicate On return, this parameter will contain key predicate part of the segment,
     *                              null if predicate is absent
     *
     * @throws ODataException If any error occurs while processing segment
     */
    private function extractSegmentIdentifierAndKeyPredicate($segment, &$identifier, &$keyPredicate)
    {
        $predicateStart = strpos($segment, '(');
        if ($predicateStart === false) {
            $identifier = $segment;
            $keyPredicate = null;

            return;
        }

        $segmentLength = strlen($segment);
        if (strrpos($segment, ')') !== $segmentLength - 1) {
            throw ODataException::createSyntaxError(Messages::syntaxError());
        }

        $identifier = substr($segment, 0, $predicateStart);
        ++$predicateStart;
        $keyPredicate = substr($segment, $predicateStart, $segmentLength - $predicateStart - 1);
    }

    /**
     * Process a collection of OData URI segment strings and turn them into segment descriptors.
     *
     * @param string[] $segments    array of segments strings to parse
     * @param bool     $checkRights Whether to check for rights or not
     *
     * @throws ODataException Exception in case of any error found while precessing segments
     */
    private function createSegmentDescriptors($segments, $checkRights)
    {
        if (empty($segments)) {
            //If there's no segments, then it's the service root
            $descriptor = new SegmentDescriptor();
            $descriptor->setTargetKind(TargetKind::SERVICE_DIRECTORY());
            $this->_segmentDescriptors[] = $descriptor;

            return;
        }

        $segmentCount = count($segments);
        $identifier = $keyPredicate = null;
        $this->extractSegmentIdentifierAndKeyPredicate($segments[0], $identifier, $keyPredicate);
        $previous = $this->_createFirstSegmentDescriptor(
            $identifier,
            $keyPredicate,
            $checkRights
        );
        assert($previous instanceof SegmentDescriptor, get_class($previous));
        $this->_segmentDescriptors[0] = $previous;

        for ($i = 1; $i < $segmentCount; ++$i) {
            $thisSegment = $segments[$i];
            $current = $this->createNextSegment($previous, $thisSegment, $checkRights);

            $current->setPrevious($previous);
            $previous->setNext($current);
            $this->_segmentDescriptors[] = $current;
            $previous = $current;
        }

        //At this point $previous is the final segment..which cannot be a $link
        if ($previous->getTargetKind() == TargetKind::LINK()) {
            throw ODataException::createBadRequestError(Messages::segmentParserMissingSegmentAfterLink());
        }
    }

    /**
     * @param string $segment
     * @param bool   $checkRights
     */
    private function createNextSegment(SegmentDescriptor $previous, $segment, $checkRights)
    {
        $previousKind = $previous->getTargetKind();
        if ($previousKind == TargetKind::METADATA()
            || $previousKind == TargetKind::BATCH()
            || $previousKind == TargetKind::PRIMITIVE_VALUE()
            || $previousKind == TargetKind::BAG()
            || $previousKind == TargetKind::MEDIA_RESOURCE()
        ) {
            //All these targets are terminal segments, there cannot be anything after them.
            throw ODataException::resourceNotFoundError(
                Messages::segmentParserMustBeLeafSegment($previous->getIdentifier())
            );
        }

        $identifier = $keyPredicate = null;
        $this->extractSegmentIdentifierAndKeyPredicate($segment, $identifier, $keyPredicate);
        $hasPredicate = !is_null($keyPredicate);

        $singleton = $this->providerWrapper->resolveSingleton($identifier);
        if (null !== $singleton) {
            throw ODataException::createSyntaxError("Singleton must be first element");
        }

        if ($previousKind == TargetKind::PRIMITIVE()) {
            if ($identifier !== ODataConstants::URI_VALUE_SEGMENT) {
                throw ODataException::resourceNotFoundError(
                    Messages::segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment(
                        $identifier,
                        $previous->getIdentifier()
                    )
                );
            }

            $this->_assertion(!$hasPredicate);
            $current = SegmentDescriptor::createFrom($previous);
            $current->setIdentifier(ODataConstants::URI_VALUE_SEGMENT);
            $current->setTargetKind(TargetKind::PRIMITIVE_VALUE());
            $current->setSingleResult(true);
        } elseif (!is_null($previous->getPrevious())
                  && $previous->getPrevious()->getIdentifier() === ODataConstants::URI_LINK_SEGMENT
                  && $identifier !== ODataConstants::URI_COUNT_SEGMENT) {
            throw ODataException::createBadRequestError(
                Messages::segmentParserNoSegmentAllowedAfterPostLinkSegment($identifier)
            );
        } elseif ($previousKind == TargetKind::RESOURCE()
            && $previous->isSingleResult()
            && $identifier === ODataConstants::URI_LINK_SEGMENT
        ) {
            $this->_assertion(!$hasPredicate);
            $current = SegmentDescriptor::createFrom($previous);
            $current->setIdentifier(ODataConstants::URI_LINK_SEGMENT);
            $current->setTargetKind(TargetKind::LINK());
        } else {
            //Do a sanity check here
            if ($previousKind != TargetKind::COMPLEX_OBJECT()
                && $previousKind != TargetKind::RESOURCE()
                && $previousKind != TargetKind::LINK()
            ) {
                throw ODataException::createInternalServerError(
                    Messages::segmentParserInconsistentTargetKindState()
                );
            }

            if (!$previous->isSingleResult() && $identifier !== ODataConstants::URI_COUNT_SEGMENT) {
                throw ODataException::createBadRequestError(
                    Messages::segmentParserCannotQueryCollection($previous->getIdentifier())
                );
            }

            $current = new SegmentDescriptor();
            $current->setIdentifier($identifier);
            $current->setTargetSource(TargetSource::PROPERTY);
            $previousType = $previous->getTargetResourceType();
            $projectedProperty = $previousType->resolveProperty($identifier);
            $current->setProjectedProperty($projectedProperty);

            if ($identifier === ODataConstants::URI_COUNT_SEGMENT) {
                if ($previousKind != TargetKind::RESOURCE()) {
                    throw ODataException::createBadRequestError(
                        Messages::segmentParserCountCannotBeApplied($previous->getIdentifier())
                    );
                }

                if ($previous->isSingleResult()) {
                    throw ODataException::createBadRequestError(
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
            } elseif ($identifier === ODataConstants::URI_VALUE_SEGMENT
                && $previousKind == TargetKind::RESOURCE()
            ) {
                $current->setSingleResult(true);
                $current->setTargetResourceType(
                    $previous->getTargetResourceType()
                );
                $current->setTargetKind(TargetKind::MEDIA_RESOURCE());
            } elseif (is_null($projectedProperty)) {
                if (!is_null($previous->getTargetResourceType())
                    && !is_null($previous->getTargetResourceType()->tryResolveNamedStreamByName($identifier))
                ) {
                    $current->setTargetKind(TargetKind::MEDIA_RESOURCE());
                    $current->setSingleResult(true);
                    $current->setTargetResourceType(
                        $previous->getTargetResourceType()
                    );
                } else {
                    throw ODataException::createResourceNotFoundError($identifier);
                }
            } else {
                $current->setTargetResourceType($projectedProperty->getResourceType());
                $current->setSingleResult($projectedProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE);
                if ($previousKind == TargetKind::LINK()
                    && $projectedProperty->getTypeKind() != ResourceTypeKind::ENTITY
                ) {
                    throw ODataException::createBadRequestError(
                        Messages::segmentParserLinkSegmentMustBeFollowedByEntitySegment(
                            $identifier
                        )
                    );
                }

                switch ($projectedProperty->getKind()) {
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
                        $resourceSetWrapper = $this->providerWrapper->getResourceSetWrapperForNavigationProperty(
                            $previous->getTargetResourceSetWrapper(),
                            $previous->getTargetResourceType(),
                            $projectedProperty
                        );
                        if (is_null($resourceSetWrapper)) {
                            throw ODataException::createResourceNotFoundError($projectedProperty->getName());
                        }

                        $current->setTargetResourceSetWrapper($resourceSetWrapper);
                        break;
                    default:
                        if (!$projectedProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                            throw ODataException::createInternalServerError(
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

                if ($checkRights && !is_null($current->getTargetResourceSetWrapper())) {
                    $current->getTargetResourceSetWrapper()
                        ->checkResourceSetRightsForRead(
                            $current->isSingleResult()
                        );
                }
            }
        }

        return $current;
    }

    /**
     * Create SegmentDescriptor for the first segment.
     *
     * @param string $segmentIdentifier The identifier part of the first segment
     * @param string $keyPredicate      The predicate part of the first segment if any else NULL
     * @param bool   $checkRights       Whether to check the rights on this segment
     *
     * @throws ODataException Exception if any validation fails
     *
     * @return SegmentDescriptor Descriptor for the first segment
     */
    private function _createFirstSegmentDescriptor($segmentIdentifier, $keyPredicate, $checkRights)
    {
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
            throw ODataException::createBadRequestError(
                Messages::segmentParserSegmentNotAllowedOnRoot(
                    ODataConstants::URI_COUNT_SEGMENT
                )
            );
        }

        if ($segmentIdentifier === ODataConstants::URI_LINK_SEGMENT) {
            throw ODataException::createBadRequestError(
                Messages::segmentParserSegmentNotAllowedOnRoot(
                    ODataConstants::URI_LINK_SEGMENT
                )
            );
        }

        $singleton = $this->providerWrapper->resolveSingleton($segmentIdentifier);
        if (null !== $singleton) {
            $this->_assertion(is_null($keyPredicate));
            $descriptor->setTargetKind(TargetKind::SINGLETON());
            $descriptor->setTargetSource(TargetSource::ENTITY_SET);
            $descriptor->setTargetResourceType($singleton->getResourceType());
            $descriptor->setSingleResult(true);

            return $descriptor;
        }

        $resourceSetWrapper = $this->providerWrapper->resolveResourceSet($segmentIdentifier);
        if (null === $resourceSetWrapper) {
            throw ODataException::createResourceNotFoundError($segmentIdentifier);
        }

        $descriptor->setTargetResourceSetWrapper($resourceSetWrapper);
        $descriptor->setTargetResourceType($resourceSetWrapper->getResourceType());
        $descriptor->setTargetSource(TargetSource::ENTITY_SET);
        $descriptor->setTargetKind(TargetKind::RESOURCE());
        if (null !== $keyPredicate) {
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
     * validates the KeyDescriptor.
     *
     * @param string       $segment      The uri segment in the form identifier
     *                                   (keyPredicate)
     * @param ResourceType $resourceType The Resource type whose keys need to
     *                                   be parsed
     * @param string       $keyPredicate The key predicate to parse and generate
     *                                   KeyDescriptor for
     *
     * @throws ODataException Exception if any error occurs while parsing and
     *                        validating the key predicate
     *
     * @return KeyDescriptor Describes the key values in the $keyPredicate
     */
    private function _createKeyDescriptor($segment, ResourceType $resourceType, $keyPredicate)
    {
        /**
         * @var KeyDescriptor
         */
        $keyDescriptor = null;
        if (!KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor)) {
            throw ODataException::createSyntaxError(Messages::syntaxError());
        }

        // Note: Currently WCF Data Service does not support multiple
        // 'Positional values' so Order_Details(10248, 11) is not valid
        if (!$keyDescriptor->isEmpty()
            && !$keyDescriptor->areNamedValues()
            && $keyDescriptor->valueCount() > 1
        ) {
            throw ODataException::createSyntaxError(
                Messages::segmentParserKeysMustBeNamed($segment)
            );
        }
        $keyDescriptor->validate($segment, $resourceType);

        return $keyDescriptor;
    }

    /**
     * Assert that the given condition is true, if false throw
     * ODataException for syntax error.
     *
     * @param bool $condition The condition to assert
     *
     * @throws ODataException
     */
    private function _assertion($condition)
    {
        if (!$condition) {
            throw ODataException::createSyntaxError(Messages::syntaxError());
        }
    }
}
