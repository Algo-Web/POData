<?php
namespace POData;

use POData\BaseService;

class BatchProvider{
    protected $_service;
    protected $batchboundry = '';
    protected $changeSetBoundry = [];
    protected $request;
    public function __construct(BaseService $service, $request){
        $this->_service = $service;
        $this->request = $request;
    }
    
     public function handleBatchRequest(){
        $request = $this->request;
        $prefix = 'HTTP_';
        $host = $this->_service->getHost();
        assert(substr($host->getRequestContentType(),0,16) === "multipart/mixed;");
        $batchBoundry = substr($host->getRequestContentType(),26);
        $batchBits = explode($batchBoundry, $request->getData());
        $batchResonseBody = "";
        foreach($batchBits as $batchBit){
            $contentIDToLocation = [];
            $batchBit = trim($batchBit,'-');
            $batchBit = trim($batchBit);
            if($batchBit === ""){
                continue;
            }
            $batchBitLines = explode("\n",$batchBit);
            assert(substr($batchBitLines[0],0,30) === "Content-Type: multipart/mixed;");
            $changeSetBundry = trim(substr($batchBitLines[0],40));
            $changeSetBits = explode($changeSetBundry, $batchBit);
            array_shift($changeSetBits);
            foreach($changeSetBits as $operation){
                $contentID = -1;
                $operation = trim($operation, '-');
                $operation = trim($operation);
                if($operation == ""){
                    continue;
                }
                $operationParts = explode("\n", $operation);
// TODO: we should array shift till we remove a blank line. but this will do for now.
                array_shift($operationParts);
                array_shift($operationParts);
                array_shift($operationParts);
                $requestPath = trim(array_shift($operationParts));
                $content = trim(array_pop($operationParts));
                foreach($contentIDToLocation as $contentID => $location){
                   $content = str_replace('$' . $contentID, $location, $content);
                }
                $serverParts = [];
                foreach($operationParts as $headerBit){
                    $headerBit = trim($headerBit);
                    if($headerBit == ""){
                        continue;
                    }
                    $headerSides = explode(":", $headerBit);
                    if(count($headerSides) != 2){
                        //TODO: we should throw an error her
                        dd($headerBit);

                    }
                    if(trim($headerSides[0]) == "Content-ID"){
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
                }
                $RequestPathParts = explode(" ", $requestPath);
                $operationRequest = \Illuminate\Http\Request::create($RequestPathParts[1],$RequestPathParts[0],[],[],[],$serverParts,$content);
                $newContext = new \POData\OperationContext\Web\Illuminate\IlluminateOperationContext($operationRequest);
                $newHost = new \POData\OperationContext\ServiceHost($newContext, $operationRequest);

                $this->_service->setHost($newHost);
                $this->_service->handleRequest();
                $odataResponse = $newContext->outgoingResponse();
                if($RequestPathParts[0] != "GET"){
                    $contentIDToLocation[$contentID] = $odataResponse->getHeaders()["Location"];
                }

                dd($contentIDToLocation, $odataResponse);
            }
            dd($changeSetBundry,$changeSetBits);
        }

        dd($batchBits);
        dd($request);
    }

}
