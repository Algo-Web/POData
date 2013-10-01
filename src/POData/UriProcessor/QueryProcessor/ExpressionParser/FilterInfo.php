<?php

namespace POData\UriProcessor\QueryProcessor\ExpressionParser;

use POData\UriProcessor\QueryProcessor\AnonymousFunction;
use POData\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;

/**
 * Class FilterInfo
 * @package POData\UriProcessor\QueryProcessor\ExpressionParser
 */
class FilterInfo
{

	/**
	 * Collection of navigation properties specified in the filter
	 * clause, if no navigation (resource reference) property used
	 * in the clause then this property will be null.
	 *
	 * e.g. $filter=NaviProp1/NaviProp2/PrimitiveProp eq 12
	 *      $filter=NaviPropA/NaviPropB/PrimitiveProp gt 56.3
	 * In this case array will be as follows:
	 * array(array(NaviProp1, NaviProp2), array(NaviPropA, NaviPropB))
	 *
	 * @var array(array(ResourceProperty))/NULL
	 */
	private $_navigationPropertiesUsedInTheFilterClause;


    /**
     * The translated expression based on the Expression provider, if the end developer
     * opt for IDSQP2 then he is responsible for implementing IExpressionProvider
     * in this case this member variable will hold the expression generated through
     * custom ExpressionProvider, if user opt for IDSQP then the default 
     * PHPExpressionProvider will be used, in this case this member variable will
     * hold the PHP expression generated through the PHPExpressionProvider.
     * 
     * @var string
     */
    private $_filterExpressionAsDataSourceExpression;


    /**
     * @param array $navigationPropertiesUsedInTheFilterClause navigation properties in the $filter clause.
     * @param string $filterExpAsDataSourceExp The $filter expression as expression specific to data source
     *
     */
    public function __construct($navigationPropertiesUsedInTheFilterClause, $filterExpAsDataSourceExp)
    {
	    $this->_navigationPropertiesUsedInTheFilterClause = $navigationPropertiesUsedInTheFilterClause;
        $this->_filterExpressionAsDataSourceExpression = $filterExpAsDataSourceExp;
    }


	public function getNavigationPropertiesUsed()
	{
		return $this->_navigationPropertiesUsedInTheFilterClause;
	}


    /**
     * Gets the data source specific expression as string.  
     * 
     * @return string
     */
    public function getExpressionAsString()
    {
        return $this->_filterExpressionAsDataSourceExpression;
    }


}