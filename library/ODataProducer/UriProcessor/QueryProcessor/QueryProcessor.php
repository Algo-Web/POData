<?php
/** 
 * Processor to process the query options of the request uri.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor
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
namespace ODataProducer\UriProcessor\QueryProcessor;
use ODataProducer\Providers\Metadata\Type\Int32;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\UriProcessor\RequestCountOption;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetSource;
use ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenParser;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByParser;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\InternalFilterInfo;
use ODataProducer\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandProjectionParser;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\ODataConstants;
use ODataProducer\DataService;
/**
 * OData query options processor.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class QueryProcessor
{
    /**
     * Holds details of the request that client has submitted.
     * 
     * @var RequestDescription
     */
    private $_requestDescription;

    /**
     * Holds reference to the underlying data service specific
     * instance.  
     * 
     * @var DataService
     */
    private $_dataService;

    /**
     * Whether the $orderby, $skip, $take and $count options can be 
     * applied to the request.
     * 
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
     * @param RequestDescription &$requestDescription Description of the request 
     *                                                submitted by client.
     * @param DataService        &$dataService        Reference to the data service.
     */
    private function __construct(RequestDescription &$requestDescription, 
        DataService &$dataService
    ) {
        $this->_requestDescription = $requestDescription;
        $this->_dataService = $dataService;
        $requestTargetKind = $requestDescription->getTargetKind();
        $isSingleResult = $requestDescription->isSingleResult();
        $requestCountOption = $requestDescription->getRequestCountOption();
        $this->_setQueryApplicable 
            = ($requestTargetKind == RequestTargetKind::RESOURCE && !$isSingleResult)
                || $requestCountOption == RequestCountOption::VALUE_ONLY;
        $this->_pagingApplicable 
            = $this->_requestDescription->getTargetKind() == RequestTargetKind::RESOURCE
                && !$this->_requestDescription->isSingleResult() 
                && ($requestCountOption != RequestCountOption::VALUE_ONLY);
        $targetResourceType = $this->_requestDescription->getTargetResourceType();
        $targetResourceSetWrapper 
            = $this->_requestDescription->getTargetResourceSetWrapper();
        $this->_expandSelectApplicable = !is_null($targetResourceType) 
            && !is_null($targetResourceSetWrapper)
            && $targetResourceType->getResourceTypeKind() == ResourceTypeKind::ENTITY
            && !$this->_requestDescription->isLinkUri();
        
    }

    /**
     * Process the odata query options and update RequestDescription
     * accordingly. 
     * 
     * @param RequestDescription &$requestDescription Description of the request 
     *                                                submitted by client.
     * @param DataService        &$dataService        Reference to the data service.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    public static function process(RequestDescription &$requestDescription, 
        DataService &$dataService
    ) {
        $queryProcessor = new QueryProcessor($requestDescription, $dataService);
        if ($requestDescription->getTargetSource() == RequestTargetSource::NONE) {
            //A service directory, metadata or batch request
            $queryProcessor->_checkForEmptyQueryArguments();
        } else {
            $queryProcessor->_processQuery();
        }

        unset($queryProcessor);
    }

    /**
     * Processes the odata query options in the request uri and update
     * the request description instance with processed details.
     * 
     * @return void
     * 
     * @throws ODataException If any error occured while processing the 
     *                        query options.
     */
    private function _processQuery()
    {
        try {
            $this->_processSkipAndTop();
            $this->_processOrderBy();
            $this->_processFilter();
            $this->_processCount();
            $this->_processSkipToken();
            $this->_processExpandAndSelect();
        } catch (ODataException $odataException) {
            throw $odataException;
        }
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
        if ($this->_readSkipOrTopOption(
            ODataConstants::HTTPQUERY_STRING_SKIP,
            $value
        )
        ) {
            $this->_requestDescription->setSkipCount($value);
        }

        $pageSize = 0;
        $isPagingRequired = $this->_isSSPagingRequired();
        if ($isPagingRequired) {
            $pageSize = $this->_requestDescription
                ->getTargetResourceSetWrapper()
                ->getResourceSetPageSize(); 
        }

        if ($this->_readSkipOrTopOption(
            ODataConstants::HTTPQUERY_STRING_TOP, 
            $value
        )
        ) {
            $this->_requestDescription->setTopOptionCount($value);
            if ($isPagingRequired && $pageSize < $value) {
                //If $top is greater than or equal to page size, 
                //we will need a $skiptoken and thus our response 
                //will be 2.0
                $this->_requestDescription
                    ->raiseResponseVersion(2, 0, $this->_dataService);
                $this->_requestDescription->setTopCount($pageSize);
            } else {
                $this->_requestDescription->setTopCount($value);
            }
        } else if ($isPagingRequired) {
            $this->_requestDescription
                ->raiseResponseVersion(2, 0, $this->_dataService);
            $this->_requestDescription->setTopCount($pageSize);
        }

        if (!is_null($this->_requestDescription->getSkipCount()) 
            || !is_null($this->_requestDescription->getTopCount())
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
        $orderBy = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_ORDERBY
        );

        if (!is_null($orderBy)) {
            $this->_checkSetQueryApplicable();
        }

        $targetResourceType = $this->_requestDescription->getTargetResourceType();
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
        if (!is_null($this->_requestDescription->getSkipCount())
            || !is_null($this->_requestDescription->getTopCount())
        ) {
            $orderBy = !is_null($orderBy) ? $orderBy . ', ' : null;
            $keys = array_keys($targetResourceType->getKeyProperties());
            //assert(!empty($keys))
            foreach ($keys as $key) {
                $orderBy = $orderBy . $key . ', ';
            }

            $orderBy = rtrim($orderBy, ', ');
        }

        if (!is_null($orderBy)) {
            try {
                $internalOrderByInfo = OrderByParser::parseOrderByClause(
                    $this->_requestDescription->getTargetResourceSetWrapper(), 
                    $targetResourceType, 
                    $orderBy, 
                    $this->_dataService->getMetadataQueryProviderWrapper()
                );

                $this->_requestDescription->setInternalOrderByInfo(
                    $internalOrderByInfo
                );
            } catch (ODataException $odataException) {
                throw $odataException;
            }
        }
    }

    /**
     * Process the $filter option in the request and update request decription.
     * 
     * @return void
     * 
     * @throws ODataException Throws error in the following cases:
     *                          (1) If $filter cannot be applied to the 
     *                              resource targetted by the request uri
     *                          (2) If any error occured while parsing and
     *                              translating the odata $filter expression
     *                              to expression tree
     *                          (3) If any error occured while generating
     *                              php expression from expression tree
     */ 
    private function _processFilter()
    {
        $filter = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_FILTER
        );
        if (!is_null($filter)) {
            $requestTargetKind = $this->_requestDescription->getTargetKind();
            if (!($requestTargetKind == RequestTargetKind::RESOURCE 
                || $requestTargetKind == RequestTargetKind::COMPLEX_OBJECT 
                || $this->_requestDescription->getRequestCountOption() == RequestCountOption::VALUE_ONLY)
            ) {
                ODataException::createBadRequestError(
                    Messages::queryProcessorQueryFilterOptionNotApplicable()
                );
            }
            $resourceType = $this->_requestDescription->getTargetResourceType();
            try {
            	$expressionProvider = $this->_dataService->getMetadataQueryProviderWrapper()->getExpressionProvider();      
                $internalFilterInfo = ExpressionParser2::parseExpression2(
                    $filter, $resourceType, $expressionProvider
                );
                $this->_requestDescription->setInternalFilterInfo(
                    $internalFilterInfo
                );                
            } catch (ODataException $odataException) {
                throw $odataException;
            }
        }
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
        $inlineCount = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_INLINECOUNT
        );

        if (!is_null($inlineCount)) {
            if (!$this->_dataService->getServiceConfiguration()->getAcceptCountRequests()) {
                ODataException::createBadRequestError(
                    Messages::dataServiceConfigurationCountNotAccepted()
                );
            }

            $inlineCount = trim($inlineCount);
            if ($inlineCount === ODataConstants::URI_ROWCOUNT_OFFOPTION) {
                return;
            }

            if ($this->_requestDescription->getRequestCountOption() == RequestCountOption::VALUE_ONLY
            ) {
                ODataException::createBadRequestError(
                    Messages::queryProcessorInlineCountWithValueCount()
                );
            }

            $this->_checkSetQueryApplicable();
            if ($inlineCount === ODataConstants::URI_ROWCOUNT_ALLOPTION) {
                $this->_requestDescription->setRequestCountOption(
                    RequestCountOption::INLINE
                );
                $this->_requestDescription->raiseMinimumVersionRequirement(
                    2, 
                    0, 
                    $this->_dataService
                );
                $this->_requestDescription->raiseResponseVersion(
                    2, 
                    0, 
                    $this->_dataService
                );
            } else {
                ODataException::createBadRequestError(
                    Messages::queryProcessorInvalidInlineCountOptionError()
                );
            }
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
     *                              resource targetted by the request uri
     *                          (2) If paging is not enabled for the resource
     *                              targetted by the request uri
     *                          (3) If parsing of $skiptoken fails
     *                          (4) If capability negotiation over version fails
     */
    private function _processSkipToken()
    {
        $skipToken = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_SKIPTOKEN
        );
        if (!is_null($skipToken)) {
            if (!$this->_pagingApplicable) {
                ODataException::createBadRequestError(
                    Messages::queryProcessorSkipTokenNotAllowed()
                );
            }

            if (!$this->_isSSPagingRequired()) {
                ODataException::createBadRequestError(
                    Messages::queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet()
                );
            }

            $internalOrderByInfo 
                = $this->_requestDescription->getInternalOrderByInfo();
            //assert($internalOrderByInfo != null)
            $targetResourceType 
                = $this->_requestDescription->getTargetResourceType();
            //assert($targetResourceType != null)
            try {
                $internalSkipTokenInfo = SkipTokenParser::parseSkipTokenClause(
                    $targetResourceType, 
                    $internalOrderByInfo, 
                    $skipToken
                );
                $this->_requestDescription
                    ->setInternalSkipTokenInfo($internalSkipTokenInfo);
                $this->_requestDescription->raiseMinimumVersionRequirement(
                    2, 
                    0, 
                    $this->_dataService
                );
                $this->_requestDescription->raiseResponseVersion(
                    2, 
                    0, 
                    $this->_dataService
                );
            } catch (ODataException $odataException) {
                throw $odataException;
            }
        }
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
        $expand = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_EXPAND
        );

        if (!is_null($expand)) {
            $this->_checkExpandOrSelectApplicable(
                ODataConstants::HTTPQUERY_STRING_EXPAND
            );
        }

        $select = $this->_dataService->getHost()->getQueryStringItem(
            ODataConstants::HTTPQUERY_STRING_SELECT
        );

        if (!is_null($select)) {
            if (!$this->_dataService->getServiceConfiguration()->getAcceptProjectionRequests()) {
                ODataException::createBadRequestError(
                    Messages::dataServiceConfigurationProjectionsNotAccepted()
                );
            }

            $this->_checkExpandOrSelectApplicable(
                ODataConstants::HTTPQUERY_STRING_SELECT
            );
        }

        // We will generate RootProjectionNode in case of $link request also, but
        // expand and select in this case must be null (we are ensuring this above)
        // 'RootProjectionNode' is required while generating next page Link
        if ($this->_expandSelectApplicable 
            || $this->_requestDescription->isLinkUri()
        ) {
            try {
                 $rootProjectionNode = ExpandProjectionParser::parseExpandAndSelectClause(
                     $this->_requestDescription->getTargetResourceSetWrapper(), 
                     $this->_requestDescription->getTargetResourceType(), 
                     $this->_requestDescription->getInternalOrderByInfo(), 
                     $this->_requestDescription->getSkipCount(), 
                     $this->_requestDescription->getTopCount(), 
                     $expand, 
                     $select, 
                     $this->_dataService->getMetadataQueryProviderWrapper()
                 );
                if ($rootProjectionNode->isSelectionSpecified()) {
                    $this->_requestDescription->raiseMinimumVersionRequirement(
                        2, 
                        0, 
                        $this->_dataService
                    );
                }

                if ($rootProjectionNode->hasPagedExpandedResult()) {
                    $this->_requestDescription->raiseResponseVersion(
                        2, 
                        0, 
                        $this->_dataService
                    );
                }
                $this->_requestDescription->setRootProjectionNode(
                    $rootProjectionNode
                );
            } catch (ODataException $odataException) {
                    throw $odataException;
            }
        }
    } 

    /**
     * Is server side paging is configured, this function return true
     * if the resource targetted by the resource path is applicable
     * for paging and paging is enabled for the targetted resource set
     * else false.
     * 
     * @return boolean
     */
    private function _isSSPagingRequired()
    {
        if ($this->_pagingApplicable) {
            $targetResourceSetWrapper 
                = $this->_requestDescription->getTargetResourceSetWrapper();
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
        $value = $this->_dataService->getHost()->getQueryStringItem($queryItem);
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
        $dataServiceHost = $this->_dataService->getHost();
        if (!is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_FILTER)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_EXPAND)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_INLINECOUNT)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_ORDERBY)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SELECT)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SKIP)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_SKIPTOKEN)) 
            || !is_null($dataServiceHost->getQueryStringItem(ODataConstants::HTTPQUERY_STRING_TOP))
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
     * @throws ODataException Throws bad request error if any of the query 
     *                        options $orderby, $inlinecount, $skip or $top
     *                        cannot be applied to the requested resource.
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
?>