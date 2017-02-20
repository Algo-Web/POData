<?php

namespace POData\UriProcessor\QueryProcessor\OrderByParser;

use POData\Common\Messages;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\Metadata\Type\Guid;
use POData\Providers\Metadata\Type\StringType;

/**
 * Class OrderByLeafNode.
 *
 * Type to represent leaf node of 'OrderBy Tree', a leaf node
 * in OrderByTree represents last sub path segment of an orderby
 * path segment.
 */
class OrderByLeafNode extends OrderByBaseNode
{
    /**
     * The order of sorting to be performed using this property.
     *
     * @var bool
     */
    private $_isAscending;

    private $_anonymousFunction;

    /**
     * Constructs new instance of OrderByLeafNode.
     *
     * @param string           $propertyName     Name of the property
     *                                           corrosponds to the
     *                                           sub path segment represented
     *                                           by this node
     * @param ResourceProperty $resourceProperty Resource property corrosponds
     *                                           to the sub path
     *                                           segment represented by this node
     * @param bool             $isAscending      The order of sorting to be
     *                                           performed, true for
     *                                           ascending order and false
     *                                           for descending order
     */
    public function __construct(
        $propertyName,
        ResourceProperty $resourceProperty,
        $isAscending
    ) {
        parent::__construct($propertyName, $resourceProperty);
        $this->_isAscending = $isAscending;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessorOrderByParser.OrderByBaseNode::free()
     */
    public function free()
    {
        // By the time we call this function, the top level sorter function
        // will be already generated so we can free
        unset($this->_anonymousFunction);
        $this->_anonymousFunction = null;
    }

    /**
     * (non-PHPdoc).
     *
     * @see library/POData/QueryProcessorOrderByParser.OrderByBaseNode::getResourceType()
     *
     * @return \POData\Providers\Metadata\ResourceType
     */
    public function getResourceType()
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * To check the order of sorting to be performed.
     *
     * @return bool
     */
    public function isAscending()
    {
        return $this->_isAscending;
    }

    /**
     * Build comparison function for this leaf node.
     *
     * @param string[] $ancestors Array of parent properties e.g. array('Orders', 'Customer', 'Customer_Demographics')
     *
     * @return \Closure
     */
    public function buildComparisonFunction($ancestors)
    {
        if (count($ancestors) == 0) {
            throw new \InvalidArgumentException(
                Messages::orderByLeafNodeArgumentShouldBeNonEmptyArray()
            );
        }

        $a = $this->_isAscending ? 1 : -1;

        $retVal = function ($object1, $object2) use ($ancestors, $a) {
            $accessor1 = $object1;
            $accessor2 = $object2;
            $flag1 = is_null($accessor1);
            $flag2 = is_null($accessor2);
            foreach ($ancestors as $i => $ancestor) {
                if ($i == 0) {
                    continue;
                }
                $accessor1 = $accessor1->$ancestor;
                $accessor2 = $accessor2->$ancestor;
                $flag1 |= is_null($accessor1);
                $flag2 |= is_null($accessor2);
            }
            $propertyName = $this->propertyName;
            $getter = 'get' . ucfirst($propertyName);
            if (!is_null($accessor1)) {
                $accessor1 = method_exists($accessor1, $getter) ? $accessor1->$getter() : $accessor1->$propertyName;
            }
            if (!is_null($accessor2)) {
                $accessor2 = method_exists($accessor2, $getter) ? $accessor2->$getter() : $accessor2->$propertyName;
            }

            $flag1 |= is_null($accessor1);
            $flag2 |= is_null($accessor2);

            if ($flag1 && $flag2) {
                return 0;
            } elseif ($flag1) {
                return $a*-1;
            } elseif ($flag2) {
                return $a*1;
            }
            $type = $this->resourceProperty->getInstanceType();
            if ($type instanceof DateTime) {
                $result = strtotime($accessor1) - strtotime($accessor2);
            } elseif ($type instanceof StringType) {
                $result = strcmp($accessor1, $accessor2);
            } elseif ($type instanceof Guid) {
                $result = strcmp($accessor1, $accessor2);
            } else {
                $delta = $accessor1 - $accessor2;
                $result = (0 == $delta) ? 0 : $delta/abs($delta);
            }

            return $a*$result;
        };

        return $retVal;
    }
}
