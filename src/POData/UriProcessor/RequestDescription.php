<?php

declare(strict_types=1);

namespace POData\UriProcessor;

use POData\Common\Messages;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Url;
use POData\Common\Version;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\OperationContext\IHTTPRequest;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceStreamInfo;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryType;
use POData\Readers\ODataReaderRegistry;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class RequestDescription.
 */
class RequestDescription
{
    /**
     * Holds the value of HTTP 'DataServiceVersion' header in the request,
     * DataServiceVersion header value states the version of the
     * Open Data Protocol used by the client to generate the request.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning.
     *
     * @var Version
     */
    private $requestVersion = null;

    /**
     * Holds the value of HTTP 'MaxDataServiceVersion' header in the request,
     * MaxDataServiceVersion header value specifies the maximum version number
     * the client can accept in a response.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning.
     *
     * @var Version
     */
    private $requestMaxVersion = null;

    /**
     * This is the value of 'DataServiceVersion' header to be output in the response. this header
     * value states the OData version the server used to generate the response.
     * While processing the query and result set this value will be keeps on
     * updating, after every update this is compared against the
     * 'MaxDataServiceVersion' header in the client request to see whether the
     * client can interpret the response or not. The client should use this
     * value to determine whether it can correctly interpret the response or not.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning.
     *
     * @var Version
     */
    private $requiredMinResponseVersion;

    /**
     * The minimum client version requirement, This value keeps getting updated
     * during processing of query, this is compared against the
     * DataServiceVersion header in the client request and if the client request
     * is less than this value then we fail the request (e.g. $count request
     * was sent but client said it was Version 1.0).
     *
     * @var Version
     */
    private $requiredMinRequestVersion;

    /** @var Version */
    private $maxServiceVersion;

    /**
     * Collection of known data service versions.
     *
     * @var Version[]
     */
    private static $knownDataServiceVersions = null;

    /**
     * @var Url
     */
    private $requestUrl;

    /**
     * Collection of SegmentDescriptor containing information about
     * each segment in the resource path part of the request uri.
     *
     * @var SegmentDescriptor[]
     */
    private $segments;

    /**
     * Holds reference to the last segment descriptor.
     *
     * @var SegmentDescriptor
     */
    private $lastSegment;

    /**
     * The name of the container for results.
     *
     * @var string|null
     */
    private $containerName;

    /**
     * The count option specified in the request.
     *
     * @var QueryType
     */
    public $queryType;

    /**
     * Number of segments.
     *
     * @var int
     */
    private $segmentCount;

    /**
     * Holds the value of $skip query option, if no $skip option
     * found then this parameter will be NULL.
     *
     * @var int|null
     */
    private $skipCount;

    /**
     * Holds the value of take count, this value is depends on
     * presence of $top option and configured page size.
     *
     * @var int|null
     */
    private $topCount;

    /**
     * Holds the value of $top query option, if no $top option
     * found then this parameter will be NULL.
     *
     * @var int|null
     */
    private $topOptionCount;

    /**
     * Holds the parsed details for sorting, this will
     * be set in 3 cases
     * (1) if $orderby option is specified in the request uri
     * (2) if $skip or $top option is specified in the request uri
     * (3) if server side paging is enabled for the resource
     *     targeted by the request uri.
     *
     * @var InternalOrderByInfo|null
     */
    private $internalOrderByInfo;

    /**
     * Holds the parsed details for $skiptoken option, this will
     * be NULL if $skiptoken option is absent.
     *
     * @var InternalSkipTokenInfo|null
     */
    private $internalSkipTokenInfo;

    /**
     * Holds the parsed details for $filter option, this will be NULL if $filter option is absent.
     *
     * @var FilterInfo|null
     */
    private $filterInfo;

    /**
     * Holds reference to the root of the tree describing expand
     * and select information, this field will be NULL if no
     * $expand or $select specified in the request uri.
     *
     * @var RootProjectionNode|null
     */
    private $rootProjectionNode;

