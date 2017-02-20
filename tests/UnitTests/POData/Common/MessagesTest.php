<?php

namespace UnitTests\POData\Common;

use UnitTests\POData\TestCase;

class MessagesTest extends TestCase
{
    public function testVerifyMessageList()
    {
        //This test is here to fail if someone adds a message but not a test
        $actual = get_class_methods('POData\Common\Messages');

        $expected = [
            'expressionLexerUnterminatedStringLiteral',
            'expressionLexerDigitExpected',
            'expressionLexerSyntaxError',
            'expressionLexerInvalidCharacter',
            'expressionParserInCompatibleTypes',
            'expressionParserOperatorNotSupportNull',
            'expressionParserOperatorNotSupportGuid',
            'expressionParserOperatorNotSupportBinary',
            'expressionParserUnrecognizedLiteral',
            'expressionParserUnknownFunction',
            'expressionParser2BooleanRequired',
            'expressionParser2UnexpectedExpression',
            'expressionParser2NonPrimitivePropertyNotAllowed',
            'expressionLexerNoApplicableFunctionsFound',
            'expressionLexerNoPropertyInType',
            'resourceAssociationSetPropertyMustBeNullOrInstanceofResourceProperty',
            'resourceAssociationSetEndPropertyMustBeNavigationProperty',
            'resourceAssociationSetEndResourceTypeMustBeAssignableToResourceSet',
            'resourceAssociationSetResourcePropertyCannotBeBothNull',
            'resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional',
            'resourceAssociationTypeEndPropertyMustBeNullOrInstanceofResourceProperty',
            'resourceAssociationTypeEndBothPropertyCannotBeNull',
            'expressionParserEntityCollectionNotAllowedInFilter',
            'commonArgumentShouldBeInteger',
            'commonArgumentShouldBeNonNegative',
            'commonNotValidPrimitiveEDMType',
            'configurationMaxResultAndPageSizeMutuallyExclusive',
            'configurationResourceSetNameNotFound',
            'configurationRightsAreNotInRange',
            'configurationCountNotAccepted',
            'resourceTypeNoBaseTypeForPrimitive',
            'resourceTypeNoAbstractForPrimitive',
            'resourceTypeTypeShouldImplementIType',
            'resourceTypeTypeShouldReflectionClass',
            'resourceTypeMissingKeyPropertiesForEntity',
            'resourceTypeNoAddPropertyForPrimitive',
            'resourceTypeKeyPropertiesOnlyOnEntityTypes',
            'resourceTypeETagPropertiesOnlyOnEntityTypes',
            'resourceTypePropertyWithSameNameAlreadyExists',
            'resourceTypeNoKeysInDerivedTypes',
            'resourceTypeHasStreamAttributeOnlyAppliesToEntityType',
            'resourceTypeNamedStreamsOnlyApplyToEntityType',
            'resourceTypeNamedStreamWithSameNameAlreadyExists',
            'resourcePropertyInvalidKindParameter',
            'resourcePropertyPropertyKindAndResourceTypeKindMismatch',
            'resourceSetContainerMustBeAssociatedWithEntityType',
            'providersWrapperExpressionProviderMustNotBeNullOrEmpty',
            'providersWrapperInvalidExpressionProviderInstance',
            'providersWrapperContainerNameMustNotBeNullOrEmpty',
            'providersWrapperContainerNamespaceMustNotBeNullOrEmpty',
            'providersWrapperEntitySetNameShouldBeUnique',
            'providersWrapperEntityTypeNameShouldBeUnique',
            'providersWrapperIDSMPGetResourceSetReturnsInvalidResourceSet',
            'queryProviderReturnsNonQueryResult',
            'queryProviderResultCountMissing',
            'queryProviderResultsMissing',
            'providersWrapperIDSQPMethodReturnsUnExpectedType',
            'providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties',
            'providersWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys',
            'navigationInvalidResourceType',
            'keyDescriptorKeyCountNotMatching',
            'keyDescriptorMissingKeys',
            'keyDescriptorInCompatibleKeyType',
            'keyDescriptorInCompatibleKeyTypeAtPosition',
            'keyDescriptorValidateNotCalled',
            'syntaxError',
            'urlMalformedUrl',
            'segmentParserKeysMustBeNamed',
            'segmentParserMustBeLeafSegment',
            'segmentParserNoSegmentAllowedAfterPostLinkSegment',
            'segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment',
            'segmentParserCannotQueryCollection',
            'segmentParserCountCannotFollowSingleton',
            'segmentParserLinkSegmentMustBeFollowedByEntitySegment',
            'segmentParserMissingSegmentAfterLink',
            'segmentParserSegmentNotAllowedOnRoot',
            'segmentParserInconsistentTargetKindState',
            'segmentParserUnExpectedPropertyKind',
            'segmentParserCountCannotBeApplied',
            'uriProcessorResourceNotFound',
            'uriProcessorForbidden',
            'metadataAssociationTypeSetBidirectionalAssociationMustReturnSameResourceAssociationSetFromBothEnd',
            'metadataAssociationTypeSetMultipleAssociationSetsForTheSameAssociationTypeMustNotReferToSameEndSets',
            'metadataAssociationTypeSetInvalidGetDerivedTypesReturnType',
            'metadataResourceTypeSetNamedStreamsOnDerivedEntityTypesNotSupported',
            'metadataResourceTypeSetBagOfComplexTypeWithDerivedTypes',
            'metadataWriterExpectingEntityOrComplexResourceType',
            'metadataWriterNoResourceAssociationSetForNavigationProperty',
            'expandedProjectionNodeArgumentTypeShouldBeProjection',
            'expandProjectionParserPropertyNotFound',
            'expandProjectionParserExpandCanOnlyAppliedToEntity',
            'expandProjectionParserPrimitivePropertyUsedAsNavigationProperty',
            'expandProjectionParserComplexPropertyAsInnerSelectSegment',
            'expandProjectionParserBagPropertyAsInnerSelectSegment',
            'expandProjectionParserUnexpectedPropertyType',
            'expandProjectionParserPropertyWithoutMatchingExpand',
            'orderByInfoPathSegmentsArgumentShouldBeNonEmptyArray',
            'orderByInfoNaviUsedArgumentShouldBeNullOrNonEmptyArray',
            'orderByPathSegmentOrderBySubPathSegmentArgumentShouldBeNonEmptyArray',
            'orderByParserPropertyNotFound',
            'orderByParserBagPropertyNotAllowed',
            'orderByParserPrimitiveAsIntermediateSegment',
            'orderByParserSortByBinaryPropertyNotAllowed',
            'orderByParserResourceSetReferenceNotAllowed',
            'orderByParserSortByNavigationPropertyIsNotAllowed',
            'orderByParserSortByComplexPropertyIsNotAllowed',
            'orderByParserUnExpectedState',
            'orderByParserUnexpectedPropertyType',
            'orderByParserFailedToCreateDummyObject',
            'orderByParserFailedToAccessOrInitializeProperty',
            'failedToAccessProperty',
            'orderByLeafNodeArgumentShouldBeNonEmptyArray',
            'badRequestInvalidPropertyNameSpecified',
            'anonymousFunctionParameterShouldStartWithDollarSymbol',
            'skipTokenParserSyntaxError',
            'skipTokenParserUnexpectedTypeOfOrderByInfoArg',
            'skipTokenParserSkipTokenNotMatchingOrdering',
            'skipTokenParserNullNotAllowedForKeys',
            'skipTokenParserInCompatibleTypeAtPosition',
            'skipTokenInfoBothOrderByPathAndOrderByValuesShouldBeSetOrNotSet',
            'internalSkipTokenInfoFailedToAccessOrInitializeProperty',
            'internalSkipTokenInfoBinarySearchRequireArray',
            'requestVersionTooLow',
            'requestVersionIsBiggerThanProtocolVersion',
            'requestDescriptionInvalidVersionHeader',
            'requestDescriptionUnSupportedVersion',
            'uriProcessorRequestUriDoesNotHaveTheRightBaseUri',
            'queryProcessorInvalidValueForFormat',
            'queryProcessorNoQueryOptionsApplicable',
            'queryProcessorQueryFilterOptionNotApplicable',
            'queryProcessorQuerySetOptionsNotApplicable',
            'queryProcessorSkipTokenNotAllowed',
            'queryProcessorQueryExpandOptionNotApplicable',
            'queryProcessorInlineCountWithValueCount',
            'queryProcessorInvalidInlineCountOptionError',
            'queryProcessorIncorrectArgumentFormat',
            'queryProcessorSkipTokenCannotBeAppliedForNonPagedResourceSet',
            'queryProcessorSelectOrExpandOptionNotApplicable',
            'configurationProjectionsNotAccepted',
            'providersWrapperNull',
            'invalidMetadataInstance',
            'invalidQueryInstance',
            'streamProviderWrapperGetStreamETagReturnedInvalidETagFormat',
            'streamProviderWrapperGetStreamContentTypeReturnsEmptyOrNull',
            'streamProviderWrapperInvalidStreamFromGetReadStream',
            'streamProviderWrapperGetReadStreamUriMustReturnAbsoluteUriOrNull',
            'streamProviderWrapperMustImplementIStreamProviderToSupportStreaming',
            'streamProviderWrapperMaxProtocolVersionMustBeV3OrAboveToSupportNamedStreams',
            'streamProviderWrapperMustImplementStreamProvider2ToSupportNamedStreams',
            'streamProviderWrapperMustNotSetContentTypeAndEtag',
            'streamProviderWrapperInvalidStreamInstance',
            'streamProviderWrapperInvalidStream2Instance',
            'badProviderInconsistentEntityOrComplexTypeUsage',
            'badQueryNullKeysAreNotSupported',
            'objectModelSerializerFailedToAccessProperty',
            'objectModelSerializerLoopsNotAllowedInComplexTypes',
            'unsupportedMediaType',
            'httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter',
            'httpProcessUtilityMediaTypeUnspecified',
            'httpProcessUtilityMediaTypeRequiresSlash',
            'httpProcessUtilityMediaTypeRequiresSubType',
            'httpProcessUtilityMediaTypeMissingValue',
            'httpProcessUtilityEscapeCharWithoutQuotes',
            'httpProcessUtilityEscapeCharAtEnd',
            'httpProcessUtilityClosingQuoteNotFound',
            'httpProcessUtilityMalformedHeaderValue',
            'noETagPropertiesForType',
            'eTagValueDoesNotMatch',
            'eTagCannotBeSpecified',
            'bothIfMatchAndIfNoneMatchHeaderSpecified',
            'eTagNotAllowedForNonExistingResource',
            'onlyReadSupport',
            'badRequestInvalidUriForThisVerb',
            'noDataForThisVerb',
            'badRequestInvalidUriForMediaResource',
            'hostNonODataOptionBeginsWithSystemCharacter',
            'hostODataQueryOptionFoundWithoutValue',
            'hostODataQueryOptionCannotBeSpecifiedMoreThanOnce',
            'hostMalFormedBaseUriInConfig',
            'hostRequestUriIsNotBasedOnRelativeUriInConfig',
        ];

        $this->assertEquals(sort($expected), sort($actual), 'You probably added a message without a corresponding test!');
        foreach ($actual as $funcName) {
            $param = [];
            $fct = new \ReflectionMethod('POData\Common\Messages', $funcName);
            if ($fct->getNumberOfRequiredParameters() == 0) {
                $r = $fct->invokeArgs(null, $param);
                $this->assertTrue(is_string($r));
                $this->assertNotEmpty($r);
                continue;
            }
            $p = $fct->getParameters();
            if (phpversion() < 7) {
                for ($i = 0; $i < $fct->getNumberOfRequiredParameters(); $i++) {
                    $param[] = 'the dingus TestString';
                }
                //Done this way due to php framework limitation
                try {
                    $r = $fct->invokeArgs(null, $param);
                    $this->assertTrue(is_string($r));
                    $this->assertNotEmpty($r);
                } catch (\Exception $e) {
                }
                continue;
            }
            for ($i = 0; $i < $fct->getNumberOfParameters(); $i++) {
                $param[] = 'the dingus TestString';
                if ($p[$i]->hasType()) {
                    continue 2;
                }
            }
            $r = $fct->invokeArgs(null, $param);
            $this->assertTrue(is_string($r));
            $this->assertNotEmpty($r);
        }
    }
}
