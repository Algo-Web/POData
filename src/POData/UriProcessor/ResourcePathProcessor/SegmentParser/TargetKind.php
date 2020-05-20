<?php

declare(strict_types=1);

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use Cruxinator\BitMask\BitMask;
use MyCLabs\Enum\Enum;

/**
 * @method static TargetKind NOTHING()
 * @method isNOTHING(): bool
 * @method static TargetKind SERVICE_DIRECTORY()
 * @method isSERVICE_DIRECTORY(): bool
 * @method static TargetKind RESOURCE()
 * @method isRESOURCE(): bool
 * @method static TargetKind COMPLEX_OBJECT()
 * @method isCOMPLEX_OBJECT(): bool
 * @method static TargetKind PRIMITIVE()
 * @method isPRIMITIVE(): bool
 * @method static TargetKind PRIMITIVE_VALUE()
 * @method isPRIMITIVE_VALUE(): bool
 * @method static TargetKind METADATA()
 * @method isMETADATA(): bool
 * @method static TargetKind VOID_SERVICE_OPERATION()
 * @method isVOID_SERVICE_OPERATION(): bool
 * @method static TargetKind BATCH()
 * @method isBATCH(): bool
 * @method static TargetKind LINK()
 * @method isLINK(): bool
 * @method static TargetKind MEDIA_RESOURCE()
 * @method isMEDIA_RESOURCE(): bool
 * @method static TargetKind BAG()
 * @method isBAG(): bool
 * @method static TargetKind SINGLETON()
 * @method isSINGLETON(): bool
 * @method isComponentOfTERMINAL(): bool
 * @method isComponentOfSPECIAL_PURPOSE(): bool
 * @method isComponentOfNON_FILTERABLE(): bool
 */
class TargetKind extends BitMask
{
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
    protected const RESOURCE = 4;

    /**
     * A single complex value is requested (eg: an Address).
     * e.g. http://localhost/myservice.svc/Address.
     */
    protected const COMPLEX_OBJECT = 8;

    /**
     * A single value is requested (eg: a Picture property).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName
     *      http://localhost/myservice.svc/Address/LineNumber.
     */
    protected const PRIMITIVE = 16;

    /**
     * A single value is requested (eg: the raw stream of a Picture).
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/CustomerName/$value
     *      http://localhost/myservice.svc/Customers/$count.
     */
    protected const PRIMITIVE_VALUE = 32;

    /**
     * System metadata.
     * e.g. http://localhost/myservice.svc/$metadata.
     */
    protected const METADATA = 64;

    /**
     * A data-service-defined operation that doesn't return anything.
     */
    protected const VOID_SERVICE_OPERATION = 128;

    /**
     * The request is a batch request.
     * e.g. http://localhost/myservice.svc/$batch.
     */
    protected const BATCH = 256;

    /**
     * The request is a link operation - bind or unbind or simple get
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/$links/Orders.
     */
    protected const LINK = 512;

    /**
     * A stream property value is requested.
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/$value
     * e.g. http://localhost/myservice.svc/Albums('trip')/Photos('123')/ThumNail64x64/$value.
     */
    protected const MEDIA_RESOURCE = 1024;

    /**
     * A single bag of primitive or complex values is requested
     * e.g. http://localhost/myservice.svc/Customers('ALFKI')/EMails.
     */
    protected const BAG = 2048;

    /**
     * A singleton (parameter-less function wrapper).
     */
    protected const SINGLETON = 4096;

    protected const NON_FILTERABLE = 4 | 8;

    protected const SPECIAL_PURPOSE = 2 | 64 | 256;

    protected const TERMINAL = 32 | 64 | 256 | 1024 | 2048;

    /**
     * Is this segment a terminal segment - nothing else can be added after it?
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return $this->isComponentOfTERMINAL();
    }

    /**
     * Can this segment be accessed without necessarily directly touching database?
     *
     * @return bool
     */
    public function isSpecialPurpose(): bool
    {
        return $this->isComponentOfSPECIAL_PURPOSE();
    }

    /**
     * Is filtering prohibited for this type?
     *
     * @return bool
     */
    public function isNotFilterable(): bool
    {
        return $this->isComponentOfNON_FILTERABLE();
    }
}
