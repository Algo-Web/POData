<?php

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
    use common,
        expressionParser,
        metadataAssociationType,
        orderByInfo,
        resourceAssociationSet,
        segmentParser,
        configuration,
        http,
        metadataResourceType,
        providersWrapper,
        resourceAssociationType,
        skipTokenInfo,
        eTag,
        httpProcessUtility,
        metadataWriter,
        queryProcessor,
        resourceProperty,
        skipTokenParser,
        expandProjectionParser,
        IService,
        navigation,
        queryProvider,
        resourceSet,
        streamProviderWrapper,
        expressionLexer,
        keyDescriptor,
        objectModelSerializer,
        request,
        resourceType,
        uriProcessor;
}
