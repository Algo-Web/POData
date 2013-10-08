<?php

namespace POData\UriProcessor\QueryProcessor;

use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandProjectionParser;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Common\ODataConstants;
use POData\IService;
use POData\Providers\Query\QueryType;

/**
 * Class QueryProcessor
 * @package POData\UriProcessor\QueryProcessor
 */
class QueryProcessor
{
    /**
     * Holds details of the request that client has submitted.
     * 
     * @var RequestDescription
     */
    private $request;

    /**
     * Holds reference to the underlying data service specific
     * instance.  
     * 
     * @var IService
     */
    private $service;

    /**
     * If $orderby, $skip, $top and $count options can be applied to the request.
     * @var boolean
     */
    private $_setQueryApplicable;

    /**
     * Whether the top level request is a candidate for paging
     * 
     * @var boolean
     */
    private $_pagingApplicable;

    /**
     * Whether $expand, $select can be applied to the request.
     * 
     * @var boolean
     */
    private $_expandSelectApplicable;

    /**
     * Creates new instance of QueryProcessor
     * 
     * @param RequestDescription $request Description of the request submitted by client.
     * @param IService        $service        Reference to the service implementation.
     */
    private function __construct(RequestDescription $request, IService $service ) {
        $this->request = $request;
        $this->service = $service;

        $isSingleResult = $request->isSingleResult();

	    //$top, $skip, $order, $inlinecount & $count are only applicable if:
	    //The query targets a resource collection
        $this->_setQueryApplicable = ($request->getTargetKind() == TargetKind::RESOURCE && !$isSingleResult);
	    //Or it's a $count resource (although $inlinecount isn't applicable in this case..but there's a check somewhere else for this
	    $this->_setQueryApplicable |= $request->queryType == QueryType::COUNT();

	    //Paging is allowed if
	    //The request targets a resource collection
	    //and the request isn't for a $count segment
	    $this->_pagingApplicable = $this->request->getTargetKind() == TargetKind::RESOURCE && !$isSingleResult && ($request->queryType != QueryType::COUNT());

	    $targetResourceType = $this->request->getTargetResourceType();
        $targetResourceSetWrapper = $this->request->getTargetResourceSetWrapper();

	    $this->_expandSelectApplicable = !is_null($targetResourceType)
            && !is_null($targetResourceSetWrapper)
            && $targetResourceType->getResourceTypeKind() == ResourceTypeKind::ENTITY
            && !$this->request->isLinkUri();
        
    }

    /**
     * Process the OData query options and update RequestDescription accordingly.
     *
     * @param RequestDescription $request Description of the request submitted by client.
     * @param IService        $service        Reference to the data service.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    public static function process(RequestDescription $request, IService $service ) {
        $queryProcessor = new QueryProcessor($request, $service);
        if ($request->getTargetSource() == TargetSource::NONE) {
            //A service directory, metadata or batch request
            $queryProcessor->_checkForEmptyQueryArguments();
        } else {
            $queryProcessor->_processQuery();
        }

        unset($queryProcessor);
    }


	private function processFormat(){
		$version = $this->service->getHost()->getRequestVersion();

		$format = $this->service->getHost()->getOperationContext()->incomingRequest()->getQueryParameters();

		reset($queryOptions);
		// Check whether user specified $format query option
		while ($queryOption = current($queryOptions)) {
			$optionName = key($queryOption);
			$optionValue = current($queryOption);
			if (!empty($optionName) && $optionName === ODataConstants::HTTPQUERY_STRING_FORMAT) {
				//$optionValue is the format
				switch($optionValue) {
					case ODataConstants::FORMAT_ATOM:
						$this->request->responseFormat = ODataConstants::MIME_APPLICATION_ATOM . ';q=1.0';
						break;

					case ODataConstants::FORMAT_JSON:
						$this->setRequestAccept(
							ODataConstants::MIME_APPLICATION_JSON . ';q=1.0'
						);
						break;

					default:
						// Invalid format value, this error should not be
						// serialized in atom or json format since we don't
						// know which format client can understand, so error
						// will be in plain text.
						header(
							ODataConstants::HTTPRESPONSE_HEADER_CONTENTTYPE .
							':' .
							ODataConstants::MIME_TEXTPLAIN
						);

						header(
							ODataConstants::HTTPRESPONSE_HEADER_STATUS .
							':' . HttpStatus::CODE_BAD_REQUEST . ' ' . 'Bad Request'
						);

						echo Messages::queryProcessorInvalidValueForFormat();
						exit;

				}

				break;
			}

			next($queryOptions);
		}
	}


    /**
     * Processes the odata query options in the request uri and update the request description instance with processed details.
     * @return void
     * 
     * @throws ODataException If any error occured while processing the query options.
     *
     */
    private function _processQuery()
    {
        $this->_processSkipAndTop();
        $this->_processOrderBy();
        $this->_processFilter();
        $this->_processCount();
        $this->_processSkipToken();
        $this->_processExpandAndSelect();
    }

