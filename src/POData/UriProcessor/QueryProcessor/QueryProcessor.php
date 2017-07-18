<?php

namespace POData\UriProcessor\QueryProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\Providers\Metadata\ResourceTypeKind;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandProjectionParser;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class QueryProcessor.
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
     *
     * @var bool
     */
    private $setQueryApplicable;

    /**
     * Whether the top level request is a candidate for paging.
     *
     * @var bool
     */
    private $pagingApplicable;

    /**
     * Whether $expand, $select can be applied to the request.
     *
     * @var bool
     */
    private $expandSelectApplicable;

    /**
     * Creates new instance of QueryProcessor.
     *
     * @param RequestDescription $request Description of the request submitted by client
     * @param IService           $service Reference to the service implementation
     */
    private function __construct(RequestDescription $request, IService $service)
    {
        $this->request = $request;
        $this->service = $service;

        $isSingleResult = $request->isSingleResult();

        //$top, $skip, $order, $inlinecount & $count are only applicable if:
        //The query targets a resource collection
        $this->setQueryApplicable = ($request->getTargetKind() == TargetKind::RESOURCE() && !$isSingleResult);
        //Or it's a $count resource (although $inlinecount isn't applicable in this case..
        //but there's a check somewhere else for this
        $this->setQueryApplicable |= $request->queryType == QueryType::COUNT();

        //Paging is allowed if
        //The request targets a resource collection
        //and the request isn't for a $count segment
        $this->pagingApplicable = $this->request->getTargetKind() == TargetKind::RESOURCE()
                                   && !$isSingleResult
                                   && ($request->queryType != QueryType::COUNT());

        $targetResourceType = $this->request->getTargetResourceType();
        $targetResourceSetWrapper = $this->request->getTargetResourceSetWrapper();

        $this->expandSelectApplicable = null !== $targetResourceType
            && null !== $targetResourceSetWrapper
            && $targetResourceType->getResourceTypeKind() == ResourceTypeKind::ENTITY
            && !$this->request->isLinkUri();
    }

    /**
     * Process the OData query options and update RequestDescription accordingly.
     *
     * @param RequestDescription $request Description of the request submitted by client
     * @param IService           $service Reference to the data service
     *
     * @throws ODataException
     */
    public static function process(RequestDescription $request, IService $service)
    {
        $queryProcessor = new self($request, $service);
        if ($request->getTargetSource() == TargetSource::NONE) {
            //A service directory, metadata or batch request
            $queryProcessor->checkForEmptyQueryArguments();
        } else {
            $queryProcessor->processQuery();
        }

        unset($queryProcessor);
    }

    /**
     * Processes the odata query options in the request uri and update the request description
     * instance with processed details.
     *
     * @throws ODataException If any error occurred while processing the query options
     */
    private function processQuery()
    {
        $this->processSkipAndTop();
        $this->processOrderBy();
        $this->processFilter();
        $this->processCount();
        $this->processSkipToken();
        $this->processExpandAndSelect();
    }

    /**
     * Process $skip and $top options.
     *
     *
     * @throws ODataException Throws syntax error if the $skip or $top option
     *                        is specified with non-integer value, throws
     *                        bad request error if the $skip or $top option
     *                        is not applicable for the requested resource
     */
    private function processSkipAndTop()
    {
        $value = null;
        if ($this->readSkipOrTopOption(ODataConstants::HTTPQUERY_STRING_SKIP, $value)) {
            $this->request->setSkipCount($value);
        }

        $pageSize = 0;
        $isPagingRequired = $this->isSSPagingRequired();
        if ($isPagingRequired) {
            $pageSize = $this->request
                ->getTargetResourceSetWrapper()
                ->getResourceSetPageSize();
        }

        if ($this->readSkipOrTopOption(ODataConstants::HTTPQUERY_STRING_TOP, $value)) {
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
        } elseif ($isPagingRequired) {
            $this->request->raiseResponseVersion(2, 0);
            $this->request->setTopCount($pageSize);
        }

        if (null !== $this->request->getSkipCount()
            || null !== $this->request->getTopCount()
        ) {
            $this->checkSetQueryApplicable();
        }
    }

    /**
     * Process $orderby option, This function requires _processSkipAndTopOption
     * function to be already called as this function need to know whether
     * client has requested for skip, top or paging is enabled for the
     * requested resource in these cases function generates additional orderby
     * expression using keys.
     *
     *
     * @throws ODataException If any error occurs while parsing orderby option
     */
    private function processOrderBy()
    {
        $orderBy = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_ORDERBY);

        if (null !== $orderBy) {
            $this->checkSetQueryApplicable();
        }

        $targetResourceType = $this->request->getTargetResourceType();
        assert($targetResourceType != null, 'Request target resource type must not be null');
        /*
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
        if (null !== $this->request->getSkipCount() || null !== $this->request->getTopCount()) {
            $orderBy = null !== $orderBy ? $orderBy . ', ' : null;
            $keys = array_keys($targetResourceType->getKeyProperties());
            //assert(!empty($keys))
            foreach ($keys as $key) {
                $orderBy = $orderBy . $key . ', ';
            }

            $orderBy = rtrim($orderBy, ', ');
        }

        if (null !== $orderBy && '' != trim($orderBy)) {
            $setWrapper = $this->request->getTargetResourceSetWrapper();
            assert(null != $setWrapper, 'Target resource set wrapper must not be null');
            $internalOrderByInfo = OrderByParser::parseOrderByClause(
                $setWrapper,
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
     * Process the $filter option in the request and update request description.
     *
     *
     * @throws ODataException Throws error in the following cases:
     *                        (1) If $filter cannot be applied to the
     *                        resource targeted by the request uri
     *                        (2) If any error occurred while parsing and
     *                        translating the odata $filter expression
     *                        to expression tree
     *                        (3) If any error occurred while generating
     *                        php expression from expression tree
     */
    private function processFilter()
    {
        $filter = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FILTER);
        if (null === $filter) {
            return;
        }

        $kind = $this->request->getTargetKind();
        if (!($kind == TargetKind::RESOURCE()
            || $kind == TargetKind::COMPLEX_OBJECT()
            || $this->request->queryType == QueryType::COUNT())
        ) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorQueryFilterOptionNotApplicable()
            );
        }
        $resourceType = $this->request->getTargetResourceType();
        $expressionProvider = $this->service->getProvidersWrapper()->getExpressionProvider();
        $filterInfo = ExpressionParser2::parseExpression2($filter, $resourceType, $expressionProvider);
        $this->request->setFilterInfo($filterInfo);
    }

    /**
     * Process the $inlinecount option and update the request description.
     *
     *
     * @throws ODataException Throws bad request error in the following cases
     *                        (1) If $inlinecount is disabled by the developer
     *                        (2) If both $count and $inlinecount specified
     *                        (3) If $inlinecount value is unknown
     *                        (4) If capability negotiation over version fails
     */
    private function processCount()
    {
        $inlineCount = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT);

        //If it's not specified, we're done
        if (null === $inlineCount) {
            return;
        }

        //If the service doesn't allow count requests..then throw an exception
        if (!$this->service->getConfiguration()->getAcceptCountRequests()) {
            throw ODataException::createBadRequestError(
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
        if ($this->request->queryType == QueryType::COUNT()) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorInlineCountWithValueCount()
            );
        }

        $this->checkSetQueryApplicable(); //TODO: why do we do this check?

        if ($inlineCount === ODataConstants::URI_ROWCOUNT_ALLOPTION) {
            $this->request->queryType = QueryType::ENTITIES_WITH_COUNT();

            $this->request->raiseMinVersionRequirement(2, 0);
            $this->request->raiseResponseVersion(2, 0);
        } else {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorInvalidInlineCountOptionError()
            );
        }
    }

    /**
     * Process the $skiptoken option in the request and update the request
     * description, this function requires _processOrderBy method to be
     * already invoked.
     *
     *
     * @throws ODataException Throws bad request error in the following cases
     *                        (1) If $skiptoken cannot be applied to the
     *                        resource targeted by the request uri
     *                        (2) If paging is not enabled for the resource
     *                        targeted by the request uri
     *                        (3) If parsing of $skiptoken fails
     *                        (4) If capability negotiation over version fails
     */
    private function processSkipToken()
    {
        $skipToken = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SKIPTOKEN);
        if (null === $skipToken) {
            return;
        }

        if (!$this->pagingApplicable) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorSkipTokenNotAllowed()
            );
        }

        if (!$this->isSSPagingRequired()) {
            $set = $this->request->getTargetResourceSetWrapper();
            $setName = (null != $set) ? $set->getName() : 'null';
            $msg = Messages::queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet($setName);
            throw ODataException::createBadRequestError($msg);
        }

        $internalOrderByInfo = $this->request->getInternalOrderByInfo();
        assert($internalOrderByInfo != null, 'Internal order info must not be null');
        $targetResourceType = $this->request->getTargetResourceType();
        assert($targetResourceType != null, 'Request target resource type must not be null');

        $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause(
            $targetResourceType,
            $internalOrderByInfo,
            $skipToken
        );
        $this->request->setInternalSkipTokenInfo($internalSkipTokenInfo);
        $this->request->raiseMinVersionRequirement(2, 0);
        $this->request->raiseResponseVersion(2, 0);
    }

    /**
     * Process the $expand and $select option and update the request description.
     *
     *
     * @throws ODataException Throws bad request error in the following cases
     *                        (1) If $expand or select cannot be applied to the
     *                        requested resource.
     *                        (2) If projection is disabled by the developer
     *                        (3) If some error occurs while parsing the options
     */
    private function processExpandAndSelect()
    {
        $expand = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_EXPAND);

        if (null !== $expand) {
            $this->checkExpandOrSelectApplicable(ODataConstants::HTTPQUERY_STRING_EXPAND);
        }

        $select = $this->service->getHost()->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SELECT);

        if (null !== $select) {
            if (!$this->service->getConfiguration()->getAcceptProjectionRequests()) {
                throw ODataException::createBadRequestError(Messages::configurationProjectionsNotAccepted());
            }

            $this->checkExpandOrSelectApplicable(ODataConstants::HTTPQUERY_STRING_SELECT);
        }

        // We will generate RootProjectionNode in case of $link request also, but
        // expand and select in this case must be null (we are ensuring this above)
        // 'RootProjectionNode' is required while generating next page Link
        if ($this->expandSelectApplicable || $this->request->isLinkUri()) {
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
                $this->request->raiseMinVersionRequirement(2, 0);
            }

            if ($rootProjectionNode->hasPagedExpandedResult()) {
                $this->request->raiseResponseVersion(2, 0);
            }
            $this->request->setRootProjectionNode($rootProjectionNode);
        }
    }

    /**
     * Is server side paging is configured, this function return true
     * if the resource targeted by the resource path is applicable
     * for paging and paging is enabled for the targeted resource set
     * else false.
     *
     * @return bool
     */
    private function isSSPagingRequired()
    {
        if ($this->pagingApplicable) {
            $targetResourceSetWrapper = $this->request->getTargetResourceSetWrapper();
            //assert($targetResourceSetWrapper != NULL)
            return 0 != $targetResourceSetWrapper->getResourceSetPageSize();
        }

        return false;
    }

    /**
     * Read skip or top query option value which is expected to be positive
     * integer.
     *
     * @param string $queryItem The name of the query item to read from request
     *                          uri ($skip or $top)
     * @param int    &$value    On return, If the requested query item is
     *                          present with a valid integer value then this
     *                          argument will holds that integer value
     *                          otherwise holds zero
     *
     * @throws ODataException Throws syntax error if the requested argument
     *                        is present and it is not an integer
     *
     * @return bool True     If the requested query item with valid integer
     *              value is present in the request, false query
     *              item is absent in the request uri
     */
    private function readSkipOrTopOption($queryItem, &$value)
    {
        $value = $this->service->getHost()->getQueryStringItem($queryItem);
        if (null !== $value) {
            $int = new Int32();
            if (!$int->validate($value, $outValue)) {
                throw ODataException::createSyntaxError(
                    Messages::queryProcessorIncorrectArgumentFormat(
                        $queryItem,
                        $value
                    )
                );
            }

            $value = intval($value);
            if (0 > $value) {
                throw ODataException::createSyntaxError(
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
     *
     * @throws ODataException Throws bad request error if client request
     *                        includes any odata query option
     */
    private function checkForEmptyQueryArguments()
    {
        $serviceHost = $this->service->getHost();
        $items = [
            ODataConstants::HTTPQUERY_STRING_FILTER,
            ODataConstants::HTTPQUERY_STRING_EXPAND,
            ODataConstants::HTTPQUERY_STRING_INLINECOUNT,
            ODataConstants::HTTPQUERY_STRING_ORDERBY,
            ODataConstants::HTTPQUERY_STRING_SELECT,
            ODataConstants::HTTPQUERY_STRING_SKIP,
            ODataConstants::HTTPQUERY_STRING_SKIPTOKEN,
            ODataConstants::HTTPQUERY_STRING_TOP
        ];

        $allNull = true;
        foreach ($items as $queryItem) {
            $item = $serviceHost->getQueryStringItem($queryItem);
            $currentNull = null === $item;
            $allNull = ($currentNull && $allNull);
            if (false === $allNull) {
                break;
            }
        }

        if (false === $allNull) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorNoQueryOptionsApplicable()
            );
        }
    }

    /**
     * To check whether the the query options $orderby, $inlinecount, $skip
     * or $top is applicable for the current requested resource.
     *
     *
     * @throws ODataException Throws bad request error if any of the query options $orderby, $inlinecount,
     *                        $skip or $top cannot be applied to the requested resource
     */
    private function checkSetQueryApplicable()
    {
        if (!$this->setQueryApplicable) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorQuerySetOptionsNotApplicable()
            );
        }
    }

    /**
     * To check whether the the query options $select, $expand
     * is applicable for the current requested resource.
     *
     * @param string $queryItem The query option to check
     *
     * @throws ODataException Throws bad request error if the query
     *                        options $select, $expand cannot be
     *                        applied to the requested resource
     */
    private function checkExpandOrSelectApplicable($queryItem)
    {
        if (!$this->expandSelectApplicable) {
            throw ODataException::createBadRequestError(
                Messages::queryProcessorSelectOrExpandOptionNotApplicable($queryItem)
            );
        }
    }
}
