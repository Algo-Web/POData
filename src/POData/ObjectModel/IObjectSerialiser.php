<?php

namespace POData\ObjectModel;

use POData\IService;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceType;
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
     * @param mixed $entryObject Reference to the entry object to be written
     *
     * @return ODataEntry
     */
    public function writeTopLevelElement($entryObject);

    /**
     * Write top level feed element.
     *
     * @param array &$entryObjects Array of entry resources to be written
     *
     * @return ODataFeed
     */
    public function writeTopLevelElements(&$entryObjects);

    /**
     * Write top level url element.
     *
     * @param mixed $entryObject The entry resource whose url to be written
     *
     * @return ODataURL
     */
    public function writeUrlElement($entryObject);

    /**
     * Write top level url collection.
     *
     * @param array $entryObjects Array of entry resources
     *                            whose url to be written
     *
     * @return ODataURLCollection
     */
    public function writeUrlElements($entryObjects);

    /**
     * Write top level complex resource.
     *
     * @param mixed        &$complexValue The complex object to be
     *                                    written
     * @param string       $propertyName  The name of the
     *                                    complex property
     * @param ResourceType &$resourceType Describes the type of
     *                                    complex object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelComplexObject(&$complexValue, $propertyName, ResourceType &$resourceType);

    /**
     * Write top level bag resource.
     *
     * @param mixed        &$BagValue     The bag object to be
     *                                    written
     * @param string       $propertyName  The name of the
     *                                    bag property
     * @param ResourceType &$resourceType Describes the type of
     *                                    bag object
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelBagObject(&$BagValue, $propertyName, ResourceType &$resourceType);

    /**
     * Write top level primitive value.
     *
     * @param mixed            &$primitiveValue   The primitve value to be
     *                                            written
     * @param ResourceProperty &$resourceProperty Resource property
     *                                            describing the
     *                                            primitive property
     *                                            to be written
     *
     * @return ODataPropertyContent
     */
    public function writeTopLevelPrimitive(&$primitiveValue, ResourceProperty &$resourceProperty);

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
     * Gets the segment stack instance.
     *
     * @return SegmentStack
     */
    public function getStack();
}