    /**
     * Process $skip and $top options
     * 
     * @return void
     * 
     * @throws ODataException Throws syntax error if the $skip or $top option
     *                        is specified with non-integer value, throws
     *                        bad request error if the $skip or $top option
     *                        is not applicable for the requested resource. 
     */
    private function _processSkipAndTop()
    {
        $value = null;
        if ($this->_readSkipOrTopOption( ODataConstants::HTTPQUERY_STRING_SKIP, $value ) ) {
            $this->request->setSkipCount($value);
        }

        $pageSize = 0;
        $isPagingRequired = $this->_isSSPagingRequired();
        if ($isPagingRequired) {
            $pageSize = $this->request
                ->getTargetResourceSetWrapper()
                ->getResourceSetPageSize(); 
        }

        if ($this->_readSkipOrTopOption(ODataConstants::HTTPQUERY_STRING_TOP, $value) ) {
            $this->request->setTopOptionCount($value);
            if ($isPagingRequired && $pageSize < $value) {
                //If $top is greater than or equal to page size, 
                //we will need a $skiptoken and thus our response 
                //will be 2.0
                $this->request->raiseResponseVersion(2, 0);
                $this->request->setTopCount($pageSize);
            } else {
                $this->request->setTopCount($value);
            }
        } else if ($isPagingRequired) {
            $this->request->raiseResponseVersion(2, 0);
            $this->request->setTopCount($pageSize);
        }

        if (!is_null($this->request->getSkipCount())
            || !is_null($this->request->getTopCount())
        ) {
            $this->_checkSetQueryApplicable();
        }
    }

    /**
     * Process $orderby option, This function requires _processSkipAndTopOption
     * function to be already called as this function need to know whether 
     * client has requested for skip, top or paging is enabled for the 
     * requested resource in these cases function generates additional orderby
     * expression using keys.
     * 
     * @return void
     * 
     * @throws ODataException If any error occurs while parsing orderby option.
     */
    private function _processOrderBy()
    {
        $orderBy = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_ORDERBY );

        if (!is_null($orderBy)) {
            $this->_checkSetQueryApplicable();
        }

        $targetResourceType = $this->request->getTargetResourceType();
        //assert($targetResourceType != null)
        /**
         * We need to do sorting in the folowing cases, irrespective of 
         * $orderby clause is present or not.
         * 1. If $top or $skip is specified
         *     skip and take will be applied on sorted list only. If $skip 
         *     is specified then RequestDescription::getSkipCount will give 
         *     non-null value. If $top is specified then 
         *     RequestDescription::getTopCount will give non-null value.
         * 2. If server side paging is enabled for the requested resource
         *     If server-side paging is enabled for the requested resource then 
         *     RequestDescription::getTopCount will give non-null value.
         *      
         */
        if (!is_null($this->request->getSkipCount())|| !is_null($this->request->getTopCount())) {
            $orderBy = !is_null($orderBy) ? $orderBy . ', ' : null;
            $keys = array_keys($targetResourceType->getKeyProperties());
            //assert(!empty($keys))
            foreach ($keys as $key) {
                $orderBy = $orderBy . $key . ', ';
            }

            $orderBy = rtrim($orderBy, ', ');
        }