    /**
     * Holds number of entities in the result set, if either $count or
     * $inlinecount=allpages is specified, otherwise NULL.
     *
     *
     * @var int|null
     */
    private $countValue;

    /**
     * Data of request from request body.
     *
     * @var array|string|ODataFeed|ODataEntry|null
     */
    private $data;

    /**
     * Flag indicating status of query execution.
     *
     * @var bool
     */
    private $isExecuted;

    /**
     * Reference to Uri processor.
     *
     * @var IUriProcessor
     */
    private $uriProcessor;
    /**
     * @var ODataReaderRegistry
     */
    private $readerRegistry;

    /**
     * @param  SegmentDescriptor[]                              $segmentDescriptors Description of segments
     *                                                                              in the resource path
     * @param  Url                                              $requestUri
     * @param  Version                                          $serviceMaxVersion
     * @param  string|null                                      $requestVersion
     * @param  string|null                                      $maxRequestVersion
     * @param  string|null                                      $dataType
     * @param  IHTTPRequest|null                                $payload
     * @param  ODataReaderRegistry                              $readerRegistry
     * @throws ODataException
     */
    public function __construct(
        array $segmentDescriptors,
        Url $requestUri,
        Version $serviceMaxVersion,
        $requestVersion,
        $maxRequestVersion,
        $dataType = null,
        IHTTPRequest $payload = null,
        ODataReaderRegistry $readerRegistry = null
    ) {
        $this->readerRegistry = $readerRegistry;
        $this->segments       = $segmentDescriptors;
        $this->segmentCount   = count($this->segments);
        $this->requestUrl     = $requestUri;
        $this->lastSegment    = $segmentDescriptors[$this->segmentCount - 1];
        $this->queryType      = QueryType::ENTITIES();
        //we use this for validation checks down in validateVersions...
        //but maybe we should check that outside of this object...
        $this->maxServiceVersion = $serviceMaxVersion;

        //Per OData 1 & 2 spec we must return the smallest size
        //We start at 1.0 and move it up as features are requested
        $this->requiredMinResponseVersion = clone Version::v1();
        $this->requiredMinRequestVersion  = clone Version::v1();

        //see http://www.odata.org/documentation/odata-v2-documentation/overview/#ProtocolVersioning
        //if requestVersion isn't there, use Service Max Version
        $this->requestVersion = null === $requestVersion
            ? $serviceMaxVersion
            : self::parseVersionHeader($requestVersion, ODataConstants::ODATAVERSIONHEADER);

        //if max version isn't there, use the request version
        $this->requestMaxVersion = null === $maxRequestVersion
            ? $this->requestVersion
            : self::parseVersionHeader($maxRequestVersion, ODataConstants::ODATAMAXVERSIONHEADER);

        //if it's OData v3..things change a bit
        if ($this->maxServiceVersion == Version::v3()) {
            if (null === $maxRequestVersion) {
                //if max request version isn't specified we use the service max version instead of the request version
                //thus we favour newer versions
                $this->requestMaxVersion = $this->maxServiceVersion;
            }

            //also we change min response version to be the max version, again favoring later things
            //note that if the request max version is specified, it is still respected
            $this->requiredMinResponseVersion = clone $this->requestMaxVersion;
        }

        $this->containerName         = null;
        $this->skipCount             = null;
        $this->topCount              = null;
        $this->topOptionCount        = null;
        $this->internalOrderByInfo   = null;
        $this->internalSkipTokenInfo = null;

        $this->filterInfo = null;
        $this->countValue = null;
        $this->isExecuted = false;
        $this->data       = isset($payload) ? $payload->getAllInput() : null;

        // Define data from request body
        if (null !== $dataType) {
            $this->readData($dataType);
        }
    }

