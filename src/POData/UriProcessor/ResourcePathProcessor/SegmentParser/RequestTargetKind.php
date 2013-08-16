<?php

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

/**
 * Class RequestTargetKind
 * @package POData\UriProcessor\ResourcePathProcessor\SegmentParser
 */
class RequestTargetKind
{
    /**
     * Nothing specific is being requested.
     * e.g. http://localhost
     */
    const NOTHING = 1;

    /**
     * A top-level directory of service capabilities.
     * e.g. http://localhost/myservice.svc
     */
    const SERVICE_DIRECTORY = 2;

    /**
     * Entity Resource is requested - it can be a collection or a single value.
     * e.g. http://localhost/myservice.svc/Customers
     *      http://localhost/myservice.svc/Customers('ALFKI')/Orders(123)
     */
    const RESOURCE = 3;

    /**
     * A single complex value is requested (eg: an Address).
     * e.g. http://localhost/myservice.svc/Address
     */
    const COMPLEX_OBJECT = 4;

    /**
     * A single value is requested (eg: a Picture property).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName
     *      http://localhost/myservice.svc/Address/LineNumber
     */
    const PRIMITIVE = 5;

    /**
     * A single value is requested (eg: the raw stream of a Picture).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName/$value
     *      http://localhost/myservice.svc/Customers/$count
     */
    const PRIMITIVE_VALUE= 6;

    /**
     * System metadata.
     * e.g. http://localhost/myservice.svc/$metadata
     */
    const METADATA = 7;

    /**
     * A data-service-defined operation that doesn't return anything.
     */
    const VOID_SERVICE_OPERATION = 8;

    /**
     * The request is a batch request.
     * e.g. http://localhost/myservice.svc/$batch
     */
    const BATCH = 9;

    /**
     * The request is a link operation - bind or unbind or simple get
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/$links/Orders
     */
    const LINK = 10;

    /**
     * A stream property value is requested.
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/$value
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/ThumNail64x64/$value
     */
    const MEDIA_RESOURCE = 11;

    /**
     * A single bag of primitive or complex values is requested
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/EMails
     */
    const BAG = 12;
}