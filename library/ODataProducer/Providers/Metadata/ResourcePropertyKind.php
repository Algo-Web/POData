<?php
/** 
 * Enum for different kind of properties resource can have.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers\Metadata;
/**
 * Enum for different kind of properties resource.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ResourcePropertyKind
{
    /**
     * A bag of primitive or complex types
     */
    const BAG = 1;

    /**
     * A complex (compound) property
     */
    const COMPLEX_TYPE = 2;

    /**
     * Whether this property is a etag property
     */
    const ETAG = 4;

    /**
     * A property that is part of the key
     */
    const KEY = 8;

    /**
     * A primitive type property
     */
    const PRIMITIVE = 16;

    /**
     * A reference to another resource
     */
    const RESOURCE_REFERENCE = 32;

    /**
     * A reference to another resource set
     */
    const RESOURCESET_REFERENCE = 64;
}
?>