<?php
/** 
 * A type to hold description of the OData request that a client
 * has submitted.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor
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
namespace ODataProducer\UriProcessor;
use ODataProducer\Common\Url;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\Version;
use ODataProducer\Common\ODataException;
use ODataProducer\DataService;
use ODataProducer\Providers\Metadata\ResourceSetWrapper;
use ODataProducer\Providers\Metadata\ResourceStreamInfo;
use ODataProducer\UriProcessor\UriProcessor;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\InternalFilterInfo;
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
/**
 * Type to hold clinet submitted request.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class RequestDescription
{
    /**
     * Holds the value of HTTP 'DataServiceVersion' header in the request, 
     * DataServiceVersion header value states the version of the 
     * Open Data Protocol used by the client to generate the request.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning
     * 
     * @var Version
     */
    private $_requestDataServiceVersion = null;

    /**
     * Holds the value of HTTP 'MaxDataServiceVersion' header in the request,
     * MaxDataServiceVersion header value specifies the maximum version number
     * the client can accept in a response.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning
     * 
     * @var Version
     */
    private $_requestMaxDataServiceVersion = null;

    /**
     * This is the value of 'DataServiceVersion' header in response, this header
     * value states the OData version the server used to generate the response.
     * While processing the query and result set this value will be keeps on
     * updating, after every updation this is compared against the 
     * 'MaxDataServiceVersion' header in the client request to see whether the 
     * client can interpret the response or not. The client should use this 
     * value to determine whether it can correctly interpret the response or not.
     * Refer http://www.odata.org/developers/protocols/overview#ProtocolVersioning
     * 
     * @var Version
     */
    private $_responseDataServiceVersion;

    /**
     * The minimum client version requirement, This value keeps getting updated
     * during processing of query, this is compared against the 
     * DataServiceVersion header in the client request and if the client request
     * is less than this value then we fail the request (e.g. $count request
     * was sent but client said it was Version 1.0).
     * 
     * @var Version
     */
    private $_minimumRequiredClientVersion;

    /**
     * Collection of known data service versions.
     * 
     * @var array(Version)
     */
    private static $_knownDataServiceVersions = null;

    /**
     * The request Uri.
     * 
     * @var Uri
     */
    private $_requestUri;

    /**
     * Collection of SegmentDescriptor containing information about 
     * each segment in the resource path part of the request uri.
     * 
     * @var array(SegmentDescriptor)
     */
    private $_segmentDescriptors;

    /**
     * Holds reference to the last segment descriptor.
     * 
     * @var SegmentDescriptor
     */
    private $_lastSegmentDescriptor;

    /**
     * The name of the container for results
     * 
     * @var string/NULL
     */
    private $_containerName;

    /**
     * The count option specified in the request.
     * 
     * @var RequestCountOption
     */
    private $_requestCountOption;

    /**
     * Number of segments.
     * 
     * @var int
     */
    private $_segmentCount;

    /**
     * Holds the value of $skip query option, if no $skip option
     * found then this parameter will be NULL.
     * 
     * @var int/NULL
     */
    private $_skipCount;

    /**
     * Holds the value of take count, this value is depends on
     * presence of $top option and configured page size.
     * 
     * @var int/NULL
     */
    private $_topCount;

    /**
     * Holds the value of $top query option, if no $top option
     * found then this parameter will be NULL.
     * 
     * @var int/NULL
     */
    private $_topOptionCount;

    /**
     * Holds the parsed details for sorting, this will
     * be set in 3 cases
     * (1) if $orderby option is specified in the request uri
     * (2) if $skip or $top option is specified in the request uri
     * (3) if server side paging is enabled for the resource 
     *     targetted by the request uri.
     * 
     * @var InternalOrderByInfo/NULL
     */
    private $_internalOrdeByInfo;

    /**
     * Holds the parsed details for $skiptoken option, this will
     * be NULL if $skiptoken option is absent.
     * 
     * @var InternalSkipTokenInfo/NULL
     */
    private $_internalSkipTokenInfo;

    /**
     * Holds the parsed details for $filter option, this will
     * be NULL if $filter option is absent.
     * 
     * @var InternalFilterInfo/NULL
     */
    private $_internalFilterInfo;

    /**
     * Holds reference to the root of the tree describing expand
     * and select information, this field will be NULL if no 
     * $expand or $select specified in the request uri.
     * 
     * @var RootProjectionNode/NULL
     */
    private $_rootProjectionNode;

    /**
     * Holds number of entitties in the result set, if either $count or
     * $inlinecount=allpages is specified, otherwise NULL
     * 
     * 
     * @var int/NULL
     */
    private $_countValue;

    /**
     * Flag indcating status of query execution.
     * 
     * @var boolean
     */
    private $_isExecuted;

    /**
     * Reference to Uri processor.
     * 
     * @var UriProcessor
     */
    private $_uriProcessor;

    /**
     * Constructs a new instance of RequestDescription.
     * 
     * @param array(SegmentDescriptor) &$segmentDescriptors Description of segments
     *                                                      in the resource path.
     * @param Url                      &$requestUri         The request Uri.
     */
    public function __construct(&$segmentDescriptors, Url &$requestUri)
    {
        $this->_segmentDescriptors = $segmentDescriptors;
        $this->_segmentCount = count($this->_segmentDescriptors);
        $this->_requestUri = $requestUri;        
        $this->_lastSegmentDescriptor 
            = $segmentDescriptors[$this->_segmentCount - 1];
        $this->_requestCountOption = RequestCountOption::NONE;
        $this->_responseDataServiceVersion = new Version(1, 0);
        $this->_minimumRequiredClientVersion = new Version(1, 0);
        $this->_containerName = null;
        $this->_skipCount = null;
        $this->_topCount = null;
        $this->_topOptionCount = null;
        $this->_internalOrdeByInfo = null;
        $this->_internalSkipTokenInfo = null;
        $this->_internalFilterInfo = null;
        $this->_countValue = null;
        $this->_isExecuted = false;
    }

    /**
     * Raise the minimum client version requirement for this request and
     * perform capability negotiation.
     * 
     * @param int         $major       The major segment of the version
     * @param int         $minor       The minor segment of the version
     * @param DataService $dataService The data service instance
     * 
     * @return void
     * 
     * @throws ODataException If capability negotiation fails.
     */
    public function raiseMinimumVersionRequirement($major, 
        $minor, 
        DataService $dataService
    ) {
        $this->_minimumRequiredClientVersion->raiseVersion($major, $minor);
        self::checkVersion($this, $dataService);
    }

    /**
     * Raise the response version for this request and perform 
     * capability negotiation.
     * 
     * @param int         $major       The major segment of the version
     * @param int         $minor       The minor segment of the version
     * @param DataService $dataService The data service instance
     * 
     * @return void
     * 
     * @throws ODataException If capability negotiation fails.
     */  
    public function raiseResponseVersion($major, 
        $minor, 
        DataService $dataService
    ) {
        $this->_responseDataServiceVersion->raiseVersion($major, $minor);
        self::checkVersion($this, $dataService);
    }

    /**
     * Gets collection of segment descriptors containing information about
     * each segment in the resource path part of the request uri.
     * 
     * @return array(SegmentDescriptor)
     */
    public function &getSegmentDescriptors()
    {
        return $this->_segmentDescriptors;
    }

    /**
     * Gets referece to the descriptor of last segment.
     * 
     * @return SegmentDescriptor
     */
    public function &getLastSegmentDescriptor()
    {
        return $this->_lastSegmentDescriptor;
    }

    /**
     * Gets kind of resource targetted by the resource path.
     * 
     * @return RequestTargetKind
     */
    public function getTargetKind()
    {
        return $this->_lastSegmentDescriptor->getTargetKind();
    }

    /**
     * Gets kind of 'source of data' targetted by the resource path.
     * 
     * @return RequestTargetSource
     */
    public function getTargetSource()
    {
        return $this->_lastSegmentDescriptor->getTargetSource();
    }

    /**
     * Gets reference to the ResourceSetWrapper instance targetted by 
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
     *          http://server/NW.svc/Employees(123)/Thumnail48_48
     *      (g) metadata
     *          http://server/NW.svc/$metadata
     *      (h) service directory
     *          http://server/NW.svc
     *      (i) $bath
     *          http://server/NW.svc/$batch
     *       
     * @return ResourceSetWrapper/NULL
     */
    public function getTargetResourceSetWrapper()
    {
        return $this->_lastSegmentDescriptor->getTargetResourceSetWrapper();
    }

    /**
     * Gets reference to the ResourceType instance targetted by 
     * the resource path, ResourceType will present in the 
     * following cases:
     * if the last segement descriptor describes
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
     *          http://server/NW.svc/Employees(123)/Thumnail48_48
     * ResourceType will be absent (NULL) in the following cases:
     * if the last segment descriptor describes
     *      (a) metadata
     *          http://server/NW.svc/$metadata
     *      (b) service directory
     *          http://server/NW.svc
     *      (c) $bath
     *          http://server/NW.svc/$batch
     *      
     * @return ResourceType/NULL
     */
    public function getTargetResourceType()
    {
        return $this->_lastSegmentDescriptor->getTargetResourceType();
    }

    /**
     * Gets reference to the ResourceProperty instance targetted by 
     * the resource path, ResourceProperty will present in the 
     * following cases:
     * if the last segement descriptor describes
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
     *          http://server/NW.svc/Employees(123)/$value
     *       
     * ResourceType will be absent (NULL) in the following cases:
     * if the last segment descriptor describes
     *      (a) If last segment is the only segment pointing to
     *          ResourceSet (single or multiple)
     *          http://server/NW.svc/Customers
     *          http://server/NW.svc/Customers('ALFKI')
     *      (b) Named Stream
     *          http://server/NW.svc/Employees(123)/Thumnail48_48
     *      (c) metadata
     *          http://server/NW.svc/$metadata
     *      (d) service directory
     *          http://server/NW.svc
     *      (e) $bath
     *          http://server/NW.svc/$batch
     *      
     * @return ResourceProperty/NULL
     */
    public function getProjectedProperty()
    {
        return  $this->_lastSegmentDescriptor->getProjectedProperty();
    }

    /**
     * Gets the name of the container for results.
     * 
     * @return string/NULL
     */
    public function getContainerName()
    {
        return $this->_containerName;
    }

    /**
     * Sets the name of the container for results.
     * 
     * @param string $containerName The container name.
     * 
     * @return void
     */
    public function setContainerName($containerName)
    {
        $this->_containerName = $containerName;
    }

    /**
     * Whether thr request targets a single result or not.
     * 
     * @return boolean
     */
    public function isSingleResult()
    {
        return $this->_lastSegmentDescriptor->isSingleResult();
    }

    /**
     * Gets the identifier associated with the the resource path. 
     * 
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_lastSegmentDescriptor->getIdentifier();
    }

    /**
     * Gets the request uri.
     * 
     * @return Url
     */
    public function getRequestUri()
    {
        return $this->_requestUri;
    }

    /**
     * Gets the value of $skip query option
     * 
     * @return int/NULL The value of $skip query option, NULL
     *                  if $skip is absent.
     */
    public function getSkipCount()
    {
        return $this->_skipCount;
    }

    /**
     * Sets skip value
     * 
     * @param int $skipCount The value of $skip query option.
     * 
     * @return void
     */
    public function setSkipCount($skipCount)
    {
        $this->_skipCount = $skipCount;
    }

    /**
     * Gets the value of take count
     * 
     * @return int/NULL The value of take, NULL
     *                  if no take to be applied.
     */
    public function getTopCount()
    {
        return $this->_topCount;
    }

    /**
     * Sets the value of take count
     * 
     * @param int $topCount The value of take query option
     * 
     * @return void
     */
    public function setTopCount($topCount)
    {
        $this->_topCount = $topCount;
    }

    /**
     * Gets the value of $top query option
     * 
     * @return int/NULL The value of $top query option, NULL
     *                  if $top is absent.
     */
    public function getTopOptionCount()
    {
        return $this->_topOptionCount;
    }

    /**
     * Sets top value
     * 
     * @param int $topOptionCount The value of $top query option
     * 
     * @return void
     */
    public function setTopOptionCount($topOptionCount)
    {
        $this->_topOptionCount = $topOptionCount;
    }

    /**
     * Gets sorting (orderby) information, this function return
     * sorting information in 3 cases:
     * (1) if $orderby option is specified in the request uri
     * (2) if $skip or $top option is specified in the request uri
     * (3) if server side paging is enabled for the resource targetted 
     *     by the request uri.
     * 
     * @return InternalOrderByInfo/NULL
     */
    public function getInternalOrderByInfo()
    {
        return $this->_internalOrdeByInfo;
    }

    /**
     * Sets sorting (orderby) information.
     *     
     * @param InternalOrderByInfo &$internalOrderByInfo The sorting information.
     * 
     * @return void
     */
    public function setInternalOrderByInfo(InternalOrderByInfo &$internalOrderByInfo)
    {
        $this->_internalOrdeByInfo = $internalOrderByInfo;
    }

    /**
     * Gets the parsed details for $skiptoken option.
     * 
     * @return InternalSkipTokenInfo/NULL Returns parsed details of $skiptoken
     *                                    option, NULL if $skiptoken is absent.
     */
    public function getInternalSkipTokenInfo()
    {
        return $this->_internalSkipTokenInfo;
    }

    /**
     * Sets $skiptoken information.
     *
     * @param InternalSkipTokenInfo &$internalSkipTokenInfo The paging information.
     * 
     * @return void
     */
    public function setInternalSkipTokenInfo(
        InternalSkipTokenInfo &$internalSkipTokenInfo
    ) {
        $this->_internalSkipTokenInfo = $internalSkipTokenInfo;
    }

    /**
     * Gets the parsed details for $filter option.
     * 
     * @return InternalFilterInfo/NULL Returns parsed details of $filter
     *                                 option, NULL if $filter is absent.
     */
    public function getInternalFilterInfo()
    {
        return $this->_internalFilterInfo;
    }

    /**
     * Sets $filter information.
     *     
     * @param InternalFilterInfo &$internalFilterInfo The filter information.
     * 
     * @return void
     */
    public function setInternalFilterInfo(InternalFilterInfo &$internalFilterInfo)
    {
        $this->_internalFilterInfo = $internalFilterInfo;
    }

    /**
     * Sets $expand and $select information.
     *     
     * @param RootProjectionNode &$rootProjectionNode Root of the projection tree.
     * 
     * @return void
     */
    public function setRootProjectionNode(RootProjectionNode &$rootProjectionNode)
    {
        $this->_rootProjectionNode =  $rootProjectionNode;
    }

    /**
     * Gets the root of the tree describing expand and select options,
     * 
     * @return RootProjectionNode/NULL Returns parsed details of $expand
     *                                 and $select options, NULL if 
     *                                 $both options are absent.
     */
    public function getRootProjectionNode()
    {
        return $this->_rootProjectionNode;
    }

    /**
     * Gets the count option associated with the request.
     * 
     * @return RequestCountOption
     */
    public function getRequestCountOption()
    {
        return $this->_requestCountOption;
    }

    /**
     * Sets the count option associated with the request.
     * 
     * @param RequestCountOption $countOption The count option.
     * 
     * @return void
     */
    public function setRequestCountOption($countOption)
    {
        $this->_requestCountOption = $countOption;
    }

    /**
     * Gets the count of result set if $count or $inlinecount=allpages
     * has been applied otherwise NULL
     * 
     * @return int/NULL
     */
    public function getCountValue()
    {
        return $this->_countValue;
    }

    /**
     * Sets the count of result set.
     * 
     * @param int $countValue The count value.
     * 
     * @return void
     */
    public function setCountValue($countValue)
    {
        $this->_countValue = $countValue;
    }

    /**
     * To set the flag indicating the execution status as true.
     * 
     * @return void
     */
    public function setExecuted()
    {
        $this->_isExecuted = true;
    }

    /**
     * To check whether to execute the query using IDSQP.
     * 
     * @return boolean True if query need to be executed, False otherwise.
     */
    public function needExecution()
    {
        return !$this->_isExecuted 
            && ($this->_lastSegmentDescriptor->getTargetKind() != RequestTargetKind::METADATA)
            && ($this->_lastSegmentDescriptor->getTargetKind() != RequestTargetKind::SERVICE_DIRECTORY);
    }

    /**
     * To check if the resource path is a request for link uri.
     * 
     * @return boolean True if request is for link uri else false.
     */
    public function isLinkUri()
    {
        return (($this->_segmentCount > 2) && 
            ($this->_segmentDescriptors[$this->_segmentCount - 2]->getTargetKind() == 
             RequestTargetKind::LINK));
    }

    /**
     * To check if the resource path is a request for meida resource
     * 
     * @return boolean True if request is for media resource else false.
     */
    public function isMediaResource()
    {
        return ($this->_lastSegmentDescriptor->getTargetKind() == RequestTargetKind::MEDIA_RESOURCE); 
    }

    /**
     * To check if the resource path is a request for named stream
     * 
     * @return boolean True if request is for named stream else false.
     */
    public function isNamedStream()
    {
        return $this->isMediaResource() && 
            !($this->_lastSegmentDescriptor->getIdentifier() === ODataConstants::URI_VALUE_SEGMENT);
    }

    /**
     * Get ResourceStreamInfo for the media link entry or named stream request.
     * 
     * @return ResourceStreamInfo/NULL Instance of ResourceStreamInfo if the
     *         current request targets named stream, NULL for MLE
     */
    public function getResourceStreamInfo()
    {
        //assert($this->isMediaResource)
        if ($this->isNamedStream()) {
            return $this->getTargetResourceType()
                ->tryResolveNamedStreamByName(
                    $this->_lastSegmentDescriptor->getIdentifier()
                );
        }

        return null;
    }

    /**
     * Gets the resource instance targetted by the request uri.
     * Note: This value will be populated after query execution only.
     * 
     * @return mixed
     */
    public function getTargetResult()
    {
        return $this->_lastSegmentDescriptor->getResult();
    }

    /**
     * Gets the OData version the server used to generate the response.
     * 
     * @return Version
     */
    public function getResponseDataServiceVersion()
    {
        return $this->_responseDataServiceVersion;
    }

    /**
     * Checks whether etag headers are allowed for this request.
     * 
     * @return boolean True if ETag header (If-Match or If-NoneMatch)
     *                 is allowed for the request, False otherwise.
     */
    public function isETagHeaderAllowed()
    {
        return $this->_lastSegmentDescriptor->isSingleResult()
            && ($this->_requestCountOption != RequestCountOption::VALUE_ONLY) 
            && !$this->isLinkUri() 
            && (is_null($this->_rootProjectionNode) 
                || !($this->_rootProjectionNode->isExpansionSpecified())
                );
    }

    /**
     * Gets collection of known data service versions, currently 1.0, 2.0 and 3.0.
     * 
     * @return array(Version)
     */
    public static function getKnownDataServiceVersions()
    {
        if (is_null(self::$_knownDataServiceVersions)) {
            self::$_knownDataServiceVersions = array(new Version(1, 0),
                                                    new Version(2, 0),
                                                    new Version(3, 0));
        }

        return self::$_knownDataServiceVersions;
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
     *  initializing the properties respresenting 'DataServiceVersion' and
     *  'MaxDataServiceVersion'.
     *  
     * @param RequestDescription $requestDescription The request description object
     * @param DataService        $dataService        The Service to check
     * 
     * @return void
     * 
     * @throws ODataException If any of the above 3 check fails.
     */
    public static function checkVersion(RequestDescription $requestDescription, 
        DataService $dataService
    ) {
        if (is_null($requestDescription->_requestDataServiceVersion)) {
            $version = $dataService->getHost()->getRequestVersion();
            //'DataServiceVersion' header not present in the request, so use
            //default value as the maximum version number that the server can 
            //interpret.
            if (is_null($version)) {
                $knownVersions = $requestDescription::getKnownDataServiceVersions();
                $version = $knownVersions[count($knownVersions) - 1];
            } else {
                $version = $requestDescription::_validateAndGetVersion(
                    $version, ODataConstants::ODATAVERSIONHEADER
                );
            }

            $requestDescription->_requestDataServiceVersion = $version;
        }

        if (is_null($requestDescription->_requestMaxDataServiceVersion)) {
            $version = $dataService->getHost()->getRequestMaxVersion();
            //'MaxDataServiceVersion' header not present in the request, so use
            //default value as the maximum version number that the server can 
            //interpret.
            if (is_null($version)) {
                $knownVersions = $requestDescription::getKnownDataServiceVersions();
                $version = $knownVersions[count($knownVersions) - 1];
            } else {
                $version = $requestDescription::_validateAndGetVersion(
                    $version, ODataConstants::ODATAMAXVERSIONHEADER
                );
            }

            $requestDescription->_requestMaxDataServiceVersion = $version;
        }

        if ($requestDescription->_requestDataServiceVersion->compare(
            $requestDescription->_minimumRequiredClientVersion
        ) < 0
        ) {
            ODataException::createBadRequestError(
                Messages::requestDescriptionDataServiceVersionTooLow(
                    $requestDescription->_requestDataServiceVersion->toString(),
                    $requestDescription->_minimumRequiredClientVersion->toString()
                )
            );
        }

        if ($requestDescription->_requestMaxDataServiceVersion->compare(
            $requestDescription->_responseDataServiceVersion
        ) < 0
        ) {
            ODataException::createBadRequestError(
                Messages::requestDescriptionDataServiceVersionTooLow(
                    $requestDescription->_requestMaxDataServiceVersion->toString(),
                    $requestDescription->_responseDataServiceVersion->toString()
                )
            );
        }

        $configuration = $dataService->getServiceConfiguration();
        $maxConfiguredProtocolVersion = $configuration->getMaxDataServiceVersionObject();
        if ($maxConfiguredProtocolVersion->compare(
            $requestDescription->_responseDataServiceVersion
        ) < 0
        ) {
            ODataException::createBadRequestError(
                Messages::requestDescriptionResponseVersionIsBiggerThanProtocolVersion(
                    $requestDescription->_responseDataServiceVersion->toString(),
                    $maxConfiguredProtocolVersion->toString()
                )
            );
        }
    }

    /**
     * Validates the given version in string format and returns the version as instance of Version
     * 
     * @param string $versionHeader The DataServiceVersion or MaxDataServiceVersion header value
     * @param string $headerName    The name of the header
     * 
     * @return Version
     * 
     * @throws ODataException If the version is malformed or not supported
     */
    private static function _validateAndGetVersion($versionHeader, $headerName)
    {
        $libName = null;
        $versionHeader = trim($versionHeader);
        $libNameIndex = strpos($versionHeader, ';');
        if ($libNameIndex !== false) {
            $libName = substr($versionHeader, $libNameIndex);
        } else {
            $libNameIndex = strlen($versionHeader);
        }

        $dotIndex = -1;
        for ($i = 0; $i < $libNameIndex; $i++) {
            if ($versionHeader[$i] == '.') {
                if ($dotIndex != -1) {
                    ODataException::createBadRequestError(
                        Messages::requestDescriptionInvalidVersionHeader(
                            $versionHeader,
                            $headerName
                        )
                    );
                }

                $dotIndex = $i;
            } else if ($versionHeader[$i] < '0' || $versionHeader[$i] > '9') {
                ODataException::createBadRequestError(
                    Messages::requestDescriptionInvalidVersionHeader(
                        $versionHeader,
                        $headerName
                    )
                );
            }
        }

        $major = $minor = 0;
        if ($dotIndex != -1) {
            if ($dotIndex == 0) {
                ODataException::createBadRequestError(
                    Messages::requestDescriptionInvalidVersionHeader(
                        $versionHeader,
                        $headerName
                    )
                );
            }

            $major = intval(substr($versionHeader, 0, $dotIndex));
            $minor = intval(substr($versionHeader, $dotIndex + 1, $libNameIndex));

        } else {
            $major = intval(substr($versionHeader, 0, $dotIndex));
            $minor = 0;
        }

        $version = new Version($major, $minor);
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
            ODataException::createBadRequestError(
                Messages::requestDescriptionUnSupportedVersion(
                    $headerName,
                    $versionHeader, $availableVersions
                )
            );
        }

        return $version;
    }

    /**
     * Gets reference to the UriProcessor instance.
     * 
     * @return UriProcessor
     */
    public function getUriProcessor()
    {
        return $this->_uriProcessor;
    }

    /**
     * Set reference to UriProcessor instance.
     * 
     * @param UriProcessor $uriProcessor Reference to the UriProcessor
     *
     * @return void
     */
    public function setUriProcessor(UriProcessor $uriProcessor)
    {
        $this->_uriProcessor = $uriProcessor;
    }
}
?>