    /**
     * Define request data from body.
     *
     * @param  string $dataType
     * @return void
     */
    private function readData($dataType)
    {
        if (null === $this->data) {
            return;
        }
        $string = $this->data;
        if ($dataType === MimeTypes::MIME_APPLICATION_JSON) {
            $data       = !is_array($string) ? json_decode($string, true) : $string;
            $this->data = $data;
            return;
        }

        $reader = $this->readerRegistry->getReader($this->requestVersion, $dataType);
        if ($reader !== null) {
            if (is_array($string) && 1 == count($string)) {
                $string = $string[0];
            }
            if (0 == strlen(trim($string))) {
                return;
            }
            $this->data = $reader->read($string);
        }
    }

    /**
     * Get request data from body.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Raise the minimum client version requirement for this request and
     * perform capability negotiation.
     *
     * @param int $major The major segment of the version
     * @param int $minor The minor segment of the version
     *
     * @throws ODataException If capability negotiation fails
     */
    public function raiseMinVersionRequirement($major, $minor)
    {
        if ($this->requiredMinRequestVersion->raiseVersion($major, $minor)) {
            $this->validateVersions();
        }
    }

    /**
     * Raise the response version for this request and perform capability negotiation.
     *
     *
     * @param int $major The major segment of the version
     * @param int $minor The minor segment of the version
     *
     * @throws ODataException If capability negotiation fails
     */
    public function raiseResponseVersion($major, $minor)
    {
        if ($this->requiredMinResponseVersion->raiseVersion($major, $minor)) {
            $this->validateVersions();
        }
    }

    /**
     * Gets collection of segment descriptors containing information about
     * each segment in the resource path part of the request uri.
     *
     * @return SegmentDescriptor[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Gets reference to the descriptor of last segment.
     *
     * @return SegmentDescriptor
     */
    public function getLastSegment()
    {
        return $this->lastSegment;
    }

    /**
     * Gets kind of resource targeted by the resource path.
     *
     * @return TargetKind
     */
    public function getTargetKind()
    {
        return $this->lastSegment->getTargetKind();
    }

    /**
     * Gets kind of 'source of data' targeted by the resource path.
     *
     * @return TargetSource
     */
    public function getTargetSource()
    {
        return $this->lastSegment->getTargetSource();
    }

    /**
     * Gets reference to the ResourceSetWrapper instance targeted by
     * the resource path, ResourceSetWrapper will present in the
     * following cases:
     * if the last segment descriptor describes
     *      (a) resource set
     *          http://server/NW.svc/Customers
     *          http://server/NW.svc/Customers('ALFKI')
     *          http://server/NW.svc/Customers('ALFKI')/Orders
     *          http://server/NW.svc/Customers('ALFKI')/Orders(123)
     *          http://server/NW.svc/Customers('ALFKI')/$links/Orders
     *      (b) resource set reference
     *          http://server/NW.svc/Orders(123)/Customer
     *          http://server/NW.svc/Orders(123)/$links/Customer
     *      (c) $count
     *          http://server/NW.svc/Customers/$count
     * ResourceSet wrapper will be absent (NULL) in the following cases:
     * if the last segment descriptor describes
     *      (a) Primitive
     *          http://server/NW.svc/Customers('ALFKI')/Country
     *      (b) $value on primitive type
     *          http://server/NW.svc/Customers('ALFKI')/Country/$value
     *      (c) Complex
     *          http://server/NW.svc/Customers('ALFKI')/Address
     *      (d) Bag
     *          http://server/NW.svc/Employees(123)/Emails
     *      (e) MLE
     *          http://server/NW.svc/Employees(123)/$value
     *      (f) Named Stream
     *          http://server/NW.svc/Employees(123)/Thumbnail48_48
     *      (g) metadata
     *          http://server/NW.svc/$metadata
     *      (h) service directory
     *          http://server/NW.svc
     *      (i) $bath
     *          http://server/NW.svc/$batch.
     *
     * @return ResourceSetWrapper|null
     */
    public function getTargetResourceSetWrapper()
    {
        return $this->lastSegment->getTargetResourceSetWrapper();
    }

