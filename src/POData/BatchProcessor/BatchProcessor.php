<?php
namespace POData\BatchProcessor;

use POData\BaseService;
use POData\UriProcessor\RequestDescription;

class BatchProcessor
{
    protected $service;
    protected $data;
    protected $batchBoundary = '';
    protected $request;
    protected $ChangeSetProcessors = [];

    /**
     * @param BaseService        $service
     * @param RequestDescription $request
     */
    public function __construct(BaseService $service, RequestDescription $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    public function getBoundary()
    {
        return $this->batchBoundary;
    }

    public function handleBatch()
    {
        $host = $this->getService()->getHost();
        $contentType = $host->getRequestContentType();
        assert('multipart/mixed;' === substr($contentType, 0, 16));
        $this->data = trim($this->getRequest()->getData());
        $this->data = preg_replace('~\r\n?~', "\n", $this->data);
        $this->batchBoundary = substr($contentType, 26);

        $matches = explode('--' . $this->batchBoundary, $this->data);
        foreach ($matches as $match) {
            $match = trim($match);
            if ('' === $match || '--' === $match) {
                continue;
            }
            $header = explode("\n\n", $match)[0];
            $isChangeset = false === strpos($header, 'Content-Type: application/http');
            $this->ChangeSetProcessors[] = $this->getParser($this->getService(), $match, $isChangeset);
        }

        foreach ($this->ChangeSetProcessors as $csp) {
            $csp->handleData();
            $csp->process();
        }
    }

    public function getResponse()
    {
        $response = '';
        $splitter =  '--' . $this->batchBoundary . "\r\n";
        $raw = $this->ChangeSetProcessors;
        foreach ($raw as $contentID => &$workingObject) {
            $response .= $splitter;
            $response .= $workingObject->getResponse() . "\r\n";
        }
        $response .= trim($splitter) . "--\r\n";
        return $response;
    }


    protected function getParser(BaseService $service, $match, $isChangeset)
    {
        if ($isChangeset) {
            return new ChangeSetParser($service, $match);
        }
        return new QueryParser($service, $match);
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
}
