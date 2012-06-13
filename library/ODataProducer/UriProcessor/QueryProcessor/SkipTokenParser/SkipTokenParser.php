<?php
/** 
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
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
use ODataProducer\Providers\Metadata\Type\Null1;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
/**
 * $skiptoken option parser.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class SkipTokenParser
{
    /**
     * Parse the given skiptoken, validate it using the given InternalOrderByInfo 
     * and generates instance of InternalSkipTokenInfo.
     * 
     * @param ResourceType        &$resourceType        The resource type of the
     *                                                  resource targetted by the
     *                                                  resource path.
     * @param InternalOrderByInfo &$internalOrderByInfo The $orderby details.
     * @param string              $skipToken            The $skiptoken value.
     * 
     * @return InternalSkipTokenInfo
     * 
     * @throws ODataException
     */
    public static function parseSkipTokenClause(
        ResourceType &$resourceType, 
        InternalOrderByInfo &$internalOrderByInfo, 
        $skipToken
    ) {
        $tokenValueDescriptor = null;
        if (!KeyDescriptor::tryParseValuesFromSkipToken(
            $skipToken, 
            $tokenValueDescriptor
        )
        ) {
            ODataException::createSyntaxError(
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
                ODataException::createBadRequestError(
                    Messages::skipTokenParserSkipTokenNotMatchingOrdering(
                        $count, $skipToken, $orderByPathCount
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
                    ODataException::createSyntaxError(
                        Messages::skipTokenParserInCompatibleTypeAtPosition(
                            $skipToken, $expectedType->getFullTypeName(), $i,
                            $typeProvidedInSkipToken->getFullTypeName()
                        )
                    );
                }
            }

            $i++;
        }

        return  new InternalSkipTokenInfo(
            $internalOrderByInfo, 
            $positionalValues, 
            $resourceType
        );
    }
}
?>