    /**
     * Gets reference to the ResourceType instance targeted by
     * the resource path, ResourceType will present in the
     * following cases:
     * if the last segment descriptor describes
     *      (a) resource set
     *          http://server/NW.svc/Customers
     *          http://server/NW.svc/Customers('ALFKI')
     *          http://server/NW.svc/Customers('ALFKI')/Orders
     *          http://server/NW.svc/Customers('ALFKI')/Orders(123)
     *          http://server/NW.svc/Customers('ALFKI')/$links/Orders
     *      (b) resource set reference
     *          http://server/NW.svc/Orders(123)/Customer
     *          http://server/NW.svc/Orders(123)/$links/Customer
     *      (c) $count
     *          http://server/NW.svc/Customers/$count
     *      (d) Primitive
     *          http://server/NW.svc/Customers('ALFKI')/Country
     *      (e) $value on primitive type
     *          http://server/NW.svc/Customers('ALFKI')/Country/$value
     *      (f) Complex
     *          http://server/NW.svc/Customers('ALFKI')/Address
     *      (g) Bag
     *          http://server/NW.svc/Employees(123)/Emails
     *      (h) MLE
     *          http://server/NW.svc/Employees(123)/$value
     *      (i) Named Stream
     *          http://server/NW.svc/Employees(123)/Thumbnail48_48
     * ResourceType will be absent (NULL) in the following cases:
     * if the last segment descriptor describes
     *      (a) metadata
     *          http://server/NW.svc/$metadata
     *      (b) service directory
     *          http://server/NW.svc
     *      (c) $bath
     *          http://server/NW.svc/$batch.
     *
     * @return ResourceType|null
     */
    public function getTargetResourceType()
    {
        return $this->lastSegment->getTargetResourceType();
    }

    /**
     * Gets reference to the ResourceProperty instance targeted by
     * the resource path, ResourceProperty will present in the
     * following cases:
     * if the last segment descriptor describes
     *      (a) resource set (after 1 level)
     *          http://server/NW.svc/Customers('ALFKI')/Orders
     *          http://server/NW.svc/Customers('ALFKI')/Orders(123)
     *          http://server/NW.svc/Customers('ALFKI')/$links/Orders
     *      (b) resource set reference
     *          http://server/NW.svc/Orders(123)/Customer
     *          http://server/NW.svc/Orders(123)/$links/Customer
     *      (c) $count
     *          http://server/NW.svc/Customers/$count
     *      (d) Primitive
     *          http://server/NW.svc/Customers('ALFKI')/Country
     *      (e) $value on primitive type
     *          http://server/NW.svc/Customers('ALFKI')/Country/$value
     *      (f) Complex
     *          http://server/NW.svc/Customers('ALFKI')/Address
     *      (g) Bag
     *          http://server/NW.svc/Employees(123)/Emails
     *      (h) MLE
     *          http://server/NW.svc/Employees(123)/$value.
     *
     * ResourceType will be absent (NULL) in the following cases:
     * if the last segment descriptor describes
     *      (a) If last segment is the only segment pointing to
     *          ResourceSet (single or multiple)
     *          http://server/NW.svc/Customers
     *          http://server/NW.svc/Customers('ALFKI')
     *      (b) Named Stream
     *          http://server/NW.svc/Employees(123)/Thumbnail48_48
     *      (c) metadata
     *          http://server/NW.svc/$metadata
     *      (d) service directory
     *          http://server/NW.svc
     *      (e) $bath
     *          http://server/NW.svc/$batch
     *
     * @return ResourceProperty|null
     */
    public function getProjectedProperty()
    {
        return  $this->lastSegment->getProjectedProperty();
    }

    /**
     * Gets the name of the container for results.
     *
     * @return string|null
     */
    public function getContainerName()
    {
        return $this->containerName;
    }

    /**
     * Sets the name of the container for results.
     *
     * @param string $containerName The container name
     */
    public function setContainerName($containerName)
    {
        $this->containerName = $containerName;
    }

    /**
     * Whether thr request targets a single result or not.
     *
     * @return bool
     */
    public function isSingleResult()
    {
        return $this->lastSegment->isSingleResult();
    }

