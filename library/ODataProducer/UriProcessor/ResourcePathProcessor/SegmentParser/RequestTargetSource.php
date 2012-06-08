<?php
/** 
 * An enumeration to describe the source of result for the client request.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
/**
 * Client request result source enumerations.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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