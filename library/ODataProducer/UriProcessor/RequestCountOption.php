<?php
/**
 * Enumeration for OData count request options.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\UriProcessor;
/** 
 * Enumeration for OData count request options.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_UriProcessor
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class RequestCountOption
{
    /**
     * No count option specified
     */
    const NONE = 0;

    /**
     * $count option specified
     */
    const VALUE_ONLY = 1;

    /**
     * $inlinecount option specified.
     */
    const INLINE = 2;
}
?>