    /**
     * Gets the identifier associated with the the resource path.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->lastSegment->getIdentifier();
    }

    /**
     * Gets the request uri.
     *
     * @return Url
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * Gets the value of $skip query option.
     *
     * @return int|null The value of $skip query option, NULL if $skip is absent
     */
    public function getSkipCount()
    {
        return $this->skipCount;
    }

    /**
     * Sets skip value.
     *
     * @param int $skipCount The value of $skip query option
     */
    public function setSkipCount($skipCount)
    {
        $this->skipCount = $skipCount;
    }

    /**
     * Gets the value of take count.
     *
     * @return int|null The value of take, NULL if no take to be applied
     */
    public function getTopCount()
    {
        return $this->topCount;
    }

    /**
     * Sets the value of take count.
     *
     * @param int $topCount The value of take query option
     */
    public function setTopCount($topCount)
    {
        $this->topCount = $topCount;
    }

    /**
     * Gets the value of $top query option.
     *
     * @return int|null The value of $top query option, NULL if $top is absent
     */
    public function getTopOptionCount()
    {
        return $this->topOptionCount;
    }

    /**
     * Sets top value.
     *
     * @param int $topOptionCount The value of $top query option
     */
    public function setTopOptionCount($topOptionCount)
    {
        $this->topOptionCount = $topOptionCount;
    }

    /**
     * Gets sorting (orderby) information, this function return
     * sorting information in 3 cases:
     * (1) if $orderby option is specified in the request uri
     * (2) if $skip or $top option is specified in the request uri
     * (3) if server side paging is enabled for the resource targeted
     *     by the request uri.
     *
     * @return InternalOrderByInfo|null
     */
    public function getInternalOrderByInfo()
    {
        return $this->internalOrderByInfo;
    }

    /**
     * Sets sorting (orderby) information.
     *
     * @param InternalOrderByInfo &$internalOrderByInfo The sorting information
     */
    public function setInternalOrderByInfo(InternalOrderByInfo &$internalOrderByInfo)
    {
        $this->internalOrderByInfo = $internalOrderByInfo;
    }

    /**
     * Gets the parsed details for $skiptoken option.
     *
     * @return InternalSkipTokenInfo|null Returns parsed details of $skiptoken option, NULL if $skiptoken is absent
     */
    public function getInternalSkipTokenInfo()
    {
        return $this->internalSkipTokenInfo;
    }

    /**
     * Sets $skiptoken information.
     *
     * @param InternalSkipTokenInfo &$internalSkipTokenInfo The paging information
     */
    public function setInternalSkipTokenInfo(
        InternalSkipTokenInfo &$internalSkipTokenInfo
    ) {
        $this->internalSkipTokenInfo = $internalSkipTokenInfo;
    }

    /**
     * @return FilterInfo|null Returns parsed details of $filter option, NULL if $filter is absent
     */
    public function getFilterInfo()
    {
        return $this->filterInfo;
    }

    /**
     * @param FilterInfo $filterInfo The filter information
     */
    public function setFilterInfo(FilterInfo $filterInfo)
    {
        $this->filterInfo = $filterInfo;
    }

    /**
     * Sets $expand and $select information.
     *
     * @param RootProjectionNode &$rootProjectionNode Root of the projection tree
     */
    public function setRootProjectionNode(RootProjectionNode &$rootProjectionNode)
    {
        $this->rootProjectionNode = $rootProjectionNode;
    }

    /**
     * Gets the root of the tree describing expand and select options,.
     *
     * @return RootProjectionNode|null Returns parsed details of $expand
     *                                 and $select options, NULL if
     *                                 $both options are absent
     */
    public function getRootProjectionNode()
    {
        return $this->rootProjectionNode;
    }

    /**
     * Gets the count of result set if $count or $inlinecount=allpages
     * has been applied otherwise NULL.
     *
     * @return int|null
     */
    public function getCountValue()
    {
        return $this->countValue;
    }

    /**
     * Sets the count of result set.
     *
     * @param int $countValue The count value
     */
    public function setCountValue($countValue)
    {
        $this->countValue = $countValue;
    }

