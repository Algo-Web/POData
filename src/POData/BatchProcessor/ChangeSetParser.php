<?php

declare(strict_types=1);

namespace POData\BatchProcessor;

use Exception;
use Illuminate\Support\Str;
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
        $this->service = $service;
        $this->data    = trim($body);
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
                $workingObject->Content    = str_replace('$' . $lookupID, $location, $workingObject->Content);
                $workingObject->RequestURL = str_replace('$' . $lookupID, $location, $workingObject->RequestURL);
            }

            $this->processSubRequest($workingObject);
            if ('GET' != $workingObject->RequestVerb &&
                !StringUtility::contains($workingObject->RequestURL, '/$links/')) {
                if (null === $workingObject->Response->getHeaders()['Location']) {
                    $msg = 'Location header not set in subrequest response for ' . $workingObject->RequestVerb
                        . ' request url ' . $workingObject->RequestURL;
                    throw new Exception($msg);
                }
                $this->contentIDToLocationLookup[$contentID] = $workingObject->Response->getHeaders()['Location'];
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
     * @param $workingObject
     * @throws ODataException
     * @throws UrlFormatException
     */
    protected function processSubRequest(&$workingObject)
    {
        $newContext = new WebOperationContext($workingObject->Request);
        $newHost    = new ServiceHost($newContext);

        $this->getService()->setHost($newHost);
        $this->getService()->handleRequest();
        $workingObject->Response = $newContext->outgoingResponse();
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
            $headers = $workingObject->Response->getHeaders();
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
            $response .= $workingObject->Response->getStream();
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

    /**
     * @throws Exception
     */
    public function handleData()
    {
        $ODataEOL = $this->getService()->getConfiguration()->getLineEndings();

        $firstLine               = trim(strtok($this->getData(), $ODataEOL));// with trim matches both crlf and lf
        $this->changeSetBoundary = substr($firstLine, 40);

        $prefix  = 'HTTP_';
        $matches = explode('--' . $this->changeSetBoundary, $this->getData());
        array_shift($matches);
        $contentIDinit = -1;
        foreach ($matches as $match) {
            if ('--' === trim($match)) {
                continue;
            }

            $stage               = 0;
            $gotRequestPathParts = false;
            $match               = trim($match);
            $lines               = explode($ODataEOL, $match);

            $requestPathParts = [];
            $serverParts      = [];
            $contentID        = $contentIDinit;
            $content          = '';

            foreach ($lines as $line) {
                if ('' == $line) {
                    $stage++;
                    continue;
                }
                switch ($stage) {
                    case 0:
                        if (strtolower('Content-Type') == strtolower(substr($line, 0, 12))
                            && 'application/http' != strtolower(substr($line, -16))
                        ) {
                            //TODO: throw an error about incorrect content type for changeSet
                        }
                        if (strtolower('Content-Transfer-Encoding') == strtolower(substr($line, 0, 25))
                            && 'binary' != strtolower(substr($line, -6))
                        ) {
                            //TODO: throw an error about unsupported encoding
                        }
                        break;
                    case 1:
                        if (!$gotRequestPathParts) {
                            $requestPathParts    = explode(' ', $line);
                            $gotRequestPathParts = true;
                            continue 2;
                        }
                        $headerSides = explode(':', $line);
                        if (count($headerSides) != 2) {
                            throw new Exception('Malformed header line: ' . $line);
                        }
                        if (strtolower(trim($headerSides[0])) == strtolower('Content-ID')) {
                            $contentID = trim($headerSides[1]);
                            continue 2;
                        }

                        $name  = trim($headerSides[0]);
                        $name  = strtr(strtoupper($name), '-', '_');
                        $value = trim($headerSides[1]);
                        if (!StringUtility::startsWith($name, $prefix) && $name != 'CONTENT_TYPE') {
                            $name = $prefix . $name;
                        }
                        $serverParts[$name] = $value;

                        break;
                    case 2:
                        $content .= $line;
                        break;
                    default:
                        throw new Exception('how did we end up with more than 3 stages??');
                }
            }

            if ($contentIDinit == $contentID) {
                $contentIDinit--;
            }

            $this->rawRequests[$contentID] = (object)[
                'RequestVerb' => $requestPathParts[0],
                'RequestURL' => $requestPathParts[1],
                'ServerParams' => $serverParts,
                'Content' => $content,
                'Request' => new IncomingRequest(
                    new HTTPRequestMethod($requestPathParts[0]),
                    [],
                    [],
                    $serverParts,
                    null,
                    $content,
                    $requestPathParts[1]
                ),
                'Response' => null
            ];
        }
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}
