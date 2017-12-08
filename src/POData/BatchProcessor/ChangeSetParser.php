<?php
namespace POData\BatchProcessor;

use \POData\OperationContext\ServiceHost;
use Illuminate\Http\Request;
use POData\BaseService;
use POData\OperationContext\Web\Illuminate\IlluminateOperationContext;

class ChangeSetParser implements IBatchParser
{
    protected $_data;
    protected $changeSetBoundtry;
    protected $rawRequests = [];
    protected $_service;
    protected $ContentIDToLocationLookup =[];

    public function __construct(BaseService $service, $body)
    {
        $this->_service = $service;
        $this->_data = trim($body);
        $firstLine = trim(strtok($this->_data, "\n"));
        $this->changeSetBoundtry = substr($firstLine, 40);

        $prefix = 'HTTP_';
        $matches = explode('--'.$this->changeSetBoundtry, $this->_data);
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
                        if (strtolower('Content-Type') == strtolower(substr($line, 0, 12)) && 'application/http' != strtolower(substr($line, -16))) {
                            //TODO: thro an error about incorrect content type for changeSet
                        }
                        if (strtolower('Content-Transfer-Encoding') == strtolower(substr($line, 0, 25)) && 'binary' != strtolower(substr($line, -6))) {
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
                            //TODO: we should throw an error her
                            dd($line);
                        }
                        if (strtolower(trim($headerSides[0])) == strtolower('Content-ID')) {
                            $contentID = trim($headerSides[1]);
                            continue;
                        }

                        $name = trim($headerSides[0]);
                        $name = strtr(strtoupper($name), '-', '_');
                        $value = trim($headerSides[1]);
                        if (! starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                            $name = $prefix.$name;
                        }
                        $serverParts[$name] = $value;

                        break;
                    case 2:
                        $content .= $line;
                        break;
                    default:
                        dd('how did we end up with more then 3 stanges??');
               }
            }
            if ($contentIDinit == $contentID) {
                $contentIDinit--;
            }
            $this->rawRequests[$contentID] = (object)['RequestVerb'  => $RequestPathParts[0],
                                                      'RequestURL'   => $RequestPathParts[1],
                                                      'ServerParams' => $serverParts,
                                                      'Content'      => $content,
                                                      'Request'      => null,
                                                      'Response'     => null];
        }
    }


    public function process()
    {
        foreach ($this->rawRequests as $contentID => $workibngObject) {
            foreach ($this->ContentIDToLocationLookup as $contentID => $location) {
                if (0 > $contentID) {
                    continue;
                }
                $workibngObject->Content = str_replace('$' . $contentID, $location, $workibngObject->Content);
            }

            $workibngObject->Request = Request::create($workibngObject->RequestURL, $workibngObject->RequestVerb, [], [], [], $workibngObject->ServerParams, $workibngObject->Content);
            $newContext = new IlluminateOperationContext($workibngObject->Request);
            $newHost = new ServiceHost($newContext, $workibngObject->Request);

            $this->_service->setHost($newHost);
            $this->_service->handleRequest();
            $workibngObject->Response = $newContext->outgoingResponse();
            if ($workibngObject->RequestVerb != 'GET') {
                $this->ContentIDToLocationLookup[$contentID] = $workibngObject->Response->getHeaders()['Location'];
            }
        }
    }
    public function getResponse(){
        $response = "";
        foreach ($this->rawRequests as $contentID => $workibngObject) {
            $response .= $this->changeSetBoundtry === false ? "" : '--' . $this->changeSetBoundtry . "\r\n";
            $response .= 'Content-Type: application/http' . "\r\n";
            $response .= 'Content-Transfer-Encoding: binary' . "\r\n";
            $response .= "\r\n";
            $headers = $workibngObject->Response->getHeaders();
            foreach ($headers as $headerName => $headerValue) {
                if (null !== $headerValue) {
                     $response .= $headerName . ':' . $headerValue . "\r\n";
                }
            }
            $response .= "\r\n";
            $response .= $workibngObject->Response->getStream();


        }
        $response .= $this->changeSetBoundtry === false ? "" : '--' . $this->changeSetBoundtry . '--' . "\r\n";
        return $response;
    }
}
