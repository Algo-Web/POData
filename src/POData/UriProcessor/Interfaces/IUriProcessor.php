<?php

namespace POData\UriProcessor\Interfaces;

use POData\Common\ODataException;
use POData\IService;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestExpander;
use POData\UriProcessor\UriProcessor;

/**
 * Class UriProcessor.
 *
 * A type to process client's requets URI
 * The syntax of request URI is:
 *  Scheme Host Port ServiceRoot ResourcePath ? QueryOption
 * For more details refer:
 * http://www.odata.org/developers/protocols/uri-conventions#UriComponents
 */
interface IUriProcessor
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
    public static function process(IService $service);

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest();

    /**
     * Gets reference to the request submitted by client.
     *
     * @return ProvidersWrapper
     */
    public function getProviders();

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService();

    /**
     * Gets the request expander instance.
     *
     * @return RequestExpander
     */
    public function getExpander();

    /**
     * Execute the client submitted request against the data source.
     */
    public function execute();
}
