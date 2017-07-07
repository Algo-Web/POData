<?php

namespace POData\UriProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

/**
 * Class UriProcessorNew.
 *
 * A type to process client's requested URI
 * The syntax of request URI is:
 *  Scheme Host Port ServiceRoot ResourcePath ? QueryOption
 * For more details refer:
 * http://www.odata.org/developers/protocols/uri-conventions#UriComponents
 */
class UriProcessorNew implements IUriProcessor
{

    /**
     * Process the resource path and query options of client's request uri.
     *
     * @param IService $service Reference to the data service instance
     *
     * @throws ODataException
     *
     * @return IUriProcessor
     */
    public static function process(IService $service)
    {
        // TODO: Implement process() method.
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest()
    {
        // TODO: Implement getRequest() method.
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return ProvidersWrapper
     */
    public function getProviders()
    {
        // TODO: Implement getProviders() method.
    }

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService()
    {
        // TODO: Implement getService() method.
    }

    /**
     * Gets the request expander instance.
     *
     * @return RequestExpander
     */
    public function getExpander()
    {
        // TODO: Implement getExpander() method.
    }

    /**
     * Execute the client submitted request against the data source.
     */
    public function execute()
    {
        // TODO: Implement execute() method.
    }
}
