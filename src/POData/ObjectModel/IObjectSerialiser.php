<?php

namespace POData\ObjectModel;

use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Query\QueryResult;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\SegmentStack;

/**
 * Class ObjectModelSerializer.
 */
interface IObjectSerialiser
{
    /**
     * Write a top level entry resource.
     *
     * @param QueryResult $entryObject      Results property contains reference to the entry object to be written
     *
     * @return ODataEntry
     */
    public function writeTopLevelElement(QueryResult $entryObject);

    /**
     * Write top level feed element.
     *
     * @param QueryResult &$entryObjects    Results property contains array of entry resources to be written
     *
     * @return ODataFeed
     */
    public function writeTopLevelElements(QueryResult &$entryObjects);

    /**
     * Write top level url element.
     *
     * @param QueryResult $entryObject      Results property contains the entry resource whose url to be written
     *
     * @return ODataURL
     */
    public function writeUrlElement(QueryResult $entryObject);

    /**
     * Write top level url collection.
     *
     * @param QueryResult $entryObjects     Results property contains the array of entry resources whose urls are
     *                                      to be written
     *
     * @return ODataURLCollection
     */
    public function writeUrlElements(QueryResult $entryObjects);

    /**
     * Write top level complex resource.
     *
     * @param QueryResult  &$complexValue Results property contains the complex object to be written
     * @param string       $propertyName  The name of the complex property
     * @param ResourceType &$resourceType Describes the type of complex object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(QueryResult &$complexValue, $propertyName, ResourceType &$resourceType);

    /**
     * Write top level bag resource.
     *
     * @param QueryResult  &$BagValue     Results property contains the bag object to be written
     * @param string       $propertyName  The name of the bag property
     * @param ResourceType &$resourceType Describes the type of bag object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(QueryResult &$BagValue, $propertyName, ResourceType &$resourceType);

    /**
     * Write top level primitive value.
     *
     * @param QueryResult      &$primitiveValue   Results property contains the primitive value to be written
     * @param ResourceProperty &$resourceProperty Resource property describing the primitive property to be written
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(QueryResult &$primitiveValue, ResourceProperty &$resourceProperty = null);

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest();

    /**
     * Sets reference to the request submitted by client.
     *
     * @param RequestDescription $request
     */
    public function setRequest(RequestDescription $request);

    /**
     * Gets the data service instance.
     *
     * @return IService
     */
    public function getService();

    /**
     * Sets the data service instance.
     *
     * @return IService
     */
    public function setService(IService $service);

    /**
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    public function getStack();
}
