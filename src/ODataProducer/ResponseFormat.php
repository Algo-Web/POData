<?php

namespace ODataProducer;

/**
 * Class ResponseFormat
 * @package ODataProducer
 */
class ResponseFormat
{
    /**
     * The application/atom+xml format.
     * Possible resources that can be serialized using this format
     *  (1) Entry
     *      e.g. /Customer('ALFKI')
     */    
    const ATOM = 1;

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
    const BINARY = 2;

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
    const JSON = 3;

    /**
     * An XML document for CSDL
     * Possible resources that can be serialized using this format
     *   (1) Metadata
     *       e.g. NorthWindServcie.svc/$metadata
     */
    const METADATA_DOCUMENT = 4;

    /**
     * An XML document for primitive complex and bag types
     *   e.g. /Customer('ALFKI')/CompanyName
     *       /Customer('ALFKI')/Address
     *       /Customer('ALFKI')/EMails 
     * 
     */
    const PLAIN_XML = 5;

    /**
     * A text-based format.
     * Possible resources that can be serialized using this format
     *  (1) Primitive value
     *      e.g. /Customer('ALFKI')/CompanyName/$value
     *           /Costomers/$count
     * 
     */
    const TEXT = 6;

    /**
     * An unsupported format
     */
    const UNSUPPORTED = 7;
}