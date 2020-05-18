<?php

declare(strict_types=1);

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use InvalidArgumentException;
use POData\Common\Messages;
use POData\Common\NotImplementedException;
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
use ReflectionException;

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
     * @var array<array>
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
     * @param  string         $text                    The text expression to parse
     * @param  ResourceType   $resourceType            The resource type in which
     *                                                 expression will be applied
     * @param  bool           $isPHPExpressionProvider True if IExpressionProvider provider is
     *                                                 implemented by user, False otherwise
     * @throws ODataException
     */
    public function __construct(string $text, ResourceType $resourceType, bool $isPHPExpressionProvider)
    {
        parent::__construct($text, $resourceType, $isPHPExpressionProvider);
        $this->navigationPropertiesUsedInTheExpression = [];
        $this->isPHPExpressionProvider                 = $isPHPExpressionProvider;
    }

    /**
     * Parse and generate expression from the the given odata expression.
     *
     *
     * @param string              $text               The text expression to parse
     * @param ResourceType        $resourceType       The resource type in which
     * @param IExpressionProvider $expressionProvider Implementation of IExpressionProvider
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException          If any error occurs while parsing the odata expression
     *                                 or building the php/custom expression
     * @return FilterInfo
     */
    public static function parseExpression2(
        string $text,
        ResourceType $resourceType,
        IExpressionProvider $expressionProvider
    ): FilterInfo {
        $expressionParser2 = new self($text, $resourceType, $expressionProvider instanceof PHPExpressionProvider);
        $expressionTree    = $expressionParser2->parseFilter();

        $expressionProvider->setResourceType($resourceType);
        $expressionProcessor = new ExpressionProcessor($expressionProvider);

        try {
            $expressionAsString = $expressionProcessor->processExpression($expressionTree);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw ODataException::createInternalServerError($invalidArgumentException->getMessage());
        }
        $expressionAsString = (isset($expressionAsString)) ? $expressionAsString : '';
        return new FilterInfo(
            $expressionParser2->navigationPropertiesUsedInTheExpression,
            $expressionAsString
        );
    }

    /**
     * Parse the expression.
     *
     * @throws NotImplementedException
     * @throws ReflectionException
     * @throws ODataException
     * @return AbstractExpression
     *
     * @see library/POData/QueryProcessor/ExpressionParser::parseFilter()
     */
    public function parseFilter(): AbstractExpression
    {
        $expression = parent::parseFilter();
        if (!$expression->typeIs(new Boolean())) {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2BooleanRequired()
            );
        }
        if ($this->isPHPExpressionProvider) {
            $resultExpression = $this->processNodeForNullability(null, $expression);
            if (null != $resultExpression) {
                return $resultExpression;
            }
        }

        return $expression;
    }

    /**
     * Process the expression node for nullability.
     *
     * @param  AbstractExpression      $parentExpression      The parent expression of expression node to process
     * @param  AbstractExpression|null $expression            The expression node to process
     * @param  bool                    $checkNullForMostChild Whether to include null check for current property
     * @throws ODataException
     * @return AbstractExpression|null
     */
    private function processNodeForNullability(
        $parentExpression,
        AbstractExpression $expression = null,
        $checkNullForMostChild = true
    ): ?AbstractExpression {
        if ($expression instanceof ArithmeticExpression) {
            return $this->processArithmeticNode($expression);
        } elseif ($expression instanceof ConstantExpression) {
            return null;
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
     * @throws ODataException
     * @return AbstractExpression|null
     */
    private function processArithmeticNode(ArithmeticExpression $expression): ?AbstractExpression
    {
        $leftNullableExpTree  = $this->processNodeForNullability($expression, $expression->getLeft());
        $rightNullableExpTree = $this->processNodeForNullability($expression, $expression->getRight());
        $resultExpression     = $this->calculateResultExpression($leftNullableExpTree, $rightNullableExpTree);

        return $resultExpression;
    }

    /**
     * @param  AbstractExpression|null $leftNullableExpTree
     * @param  AbstractExpression|null $rightNullableExpTree
     * @throws ODataException
     * @return null|AbstractExpression
     */
    private function calculateResultExpression(
        ?AbstractExpression $leftNullableExpTree,
        ?AbstractExpression $rightNullableExpTree
    ): ?AbstractExpression {
        if (null != $leftNullableExpTree && null != $rightNullableExpTree) {
            $resultExpression = $this->mergeNullableExpressionTrees(
                $leftNullableExpTree,
                $rightNullableExpTree
            );
        } else {
            $resultExpression = null != $leftNullableExpTree ? $leftNullableExpTree : $rightNullableExpTree;
        }
        return $resultExpression;
    }

    /**
     * Merge two null check expression trees by removing duplicate nodes.
     *
     * @param AbstractExpression $nullCheckExpTree1 First expression
     * @param AbstractExpression $nullCheckExpTree2 Second expression
     *
     * @throws ODataException
     * @return LogicalExpression|UnaryExpression|null
     */
    private function mergeNullableExpressionTrees(
        AbstractExpression $nullCheckExpTree1,
        AbstractExpression $nullCheckExpTree2
    ): ?AbstractExpression {
        $this->mapTable = [];
        $this->map($nullCheckExpTree1);
        $this->map($nullCheckExpTree2);
        $expression = null;
        foreach ($this->mapTable as $node) {
            if (null == $expression) {
                $expression = new UnaryExpression(
                    new FunctionCallExpression(
                        FunctionDescription::isNullCheckFunction($node->getType()),
                        [$node]
                    ),
                    ExpressionType::NOT_LOGICAL(),
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
                        ExpressionType::NOT_LOGICAL(),
                        new Boolean()
                    ),
                    ExpressionType::AND_LOGICAL()
                );
            }
        }

        return $expression;
    }

    /**
     *  Populate map table.
     *
     * @param AbstractExpression $nullCheckExpTree The expression to verify
     *
     * @throws ODataException
     */
    private function map(AbstractExpression $nullCheckExpTree): void
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
            $key    = null;
            do {
                $key    = $parent->getResourceProperty()->getName() . '_' . $key;
                $parent = $parent->getParent();
            } while (null != $parent);

            $this->mapTable[$key] = $nullCheckExpTree;
        } else {
            throw ODataException::createSyntaxError(
                Messages::expressionParser2UnexpectedExpression(get_class($nullCheckExpTree))
            );
        }
    }

    /**
     * Process an arithmetic expression node for nullability.
     *
     * @param FunctionCallExpression  $expression       The function call expression node to process
     * @param AbstractExpression|null $parentExpression The parent expression of expression node to process
     *
     * @throws ODataException
     * @return null|AbstractExpression
     */
    private function processFunctionCallNode(
        FunctionCallExpression $expression,
        ?AbstractExpression $parentExpression
    ): ?AbstractExpression {
        $paramExpressions      = $expression->getParamExpressions();
        $checkNullForMostChild = strcmp($expression->getFunctionDescription()->name, 'is_null') === 0;
        $resultExpression      = null;
        foreach ($paramExpressions as $paramExpression) {
            $resultExpression1 = $this->processNodeForNullability(
                $expression,
                $paramExpression,
                !$checkNullForMostChild
            );
            $resultExpression = $this->calculateResultExpression($resultExpression, $resultExpression1);
        }

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL()
            );
        }

        return $resultExpression;
    }

    /**
     * Process an logical expression node for nullability.
     *
     * @param LogicalExpression       $expression       The logical expression node to process
     * @param AbstractExpression|null $parentExpression The parent expression of expression node to process
     *
     * @throws ODataException
     * @return null|AbstractExpression
     */
    private function processLogicalNode(
        LogicalExpression $expression,
        ?AbstractExpression $parentExpression
    ): ?AbstractExpression {
        $leftNullableExpTree  = $this->processNodeForNullability($expression, $expression->getLeft());
        $rightNullableExpTree = $this->processNodeForNullability($expression, $expression->getRight());
        if ($expression->getNodeType() == ExpressionType::OR_LOGICAL()) {
            if (null !== $leftNullableExpTree) {
                $resultExpression = new LogicalExpression(
                    $leftNullableExpTree,
                    $expression->getLeft(),
                    ExpressionType::AND_LOGICAL()
                );
                $expression->setLeft($resultExpression);
            }

            if (null !== $rightNullableExpTree) {
                $resultExpression = new LogicalExpression(
                    $rightNullableExpTree,
                    $expression->getRight(),
                    ExpressionType::AND_LOGICAL()
                );
                $expression->setRight($resultExpression);
            }

            return null;
        }

        $resultExpression = $this->calculateResultExpression($leftNullableExpTree, $rightNullableExpTree);

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL()
            );
        }

        return $resultExpression;
    }

    /**
     * Process an property access expression node for nullability.
     *
     * @param PropertyAccessExpression $expression            The property access expression node to process
     * @param AbstractExpression|null  $parentExpression      The parent expression of expression node to process
     * @param bool                     $checkNullForMostChild Whether to check null for most child node or not
     *
     * @return LogicalExpression|UnaryExpression|null
     */
    private function processPropertyAccessNode(
        PropertyAccessExpression $expression,
        ?AbstractExpression $parentExpression,
        bool $checkNullForMostChild
    ): ?AbstractExpression {
        $navigationsUsed = $expression->getNavigationPropertiesInThePath();
        if (!empty($navigationsUsed)) {
            $this->navigationPropertiesUsedInTheExpression[] = $navigationsUsed;
        }

        $nullableExpTree = $expression->createNullableExpressionTree($checkNullForMostChild);

        if (null == $parentExpression) {
            return new LogicalExpression(
                $nullableExpTree,
                $expression,
                ExpressionType::AND_LOGICAL()
            );
        }

        return $nullableExpTree;
    }

    /**
     * Process a relational expression node for nullability.
     *
     * @param RelationalExpression    $expression       The relational expression node to process
     * @param AbstractExpression|null $parentExpression The parent expression of expression node to process
     *
     * @throws ODataException
     * @return null|AbstractExpression
     */
    private function processRelationalNode(
        RelationalExpression $expression,
        ?AbstractExpression $parentExpression
    ): ?AbstractExpression {
        $leftNullableExpTree  = $this->processNodeForNullability($expression, $expression->getLeft());
        $rightNullableExpTree = $this->processNodeForNullability($expression, $expression->getRight());

        $resultExpression = $this->calculateResultExpression($leftNullableExpTree, $rightNullableExpTree);

        if (null == $resultExpression) {
            return null;
        }

        if (null == $parentExpression) {
            return new LogicalExpression(
                $resultExpression,
                $expression,
                ExpressionType::AND_LOGICAL()
            );
        }

        return $resultExpression;
    }

    /**
     * Process an unary expression node for nullability.
     *
     * @param UnaryExpression         $expression       The unary expression node to process
     * @param AbstractExpression|null $parentExpression The parent expression of expression node to process
     *
     * @throws ODataException
     * @return AbstractExpression|null
     */
    private function processUnaryNode(
        UnaryExpression $expression,
        ?AbstractExpression $parentExpression
    ): ?AbstractExpression {
        if (ExpressionType::NEGATE() == $expression->getNodeType()) {
            return $this->processNodeForNullability($expression, $expression->getChild());
        }

        if (ExpressionType::NOT_LOGICAL() == $expression->getNodeType()) {
            $resultExpression = $this->processNodeForNullability($expression, $expression->getChild());
            if (null == $resultExpression) {
                return null;
            }

            if (null == $parentExpression) {
                return new LogicalExpression(
                    $resultExpression,
                    $expression,
                    ExpressionType::AND_LOGICAL()
                );
            }

            return $resultExpression;
        }

        throw ODataException::createSyntaxError(
            Messages::expressionParser2UnexpectedExpression(get_class($expression))
        );
    }
}
