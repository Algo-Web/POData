<?php

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

/**
 * Class TargetSource.
 */
class TargetSource
{
    /**
     * The source of data has not been determined yet or
     * The source of data is intrinsic to the sytem i.e Service Document,
     * Metadata or batch requests.
     * The associated TargetKind enum values are:
     *  TargetKind::METADATA
     *  TargetKind::SERVICE_DOCUMENT
     *  TargetKind::BATCH.
     */
    const NONE = 1;

    /**
     * An entity set provides the data.
     * The associated TargetKind enum values are:
     *  TargetKind::RESOURCE
     *  TargetKind::LINK.
     */
    const ENTITY_SET = 2;

    /**
     * A service operation provides the data.
     * The associated TargetKind enum values are:
     *  TargetKind::VOID_SERVICE_OPERATION.
     */
    const SERVICE_OPERATION = 3;

    /**
     * A property of an entity or a complex object provides the data.
     * The associated TargetKind enum values are:
     *  TargetKind::PRIMITIVE
     *  TargetKind::PRIMITIVE_VALUE
     *  TargetKind::COMPLEX_OBJECT
     *  TargetKind::MEDIA_RESOURCE
     *  TargetKind::BAG.
     */
    const PROPERTY = 4;
}
