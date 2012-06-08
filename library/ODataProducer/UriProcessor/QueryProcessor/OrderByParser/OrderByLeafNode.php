<?php
/**
 * Type to represent leaf node of 'OrderBy Tree', a leaf node 
 * in OrderByTree represents last sub path segment of an orderby 
 * path segment.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\OrderByParser;
use ODataProducer\UriProcessor\QueryProcessor\AnonymousFunction;
use ODataProducer\Providers\Metadata\Type\Guid;
use ODataProducer\Providers\Metadata\Type\String;
use ODataProducer\Providers\Metadata\Type\DateTime;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Common\Messages;
/**
 * Type to represent leaf node of 'OrderBy Tree'.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class OrderByLeafNode extends OrderByBaseNode
{
    /**
     * The order of sorting to be performed using this property
     * 
     * @var boolean
     */
    private $_isAscending;

    private $_anonymousFunction;

    /**
     * Constructs new instance of OrderByLeafNode
     * 
     * @param string           $propertyName     Name of the property
     *                                           corrosponds to the 
     *                                           sub path segment represented
     *                                           by this node.
     * @param ResourceProperty $resourceProperty Resource property corrosponds
     *                                           to the sub path 
     *                                           segment represented by this node.
     * @param boolean          $isAscending      The order of sorting to be
     *                                           performed, true for
     *                                           ascending order and false
     *                                           for descending order.
     */
    public function __construct($propertyName, 
        ResourceProperty $resourceProperty, $isAscending
    ) {
        parent::__construct($propertyName, $resourceProperty);
        $this->_isAscending = $isAscending;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/OrderByParser/ODataProducer\QueryProcessor\OrderByParser.OrderByBaseNode::free()
     * 
     * @return void
     */
    public function free()
    {
        // By the time we call this function, the top level sorter function 
        // will be already generated so we can free
        unset($this->_anonymousFunction);
        $this->_anonymousFunction = null;
    }

    /**
     * (non-PHPdoc)
     * 
     * @see library/ODataProducer/QueryProcessor/OrderByParser/ODataProducer\QueryProcessor\OrderByParser.OrderByBaseNode::getResourceType()
     * 
     * @return void
     */
    public function getResourceType()
    {
        return $this->resourceProperty->getResourceType();
    }

    /**
     * To check the order of sorting to be performed.
     * 
     * @return boolean
     */
    public function isAscending()
    {
        return $this->_isAscending;
    }

    /**
     * Build comparison function for this leaf node. 
     *
     * @param array(string) $ancestors Array of parent properties e.g. 
     *                                 array('Orders', 'Customer', 
     *                                'Customer_Demographics')
     *
     * @return AnonymousFunction
     */
    public function buildComparisonFunction($ancestors)
    {
        if (count($ancestors) == 0) {
            throw new \InvalidArgumentException(
                Messages::orderByLeafNodeArgumentShouldBeNonEmptyArray()
            );
        }

        $parameterNames = null;
        $accessor1 = null;
        $accessor2 = null;
        $a = $this->_isAscending ? 1 : -1;
        
        foreach ($ancestors as $i => $anscestor) {
            if ($i == 0) {
                $parameterNames = array (
                    '$' . $anscestor . 'A', '$' . $anscestor . 'B'
                );
                $accessor1 = $parameterNames[0];
                $accessor2 = $parameterNames[1];
                $flag1 = '$flag1 = ' . 'is_null(' . $accessor1. ') || ';
                $flag2 = '$flag2 = ' . 'is_null(' . $accessor2. ') || '; 
            } else {
                $accessor1 .= '->' . $anscestor;
                $accessor2 .= '->' . $anscestor;
                $flag1 .= 'is_null(' .$accessor1 . ')' . ' || ';
                $flag2 .= 'is_null(' .$accessor2 . ')' . ' || ';
            }
        }

        $accessor1 .= '->' . $this->propertyName;
        $accessor2 .= '->' . $this->propertyName;
        $flag1 .= 'is_null(' . $accessor1 . ')';
        $flag2 .= 'is_null(' . $accessor2 . ')';

        $code = "$flag1; 
             $flag2; 
             if(\$flag1 && \$flag2) { 
               return 0;
             } else if (\$flag1) { 
                 return $a*-1;
             } else if (\$flag2) { 
                 return $a*1;
             }
             
            ";
        $type = $this->resourceProperty->getInstanceType();
        if ($type instanceof DateTime) {
            $code .= " \$result = strtotime($accessor1) - strtotime($accessor2);";
        } else if ($type instanceof String) {
            $code .= " \$result = strcmp($accessor1, $accessor2);";
        } else if ($type instanceof Guid) {
            $code .= " \$result = strcmp($accessor1, $accessor2);";
        } else {
            $code .= " \$result = (($accessor1 == $accessor2) ? 0 : (($accessor1 > $accessor2) ? 1 : -1));";
        }

        $code .= "
             return $a*\$result;";
        $this->_anonymousFunction = new AnonymousFunction($parameterNames, $code);
        return $this->_anonymousFunction;
    }
}
?>