<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\Query\QueryResult;
use ReflectionException;

/**
 * Class InternalOrderByInfo.
 */
class InternalOrderByInfo
{
    /**
     * The structure holds information about the navigation properties used in the
     * orderby clause (if any), and orderby path if IDSQP implementor want to perform sorting.
     *
     * @var OrderByInfo
     */
    private $orderByInfo;

    /**
     * Collection of sub sorter functions corresponding to each orderby path segment.
     *
     * @var callable[]
     */
    private $subSorterFunctions;

    /**
     * The top level anonymous sorter function.
     *
     * @var callable
     */
    private $sorterFunction;

    /**
     * This object will be of type of the resource set identified by the request uri.
     *
     * @var mixed
     */
    private $dummyObject;

    /**
     * The ResourceType for the resource targeted by resource path.
     *
     * @var ResourceType
     */
    private $resourceType;

    /**
     * Creates new instance of InternalOrderByInfo.
     *
     * @param OrderByInfo  $orderByInfo        The structure holds information about the
     *                                         navigation properties used in the orderby clause
     *                                         (if any) and orderby path if IDSQP implementation wants to perform
     *                                         sorting
     * @param callable[]   $subSorterFunctions Collection of sub sorter functions corresponding to each orderby path
     *                                         segment
     * @param callable     $sorterFunction     The top level anonymous sorter function
     * @param mixed        $dummyObject        A dummy object of type of the resource set
     *                                         identified by the request uri
     * @param ResourceType $resourceType       The ResourceType for the resource targeted by resource path
     */
    public function __construct(
        OrderByInfo $orderByInfo,
        $subSorterFunctions,
        callable $sorterFunction,
        $dummyObject,
        ResourceType $resourceType
    ) {
        $this->orderByInfo        = $orderByInfo;
        $this->sorterFunction     = $sorterFunction;
        $this->subSorterFunctions = $subSorterFunctions;
        $this->dummyObject        = $dummyObject;
        $this->resourceType       = $resourceType;
    }

    /**
     * Get reference to order information to be passed to IDSQP implementation calls.
     *
     * @return OrderByInfo
     */
    public function getOrderByInfo(): OrderByInfo
    {
        return $this->orderByInfo;
    }

    /**
     * Gets reference to the top level sorter function.
     *
     * @return callable
     */
    public function getSorterFunction()
    {
        return $this->sorterFunction;
    }

    /**
     * Gets collection of sub sorter functions.
     *
     * @return callable[]
     */
    public function getSubSorterFunctions(): array
    {
        return $this->subSorterFunctions;
    }

    /**
     * Gets a dummy object of type of the resource set identified by the request uri.
     *
     * @return mixed
     */
    public function &getDummyObject()
    {
        return $this->dummyObject;
    }

    /**
     * Build value of $skiptoken from the given object which will be the
     * last object in the page.
     *
     * @param mixed $lastObject entity instance from which skiptoken needs to be built
     *
     * @throws ODataException If reflection exception occurs while accessing property
     * @return string
     */
    public function buildSkipTokenValue($lastObject): string
    {
        $nextPageLink = null;
        foreach ($this->getOrderByPathSegments() as $orderByPathSegment) {
            $index           = 0;
            $currentObject   = $lastObject;
            $subPathSegments = $orderByPathSegment->getSubPathSegments();
            $subPathCount    = count($subPathSegments);
            foreach ($subPathSegments as &$subPathSegment) {
                $isLastSegment = ($index == $subPathCount - 1);
                $segName       = $subPathSegment->getName();
                try {
                    if ($currentObject instanceof QueryResult) {
                        $currentObject = $currentObject->results;
                    }
                    $currentObject = $this->getResourceType()->getPropertyValue($currentObject, $segName);
                    if (null === $currentObject) {
                        $nextPageLink .= 'null, ';
                        break;
                    } elseif ($isLastSegment) {
                        $type = $subPathSegment->getInstanceType();
                        assert($type instanceof IType);
                        // If this is a string then do utf8_encode to convert
                        // utf8 decoded characters to
                        // corresponding utf8 char (e.g. � to í), then do a
                        // urlencode to convert í to %C3%AD
                        // urlencode is needed for datetime and guid too
                        // if ($type instanceof String || $type instanceof DateTime
                        //     || $type instanceof Guid) {
                        //    if ($type instanceof String) {
                        //        $currentObject = utf8_encode($currentObject);
                        //    }

                        //    $currentObject = urlencode($currentObject);
                        //}

                        // call IType::convertToOData to attach required suffix and prefix.
                        // e.g. $valueM, $valueF, datetime'$value', guid'$value',
                        // '$value' etc..
                        // Also we can think about moving above urlencode to this function
                        $value = $type->convertToOData(strval($currentObject));
                        $nextPageLink .= $value . ', ';
                    }
                } catch (ReflectionException $reflectionException) {
                    $msg = Messages::internalSkipTokenInfoFailedToAccessOrInitializeProperty($segName);
                    throw ODataException::createInternalServerError($msg);
                }

                ++$index;
            }
        }

        return rtrim(strval($nextPageLink), ', ');
    }

    /**
     * Get reference to the orderby path segment information.
     *
     * @return OrderByPathSegment[]
     */
    public function getOrderByPathSegments(): array
    {
        return $this->orderByInfo->getOrderByPathSegments();
    }

    /**
     * Get resource type this InternalOrderByInfo object points to.
     *
     * @return ResourceType
     */
    public function getResourceType(): ResourceType
    {
        return $this->resourceType;
    }
}