        if (!is_null($orderBy)) {

            $internalOrderByInfo = OrderByParser::parseOrderByClause(
                $this->request->getTargetResourceSetWrapper(),
                $targetResourceType,
                $orderBy,
                $this->service->getProvidersWrapper()
            );

            $this->request->setInternalOrderByInfo(
                $internalOrderByInfo
            );

        }
    }

    /**
     * Process the $filter option in the request and update request decription.
     * 
     * @return void
     * 
     * @throws ODataException Throws error in the following cases:
     *                          (1) If $filter cannot be applied to the 
     *                              resource targeted by the request uri
     *                          (2) If any error occured while parsing and
     *                              translating the odata $filter expression
     *                              to expression tree
     *                          (3) If any error occured while generating
     *                              php expression from expression tree
     */ 
    private function _processFilter()
    {
        $filter = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_FILTER );
        if (is_null($filter)) {
            return;
        }

        $kind = $this->request->getTargetKind();
        if (!($kind == TargetKind::RESOURCE
            || $kind == TargetKind::COMPLEX_OBJECT
            || $this->request->queryType == QueryType::COUNT() )
        ) {
            ODataException::createBadRequestError(
                Messages::queryProcessorQueryFilterOptionNotApplicable()
            );
        }
        $resourceType = $this->request->getTargetResourceType();
        $expressionProvider = $this->service->getProvidersWrapper()->getExpressionProvider();
        $filterInfo = ExpressionParser2::parseExpression2($filter, $resourceType, $expressionProvider);
        $this->request->setFilterInfo( $filterInfo );

    }

    /**
     * Process the $inlinecount option and update the request description.
     *
     * @return void
     * 
     * @throws ODataException Throws bad request error in the following cases
     *                          (1) If $inlinecount is disabled by the developer
     *                          (2) If both $count and $inlinecount specified
     *                          (3) If $inlinecount value is unknown
     *                          (4) If capability negotiation over version fails
     */
    private function _processCount()
    {
        $inlineCount = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_INLINECOUNT );

	    //If it's not specified, we're done
	    if(is_null($inlineCount)) return;

	    //If the service doesn't allow count requests..then throw an exception
        if (!$this->service->getConfiguration()->getAcceptCountRequests()) {
            ODataException::createBadRequestError(
                Messages::configurationCountNotAccepted()
            );
        }

        $inlineCount = trim($inlineCount);

	    //if it's set to none, we don't do inline counts
        if ($inlineCount === ODataConstants::URI_ROWCOUNT_OFFOPTION) {
            return;
        }

	    //You can't specify $count & $inlinecount together
	    //TODO: ensure there's a test for this case see #55
        if ($this->request->queryType == QueryType::COUNT() ) {
            ODataException::createBadRequestError(
                Messages::queryProcessorInlineCountWithValueCount()
            );
        }

        $this->_checkSetQueryApplicable(); //TODO: why do we do this check?


        if ($inlineCount === ODataConstants::URI_ROWCOUNT_ALLOPTION) {
	        $this->request->queryType = QueryType::ENTITIES_WITH_COUNT();

            $this->request->raiseMinVersionRequirement( 2, 0 );
            $this->request->raiseResponseVersion( 2, 0 );

        } else {
            ODataException::createBadRequestError(
                Messages::queryProcessorInvalidInlineCountOptionError()
            );
        }

    }

    /**
     * Process the $skiptoken option in the request and update the request 
     * description, this function requires _processOrderBy method to be
     * already invoked.
     * 
     * @return void
     * 
     * @throws ODataException Throws bad request error in the following cases
     *                          (1) If $skiptoken cannot be applied to the 
     *                              resource targeted by the request uri
     *                          (2) If paging is not enabled for the resource
     *                              targeted by the request uri
     *                          (3) If parsing of $skiptoken fails
     *                          (4) If capability negotiation over version fails
     */
    private function _processSkipToken()
    {
        $skipToken = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_SKIPTOKEN );
        if (is_null($skipToken)) {
            return;
        }

        if (!$this->_pagingApplicable) {
            ODataException::createBadRequestError(
                Messages::queryProcessorSkipTokenNotAllowed()
            );
        }

        if (!$this->_isSSPagingRequired()) {
            ODataException::createBadRequestError(
                Messages::queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet($this->request->getTargetResourceSetWrapper())
            );
        }

        $internalOrderByInfo = $this->request->getInternalOrderByInfo();
        //assert($internalOrderByInfo != null)
        $targetResourceType = $this->request->getTargetResourceType();
        //assert($targetResourceType != null)

        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause(
            $targetResourceType,
            $internalOrderByInfo,
            $skipToken
        );
        $this->request->setInternalSkipTokenInfo($internalSkipTokenInfo);
        $this->request->raiseMinVersionRequirement( 2, 0 );
        $this->request->raiseResponseVersion( 2, 0 );


    }

    /**
     * Process the $expand and $select option and update the request description.
     * 
     * @return void
     * 
     * @throws ODataException Throws bad request error in the following cases
     *                          (1) If $expand or select cannot be applied to the
     *                              requested resource.
     *                          (2) If projection is disabled by the developer
     *                          (3) If some error occurs while parsing the options
     */
    private function _processExpandAndSelect()
    {
        $expand = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_EXPAND );

        if (!is_null($expand)) {
            $this->_checkExpandOrSelectApplicable(ODataConstants::HTTPQUERY_STRING_EXPAND );
        }

        $select = $this->service->getHost()->getQueryStringItem( ODataConstants::HTTPQUERY_STRING_SELECT );

        if (!is_null($select)) {
            if (!$this->service->getConfiguration()->getAcceptProjectionRequests()) {
                ODataException::createBadRequestError( Messages::configurationProjectionsNotAccepted() );
            }

            $this->_checkExpandOrSelectApplicable( ODataConstants::HTTPQUERY_STRING_SELECT );
        }

        // We will generate RootProjectionNode in case of $link request also, but
        // expand and select in this case must be null (we are ensuring this above)
        // 'RootProjectionNode' is required while generating next page Link
        if ($this->_expandSelectApplicable || $this->request->isLinkUri() ) {

			$rootProjectionNode = ExpandProjectionParser::parseExpandAndSelectClause(
				 $this->request->getTargetResourceSetWrapper(),
				 $this->request->getTargetResourceType(),
				 $this->request->getInternalOrderByInfo(),
				 $this->request->getSkipCount(),
				 $this->request->getTopCount(),
				 $expand,
				 $select,
				 $this->service->getProvidersWrapper()
			);
			if ($rootProjectionNode->isSelectionSpecified()) {
			    $this->request->raiseMinVersionRequirement(2, 0 );
			}

            if ($rootProjectionNode->hasPagedExpandedResult()) {
                $this->request->raiseResponseVersion( 2, 0 );
            }
            $this->request->setRootProjectionNode($rootProjectionNode );

        }
    } 

    /**
     * Is server side paging is configured, this function return true
     * if the resource targeted by the resource path is applicable
     * for paging and paging is enabled for the targeted resource set
     * else false.
     * 
     * @return boolean
     */
    private function _isSSPagingRequired()
    {
        if ($this->_pagingApplicable) {
            $targetResourceSetWrapper = $this->request->getTargetResourceSetWrapper();
            //assert($targetResourceSetWrapper != NULL)
            return ($targetResourceSetWrapper->getResourceSetPageSize() != 0);
        }

        return false;
    }

    /**
     * Read skip or top query option value which is expected to be positive 
     * integer. 
     * 
     * @param string $queryItem The name of the query item to read from request
     *                          uri ($skip or $top).
     * @param int    &$value    On return, If the requested query item is 
     *                          present with a valid integer value then this
     *                          argument will holds that integer value 
     *                          otherwise holds zero.
     * 
     * @return boolean True     If the requested query item with valid integer 
     *                          value is present in the request, false query 
     *                          item is absent in the request uri. 
     * 
     * @throws ODataException   Throws syntax error if the requested argument 
     *                          is present and it is not an integer.
     */
    private function _readSkipOrTopOption($queryItem, &$value)
    {
        $value = $this->service->getHost()->getQueryStringItem($queryItem);
        if (!is_null($value)) {
            $int = new Int32();
            if (!$int->validate($value, $outValue)) {
                ODataException::createSyntaxError(
                    Messages::queryProcessorIncorrectArgumentFormat(
                        $queryItem, 
                        $value
                    )
                );
            }

            $value = intval($value);
            if ($value < 0) {
                ODataException::createSyntaxError(
                    Messages::queryProcessorIncorrectArgumentFormat(
                        $queryItem, 
                        $value
                    )
                );
            }

            return true;
        }

        $value = 0;
        return false;
    }
 
    /**
     * Checks whether client request contains any odata query options.
     * 
     * @return void
     * 
     * @throws ODataException Throws bad request error if client request 
     *                        includes any odata query option.
     */
    private function _checkForEmptyQueryArguments()
    {
        $serviceHost = $this->service->getHost();
        if (!is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FILTER))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_EXPAND))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_ORDERBY))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SELECT))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SKIP))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SKIPTOKEN))
            || !is_null($serviceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_TOP))
        ) {
            ODataException::createBadRequestError(
                Messages::queryProcessorNoQueryOptionsApplicable()
            );
        }
    }

    /**
     * To check whether the the query options $orderby, $inlinecount, $skip
     * or $top is applicable for the current requested resource.
     * 
     * @return void
     * 
     * @throws ODataException Throws bad request error if any of the query options $orderby, $inlinecount, $skip or $top cannot be applied to the requested resource.
     *
     */
    private function _checkSetQueryApplicable()
    {
        if (!$this->_setQueryApplicable) { 
            ODataException::createBadRequestError(
                Messages::queryProcessorQuerySetOptionsNotApplicable()
            );
        }
    }

    /**
     * To check whether the the query options $select, $expand
     * is applicable for the current requested resource.
     * 
     * @param string $queryItem The query option to check.
     * 
     * @return void
     * 
     * @throws ODataException Throws bad request error if the query 
     *                        options $select, $expand cannot be 
     *                        applied to the requested resource. 
     */
    private function _checkExpandOrSelectApplicable($queryItem)
    {
        if (!$this->_expandSelectApplicable) {
            ODataException::createBadRequestError(
                Messages::queryProcessorSelectOrExpandOptionNotApplicable($queryItem)
            );
        }
    }
}