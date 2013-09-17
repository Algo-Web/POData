<?php

namespace POData;
use MyCLabs\Enum\Enum;

/**
 * Class ResponseFormat
 * @package POData
 *
 * @method static \POData\ResponseFormat ATOM()
 * @method static \POData\ResponseFormat BINARY()
 * @method static \POData\ResponseFormat JSON()
 * @method static \POData\ResponseFormat METADATA_DOCUMENT()
 * @method static \POData\ResponseFormat PLAIN_XML()
 * @method static \POData\ResponseFormat TEXT()
 * @method static \POData\ResponseFormat UNSUPPORTED()
 */
class ResponseFormat extends Enum
{
    /**
     * The application/atom+xml format.
     * Possible resources that can be serialized using this format
     *  (1) Entry
     *      e.g. /Customer('ALFKI')
     */    
    const ATOM = "Atom";

    /**
     * The binary format.
     * Possible resources that can be serialized using this format
     *   (1) A primitive binary property value
     *       e.g. /Employees(1)/Photo/$value
     *   (2) Stream associated with Media Link entry
     *       e.g. /Albums('fmaily')/Photos('DS187')/$value
     *   (3) Stream associated with named stream property
     *       e.g. /Employees(1)/ThimNail_48X48/$value
     */
    const BINARY = "Binary";

    /**
     * The application/json format.
     * Possible resources that can be serialized using this format
     *   (1) Entry
     *       e.g. /Customer('ALFKI')
     *   (2) Primitive, complex or bag property
     *       e.g. /Customer('ALFKI')/CompanyName
     *            /Customer('ALFKI')/Address
     *            /Customer('ALFKI')/EMails
     *   (3) Service document
     *       e.g. NorthWindServcie.svc?$format=json
     */
    const JSON = "Json";

    /**
     * An XML document for CSDL
     * Possible resources that can be serialized using this format
     *   (1) Metadata
     *       e.g. NorthWindServcie.svc/$metadata
     */
    const METADATA_DOCUMENT = "Metadata Document";

    /**
     * An XML document for primitive complex and bag types
     *   e.g. /Customer('ALFKI')/CompanyName
     *       /Customer('ALFKI')/Address
     *       /Customer('ALFKI')/EMails 
     * 
     */
    const PLAIN_XML = "XML";

    /**
     * A text-based format.
     * Possible resources that can be serialized using this format
     *  (1) Primitive value
     *      e.g. /Customer('ALFKI')/CompanyName/$value
     *           /Costomers/$count
     * 
     */
    const TEXT = "Text";

    /**
     * An unsupported format
     */
    const UNSUPPORTED = "Unsupported";
}