<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\SkipTokenParser;

use InvalidArgumentException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Metadata\Type\Null1;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;

/**
 * Class InternalSkipTokenInfo.
 *
 * Type which holds information about processed skiptoken value, this type
 * also provide method to search the given result set for the skiptoken
 * and to build skiptoken from an entry object.
 */
class InternalSkipTokenInfo
{
    /**
     * Reference to an instance of InternalOrderByInfo which holds
     * sorter function(s) generated from orderby clause.
     *
     * @var InternalOrderByInfo
     */
    private $internalOrderByInfo;

    /**
     * Holds collection of values in the skiptoken corresponds to the orderby
     * path segments.
     *
     * @var array<array<IType>>
     */
    private $orderByValuesInSkipToken;

    /**
     * Holds reference to the type of the resource pointed by the request uri.
     *
     * @var ResourceType
     */
    private $resourceType;

    /**
     * Reference to the object holding parsed skiptoken value, this information
     * can be used by the IDSQP implementor for custom paging.
     *
     * @var SkipTokenInfo
     */
    private $skipTokenInfo;

    /**
     * Object which is used as a key for searching the sorted result, this object
     * will be an instance of type described by the resource type pointed by the
     * request uri.
     *
     * @var mixed
     */
    private $keyObject;

    /**
     * Creates a new instance of InternalSkipTokenInfo.
     *
     * @param InternalOrderByInfo &$internalOrderByInfo     Reference to an instance of InternalOrderByInfo which holds
     *                                                      sorter function(s) generated from orderby clause
     * @param array<array<IType>> $orderByValuesInSkipToken Collection of values in the skiptoken corresponds to the
     *                                                      orderby path segments
     * @param ResourceType        &$resourceType            Reference to the resource type pointed to by the request uri
     */
    public function __construct(
        InternalOrderByInfo &$internalOrderByInfo,
        $orderByValuesInSkipToken,
        ResourceType &$resourceType
    ) {
        $this->internalOrderByInfo      = $internalOrderByInfo;
        $this->orderByValuesInSkipToken = $orderByValuesInSkipToken;
        $this->resourceType             = $resourceType;
        $this->skipTokenInfo            = null;
        $this->keyObject                = null;
    }

    /**
     * Gets reference to the SkipTokenInfo object holding result of
     * skiptoken parsing, which used by the IDSQP implementor for
     * custom paging.
     *
     * @return SkipTokenInfo
     */
    public function getSkipTokenInfo()
    {
        if (null === $this->skipTokenInfo) {
            $orderbyInfo         = $this->getInternalOrderByInfo()->getOrderByInfo();
            $this->skipTokenInfo = new SkipTokenInfo(
                $orderbyInfo,
                $this->orderByValuesInSkipToken
            );
        }

        return $this->skipTokenInfo;
    }

    /**
     * Get reference to the InternalOrderByInfo object holding orderBy details.
     *
     * @return InternalOrderByInfo
     */
    public function getInternalOrderByInfo()
    {
        return $this->internalOrderByInfo;
    }

    /**
     * Search the sorted array of result set for key object created from the
     * skip token key values and returns index of first entry in the next page.
     *
     * @param array &$searchArray The sorted array to search
     *
     * @throws InvalidArgumentException
     * @throws ODataException
     *
     * @return int (1) If the array is empty then return -1,
     *             (2) If the key object found then return index of first record
     *             in the next page,
     *             (3) If partial matching found (means found matching for first
     *             m keys where m < n, where n is total number of positional
     *             keys, then return the index of the object which has most matching
     */
    public function getIndexOfFirstEntryInTheNextPage(array &$searchArray): int
    {
        if (empty($searchArray)) {
            return -1;
        }

        $comparer = $this->getInternalOrderByInfo()->getSorterFunction();
        //Gets the key object initialized from skiptoken
        $keyObject       = $this->getKeyObject();
        $low             = 0;
        $searchArraySize = count($searchArray) - 1;
        $high            = $searchArraySize;
        do {
            $mid    = intval($low + round(($high - $low)/2));
            $result = $comparer($keyObject, $searchArray[$mid]);
            if ($result > 0) {
                $low = $mid + 1;
            } elseif ($result < 0) {
                $high = $mid - 1;
            } else {
                //Now we found record the matches with skiptoken value, so first record of next page will at $mid + 1
                if ($mid == $searchArraySize) {
                    //Check skiptoken points to last record, in this case no more records available for next page
                    return -1;
                }

                return $mid + 1;
            }
        } while ($low <= $high);

        if ($mid >= $searchArraySize) {
            //If key object does not match with last object, then no more page
            return -1;
        } elseif ($mid <= 0) {
            //If key object is less than first object, then paged result start from 0
            return 0;
        }

        //return index of the most matching object
        return $mid;
    }