    /**
     * To set the flag indicating the execution status as true.
     */
    public function setExecuted()
    {
        $this->isExecuted = true;
    }

    /**
     * To check whether to execute the query using IDSQP.
     *
     * @return bool True if query need to be executed, False otherwise
     */
    public function needExecution()
    {
        return !$this->isExecuted
            && ($this->lastSegment->getTargetKind() != TargetKind::METADATA())
            && ($this->lastSegment->getTargetKind() != TargetKind::SERVICE_DIRECTORY());
    }

    /**
     * To check if the resource path is a request for link uri.
     *
     * @return bool True if request is for link uri else false
     */
    public function isLinkUri()
    {
        return ($this->segmentCount > 2)
            && ($this->segments[$this->segmentCount - 2]->getTargetKind() == TargetKind::LINK());
    }

    /**
     * To check if the resource path is a request for media resource.
     *
     * @return bool True if request is for media resource else false
     */
    public function isMediaResource()
    {
        return $this->lastSegment->getTargetKind() == TargetKind::MEDIA_RESOURCE();
    }

    /**
     * To check if the resource path is a request for named stream.
     *
     * @return bool True if request is for named stream else false
     */
    public function isNamedStream()
    {
        return $this->isMediaResource() && !($this->lastSegment->getIdentifier() === ODataConstants::URI_VALUE_SEGMENT);
    }

    /**
     * Get ResourceStreamInfo for the media link entry or named stream request.
     *
     * @return ResourceStreamInfo|null Instance of ResourceStreamInfo if the
     *                                 current request targets named stream, NULL for MLE
     */
    public function getResourceStreamInfo()
    {
        //assert($this->isMediaResource)
        if ($this->isNamedStream()) {
            return $this->getTargetResourceType()
                ->tryResolveNamedStreamByName(
                    $this->lastSegment->getIdentifier()
                );
        }
        return null;
    }

    /**
     * Gets the resource instance targeted by the request uri.
     * Note: This value will be populated after query execution only.
     *
     * @return mixed
     */
    public function getTargetResult()
    {
        return $this->lastSegment->getResult();
    }

    /**
     * Gets the OData version the server used to generate the response.
     *
     * @return Version
     */
    public function getResponseVersion()
    {
        return $this->requiredMinResponseVersion;
    }

    /**
     * Checks whether etag headers are allowed for this request.
     *
     * @return bool True if ETag header (If-Match or If-NoneMatch)
     *              is allowed for the request, False otherwise
     */
    public function isETagHeaderAllowed()
    {
        return $this->lastSegment->isSingleResult()
            && ($this->queryType != QueryType::COUNT())
            && !$this->isLinkUri()
            && (
                null === $this->rootProjectionNode
                || !($this->rootProjectionNode->isExpansionSpecified())
            );
    }

    /**
     * Gets collection of known data service versions, currently 1.0, 2.0 and 3.0.
     *
     * @return Version[]
     */
    public static function getKnownDataServiceVersions()
    {
        if (null === self::$knownDataServiceVersions) {
            self::$knownDataServiceVersions = [
                new Version(1, 0),
                new Version(2, 0),
                new Version(3, 0),
            ];
        }

        return self::$knownDataServiceVersions;
    }

