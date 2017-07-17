<?php

namespace POData\UriProcessor\ResourcePathProcessor\SegmentParser;

use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\ResourceType;

/**
 * Class SegmentDescriptor.
 *
 * A type used to describe a segment (Uri is made up of bunch of segments,
 * each segment is separated by '/' character)
 */
class SegmentDescriptor
{
    /**
     * The identifier for this segment (string part without the keys,
     * if key exists).
     * e.g. http://localhost/service.svc/Customers('ALFKI')/$links/Orders
     *              Segment                 identifier
     *              ---------------------------------
     *              Customers('ALFKI')      Customers
     *              $links                  $links
     *              Orders                  Orders.
     *
     * @var string
     */
    private $identifier;

    /**
     * Describes the key for this segment.
     *
     * @var KeyDescriptor
     */
    private $keyDescriptor;

    /**
     * Whether the segment targets a single result or not.
     *
     * @var bool
     */
    private $singleResult;

    /**
     * Resource set wrapper if applicable.
     *
     * @var ResourceSetWrapper
     */
    private $targetResourceSetWrapper;

    /**
     * Reference to an instance of ResourceType describes type of resource
     * targeted by this segment.
     *
     * @var ResourceType
     */
    private $targetResourceType;

    /**
     * The kind of resource targeted by this segment.
     *
     * @var TargetKind
     */
    private $targetKind;

    /**
     * The kind of 'source of data' for this segment.
     *
     * @var TargetSource
     */
    private $targetSource;

    /**
     * The property that is being projected in this segment, if there's any.
     *
     * @var ResourceProperty
     */
    private $projectedProperty;

    /**
     * The data for this segment.
     *
     * @var mixed
     */
    private $result;

    /**
     * Reference to next descriptor.
     *
     * @var SegmentDescriptor
     */
    private $next;

    /**
     * Reference to previous descriptor.
     *
     * @var SegmentDescriptor
     */
    private $previous;

    public function __construct()
    {
        $this->singleResult = false;
        $this->targetKind = TargetKind::NOTHING();
        $this->targetSource = TargetSource::NONE;
    }

    /**
     * Creates a new instance of SegmentDescriptor from another SegmentDescriptor instance.
     *
     * @param SegmentDescriptor $anotherDescriptor The descriptor whose shallow copy to be created
     *
     * @return SegmentDescriptor
     */
    public static function createFrom(SegmentDescriptor $anotherDescriptor)
    {
        $descriptor = new self();
        $descriptor->identifier = $anotherDescriptor->identifier;
        $descriptor->keyDescriptor = $anotherDescriptor->keyDescriptor;
        $descriptor->projectedProperty = $anotherDescriptor->projectedProperty;
        $descriptor->singleResult = $anotherDescriptor->singleResult;
        $descriptor->targetKind = $anotherDescriptor->targetKind;
        $descriptor->targetResourceSetWrapper = $anotherDescriptor->targetResourceSetWrapper;
        $descriptor->targetResourceType = $anotherDescriptor->targetResourceType;
        $descriptor->targetSource = $anotherDescriptor->targetSource;

        return $descriptor;
    }

    /**
     * Gets the identifier for this segment.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * sets the identifier for this segment.
     *
     * @param string $identifier The identifier part of the segment
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Gets the description of the key, if any, associated with this segment.
     *
     * @return KeyDescriptor
     */
    public function getKeyDescriptor()
    {
        return $this->keyDescriptor;
    }

    /**
     * Sets the description of the key, if any, associated with this segment.
     *
     * @param KeyDescriptor|null    $keyDescriptor  The descriptor for the key associated with this segment
     */
    public function setKeyDescriptor(KeyDescriptor $keyDescriptor = null)
    {
        $this->keyDescriptor = $keyDescriptor;
    }

    /**
     * Gets the property that is being projected in this segment, if there's any.
     *
     * @return ResourceProperty
     */
    public function getProjectedProperty()
    {
        return $this->projectedProperty;
    }

    /**
     * Sets the property that is being projected in this segment, if there's any.
     *
     * @param ResourceProperty|null $projectedProperty  The property projected in this segment
     */
    public function setProjectedProperty(ResourceProperty $projectedProperty = null)
    {
        $this->projectedProperty = $projectedProperty;
    }

    /**
     * Whether this segment targets a single result or not.
     *
     * @return bool
     */
    public function isSingleResult()
    {
        return $this->singleResult;
    }

    /**
     * Sets whether this segment targets a single result or not.
     *
     * @param bool $isSingleResult Boolean represents whether this segment targets a single result or not
     */
    public function setSingleResult($isSingleResult)
    {
        $this->singleResult = $isSingleResult;
    }

    /**
     * Gets the kind of resource targeted by this segment.
     *
     * @return TargetKind
     */
    public function getTargetKind()
    {
        return $this->targetKind;
    }

    /**
     * Sets the kind of resource targeted by this segment.
     *
     * @param TargetKind $targetKind The kind of resource
     */
    public function setTargetKind(TargetKind $targetKind)
    {
        $this->targetKind = $targetKind;
    }

    /**
     * Gets the resource set wrapper (describes the resource set for this segment and its configuration) if applicable.
     *
     * @return ResourceSetWrapper
     */
    public function getTargetResourceSetWrapper()
    {
        return $this->targetResourceSetWrapper;
    }

    /**
     * Sets the resource set wrapper (describes the resource set for this segment
     * and its configuration) if applicable.
     *
     * @param ResourceSetWrapper $resourceSetWrapper The resource set wrapper
     */
    public function setTargetResourceSetWrapper($resourceSetWrapper)
    {
        $this->targetResourceSetWrapper = $resourceSetWrapper;
    }

    /**
     * Gets reference to an instance of ResourceType describes type of resource
     * targeted by this segment.
     *
     * @return ResourceType
     */
    public function getTargetResourceType()
    {
        return $this->targetResourceType;
    }

    /**
     * Sets reference to an instance of ResourceType describes type of resource
     * targeted by this segment.
     *
     * @param ResourceType $resourceType Type describing resource targeted by this segment
     */
    public function setTargetResourceType($resourceType)
    {
        $this->targetResourceType = $resourceType;
    }

    /**
     * Gets the kind of 'source of data' for this segment.
     *
     * @return TargetSource
     */
    public function getTargetSource()
    {
        return $this->targetSource;
    }

    /**
     * Sets the kind of 'source of data' for this segment.
     *
     * @param TargetSource $targetSource The kind of 'source of data'
     */
    public function setTargetSource($targetSource)
    {
        $this->targetSource = $targetSource;
    }

    /**
     * Gets the data targeted by this segment.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Sets the data targeted by this segment.
     *
     * @param mixed $result The data targeted by this segment
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * Gets reference to next descriptor.
     *
     * @return SegmentDescriptor|null Returns reference to next descriptor, NULL if this is the last descriptor
     */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Sets reference to next descriptor.
     *
     * @param SegmentDescriptor $next Reference to next descriptor
     */
    public function setNext(SegmentDescriptor $next)
    {
        $this->next = $next;
    }

    /**
     * @return SegmentDescriptor|null Returns reference to previous descriptor, NULL if this is the first descriptor
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * Sets reference to previous descriptor.
     *
     * @param SegmentDescriptor $previous Reference to previous descriptor
     */
    public function setPrevious(SegmentDescriptor $previous)
    {
        $this->previous = $previous;
    }

    /**
     * @return bool true if this segment has a key filter with values; false otherwise
     */
    public function hasKeyValues()
    {
        return null !== $this->keyDescriptor;
    }
}
