<?php
/** 
 * An enumeration to describe the source of result for the client request.
 * 
 *
 *
 */
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
/**
 * Client request result source enumerations.
*
 */
class RequestTargetSource
{
    /**
     * The source of data has not been determined yet or
     * The source of data is intrinsic to the sytem i.e Service Document, 
     * Metadata or batch requests.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::METADATA
     *  RequestTargetKind::SERVICE_DOCUMENT
     *  RequestTargetKind::BATCH
     */
    const NONE = 1;

    /**
     * An entity set provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::RESOURCE
     *  RequestTargetKind::LINK
     */
    const ENTITY_SET = 2;

    /**
     * A service operation provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::VOID_SERVICE_OPERATION
     */
    const  SERVICE_OPERATION = 3;
    
    /**
     * A property of an entity or a complex object provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::PRIMITIVE
     *  RequestTargetKind::PRIMITIVE_VALUE
     *  RequestTargetKind::COMPLEX_OBJECT
     *  RequestTargetKind::MEDIA_RESOURCE
     *  RequestTargetKind::BAG
     */
    const PROPERTY = 4;
}
?>