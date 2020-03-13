<?php

declare(strict_types=1);

namespace POData\Common;

use POData\Common\Messages\common;
use POData\Common\Messages\configuration;
use POData\Common\Messages\eTag;
use POData\Common\Messages\expandProjectionParser;
use POData\Common\Messages\expressionLexer;
use POData\Common\Messages\expressionParser;
use POData\Common\Messages\http;
use POData\Common\Messages\httpProcessUtility;
use POData\Common\Messages\IService;
use POData\Common\Messages\keyDescriptor;
use POData\Common\Messages\metadataAssociationType;
use POData\Common\Messages\metadataResourceType;
use POData\Common\Messages\metadataWriter;
use POData\Common\Messages\navigation;
use POData\Common\Messages\objectModelSerializer;
use POData\Common\Messages\orderByInfo;
use POData\Common\Messages\providersWrapper;
use POData\Common\Messages\queryProcessor;
use POData\Common\Messages\queryProvider;
use POData\Common\Messages\request;
use POData\Common\Messages\resourceAssociationSet;
use POData\Common\Messages\resourceAssociationType;
use POData\Common\Messages\resourceProperty;
use POData\Common\Messages\resourceSet;
use POData\Common\Messages\resourceType;
use POData\Common\Messages\responseWriter;
use POData\Common\Messages\segmentParser;
use POData\Common\Messages\skipTokenInfo;
use POData\Common\Messages\skipTokenParser;
use POData\Common\Messages\streamProviderWrapper;
use POData\Common\Messages\uriProcessor;

/**
 * Class Messages helps to format error messages.
 */
class Messages
{
    use common;
    use expressionParser;
    use metadataAssociationType;
    use orderByInfo;
    use resourceAssociationSet;
    use segmentParser;
    use configuration;
    use http;
    use metadataResourceType;
    use providersWrapper;
    use resourceAssociationType;
    use skipTokenInfo;
    use eTag;
    use httpProcessUtility;
    use metadataWriter;
    use queryProcessor;
    use resourceProperty;
    use skipTokenParser;
    use expandProjectionParser;
    use IService;
    use navigation;
    use queryProvider;
    use resourceSet;
    use streamProviderWrapper;
    use expressionLexer;
    use keyDescriptor;
    use objectModelSerializer;
    use request;
    use responseWriter;
    use resourceType;
    use uriProcessor;
}
