<?php

declare(strict_types=1);
namespace POData\BatchProcessor;

use Illuminate\Support\Str;
use POData\BaseService;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;
use POData\OperationContext\Web\IncomingRequest;

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
    protected $contentIDToLocationLookup =[];

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
     * @throws \POData\Common\ODataException
     * @throws \POData\Common\UrlFormatException
     * @throws \Exception
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
            if ('GET' != $workingObject->RequestVerb && !Str::contains($workingObject->RequestURL, '/$links/')) {
                if (null === $workingObject->Response->getHeaders()['Location']) {
                    $msg = 'Location header not set in subrequest response for ' . $workingObject->RequestVerb
                        . ' request url ' . $workingObject->RequestURL;
                    throw new \Exception($msg);
                }
                $this->contentIDToLocationLookup[$contentID] = $workingObject->Response->getHeaders()['Location'];
            }
        }
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        $response = '';
        $splitter = false === $this->changeSetBoundary ? '' : '--' . $this->changeSetBoundary . "\r\n";
        $raw      = $this->getRawRequests();
        foreach ($raw as $contentID => &$workingObject) {
            $headers = $workingObject->Response->getHeaders();
            $response .= $splitter;

            $response .= 'Content-Type: application/http' . "\r\n";
            $response .= 'Content-Transfer-Encoding: binary' . "\r\n";
            $response .= "\r\n";
            $response .= 'HTTP/1.1 ' . $headers['Status'] . "\r\n";
            $response .= 'Content-ID: ' . $contentID . "\r\n";

            foreach ($headers as $headerName => $headerValue) {
                if (null !== $headerValue) {
                    $response .= $headerName . ': ' . $headerValue . "\r\n";
                }
            }
            $response .= "\r\n";
            $response .= $workingObject->Response->getStream();
        }
        $response .= trim($splitter);
        $response .= false === $this->changeSetBoundary ? "\r\n" : "--\r\n";
        $response = 'Content-Length: ' . strlen($response) . "\r\n\r\n" . $response;
        $response = false === $this->changeSetBoundary ?
            $response :
            'Content-Type: multipart/mixed; boundary=' . $this->changeSetBoundary . "\r\n" . $response;
        return $response;
    }

    /**
     * @throws \Exception
     */
    public function handleData()
    {
        $firstLine               = trim(strtok($this->getData(), "\n"));
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
            $lines               = explode(PHP_EOL, $match);

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
                            throw new \Exception('Malformed header line: ' . $line);
                        }
                        if (strtolower(trim($headerSides[0])) == strtolower('Content-ID')) {
                            $contentID = trim($headerSides[1]);
                            continue 2;
                        }

                        $name  = trim($headerSides[0]);
                        $name  = strtr(strtoupper($name), '-', '_');
                        $value = trim($headerSides[1]);
                        if (!Str::startsWith($name, $prefix) && $name != 'CONTENT_TYPE') {
                            $name = $prefix . $name;
                        }
                        $serverParts[$name] = $value;

                        break;
                    case 2:
                        $content .= $line;
                        break;
                    default:
                        throw new \Exception('how did we end up with more than 3 stages??');
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
     * @return BaseService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
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
     * @throws \POData\Common\ODataException
     * @throws \POData\Common\UrlFormatException
     */
    protected function processSubRequest(&$workingObject)
    {
        $newContext = new IlluminateOperationContext($workingObject->Request);
        $newHost    = new ServiceHost($newContext);

        $this->getService()->setHost($newHost);
        $this->getService()->handleRequest();
        $workingObject->Response = $newContext->outgoingResponse();
    }
}
