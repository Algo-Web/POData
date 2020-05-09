<?php


namespace POData\BatchProcessor;


use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\Web\IncomingRequest;

class IncomingChangeSetRequest extends IncomingRequest
{

    protected $contentID = null;

    public function __construct(string $requestChunk)
    {
        list($RequestParams, $requestHeaders, $RequestBody) = explode("\n\n", $requestChunk);

        $headerLine = strtok($requestHeaders, "\n");
        $RequestBody                                        = trim($RequestBody);
        list($RequesetType, $RequestPath, $RequestProticol) = explode(' ', $headerLine, 3);
        $inboundRequestHeaders                              = [];
        $headerLine = strtok("\n");

        while ($headerLine !== false) {
            list($key, $value)            = explode(':', $headerLine);
            $name                         = strtr(strtoupper(trim($key)), '-', '_');
            $value                        = trim($value);
            $name                         = substr($name, 0, 5) === 'HTTP_' || $name == 'CONTENT_TYPE' ? $name : 'HTTP_' . $name;
            if('HTTP_CONTENT_ID' === $name){
                $this->contentID = $value;
            }else {
                $inboundRequestHeaders[$name] = $value;
            }
            $headerLine = strtok("\n");
        }
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? 'localhost';
        $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';

        parent::__construct(
            new HTTPRequestMethod($RequesetType),
            [],
            [],
            $inboundRequestHeaders,
            null,
            $RequestBody,
            $protocol . '://' . $host . $RequestPath);
    }

    public function getContentId(): ?string {
        return $this->contentID;
    }

}