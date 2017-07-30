<?php

namespace POData\UriProcessor;

use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\ReflectionHandler;
use POData\IService;
use POData\OperationContext\HTTPRequestMethod;
use POData\Providers\Metadata\ResourcePropertyKind;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\Interfaces\IUriProcessor;
use POData\UriProcessor\QueryProcessor\QueryProcessor;
use POData\UriProcessor\ResourcePathProcessor\ResourcePathProcessor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentDescriptor;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;

class UriProcessor extends UriProcessorNew
{
}
