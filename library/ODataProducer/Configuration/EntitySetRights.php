<?php
/** 
 * Enumeration to describe the rights granded on a entity set (resource set)
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
 * Enumeration to describe the rights granded on a entity set (resource set)
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Configuration
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class EntitySetRights
{
    /**
     * Specifies no rights on this entity set
     */
    const NONE = 0;

    /**
     * Specifies the right to read one entity instance per request
     */
    const READ_SINGLE = 1;

    /**
     * Specifies the right to read multiple entity instances per request
     */
    const READ_MULTIPLE = 2;

    /**
     * Specifies the right to append (add) new entity instance to the entity set
     */
    const WRITE_APPEND = 4;

    /**
     * Specifies the right to update existing entity instance in the entity set
     */
    const WRITE_REPLACE = 8;

    /**     
     * Specifies the right to delete existing entity instance in the entity set
     */
    const WRITE_DELETE = 16;

    /**
     * Specifies the right to update existing entity instance in the entity set
     */
    const WRITE_MERGE = 32;

    /**
     * Specifies the right to read single or multiple entity instances in a 
     * single request
     * READ_SINGLE | READ_MULTIPLE
     */
    const READ_ALL = 3;

    /**
     * Specifies the right to append, delete or update entity instances in the 
     * entity set
     * WRITE_APPEND | WRITE_DELETE | WRITE_REPLACE | WRITE_MERGE
     */
    const WRITE_ALL = 60;

    /**
     * Specifies all rights to the entity set
     * READ_ALL | WRITE_ALL
     */
    const ALL = 63;
}
?>