<?php

declare(strict_types=1);

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use MyCLabs\Enum\Enum;

/**
 * @method static TargetKind NOTHING()
 * @method static TargetKind SERVICE_DIRECTORY()
 * @method static TargetKind RESOURCE()
 * @method static TargetKind COMPLEX_OBJECT()
 * @method static TargetKind PRIMITIVE()
 * @method static TargetKind PRIMITIVE_VALUE()
 * @method static TargetKind METADATA()
 * @method static TargetKind VOID_SERVICE_OPERATION()
 * @method static TargetKind BATCH()
 * @method static TargetKind LINK()
 * @method static TargetKind MEDIA_RESOURCE()
 * @method static TargetKind BAG()
 * @method static TargetKind SINGLETON()
 */
class TargetKind extends Enum
{
    protected const TERMINAL_VALUES = [6 => true, 7 => true, 9 => true, 11 => true, 12 => true];

    /**
     * Nothing specific is being requested.
     * e.g. http://localhost.
     */
    protected const NOTHING = 1;

    /**
     * A top-level directory of service capabilities.
     * e.g. http://localhost/myservice.svc.
     */
    protected const SERVICE_DIRECTORY = 2;

    /**
     * Entity Resource is requested - it can be a collection or a single value.
     * e.g. http://localhost/myservice.svc/Customers
     *      http://localhost/myservice.svc/Customers('ALFKI')/Orders(123).
     */
    protected const RESOURCE = 3;

    /**
     * A single complex value is requested (eg: an Address).
     * e.g. http://localhost/myservice.svc/Address.
     */
    protected const COMPLEX_OBJECT = 4;

    /**
     * A single value is requested (eg: a Picture property).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName
     *      http://localhost/myservice.svc/Address/LineNumber.
     */
    protected const PRIMITIVE = 5;

    /**
     * A single value is requested (eg: the raw stream of a Picture).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName/$value
     *      http://localhost/myservice.svc/Customers/$count.
     */
    protected const PRIMITIVE_VALUE = 6;

    /**
     * System metadata.
     * e.g. http://localhost/myservice.svc/$metadata.
     */
    protected const METADATA = 7;

    /**
     * A data-service-defined operation that doesn't return anything.
     */
    protected const VOID_SERVICE_OPERATION = 8;

    /**
     * The request is a batch request.
     * e.g. http://localhost/myservice.svc/$batch.
     */
    protected const BATCH = 9;

    /**
     * The request is a link operation - bind or unbind or simple get
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/$links/Orders.
     */
    protected const LINK = 10;

    /**
     * A stream property value is requested.
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/$value
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/ThumNail64x64/$value.
     */
    protected const MEDIA_RESOURCE = 11;

    /**
     * A single bag of primitive or complex values is requested
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/EMails.
     */
    protected const BAG = 12;

    /**
     * A singleton (parameter-less function wrapper).
     */
    protected const SINGLETON = 13;

    /**
     * Is this segment a terminal segment - nothing else can be added after it?
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return array_key_exists($this->getValue(), self::TERMINAL_VALUES);
    }
}
