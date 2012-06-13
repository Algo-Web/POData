<?php
/**
 * Build the basic expression tree for a given expression using base class
 * ExpressionParser, modify the expression tree to have null checks 
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
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
namespace ODataProducer\UriProcessor\QueryProcessor\ExpressionParser;
use ODataProducer\UriProcessor\QueryProcessor\AnonymousFunction;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;
use ODataProducer\Providers\Metadata\Type\Boolean;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\Providers\Metadata\ResourceTypeKind;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ArithmeticExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\LogicalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\RelationalExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ConstantExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyAccessExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\FunctionCallExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\UnaryExpression;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\ExpressionType;
use ODataProducer\UriProcessor\QueryProcessor\ExpressionParser\Expressions\PropertyNullabilityCheckExpression;
use ODataProducer\UriProcessor\QueryProcessor\FunctionDescription\FunctionDescription;
/**
 * Expression parser to take care nullability.
 *  
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_QueryProcessor_ExpressionParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ExpressionParser2 extends ExpressionParser
{

    /**
     * 
     * @var array
     */
    private $_mapTable;

    /**
     * Collection of navigation properties used in the expression.
     * 
     * @var array()/array(array(ResourceProperty))
     */
    private $_navigationPropertiesUsedInTheExpression;

    /**
     * Indicates whether the end user has implemented IExpressionProvider or not.
     * 
     * @var bool
     */
    private $_isCustomExpressionProvider;
   
    /**
     * Create new instance of ExpressionParser2
     *      
     * @param string       $text                       The text expression to parse.
     * @param ResourceType $resourceType               The resource type in which 
     *                                                 expression will be applied.
     * @param Bool         $isCustomExpressionProvider True if IExpressionProvider provider is
     *                                                 implemented by user, False otherwise
     */
    public function __construct($text, ResourceType $resourceType, 
        $isCustomExpressionProvider
    ) {
        parent::__construct($text, $resourceType, $isCustomExpressionProvider);
        $this->_navigationPropertiesUsedInTheExpression = array();
        $this->_isCustomExpressionProvider = $isCustomExpressionProvider;
    }

    /**
     * Parse and generate PHP or custom (using custom expression provider) expression 
     * from the the given odata expression.
     * 
     * @param string              $text               The text expression to parse
     * @param ResourceType        $resourceType       The resource type in which 
     *                                                expression will be applied
     * @param IExpressionProvider $expressionProvider Implementation of IExpressionProvider
     *                                                if developer is using IDSQP2, null
     *                                                in-case of IDSQP which falls to default
     *                                                expression provider that is PHP expression
     *                                                provider
     * 
     * @return InternalFilterInfo
     * 
     * @throws ODataException If any error occurs while parsing the odata 
     *                        expression or building the php/custom expression.
     */
    public static function parseExpression2($text, ResourceType $resourceType, 
        $expressionProvider
    ) {
        $isCustomExpressionProvider = !is_null($expressionProvider);
        $expressionParser2 = new ExpressionParser2(
            $text, $resourceType, $isCustomExpressionProvider
        );
        $expressionTree = null;
        try {
            $expressionTree = $expressionParser2->parseFilter();
        } catch (ODataException $odataException) {
            throw $odataException;
        }

        $expressionProcessor = null;
        $expressionAsString = null;
        $filterFunction = null;
        if (!$isCustomExpressionProvider) {
            $expressionProvider = new PHPExpressionProvider('$lt');
        }
        
        $expressionProvider->setResourceType($resourceType);
        $expressionProcessor = new ExpressionProcessor(
            $expressionTree,
            $expressionProvider
        );

        try {
            $expressionAsString = $expressionProcessor->processExpression();
        } catch (\InvalidArgumentException $invalidArgumentException) {
            ODataException::createInternalServerError(
                $invalidArgumentException->getMessage()
            );
        }

        $navigationPropertiesUsed
        = empty($expressionParser2->_navigationPropertiesUsedInTheExpression)
        ?
        null :
        $expressionParser2->_navigationPropertiesUsedInTheExpression;
        unset($expressionProcessor);
        unset($expressionParser2);
        if ($isCustomExpressionProvider) {
            $filterFunction = new AnonymousFunction(
                array(),
                ' ODataException::createInternalServerError("Library will not perform filtering in case of custom IExpressionProvider"); '
            );
        } else {
            $filterFunction = new AnonymousFunction(
                array('$lt'),
                'if(' . $expressionAsString . ') { return true; } else { return false;}'
            );
        }
        
        return new InternalFilterInfo(
            new FilterInfo($navigationPropertiesUsed),
            $filterFunction,
            $expressionAsString,
            $isCustomExpressionProvider
        );
    }

    /**
     * Parse the expression
     * 
     * @see library/ODataProducer/QueryProcessor/ODataProducer\QueryProcessor.
     *      ExpressionParser::parseFilter()
     * 
     * @return AbstractExpression
     * 
     * @throws ODataException
     */
    public  function parseFilter()
    {
        $expression = parent::parseFilter();
        if (!$expression->typeIs(new Boolean())) {
            ODataException::createSyntaxError(
                Messages::expressionParser2BooleanRequired()
            );
        }
        if (!$this->_isCustomExpressionProvider) {
            $resultExpression = $this->_processNodeForNullability($expression, null);
            if ($resultExpression != null) {
                return $resultExpression;
            }
        }
        return $expression;
    }

    /**
     * Process the expression node for nullability
     * 
     * @param AbstractExpression $expression            The expression node to 
     *                                                  process.
     * @param AbstractExpression $parentExpression      The parent expression of 
     *                                                  expression node to process.
     * @param boolean            $checkNullForMostChild whether to include null check
     *                                                  for current property.
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
        } else if ($expression instanceof ConstantExpression) {
            return null;
        } else if ($expression instanceof FunctionCallExpression) {
            return $this->_processFuncationCallNode($expression, $parentExpression);
        } else if ($expression instanceof LogicalExpression) {
            return $this->_processLogicalNode($expression, $parentExpression);
        } else if ($expression instanceof PropertyAccessExpression) {
            return $this->_processPropertyAccessNode(
                $expression, 
                $parentExpression, 
                $checkNullForMostChild
            );
        } else if ($expression instanceof RelationalExpression) {
            return $this->_processRelationalNode($expression, $parentExpression);
        } else if ($expression instanceof UnaryExpression) {
            return $this->_processUnaryNode($expression, $parentExpression);
        }

        ODataException::createSyntaxError(
            Messages::expressionParser2UnexpectedExpression(get_class($expression))
        );
    }

    /**
     * Process an arithmetic expression node for nullability
     * 
     * @param ArithmeticExpression $expression The arithmetic expression node
     *                                         to process.
     *                                          
     * @return LogicalExpression or NULL
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
     * Process an arithmetic expression node for nullability
     * 
     * @param FunctionCallExpression $expression       The function call expression
     *                                                 node to process.
     * @param AbstractExpression     $parentExpression The parent expression of 
     *                                                 expression node to process.
     * 
     * @return LogicalExpression or NULL
     */
    private function _processFuncationCallNode(FunctionCallExpression $expression, 
        $parentExpression
    ) {
        $paramExpressions = $expression->getParamExpressions();
        $checkNullForMostChild 
            = strcmp(
                $expression->getFunctionDescription()->functionName, 
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
            } else if ($resultExpression1 != null && $resultExpression == null) {
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
     *                                             to process.
     * @param AbstractExpression $parentExpression The parent expression of 
     *                                             expression node to process.
     * 
     * @return LogicalExpression or NULL
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
     * Process an property access expression node for nullability
     * 
     * @param PropertyAccessExpression $expression            The property access 
     *                                                        expression node to process.
     * @param AbstractExpression       $parentExpression      The parent expression of 
     *                                                        expression node to process.
     * @param boolean                  $checkNullForMostChild Wheter to check null for 
     *                                                        most child node or not.
     * 
     * @return LogicalExpression, RelationalExpression or NULL
     */
    private function _processPropertyAccessNode(
        PropertyAccessExpression $expression, 
        $parentExpression, $checkNullForMostChild
    ) {
        $navigationsUsed = $expression->getNavigationPropertiesInThePath();
        if (!empty($navigationsUsed)) {
            $this->_navigationPropertiesUsedInTheExpression[] = $navigationsUsed;
        } 

        $nullableExpTree 
            = $expression->createNullableExpressionTree($checkNullForMostChild);
        
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
     *                                               to process.
     * @param AbstractExpression   $parentExpression The parent expression of 
     *                                               expression node to process.
     * 
     * @return LogicalExpression or NULL
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
     * Process an unary expression node for nullability 
     * 
     * @param UnaryExpression    $expression       The unary expression node
     *                                             to process.
     * @param AbstractExpression $parentExpression The parent expression of 
     *                                             expression node to process.
     * 
     * @return LogicalExpression or NULL
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

        ODataException::createSyntaxError(
            Messages::expressionParser2UnexpectedExpression(get_class($expression))
        );
    }
    
    /**
     * Merge two null check expression trees by removing duplicate nodes.
     *
     * @param AbstractExpression $nullCheckExpTree1 First expression.
     * @param AbstractExpression $nullCheckExpTree2 Second expression.
     * 
     * @return UnaryExpression or LogicalExpression
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
     *  Populate map table 
     *
     * @param AbstractExpression $nullCheckExpTree The expression to verfiy.
     * 
     * @return void
     * 
     * @throws ODataException
     */
    private function _map($nullCheckExpTree)
    {
        if ($nullCheckExpTree instanceof LogicalExpression) {
            $this->_map($nullCheckExpTree->getLeft());
            $this->_map($nullCheckExpTree->getRight());
        } else if ($nullCheckExpTree instanceof UnaryExpression) {
            $this->_map($nullCheckExpTree->getChild());
        } else if ($nullCheckExpTree instanceof FunctionCallExpression) {
            $param = $nullCheckExpTree->getParamExpressions();
            $this->_map($param[0]);
        } else if ($nullCheckExpTree instanceof PropertyAccessExpression) {
            $parent = $nullCheckExpTree;
            $key = null;        
            do {
                $key = $parent->getResourceProperty()->getName() . '_' . $key;
                $parent = $parent->getParent();
            } while ($parent != null);
                        
            $this->_mapTable[$key] = $nullCheckExpTree;
        } else {
            ODataException::createSyntaxError(
                Messages::expressionParser2UnexpectedExpression(get_class($expTree))
            );
            
        }
    }
}