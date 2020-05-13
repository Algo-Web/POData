<?php

declare(strict_types=1);

namespace POData\Providers\Metadata;

/**
 * Class ResourceStreamInfo Contains information about a named stream on an entity type.
 */
class ResourceStreamInfo
{
    /**
     * Name of the stream.
     */
    private $name;

    /**
     * Custom state object associated with named stream.
     */
    private $customState;

    /**
     * Constructs a new instance of ResourceStreamInfo.
     *
     * @param string $name Name of the stream
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Gets name of the stream.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets custom state.
     *
     * @return object
     */
    public function getCustomState()
    {
        return $this->customState;
    }

    /**
     * Sets custom state.
     *
     * @param object $stateObject The custom object
     */
    public function setCustomState($stateObject)
    {
        $this->customState = $stateObject;
    }
}