    /**
     * Gets the key object for searching, if the object is not initialized,
     * then do it from skiptoken positional values.
     *
     * @throws ODataException If reflection exception occurs while accessing or setting property
     *
     * @return mixed
     */
    public function getKeyObject()
    {
        if (null === $this->keyObject) {
            $this->keyObject = $this->getInternalOrderByInfo()->getDummyObject();
            $i               = 0;
            foreach ($this->getInternalOrderByInfo()->getOrderByPathSegments() as $orderByPathSegment) {
                $index           = 0;
                $currentObject   = $this->keyObject;
                $subPathSegments = $orderByPathSegment->getSubPathSegments();
                $subPathCount    = count($subPathSegments);
                foreach ($subPathSegments as &$subPathSegment) {
                    $isLastSegment = ($index == $subPathCount - 1);
                    try {
                        // if currentObject = null means, previous iteration did a
                        // ReflectionProperty::getValue where ReflectionProperty
                        // represents a complex/navigation, but its null, which means
                        // the property is not set in the dummy object by OrderByParser,
                        // an unexpected state.
                        $subSegName = $subPathSegment->getName();
                        if (!$isLastSegment) {
                            $currentObject = $this->resourceType->getPropertyValue($currentObject, $subSegName);
                        } else {
                            if ($this->orderByValuesInSkipToken[$i][1] instanceof Null1) {
                                $this->resourceType->setPropertyValue($currentObject, $subPathSegment->getName(), null);
                            } else {
                                // The Lexer's Token::Text value will be always
                                // string, convert the string to
                                // required type i.e. int, float, double etc..
                                $value
                                    = $this->orderByValuesInSkipToken[$i][1]->convert(
                                        $this->orderByValuesInSkipToken[$i][0]
                                    );
                                $this->resourceType->setPropertyValue($currentObject, $subSegName, $value);
                            }
                        }
                    } catch (\ReflectionException $reflectionException) {
                        throw ODataException::createInternalServerError(
                            Messages::internalSkipTokenInfoFailedToAccessOrInitializeProperty(
                                $subPathSegment->getName()
                            )
                        );
                    }

                    ++$index;
                }

                ++$i;
            }
        }

        return $this->keyObject;
    }

    /**
     * Build next-page link from the given object which will be the last object
     * in the page.
     *
     * @param mixed $lastObject Entity instance to build next page link from
     *
     * @throws ODataException If reflection exception occurs while accessing
     *                        property
     *
     * @return string
     */
    public function buildNextPageLink($lastObject)
    {
        $nextPageLink = null;
        foreach ($this->getInternalOrderByInfo()->getOrderByPathSegments() as $orderByPathSegment) {
            $index           = 0;
            $currentObject   = $lastObject;
            $subPathSegments = $orderByPathSegment->getSubPathSegments();
            $subPathCount    = count($subPathSegments);
            foreach ($subPathSegments as &$subPathSegment) {
                $isLastSegment = ($index == $subPathCount - 1);
                try {
                    $currentObject = $this->resourceType->getPropertyValue($currentObject, $subPathSegment->getName());
                    if (null === $currentObject) {
                        $nextPageLink .= 'null, ';
                        break;
                    } elseif ($isLastSegment) {
                        $type = $subPathSegment->getInstanceType();
                        assert($type instanceof IType, get_class($type));
                        $value = $type->convertToOData($currentObject);
                        $nextPageLink .= $value . ', ';
                    }
                } catch (\ReflectionException $reflectionException) {
                    throw ODataException::createInternalServerError(
                        Messages::internalSkipTokenInfoFailedToAccessOrInitializeProperty(
                            $subPathSegment->getName()
                        )
                    );
                }

                ++$index;
            }
        }

        return rtrim($nextPageLink, ', ');
    }
}
