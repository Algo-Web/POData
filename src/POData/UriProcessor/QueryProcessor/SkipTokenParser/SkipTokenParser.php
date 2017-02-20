<?php

namespace POData\UriProcessor\QueryProcessor\SkipTokenParser;

use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Null1;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

/**
 * Class SkipTokenParser.
 *
 * A parser to parse the skiptoken option
 *
 * The syntax of skiptoken clause is:
 *
 * skiptokenClause       : [literal [, literal]{orderByPathCount}]{orderByFlag} literal [, literal] {keyCount}
 * orderByFlag           : if orderby option is present, this this is 1 else 0
 * orderByPathCount      : if orderby option is present, then this is one less
 *                         than the orderby path count
 * keyCount              : One less than the number of keys defined for the type
 *                         of the resource set identified by the Resource Path
 *                         section of the URI
 */
class SkipTokenParser
{
    /**
     * Parse the given skiptoken, validate it using the given InternalOrderByInfo
     * and generates instance of InternalSkipTokenInfo.
     *
     * @param ResourceType        &$resourceType        The resource type of the
     *                                                  resource targeted by the
     *                                                  resource path
     * @param InternalOrderByInfo &$internalOrderByInfo The $orderby details
     * @param string              $skipToken            The $skiptoken value
     *
     * @throws ODataException
     *
     * @return InternalSkipTokenInfo
     */
    public static function parseSkipTokenClause(
        ResourceType & $resourceType,
        InternalOrderByInfo & $internalOrderByInfo,
        $skipToken
    ) {
        $tokenValueDescriptor = null;
        if (!KeyDescriptor::tryParseValuesFromSkipToken(
            $skipToken,
            $tokenValueDescriptor
        )
        ) {
            throw ODataException::createSyntaxError(
                Messages::skipTokenParserSyntaxError($skipToken)
            );
        }

        $orderByPathSegments = null;
        //$positionalValues are of type array(int, array(string, IType))
        $positionalValues = &$tokenValueDescriptor->getPositionalValuesByRef();
        $count = count($positionalValues);
        $orderByPathSegments = $internalOrderByInfo->getOrderByPathSegments();
        $orderByPathCount = count($orderByPathSegments);
        if ($count != ($orderByPathCount)) {
            throw ODataException::createBadRequestError(
                Messages::skipTokenParserSkipTokenNotMatchingOrdering(
                    $count,
                    $skipToken,
                    $orderByPathCount
                )
            );
        }

        $i = 0;
        foreach ($orderByPathSegments as $orderByPathSegment) {
            $typeProvidedInSkipToken = $positionalValues[$i][1];
            if (!($typeProvidedInSkipToken instanceof Null1)) {
                $orderBySubPathSegments = $orderByPathSegment->getSubPathSegments();
                $j = count($orderBySubPathSegments) - 1;
                $expectedType = $orderBySubPathSegments[$j]->getInstanceType();
                if (!$expectedType->isCompatibleWith($typeProvidedInSkipToken)) {
                    throw ODataException::createSyntaxError(
                        Messages::skipTokenParserInCompatibleTypeAtPosition(
                            $skipToken,
                            $expectedType->getFullTypeName(),
                            $i,
                            $typeProvidedInSkipToken->getFullTypeName()
                        )
                    );
                }
            }

            ++$i;
        }

        return  new InternalSkipTokenInfo(
            $internalOrderByInfo,
            $positionalValues,
            $resourceType
        );
    }
}
