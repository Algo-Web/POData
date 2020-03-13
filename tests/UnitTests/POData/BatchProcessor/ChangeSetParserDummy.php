<?php

declare(strict_types=1);

namespace UnitTests\POData\BatchProcessor;

use Mockery as m;
use POData\BaseService;
use POData\BatchProcessor\BatchProcessor;
use POData\BatchProcessor\ChangeSetParser;
use POData\BatchProcessor\QueryParser;
use POData\Common\ErrorHandler;
use POData\Common\HttpStatus;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\IService;
use POData\OperationContext\IOperationContext;
use POData\OperationContext\ServiceHost;
use POData\OperationContext\Web\OutgoingResponse;
use POData\UriProcessor\RequestDescription;
use UnitTests\POData\TestCase;

class ChangeSetParserDummy extends ChangeSetParser
{
    public function processSubRequest(&$workingObject)
    {
        parent::processSubRequest($workingObject);
    }
}
