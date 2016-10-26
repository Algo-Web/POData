<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Expression\PHPExpressionProvider;
use POData\Providers\Metadata\Type\Boolean;
use POData\Providers\Metadata\ResourceType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\FunctionDescription;
use POData\Providers\Expression\IExpressionProvider;

/**
 * Class ExpressionParser2.
 *
 * Build the basic expression tree for a given expression using base class
 * ExpressionParser, modify the expression tree to have null checks
 */
class ExpressionParser2 extends ExpressionParser
{
    /**
     * @var array
     */
    private $_mapTable;

    /**
     * Collection of navigation properties used in the expression.
     *
     * @var array(array(ResourceProperty))
     */
    private $_navigationPropertiesUsedInTheExpression;

    /**
     * Indicates whether the end user has implemented IExpressionProvider or not.
     *
     * @var bool
     */
    private $_isPHPExpressionProvider;

    /**
     * Create new instance of ExpressionParser2.
     *
     * @param string       $text                    The text expression to parse
     * @param ResourceType $resourceType            The resource type in which
     *                                              expression will be applied
     * @param bool         $isPHPExpressionProvider True if IExpressionProvider provider is
     *                                              implemented by user, False otherwise
     */
    public function __construct($text, ResourceType $resourceType, $isPHPExpressionProvider
    ) {
        parent::__construct($text, $resourceType, $isPHPExpressionProvider);
        $this->_navigationPropertiesUsedInTheExpression = array();
        $this->_isPHPExpressionProvider = $isPHPExpressionProvider;
    }

    /**
     * Parse and generate expression from the the given odata expression.
     *
     *
     * @param string              $text               The text expression to parse
     * @param ResourceType        $resourceType       The resource type in which
     * @param IExpressionProvider $expressionProvider Implementation of IExpressionProvider
     *
     * @return FilterInfo
     *
     * @throws ODataException If any error occurs while parsing the odata expression or building the php/custom expression
     */
    public static function parseExpression2($text, ResourceType $resourceType, \POData\Providers\Expression\IExpressionProvider $expressionProvider)
    {
        $expressionParser2 = new self($text, $resourceType, $expressionProvider instanceof PHPExpressionProvider);
        $expressionTree = $expressionParser2->parseFilter();

        $expressionAsString = null;

        $expressionProvider->setResourceType($resourceType);
        $expressionProcessor = new ExpressionProcessor($expressionProvider);

        try {
            $expressionAsString = $expressionProcessor->processExpression($expressionTree);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw ODataException::createInternalServerError($invalidArgumentException->getMessage());
        }

        return new FilterInfo(
            $expressionParser2->_navigationPropertiesUsedInTheExpression,
            $expressionAsString
        );
    }

    /**
     * Parse the expression.
     *
     * @see library/POData/QueryProcessor/ExpressionParser::parseFilter()
     *
     * @return AbstractExpression
     *
     * @throws ODataException
     */
    public function parseFilter()
    {
        $expression = parent::parseFilter();
        if (!$expression->typeIs(new Boolean())) {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2BooleanRequired()
            );
        }
        if ($this->_isPHPExpressionProvider) {
            $resultExpression = $this->_processNodeForNullability($expression, null);
            if ($resultExpression != null) {
                return $resultExpression;
            }
        }

