<?php
namespace POData\BatchProcessor;

use \POData\OperationContext\ServiceHost;
use Illuminate\Http\Request;
use POData\BaseService;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;

class ChangeSetParser implements IBatchParser
{
    protected $data;
    protected $changeSetBoundary;
    protected $rawRequests = [];
    protected $service;
    protected $ContentIDToLocationLookup =[];

    public function __construct(BaseService $service, $body)
    {
        $this->service = $service;
        $this->data = trim($body);
    }

    public function process()
    {
        $raw = $this->getRawRequests();
        foreach ($raw as $contentID => &$workingObject) {
            foreach ($this->ContentIDToLocationLookup as $lookupID => $location) {
                if (0 > $lookupID) {
                    continue;
                }
                $workingObject->Content = str_replace('$' . $lookupID, $location, $workingObject->Content);
            }

            $workingObject->Request = Request::create(
                $workingObject->RequestURL,
                $workingObject->RequestVerb,
                [],
                [],
                [],
                $workingObject->ServerParams,
                $workingObject->Content
            );
            $this->processSubRequest($workingObject);
            if ('GET' != $workingObject->RequestVerb) {
                $this->ContentIDToLocationLookup[$contentID] = $workingObject->Response->getHeaders()['Location'];
            }
        }
    }

    public function getResponse()
    {
        $response = "";
        $splitter = false === $this->changeSetBoundary ? "" : '--' . $this->changeSetBoundary . "\r\n";
        $raw = $this->getRawRequests();
        foreach ($raw as $contentID => &$workingObject) {
            $response .= $splitter;
            $response .= 'Content-Type: application/http' . "\r\n";
            $response .= 'Content-Transfer-Encoding: binary' . "\r\n";
            $response .= "\r\n";
            $headers = $workingObject->Response->getHeaders();
            foreach ($headers as $headerName => $headerValue) {
                if (null !== $headerValue) {
                     $response .= $headerName . ': ' . $headerValue . "\r\n";
                }
            }
            $response .= "\r\n";
            $response .= $workingObject->Response->getStream();
        }
        $response .= $splitter;
        return $response;
    }

    public function handleData()
    {
        $firstLine = trim(strtok($this->getData(), "\n"));
        $this->changeSetBoundary = substr($firstLine, 40);

        $prefix = 'HTTP_';
        $matches = explode('--' . $this->changeSetBoundary, $this->getData());
        array_shift($matches);
        $contentIDinit = -1;
        foreach ($matches as $match) {
            if ('--' === trim($match)) {
                continue;
            }

            $stage = 0;
            $gotRequestPathParts = false;
            $match = trim($match);
            $lines = explode("\n", $match);

            $RequestPathParts = [];
            $serverParts = [];
            $contentID = $contentIDinit;
            $content = '';

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
                            $RequestPathParts = explode(' ', $line);
                            $gotRequestPathParts = true;
                            continue;
                        }
                        $headerSides = explode(':', $line);
                        if (count($headerSides) != 2) {
                            throw new \Exception('Malformed header line: '.$line);
                        }
                        if (strtolower(trim($headerSides[0])) == strtolower('Content-ID')) {
                            $contentID = trim($headerSides[1]);
                            continue;
                        }

                        $name = trim($headerSides[0]);
                        $name = strtr(strtoupper($name), '-', '_');
                        $value = trim($headerSides[1]);
                        if (!starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
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
                'RequestVerb' => $RequestPathParts[0],
                'RequestURL' => $RequestPathParts[1],
                'ServerParams' => $serverParts,
                'Content' => $content,
                'Request' => null,
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

    public function getRawRequests()
    {
        return $this->rawRequests;
    }

    protected function processSubRequest(&$workingObject)
    {
        $newContext = new IlluminateOperationContext($workingObject->Request);
        $newHost = new ServiceHost($newContext, $workingObject->Request);

        $this->getService()->setHost($newHost);
        $this->getService()->handleRequest();
        $workingObject->Response = $newContext->outgoingResponse();
    }
}
