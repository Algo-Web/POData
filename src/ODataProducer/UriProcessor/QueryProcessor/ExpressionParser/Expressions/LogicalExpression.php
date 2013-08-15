<?php

namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions;

use ODataProducer\Providers\Metadata\Type\Boolean;

/**
 * Class LogicalExpression
 * @package ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions
 */
class LogicalExpression extends BinaryExpression
{
    /**
     * Creates new instance of LogicalExpression
     * 
     * @param AbstractExpression $left     left expression
     * @param AbstractExpression $right    right expression
     * @param ExpressionType     $nodeType expression node type 
     */
    public function __construct($left, $right, $nodeType)
    {
        $this->nodeType = $nodeType;
        $this->type = new Boolean();
        parent::__construct($left, $right);
    }
}