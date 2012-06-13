<?php
/**
 * Type to represent leaf node of 'OrderBy Tree', a leaf node 
 * in OrderByTree represents last sub path segment of an orderby 
 * path segment.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
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
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_OrderByParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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