<?php

declare(strict_types=1);

namespace POData\BatchProcessor;

use Exception;
use POData\BaseService;
use POData\Common\ODataException;
use POData\Common\UrlFormatException;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\IncomingRequest;
use POData\OperationContext\Web\WebOperationContext;
use POData\StringUtility;

/**
 * Class ChangeSetParser.
 * @package POData\BatchProcessor
 */
class ChangeSetParser implements IBatchParser
{
    protected $data;
    protected $changeSetBoundary;
    /**
     * @var WebOperationContext[]
     */
    protected $rawRequests = [];
    protected $service;
    protected $contentIDToLocationLookup = [];

    /**
     * ChangeSetParser constructor.
     * @param BaseService $service
     * @param $body
     */
    public function __construct(BaseService $service, $body)
    {
        $this->service            = $service;
        $this->data               = trim(str_replace("\r", '', $body)); // removes windows specific character
    }

    /**
     * @return mixed
     */
    public function getBoundary()
    {
        return $this->changeSetBoundary;
    }

    /**
     * @throws ODataException
     * @throws UrlFormatException
     * @throws Exception
     */
    public function process()
    {
        $raw = $this->getRawRequests();
        foreach ($raw as $contentID => &$workingObject) {
            foreach ($this->contentIDToLocationLookup as $lookupID => $location) {
                if (0 > $lookupID) {
                    continue;
                }
                $workingObject->incomingRequest()->applyContentID($lookupID, $location);
            }

            $this->processSubRequest($workingObject);
            if (HTTPRequestMethod::GET() != $workingObject->incomingRequest()->getMethod() &&
                strpos($workingObject->incomingRequest()->getRawUrl(), '/$links/') === false) {
                if (null === $workingObject->outgoingResponse()->getHeaders()['Location']) {
                    $msg = 'Location header not set in subrequest response for ' . $workingObject->incomingRequest()->getMethod()
                        . ' request url ' . $workingObject->incomingRequest()->getRawUrl();
                    throw new Exception($msg);
                }
                $this->contentIDToLocationLookup[$contentID] = $workingObject->outgoingResponse()->getHeaders()['Location'];
            }
        }
    }

    /**
     * @return array
     */
    public function getRawRequests()
    {
        return $this->rawRequests;
    }

    /**
     * @param $newContext
     * @throws ODataException
     * @throws UrlFormatException
     */
    protected function processSubRequest(&$newContext)
    {
        $newHost    = new ServiceHost($newContext);
        $oldHost    = $this->getService()->getHost();
        $this->getService()->setHost($newHost);
        $this->getService()->handleRequest();
        $this->getService()->setHost($oldHost);
    }

    /**
     * @return BaseService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        $ODataEOL = $this->getService()->getConfiguration()->getLineEndings();

        $response = '';
        $splitter = false === $this->changeSetBoundary ?
            '' :
            '--' . $this->changeSetBoundary . $ODataEOL;
        $raw = $this->getRawRequests();
        foreach ($raw as $contentID => &$workingObject) {
            $headers = $workingObject->outgoingResponse()->getHeaders();
            $response .= $splitter;

            $response .= 'Content-Type: application/http' . $ODataEOL;
            $response .= 'Content-Transfer-Encoding: binary' . $ODataEOL;
            $response .= $ODataEOL;
            $response .= 'HTTP/1.1 ' . $headers['Status'] . $ODataEOL;
            $response .= 'Content-ID: ' . $contentID . $ODataEOL;

            foreach ($headers as $headerName => $headerValue) {
                if (null !== $headerValue) {
                    $response .= $headerName . ': ' . $headerValue . $ODataEOL;
                }
            }
            $response .= $ODataEOL;
            $response .= $workingObject->outgoingResponse()->getStream();
        }
        $response .= trim($splitter);
        $response .= false === $this->changeSetBoundary ?
            $ODataEOL :
            '--' . $ODataEOL;
        $response = 'Content-Length: ' .
            strlen($response) .
            $ODataEOL .
            $ODataEOL .
            $response;
        $response = false === $this->changeSetBoundary ?
            $response :
            'Content-Type: multipart/mixed; boundary=' .
            $this->changeSetBoundary .
            $ODataEOL .
            $response;
        return $response;
    }

    public function handleData()
    {
        list($headerBlock, $contentBlock) = explode("\n\n", $this->getData(), 2);
        $headers                          = self::parse_headers($headerBlock);
        $this->changeSetBoundary          = $headers['Content-Type']['boundary'];
        $matches                          = array_filter(explode('--' . $this->changeSetBoundary, $contentBlock));
        $contentIDinit                    = -1;
        foreach ($matches as $match) {
            if ('--' === trim($match)) {
                continue;
            }
            $request   = new IncomingChangeSetRequest($match);
            $contentID = $request->getContentId() ?? $contentIDinit;
            $this->rawRequests[$contentID] = new WebOperationContext($request);
            if ($contentIDinit == $contentID) {
                $contentIDinit--;
            }
            continue;
        }
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
    private static function parse_headers($headers, $splitter = "\n", $assignmentChar = ':')
    {
        $results = [];
        foreach (array_filter(explode($splitter, trim($headers))) as $line) {
            list($key, $value) = strpos($line, $assignmentChar) !== false ? explode($assignmentChar, $line, 2) : ['default', $line];
            $key               = trim($key);
            $value             = trim($value);
            if (strpos($value, ';') !== false) {
                $value = self::parse_headers($value, ';', '=');
            }
            if (isset($results[$key])) {
                if (is_array($results[$key])) {
                    $results[$key][] = $value;
                } else {
                    $results[$key] = [$results[$key], $value];
                }
            } else {
                $results[$key] = $value;
            }
        }
        return $results;
    }
}
