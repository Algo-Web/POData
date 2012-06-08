<?php
/** 
 * Enumeration to describe the supported OData versions
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Configuration
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Configuration;
/**
 * Enumeration to describe the supported OData versions
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Configuration
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class DataServiceProtocolVersion
{
    /**
     * Version 1 of the OData protocol.
     */
    const V1 = 1;
    
    /**
     * Version 2 of the OData protocol.
     */
    const V2 = 2;

    /**
     * Version 3 of the OData protocol.
     */
    const V3 = 3;
}