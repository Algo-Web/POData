<?php

namespace UnitTests\POData\UriProcessor;


use POData\Configuration\ServiceConfiguration;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\ExpandedProjectionNode;
use POData\UriProcessor\QueryProcessor\ExpandProjectionParser\RootProjectionNode;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderBySubPathSegment;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByPathSegment;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\InternalSkipTokenInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\Configuration\ProtocolVersion;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetKind;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\TargetSource;
use POData\Providers\Metadata\Type\Int32;
use POData\Providers\Metadata\Type\DateTime;
use POData\Common\Url;
use POData\Common\Version;
use POData\Common\ODataException;
use POData\OperationContext\ServiceHost;
use POData\UriProcessor\UriProcessor;
use UnitTests\POData\Facets\ServiceHostTestFake;
use UnitTests\POData\Facets\NorthWind1\NorthWindService2;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV1;
use UnitTests\POData\Facets\NorthWind1\NorthWindServiceV3;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Common\Messages;
use POData\Common\ODataConstants;
use POData\Providers\Metadata\ResourceProperty;

use POData\IService;

class UriProcessorDummy extends UriProcessor
{

}