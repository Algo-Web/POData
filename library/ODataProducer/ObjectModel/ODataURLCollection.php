<?php
/** 
 * Represents top level URL collection
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\ObjectModel;
/**
 * Type to represent collection of links for $links request.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataURLCollection
{
    /**
     * 
     * Array of ODataURL
     * @var array<ODataURL>
     */
    public $oDataUrls;
    /**
     * 
     * Enter URL to next page, if pagination is enabled
     * @var ODataLink
     */
    public $nextPageLink;
    /**
     * 
     * Enter url Count if inlineCount is requested
     * @var integer
     */
    public $count;

    /**
     * Constructor for Initialization of LinkCollection.
     */
    function __construct()
    {
        $this->oDataUrls = array();
        $this->nextPageLink = null;
        $this->count = null;
    }
}
?>