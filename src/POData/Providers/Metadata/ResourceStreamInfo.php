<?php

namespace POData\Providers\Metadata;

/**
 * Class ResourceStreamInfo Contains information about a named stream on an entity type.
 */
class ResourceStreamInfo
{
    /**
     * Name of the stream.
     */
    private $_name;

    /**
     * Custom state object associated with named stream.
     */
    private $_customState;

    /**
     * Constructs a new instance of ResourceStreamInfo.
     *
     * @param string $name Name of the stream
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Gets name of the stream.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Gets custom state.
     *
     * @return object
     */
    public function getCustomState()
    {
        return $this->_customState;
    }

    /**
     * Sets custom state.
     *
     * @param object $stateObject The custom object
     */
    public function setCustomState($stateObject)
    {
        $this->_customState = $stateObject;
    }
}
