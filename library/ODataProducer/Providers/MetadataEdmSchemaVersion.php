<?php
/**
 * Enum representing Edm Schema version for the metadata  
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers;
/**
 * Enum representing Edm Schema version for the metadata
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class MetadataEdmSchemaVersion
{
    /**
     * Edm Schema v1.0
     */
    const VERSION_1_DOT_0 = 1.0;

    /**
     * Edm Schema v1.1
     */
    const VERSION_1_DOT_1 = 1.1;

    /**
     * Edm Schema v1.2
     */    
    const VERSION_1_DOT_2 = 1.2;

    /**
     * Edm Schema v2.0
     */
    const VERSION_2_DOT_0 = 2.0;

    /**
     * Edm Schema v2.2
     */
    const VERSION_2_DOT_2 = 2.2;
}
?>