    /**
     * This function is used to perform following checking (validation)
     * for capability negotiation.
     *  (1) Check client request's 'DataServiceVersion' header value is
     *      less than or equal to the minimum version required to intercept
     *      the response
     *  (2) Check client request's 'MaxDataServiceVersion' header value is
     *      less than or equal to the version of protocol required to generate
     *      the response
     *  (3) Check the configured maximum protocol version is less than or equal
     *      to the version of protocol required to generate the response
     *  In addition to these checking, this function is also responsible for
     *  initializing the properties representing 'DataServiceVersion' and
     *  'MaxDataServiceVersion'.
     *
     *
     * @throws ODataException If any of the above 3 check fails
     */
    public function validateVersions()
    {

        //If the request version is below the minimum version required by supplied request arguments..throw an exception
        if ($this->requestVersion->compare($this->requiredMinRequestVersion) < 0) {
            throw ODataException::createBadRequestError(
                Messages::requestVersionTooLow(
                    $this->requestVersion->toString(),
                    $this->requiredMinRequestVersion->toString()
                )
            );
        }

        //If the requested max version is below the version required to fulfill the response...throw an exception
        if ($this->requestMaxVersion->compare($this->requiredMinResponseVersion) < 0) {
            throw ODataException::createBadRequestError(
                Messages::requestVersionTooLow(
                    $this->requestMaxVersion->toString(),
                    $this->requiredMinResponseVersion->toString()
                )
            );
        }

        //If the max version supported by the service is below the version required to fulfill the response..
        //throw an exception
        if ($this->maxServiceVersion->compare($this->requiredMinResponseVersion) < 0) {
            throw ODataException::createBadRequestError(
                Messages::requestVersionIsBiggerThanProtocolVersion(
                    $this->requiredMinResponseVersion->toString(),
                    $this->maxServiceVersion->toString()
                )
            );
        }
    }

    /**
     * Validates the given version in string format and returns the version as instance of Version.
     *
     * @param string $versionHeader The DataServiceVersion or MaxDataServiceVersion header value
     * @param string $headerName    The name of the header
     *
     * @throws ODataException If the version is malformed or not supported
     *
     * @return Version
     */
    private static function parseVersionHeader($versionHeader, $headerName)
    {
        $versionHeader = trim($versionHeader);
        $libNameIndex  = strpos($versionHeader, ';');
        if (false === $libNameIndex) {
            $libNameIndex = strlen($versionHeader);
        }

        $dotIndex      = -1;
        $badVersionMsg = Messages::requestDescriptionInvalidVersionHeader(
            $versionHeader,
            $headerName
        );
        for ($i = 0; $i < $libNameIndex; ++$i) {
            if ($versionHeader[$i] == '.') {
                //Throw an exception if we find more than 1 dot
                if ($dotIndex != -1) {
                    throw ODataException::createBadRequestError($badVersionMsg);
                }

                $dotIndex = $i;
            } elseif ($versionHeader[$i] < '0' || $versionHeader[$i] > '9') {
                throw ODataException::createBadRequestError($badVersionMsg);
            }
        }

        $major = intval(substr($versionHeader, 0, $dotIndex));
        $minor = 0;

        //Apparently the . is optional
        if ($dotIndex != -1) {
            if ($dotIndex == 0) {
                //If it starts with a ., throw an exception
                throw ODataException::createBadRequestError($badVersionMsg);
            }
            $minor = intval(substr($versionHeader, $dotIndex + 1, $libNameIndex));
        }

        $version = new Version($major, $minor);

        //TODO: move this somewhere...
        //$this->validateVersions();
        $isSupportedVersion = false;
        foreach (self::getKnownDataServiceVersions() as $version1) {
            if ($version->compare($version1) == 0) {
                $isSupportedVersion = true;
                break;
            }
        }

        if (!$isSupportedVersion) {
            $availableVersions = null;
            foreach (self::getKnownDataServiceVersions() as $version1) {
                $availableVersions .= $version1->toString() . ', ';
            }

            $availableVersions = rtrim($availableVersions, ', ');
            throw ODataException::createBadRequestError(
                Messages::requestDescriptionUnSupportedVersion(
                    $headerName,
                    $versionHeader,
                    $availableVersions
                )
            );
        }

        return $version;
    }

    /**
     * Gets reference to the IUriProcessor instance.
     *
     * @return IUriProcessor
     */
    public function getUriProcessor()
    {
        return $this->uriProcessor;
    }

    /**
     * Set reference to IUriProcessor instance.
     *
     * @param IUriProcessor $uriProcessor Reference to the UriProcessor
     */
    public function setUriProcessor(IUriProcessor $uriProcessor)
    {
        $this->uriProcessor = $uriProcessor;
    }
}
