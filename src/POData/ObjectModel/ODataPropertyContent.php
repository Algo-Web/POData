<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataPropertyContent represents properties of a Complex type or entity element instance.
 */
class ODataPropertyContent implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * The collection of properties.
     *
     * @var ODataProperty[]
     */
    private $properties = [];

    /**
     * ODataPropertyContent constructor.
     * @param ODataProperty[] $properties
     */
    public function __construct(array $properties)
    {
        $this->setPropertys($properties);
    }

    /**
     * @return ODataProperty[]
     */
    public function getPropertys(): array
    {
        return $this->properties;
    }

    /**
     * @param $newProperties ODataProperty[]
     * @return ODataPropertyContent
     */
    public function setPropertys(array $newProperties): self
    {
        assert(array_reduce($newProperties, function($carry, $item) { return  $carry & $item instanceof ODataProperty; }, true));
        $this->properties = $newProperties;
        return $this;
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    public function offsetGet($offset): ODataProperty
    {
        return $this->properties[$offset];
    }

    public function offsetSet($offset, $value)
    {
        assert($value instanceof ODataProperty);
        null === $offset ? $this->properties[] = $value : $this->properties[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    public function current()
    {
        return current($this->properties);
    }

    public function next()
    {
        return next($this->properties);
    }

    public function key()
    {
        return key($this->properties);
    }

    public function valid()
    {
        return key($this->properties) !== null;
    }

    public function rewind()
    {
        return reset($this->properties);
    }

    /**
     * Count elements of an object.
     * @see https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *             </p>
     *             <p>
     *             The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->properties);
    }
}
