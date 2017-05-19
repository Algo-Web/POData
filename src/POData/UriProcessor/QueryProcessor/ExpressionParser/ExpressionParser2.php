<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Expression\PHPExpressionProvider;
use POData\Providers\Metadata\ResourceType;
use POData\Providers\Metadata\Type\Boolean;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use POData\UriProcessor\QueryProcessor\FunctionDescription;

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
    private $mapTable;

    /**
     * Collection of navigation properties used in the expression.
     *
     * @var array(array(ResourceProperty))
     */
    private $navigationPropertiesUsedInTheExpression;

    /**
     * Indicates whether the end user has implemented IExpressionProvider or not.
     *
     * @var bool
     */
    private $isPHPExpressionProvider;

    /**
     * Create new instance of ExpressionParser2.
     *
     * @param string       $text                    The text expression to parse
     * @param ResourceType $resourceType            The resource type in which
     *                                              expression will be applied
     * @param bool         $isPHPExpressionProvider True if IExpressionProvider provider is
     *                                              implemented by user, False otherwise
     */
    public function __construct($text, ResourceType $resourceType, $isPHPExpressionProvider)
    {
        parent::__construct($text, $resourceType, $isPHPExpressionProvider);
        $this->navigationPropertiesUsedInTheExpression = [];
        $this->isPHPExpressionProvider = $isPHPExpressionProvider;
    }

    /**
     * Parse and generate expression from the the given odata expression.
     *
     *
     * @param string              $text               The text expression to parse
     * @param ResourceType        $resourceType       The resource type in which
     * @param IExpressionProvider $expressionProvider Implementation of IExpressionProvider
     *
     * @throws ODataException If any error occurs while parsing the odata expression or
     * building the php/custom expression
     *
     * @return FilterInfo
     */
    public static function parseExpression2($text, ResourceType $resourceType, IExpressionProvider $expressionProvider)
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
        $expressionAsString = (null !== $expressionAsString) ? $expressionAsString : "";
        return new FilterInfo(
            $expressionParser2->navigationPropertiesUsedInTheExpression,
            $expressionAsString
        );
    }

    /**
     * Parse the expression.
     *
     * @see library/POData/QueryProcessor/ExpressionParser::parseFilter()
     *
     * @throws ODataException
     *
     * @return AbstractExpression
     */
    public function parseFilter()
    {
        $expression = parent::parseFilter();
        if (!$expression->typeIs(new Boolean())) {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2BooleanRequired()
            );
        }
        if ($this->isPHPExpressionProvider) {
            $resultExpression = $this->processNodeForNullability($expression, null);
            if (null != $resultExpression) {
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
     * @throws ODataException
     *
     * @return AbstractExpression New expression tree with nullability check
     */
    private function processNodeForNullability(
        $expression,
        $parentExpression,
        $checkNullForMostChild = true
    ) {
        if ($expression instanceof ArithmeticExpression) {
            return $this->processArithmeticNode($expression);
        } elseif ($expression instanceof ConstantExpression) {
            return;
        } elseif ($expression instanceof FunctionCallExpression) {
            return $this->processFunctionCallNode($expression, $parentExpression);
        } elseif ($expression instanceof LogicalExpression) {
            return $this->processLogicalNode($expression, $parentExpression);
        } elseif ($expression instanceof PropertyAccessExpression) {
            return $this->processPropertyAccessNode(
                $expression,
                $parentExpression,
                $checkNullForMostChild
            );
        } elseif ($expression instanceof RelationalExpression) {
            return $this->processRelationalNode($expression, $parentExpression);
        } elseif ($expression instanceof UnaryExpression) {
            return $this->processUnaryNode($expression, $parentExpression);
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
    private function processArithmeticNode(ArithmeticExpression $expression)
    {
        $leftNullableExpTree = $this->processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        $resultExpression = null;
        if ($leftNullableExpTree != null && $rightNullableExpTree != null) {
            $resultExpression = $this->mergeNullableExpressionTrees(
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
    private function processFunctionCallNode(
        FunctionCallExpression $expression,
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
            $resultExpression1 = $this->processNodeForNullability(
                $paramExpression,
                $expression,
                !$checkNullForMostChild
            );
            if (null != $resultExpression1 && null != $resultExpression) {
                $resultExpression = $this->mergeNullableExpressionTrees(
                    $resultExpression,
                    $resultExpression1
                );
            } elseif (null != $resultExpression1 && null == $resultExpression) {
                $resultExpression = $resultExpression1;
            }
        }

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
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
    private function processLogicalNode(
        LogicalExpression $expression,
        $parentExpression
    ) {
        $leftNullableExpTree = $this->processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        if ($expression->getNodeType() == ExpressionType::OR_LOGICAL) {
            if (null !== $leftNullableExpTree) {
                $resultExpression = new LogicalExpression(
                    $leftNullableExpTree,
                    $expression->getLeft(),
                    ExpressionType::AND_LOGICAL
                );
                $expression->setLeft($resultExpression);
            }

            if (null !== $rightNullableExpTree) {
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
        if (null != $leftNullableExpTree && null != $rightNullableExpTree) {
            $resultExpression = $this->mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = null != $leftNullableExpTree
                               ? $leftNullableExpTree : $rightNullableExpTree;
        }

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
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
    private function processPropertyAccessNode(
        PropertyAccessExpression $expression,
        $parentExpression,
        $checkNullForMostChild
    ) {
        $navigationsUsed = $expression->getNavigationPropertiesInThePath();
        if (!empty($navigationsUsed)) {
            $this->navigationPropertiesUsedInTheExpression[] = $navigationsUsed;
        }

        $nullableExpTree = $expression->createNullableExpressionTree($checkNullForMostChild);

        if (null == $parentExpression) {
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
    private function processRelationalNode(
        RelationalExpression $expression,
        $parentExpression
    ) {
        $leftNullableExpTree = $this->processNodeForNullability(
            $expression->getLeft(),
            $expression
        );
        $rightNullableExpTree = $this->processNodeForNullability(
            $expression->getRight(),
            $expression
        );
        $resultExpression = null;
        if (null != $leftNullableExpTree && null != $rightNullableExpTree) {
            $resultExpression = $this->mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = $leftNullableExpTree != null
                               ? $leftNullableExpTree : $rightNullableExpTree;
        }

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
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
    private function processUnaryNode(
        UnaryExpression $expression,
        $parentExpression
    ) {
        if (ExpressionType::NEGATE == $expression->getNodeType()) {
            return $this->processNodeForNullability(
                $expression->getChild(),
                $expression
            );
        }

        if (ExpressionType::NOT_LOGICAL == $expression->getNodeType()) {
            $resultExpression = $this->processNodeForNullability(
                $expression->getChild(),
                $expression
            );
            if (null == $resultExpression) {
                return null;
            }

            if (null == $parentExpression) {
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
    private function mergeNullableExpressionTrees(
        $nullCheckExpTree1,
        $nullCheckExpTree2
    ) {
        $this->mapTable = [];
        $this->map($nullCheckExpTree1);
        $this->map($nullCheckExpTree2);
        $expression = null;
        $isNullFunctionDescription = null;
        foreach ($this->mapTable as $node) {
            if (null == $expression) {
                $expression = new UnaryExpression(
                    new FunctionCallExpression(
                        FunctionDescription::isNullCheckFunction($node->getType()),
                        [$node]
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
                            [$node]
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
    private function map($nullCheckExpTree)
    {
        if ($nullCheckExpTree instanceof LogicalExpression) {
            $this->map($nullCheckExpTree->getLeft());
            $this->map($nullCheckExpTree->getRight());
        } elseif ($nullCheckExpTree instanceof UnaryExpression) {
            $this->map($nullCheckExpTree->getChild());
        } elseif ($nullCheckExpTree instanceof FunctionCallExpression) {
            $param = $nullCheckExpTree->getParamExpressions();
            $this->map($param[0]);
        } elseif ($nullCheckExpTree instanceof PropertyAccessExpression) {
            $parent = $nullCheckExpTree;
            $key = null;
            do {
                $key = $parent->getResourceProperty()->getName() . '_' . $key;
                $parent = $parent->getParent();
            } while (null != $parent);

            $this->mapTable[$key] = $nullCheckExpTree;
        } else {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2UnexpectedExpression(get_class($nullCheckExpTree))
            );
        }
    }
}
