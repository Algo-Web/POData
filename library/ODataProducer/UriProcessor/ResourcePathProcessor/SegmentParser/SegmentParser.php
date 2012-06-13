<?php
/** 
 * A parser to parse the segments in OData URI, Uri is made up of bunch of segments, 
 * each segment is seperated by '/' character
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
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
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
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\Providers\Metadata\ResourcePropertyKind;
use ODataProducer\Providers\MetadataQueryProviderWrapper;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
/**
 * Parser to parse the segments in the resource path.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class SegmentParser
{
    /**
     * Holds reference to the wrapper class over IDataServiceMetadataProvider 
     * and IDataServiceQueryProvider 
     * 
     * @var MetadataQueryProviderWrapper
     */
    private $_providerWrapper;

    /**
     * Array of SegmentDescriptor describing each segment in the request Uri
     * 
     * @var array(SegmentDescriptor)
     */
    private $_segmentDescriptors;

    /**
     * Constructs a new instance of SegmentParser
     * 
     * @param array(string)                $segments        Array of segments in 
     *                                                      the request Uri
     * @param MetadataQueryProviderWrapper $providerWrapper Reference to metadata 
     *                                                      and query provider 
     *                                                      wrapper
     */
    private function __construct($segments, 
        MetadataQueryProviderWrapper $providerWrapper
    ) {
        $this->_segmentDescriptors = array();
        $this->_providerWrapper = $providerWrapper;
    }

    /**
     * Parse the given Uri segments
     * 
     * @param array(string)                $segments        Array of segments in the
     *                                                      request Uri
     * @param MetadataQueryProviderWrapper $providerWrapper Reference to metadata and
     *                                                      query provider wrapper
     * @param boolean                      $checkForRights  Whether to check for rights 
     *                                                      on the resource sets in the 
     *                                                      segments
     * 
     * @return array(SegmentDescriptor)
     * 
     * @throws ODataException If any error occurs while processing segment
     */
    public static function parseRequestUriSegements($segments, 
        MetadataQueryProviderWrapper $providerWrapper, $checkForRights = true
    ) {
        $segmentParser = new SegmentParser($segments, $providerWrapper);
        $segmentParser->_createSegmentDescriptors($segments, $checkForRights);
        return $segmentParser->_segmentDescriptors;
    }

    /**
     * Extract identifer and key predicate from a segment
     * 
     * @param array(string) $segment       The segment from which identifier and key 
     *                                     predicate to be extracted
     * @param string        &$identifier   On return, this parameter will contain 
     *                                     identifer part of the segment
     * @param string        &$keyPredicate On return, this parameter will contain 
     *                                     key predicate part of the segment, null 
     *                                     if predicate is absent
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while processing segment
     */
    private function _extractSegmentIdentifierAndKeyPredicate($segment, 
        &$identifier, &$keyPredicate
    ) {
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
        $keyPredicate 
            = substr(
                $segment, $predicateStart, 
                $segmentLength - $predicateStart - 1
            );
    }

    /**
     * Create SegmentDescriptors for a set of given segments, optionally 
     * check for rights.
     * 
     * @param array(string) $segments    String array of segments to parse
     * @param boolean       $checkRights Whether to check for rights or not
     * 
     * @return void
     * 
     * @throws ODataException Exception incase of any error found while 
     *                        precessing segments
     */
    private function _createSegmentDescriptors($segments, $checkRights)
    {        
        if (empty($segments)) {
            $this->_segmentDescriptors[] = new SegmentDescriptor();
            $this->_segmentDescriptors[0]->setTargetKind(RequestTargetKind::SERVICE_DIRECTORY);
            return;
        }

        $segmentCount = count($segments);
        $identifier = $keyPredicate = null;
        $this->_extractSegmentIdentifierAndKeyPredicate($segments[0], $identifier, $keyPredicate);
        $this->_segmentDescriptors[] = $this->_createFirstSegmentDescriptor(
            $identifier, $keyPredicate, $checkRights
        );
        $previous = $this->_segmentDescriptors[0];
        for ($i = 1; $i < $segmentCount; $i++) {
            if ($previous->getTargetKind() == RequestTargetKind::METADATA 
                || $previous->getTargetKind() == RequestTargetKind::BATCH 
                || $previous->getTargetKind() == RequestTargetKind::PRIMITIVE_VALUE 
                || $previous->getTargetKind() == RequestTargetKind::BAG 
                || $previous->getTargetKind() == RequestTargetKind::MEDIA_RESOURCE
            ) {
                    ODataException::resourceNotFoundError(
                        Messages::segmentParserMustBeLeafSegment(
                            $previous->getIdentifier()
                        )
                    );
            }

            $identifier = $keyPredicate = null;
            $this->_extractSegmentIdentifierAndKeyPredicate(
                $segments[$i], $identifier, $keyPredicate
            );
            $hasPredicate = !is_null($keyPredicate);
            $descriptor = null;
            if ($previous->getTargetKind() == RequestTargetKind::PRIMITIVE) {
                if ($identifier !== ODataConstants::URI_VALUE_SEGMENT) {
                    ODataException::resourceNotFoundError(
                        Messages::segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment(
                            $identifier, $previous->getIdentifier()
                        )
                    );
                }
                
                $this->_assertion(!$hasPredicate);
                $descriptor = SegmentDescriptor::createFrom($previous);
                $descriptor->setIdentifier(ODataConstants::URI_VALUE_SEGMENT);
                $descriptor->setTargetKind(RequestTargetKind::PRIMITIVE_VALUE);
                $descriptor->setSingleResult(true);
            } else if (!is_null($previous->getPrevious()) && $previous->getPrevious()->getIdentifier() === ODataConstants::URI_LINK_SEGMENT && $identifier !== ODataConstants::URI_COUNT_SEGMENT) {
                ODataException::createBadRequestError(
                    Messages::segmentParserNoSegmentAllowedAfterPostLinkSegment(
                        $identifier
                    )
                );
            } else if ($previous->getTargetKind() == RequestTargetKind::RESOURCE 
                && $previous->isSingleResult() 
                && $identifier === ODataConstants::URI_LINK_SEGMENT
            ) {
                $this->_assertion(!$hasPredicate);
                $descriptor = SegmentDescriptor::createFrom($previous);
                $descriptor->setIdentifier(ODataConstants::URI_LINK_SEGMENT);
                $descriptor->setTargetKind(RequestTargetKind::LINK);
            } else {                
                //Do a sanity check here
                if ($previous->getTargetKind() != RequestTargetKind::COMPLEX_OBJECT 
                    && $previous->getTargetKind() != RequestTargetKind::RESOURCE 
                    && $previous->getTargetKind() != RequestTargetKind::LINK
                ) {
                    ODataException::createInternalServerError(
                        Messages::segmentParserInconsistentTargetKindState()
                    );
                }

                if (!$previous->isSingleResult() 
                    && $identifier !== ODataConstants::URI_COUNT_SEGMENT
                ) {
                    ODataException::createBadRequestError(
                        Messages::segmentParserCannotQueryCollection(
                            $previous->getIdentifier()
                        )
                    );
                }

                $descriptor = new SegmentDescriptor();
                $descriptor->setIdentifier($identifier);
                $descriptor->setTargetSource(RequestTargetSource::PROPERTY);
                $projectedProperty 
                    = $previous->getTargetResourceType()
                        ->tryResolvePropertyTypeByName($identifier);
                $descriptor->setProjectedProperty($projectedProperty);
                if ($identifier === ODataConstants::URI_COUNT_SEGMENT) {
                    if ($previous->getTargetKind() != RequestTargetKind::RESOURCE) {
                        ODataException::createBadRequestError(
                            Messages::segmentParserCountCannotBeApplied(
                                $previous->getIdentifier()
                            )
                        );
                    }

                    if ($previous->isSingleResult()) {
                        ODataException::createBadRequestError(
                            Messages::segmentParserCountCannotFollowSingleton(
                                $previous->getIdentifier()
                            )
                        );
                    }
                    
                    $descriptor->setTargetKind(RequestTargetKind::PRIMITIVE_VALUE);
                    $descriptor->setSingleResult(true);
                    $descriptor->setTargetResourceSetWrapper(
                        $previous->getTargetResourceSetWrapper()
                    );
                    $descriptor->setTargetResourceType(
                        $previous->getTargetResourceType()
                    );
                } else if ($identifier === ODataConstants::URI_VALUE_SEGMENT 
                    && $previous->getTargetKind() == RequestTargetKind::RESOURCE
                ) {
                    $descriptor->setSingleResult(true);
                    $descriptor->setTargetResourceType(
                        $previous->getTargetResourceType()
                    );
                    $descriptor->setTargetKind(RequestTargetKind::MEDIA_RESOURCE);
                } else if (is_null($projectedProperty)) {
                    if (!is_null($previous->getTargetResourceType()) 
                        && !is_null($previous->getTargetResourceType()->tryResolveNamedStreamByName($identifier))
                    ) {
                        $descriptor->setTargetKind(
                            RequestTargetKind::MEDIA_RESOURCE
                        );
                        $descriptor->setSingleResult(true);
                        $descriptor->setTargetResourceType(
                            $previous->getTargetResourceType()
                        );
                    } else {
                        ODataException::createResourceNotFoundError($identifier);
                    }
                } else {
                    $descriptor->setTargetResourceType($projectedProperty->getResourceType());
                    $descriptor->setSingleResult($projectedProperty->getKind() != ResourcePropertyKind::RESOURCESET_REFERENCE);
                    if ($previous->getTargetKind() == RequestTargetKind::LINK 
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
                        $descriptor->setTargetKind(RequestTargetKind::COMPLEX_OBJECT);
                        break;
                    case ResourcePropertyKind::BAG | ResourcePropertyKind::PRIMITIVE:
                    case ResourcePropertyKind::BAG | ResourcePropertyKind::COMPLEX_TYPE:
                        $descriptor->setTargetKind(RequestTargetKind::BAG);
                        break;
                    case ResourcePropertyKind::RESOURCE_REFERENCE:
                    case ResourcePropertyKind::RESOURCESET_REFERENCE:
                        $descriptor->setTargetKind(RequestTargetKind::RESOURCE);
                        $resourceSetWrapper = $this->_providerWrapper->getResourceSetWrapperForNavigationProperty($previous->getTargetResourceSetWrapper(), $previous->getTargetResourceType(), $projectedProperty);
                        if (is_null($resourceSetWrapper)) {
                            ODataException::createResourceNotFoundError($projectedProperty->getName());
                        }

                        $descriptor->setTargetResourceSetWrapper($resourceSetWrapper);
                        break;
                    default:
                        if (!$projectedProperty->isKindOf(ResourcePropertyKind::PRIMITIVE)) {
                            ODataException::createInternalServerError(
                                Messages::segmentParserUnExpectedPropertyKind(
                                    'Primitive'
                                )
                            );
                        }

                        $descriptor->setTargetKind(RequestTargetKind::PRIMITIVE);
                        break;
                    }

                    if ($hasPredicate) {
                        $this->_assertion(!$descriptor->isSingleResult());
                        $keyDescriptor = $this->_createKeyDescriptor(
                            $identifier . '(' . $keyPredicate . ')',
                            $projectedProperty->getResourceType(),
                            $keyPredicate
                        );
                        $descriptor->setKeyDescriptor($keyDescriptor);
                        if (!$keyDescriptor->isEmpty()) {
                            $descriptor->setSingleResult(true);
                        }
                    }

                    if ($checkRights 
                        && !is_null($descriptor->getTargetResourceSetWrapper())
                    ) {
                        $descriptor->getTargetResourceSetWrapper()
                            ->checkResourceSetRightsForRead(
                                $descriptor->isSingleResult()
                            );
                    }
                }
            } 
            
            $descriptor->setPrevious($previous);
            $previous->setNext($descriptor);
            $this->_segmentDescriptors[] = $descriptor;
            $previous = $descriptor;
        }

        if ($previous->getTargetKind() == RequestTargetKind::LINK) {
            ODataException::createBadRequestError(
                Messages::segmentParserMissingSegmentAfterLink()
            );
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
            $descriptor->setTargetKind(RequestTargetKind::METADATA);
            return $descriptor;
        }

        if ($segmentIdentifier === ODataConstants::URI_BATCH_SEGMENT) {
            $this->_assertion(is_null($keyPredicate));
            $descriptor->setTargetKind(RequestTargetKind::BATCH);
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

        $resourceSetWrapper
            = $this->_providerWrapper->resolveResourceSet($segmentIdentifier);
        if ($resourceSetWrapper === null) {
            ODataException::createResourceNotFoundError($segmentIdentifier);
        }

        $descriptor->setTargetResourceSetWrapper($resourceSetWrapper);
        $descriptor->setTargetResourceType($resourceSetWrapper->getResourceType());
        $descriptor->setTargetSource(RequestTargetSource::ENTITY_SET);
        $descriptor->setTargetKind(RequestTargetKind::RESOURCE);
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
         * @var KeyDescriptor
         */
        $keyDescriptor = null;
        if (!KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDescriptor)) {
            ODataException::createSyntaxError(Messages::syntaxError());
        }
        
        // Note: Currenlty WCF Data Service does not support multiple 
        // 'Positional values' so Order_Details(10248, 11) is not valid
        if (!$keyDescriptor->isEmpty() 
            && !$keyDescriptor->areNamedValues() 
            && $keyDescriptor->valueCount() > 1
        ) {
            ODataException::createSyntaxError(
                Messages::segmentParserKeysMustBeNamed($segment)
            );
        }

        try {
            $keyDescriptor->validate($segment, $resourceType);
            
        } catch (ODataException $exception) {
            throw $exception;
        }

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
?>