        return $expression;
    }

    /**
     * Process the expression node for nullability.
     *
     * @param AbstractExpression $expression            The expression node to process
     * @param AbstractExpression $parentExpression      The parent expression of expression node to process
     * @param bool               $checkNullForMostChild whether to include null check for current property
     *
     * @return AbstractExpression New expression tree with nullability check
     *
     * @throws ODataException
     */
    private function _processNodeForNullability($expression, $parentExpression,
        $checkNullForMostChild = true
    ) {
        if ($expression instanceof ArithmeticExpression) {
            return $this->_processArithmeticNode($expression);
        } elseif ($expression instanceof ConstantExpression) {
            return null;
        } elseif ($expression instanceof FunctionCallExpression) {
            return $this->_processFunctionCallNode($expression, $parentExpression);
        } elseif ($expression instanceof LogicalExpression) {
            return $this->_processLogicalNode($expression, $parentExpression);
        } elseif ($expression instanceof PropertyAccessExpression) {
            return $this->_processPropertyAccessNode(
                $expression,
                $parentExpression,
                $checkNullForMostChild
            );
        } elseif ($expression instanceof RelationalExpression) {
            return $this->_processRelationalNode($expression, $parentExpression);
        } elseif ($expression instanceof UnaryExpression) {
            return $this->_processUnaryNode($expression, $parentExpression);
        }

        throw ODataException::createSyntaxError(
            Messages::expressionParser2UnexpectedExpression(get_class($expression))
        );
    }

    /**
     * Process an arithmetic expression node for nullability.
     *
     * @param ArithmeticExpression $expression The arithmetic expression node
     *                                         to process
     *
     * @return AbstractExpression|null
     */
    private function _processArithmeticNode(ArithmeticExpression $expression)
    {
        $leftNullableExpTree = $this->_processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->_processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        $resultExpression = null;
        if ($leftNullableExpTree != null && $rightNullableExpTree != null) {
            $resultExpression = $this->_mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = $leftNullableExpTree != null
                               ? $leftNullableExpTree : $rightNullableExpTree;
        }

        return $resultExpression;
    }

    /**
     * Process an arithmetic expression node for nullability.
     *
     * @param FunctionCallExpression $expression       The function call expression
     *                                                 node to process
     * @param AbstractExpression     $parentExpression The parent expression of
     *                                                 expression node to process
     *
     * @return null|AbstractExpression
     */
    private function _processFunctionCallNode(FunctionCallExpression $expression,
        $parentExpression
    ) {
        $paramExpressions = $expression->getParamExpressions();
        $checkNullForMostChild
            = strcmp(
                $expression->getFunctionDescription()->name,
                'is_null'
            ) === 0;
        $resultExpression = null;
        foreach ($paramExpressions as $paramExpression) {
            $resultExpression1 = $this->_processNodeForNullability(
                $paramExpression,
                $expression,
                !$checkNullForMostChild
            );
            if ($resultExpression1 != null && $resultExpression != null) {
                $resultExpression = $this->_mergeNullableExpressionTrees(
                    $resultExpression,
                    $resultExpression1
                );
            } elseif ($resultExpression1 != null && $resultExpression == null) {
                $resultExpression = $resultExpression1;
            }
        }

        if ($resultExpression == null) {
            return null;
        }

        if ($parentExpression == null) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL
            );
        }

        return $resultExpression;
    }

    /**
     * Process an logical expression node for nullability.
     *
     * @param LogicalExpression  $expression       The logical expression node
     *                                             to process
     * @param AbstractExpression $parentExpression The parent expression of
     *                                             expression node to process
     *
     * @return null|AbstractExpression
     */
    private function _processLogicalNode(
        LogicalExpression $expression, $parentExpression
    ) {
        $leftNullableExpTree = $this->_processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->_processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        if ($expression->getNodeType() == ExpressionType::OR_LOGICAL) {
            if ($leftNullableExpTree !== null) {
                $resultExpression = new LogicalExpression(
                    $leftNullableExpTree,
                    $expression->getLeft(),
                    ExpressionType::AND_LOGICAL
                );
                $expression->setLeft($resultExpression);
            }

            if ($rightNullableExpTree !== null) {
                $resultExpression = new LogicalExpression(
                    $rightNullableExpTree,
                    $expression->getRight(),
                    ExpressionType::AND_LOGICAL
                );
                $expression->setRight($resultExpression);
            }

            return null;
        }

        $resultExpression = null;
        if ($leftNullableExpTree != null && $rightNullableExpTree != null) {
            $resultExpression = $this->_mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = $leftNullableExpTree != null
                               ? $leftNullableExpTree : $rightNullableExpTree;
        }

        if ($resultExpression == null) {
            return null;
        }

        if ($parentExpression == null) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL
            );
        }

        return $resultExpression;
    }

    /**
     * Process an property access expression node for nullability.
     *
     * @param PropertyAccessExpression $expression            The property access
     *                                                        expression node to process
     * @param AbstractExpression       $parentExpression      The parent expression of
     *                                                        expression node to process
     * @param bool                     $checkNullForMostChild Wheter to check null for
     *                                                        most child node or not
     *
     * @return LogicalExpression|RelationalExpression|null
     */
    private function _processPropertyAccessNode(
        PropertyAccessExpression $expression,
        $parentExpression, $checkNullForMostChild
    ) {
        $navigationsUsed = $expression->getNavigationPropertiesInThePath();
        if (!empty($navigationsUsed)) {
            $this->_navigationPropertiesUsedInTheExpression[] = $navigationsUsed;
        }

        $nullableExpTree = $expression->createNullableExpressionTree($checkNullForMostChild);

        if ($parentExpression == null) {
            return new LogicalExpression(
                $nullableExpTree,
                $expression,
                ExpressionType::AND_LOGICAL
            );
        }

        return $nullableExpTree;
    }

    /**
     * Process a releational expression node for nullability.
     *
     * @param RelationalExpression $expression       The relational expression node
     *                                               to process
     * @param AbstractExpression   $parentExpression The parent expression of
     *                                               expression node to process
     *
     * @return null|AbstractExpression
     */
    private function _processRelationalNode(RelationalExpression $expression,
        $parentExpression
    ) {
        $leftNullableExpTree = $this->_processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->_processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        $resultExpression = null;
        if ($leftNullableExpTree != null && $rightNullableExpTree != null) {
            $resultExpression = $this->_mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = $leftNullableExpTree != null
                               ? $leftNullableExpTree : $rightNullableExpTree;
        }

        if ($resultExpression == null) {
            return null;
        }

        if ($parentExpression == null) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL
            );
        }

        return $resultExpression;
    }

    /**
     * Process an unary expression node for nullability.
     *
     * @param UnaryExpression    $expression       The unary expression node
     *                                             to process
     * @param AbstractExpression $parentExpression The parent expression of
     *                                             expression node to process
     *
     * @return AbstractExpression|null
     */
    private function _processUnaryNode(UnaryExpression $expression,
        $parentExpression
    ) {
        if ($expression->getNodeType() == ExpressionType::NEGATE) {
            return $this->_processNodeForNullability(
                $expression->getChild(),
                $expression
            );
        }

        if ($expression->getNodeType() == ExpressionType::NOT_LOGICAL) {
            $resultExpression = $this->_processNodeForNullability(
                $expression->getChild(),
                $expression
            );
            if ($resultExpression == null) {
                return null;
            }

            if ($parentExpression == null) {
                return new LogicalExpression(
                    $resultExpression,
                    $expression,
                    ExpressionType::AND_LOGICAL
                );
            }

            return $resultExpression;
        }

        throw ODataException::createSyntaxError(
            Messages::expressionParser2UnexpectedExpression(get_class($expression))
        );
    }

    /**
     * Merge two null check expression trees by removing duplicate nodes.
     *
     * @param AbstractExpression $nullCheckExpTree1 First expression
     * @param AbstractExpression $nullCheckExpTree2 Second expression
     *
     * @return AbstractExpression
     */
    private function _mergeNullableExpressionTrees($nullCheckExpTree1,
        $nullCheckExpTree2
    ) {
        $this->_mapTable = array();
        $this->_map($nullCheckExpTree1);
        $this->_map($nullCheckExpTree2);
        $expression = null;
        $isNullFunctionDescription = null;
        foreach ($this->_mapTable as $node) {
            if ($expression == null) {
                $expression = new UnaryExpression(
                    new FunctionCallExpression(
                        FunctionDescription::isNullCheckFunction($node->getType()),
                        array($node)
                    ),
                    ExpressionType::NOT_LOGICAL,
                    new Boolean()
                );
            } else {
                $expression = new LogicalExpression(
                    $expression,
                    new UnaryExpression(
                        new FunctionCallExpression(
                            FunctionDescription::isNullCheckFunction(
                                $node->getType()
                            ),
                            array($node)
                        ),
                        ExpressionType::NOT_LOGICAL,
                        new Boolean()
                    ),
                    ExpressionType::AND_LOGICAL
                );
            }
        }

        return $expression;
    }

    /**
     *  Populate map table.
     *
     * @param AbstractExpression $nullCheckExpTree The expression to verfiy
     *
     * @throws ODataException
     */
    private function _map($nullCheckExpTree)
    {
        if ($nullCheckExpTree instanceof LogicalExpression) {
            $this->_map($nullCheckExpTree->getLeft());
            $this->_map($nullCheckExpTree->getRight());
        } elseif ($nullCheckExpTree instanceof UnaryExpression) {
            $this->_map($nullCheckExpTree->getChild());
        } elseif ($nullCheckExpTree instanceof FunctionCallExpression) {
            $param = $nullCheckExpTree->getParamExpressions();
            $this->_map($param[0]);
        } elseif ($nullCheckExpTree instanceof PropertyAccessExpression) {
            $parent = $nullCheckExpTree;
            $key = null;
            do {
                $key = $parent->getResourceProperty()->getName() . '_' . $key;
                $parent = $parent->getParent();
            } while ($parent != null);

            $this->_mapTable[$key] = $nullCheckExpTree;
        } else {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2UnexpectedExpression(get_class($nullCheckExpTree))
            );
        }
    }
}
