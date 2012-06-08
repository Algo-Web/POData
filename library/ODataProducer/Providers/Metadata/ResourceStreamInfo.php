<?php
/** 
 * Contains information about a named stream on an entity type
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
 * Type to hold information about named stream on an entity type
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ResourceStreamInfo
{
    /**
     * Name of the stream
     */
    private $_name;
    
    /**
     * Custom state object associated with named stream
     */
    private $_customState;

    /**
     * Constructs a new instance of ResourceStreamInfo
     * 
     * @param string $name Name of the stream
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Gets name of the stream
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Gets custom state
     * 
     * @return object
     */
    public function getCustomState()
    {
        return $this->_customState;
    }

    /**
     * Sets custom state
     * 
     * @param object $stateObject The custom object
     * 
     * @return void
     */
    public function setCustomState($stateObject)
    {
        $this->_customState = $stateObject;
    }
}
?>