<?php
/**
 * Type to hold the parsed skiptoken value. The IDSQP implementor 
 * can use these details if they want to do custom paging.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor\QueryProcessor\SkipTokenParser;
use ODataProducer\Common\Messages;
use ODataProducer\Providers\Metadata\ResourceType;
use ODataProducer\UriProcessor\QueryProcessor\OrderByParser\OrderByInfo;
/**
 * Type to hold the parsed skiptoken value
 *
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor_QueryProcessor_SkipTokenParser
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class SkipTokenInfo
{
    /**
     * Type to hold information about the navigation properties used in the 
     * orderby clause (if any) and orderby path.
     * 
     * @var OrderByInfo
     */
    private $_orderByInfo;

    /**
     * Holds collection of values in the skiptoken corrosponds to the orderby
     * path segments.
     * 
     * @var array(int (array(string, IType)
     */
    private $_orderByValuesInSkipToken;

    /**
     * Constructs a new instance of SkipTokenInfo.
     * 
     * @param OrderByInfo                      &$orderByInfo             Type holding information about the navigation properties 
     *                                                                   used in the orderby clause (if any) and orderby path.
     * @param array(int,(array(string,IType))) $orderByValuesInSkipToken Collection of values in the skiptoken corrosponds
     *                                                                   to the orderby path segments.
     */
    public function __construct(OrderByInfo &$orderByInfo, $orderByValuesInSkipToken)
    {
        $this->_orderByInfo = $orderByInfo;
        $this->_orderByValuesInSkipToken = $orderByValuesInSkipToken;
    }

    /**
     * Get reference to the OrderByInfo instance holdint information about the 
     * navigation properties used in the rderby clause (if any) and orderby path.
     * 
     * @return OrderByInfo
     */
    public function getOrderByInfo()
    {
        return $this->_orderByInfo;
    }

    /**
     * Gets collection of values in the skiptoken corrosponds to the orderby 
     * path segments.
     * 
     * @return array(int,(array(string,IType)))
     */
    public function getOrderByKeysInToken()
    {
        return $this->_orderByValuesInSkipToken;
    }
}
?>