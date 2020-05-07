<?php

declare(strict_types=1);

namespace POData\BatchProcessor;

use POData\BaseService;
use POData\UriProcessor\RequestDescription;

/**
 * Class BatchProcessor.
 * @package POData\BatchProcessor
 */
class BatchProcessor
{
    protected $service;
    protected $data;
    protected $batchBoundary = '';
    protected $request;
    protected $changeSetProcessors = [];

    /**
     * @param BaseService        $service
     * @param RequestDescription $request
     */
    public function __construct(BaseService $service, RequestDescription $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getBoundary()
    {
        return $this->batchBoundary;
    }

    public function handleBatch()
    {
        $host        = $this->getService()->getHost();
        $contentType = $host->getRequestContentType();
        assert('multipart/mixed;' === substr($contentType, 0, 16));
        $rawData = $this->getRequest()->getData();
        if (is_array($rawData)) {
            $rawData = $rawData[0];
        }
        $ODataEOL            = $this->getService()->getConfiguration()->getLineEndings();
        $this->data          = trim($rawData);
        $this->data          = preg_replace('~\r\n?~', $ODataEOL, $this->data);
        $this->batchBoundary = substr($contentType, 26);

        $matches = explode('--' . $this->batchBoundary, $this->data);
        foreach ($matches as $match) {
            $match = trim($match);
            if ('' === $match || '--' === $match) {
                continue;
            }
            $header                      = explode($ODataEOL . $ODataEOL, $match)[0];
            $isChangeset                 = false === strpos($header, 'Content-Type: application/http');
            $this->changeSetProcessors[] = $this->getParser($this->getService(), $match, $isChangeset);
        }

        foreach ($this->changeSetProcessors as $csp) {
            $csp->handleData();
            $csp->process();
        }
    }

    /**
     * @return BaseService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return RequestDescription
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param BaseService $service
     * @param $match
     * @param  bool                        $isChangeset
     * @return ChangeSetParser|QueryParser
     */
    protected function getParser(BaseService $service, $match, $isChangeset)
    {
        if ($isChangeset) {
            return new ChangeSetParser($service, $match);
        }
        return new QueryParser($service, $match);
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        $ODataEOL = $this->getService()->getConfiguration()->getLineEndings();

        $response = '';
        $splitter = '--' . $this->batchBoundary . $ODataEOL;
        $raw      = $this->changeSetProcessors;
        foreach ($raw as $contentID => &$workingObject) {
            $response .= $splitter;
            $response .= $workingObject->getResponse() . $ODataEOL;
        }
        $response .= trim($splitter) . '--' . $ODataEOL;
        return $response;
    }
}
