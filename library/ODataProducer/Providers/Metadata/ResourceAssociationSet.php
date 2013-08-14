<?php
/**
 * Type to represent association (relationship) set. 
 * 
*/
namespace ODataProducer\Providers\Metadata;
use ODataProducer\Providers\Metadata\ResourceAssociationType;
use ODataProducer\Common\ODataException;
use ODataProducer\Common\Messages;
/**
 * Type for association (relationship) set.
*
 */
class ResourceAssociationSet
{
    /**
     * name of the association set
     * @var string
     */        
    private $_name;

    /** 
     * End1 of association set
     * @var ResourceAssociationSetEnd
     */
    private $_end1;

    /** 
     * End2 of association set
     * @var ResourceAssociationSetEnd
     */
    private $_end2;

    /**     
     * Note: This property will be populated by the library, 
     * so IDSMP implementor should not set this.
     * The association type hold by this association set
     * 
     * @var ResourceAssociationType
     */
    public $resourceAssociationType;

    /**
     * Construct new instance of ResourceAssociationSet
     * 
     * @param string                    $name Name of the association set
     * @param ResourceAssociationSetEnd $end1 First end set participating 
     *                                        in the association set
     * @param ResourceAssociationSetEnd $end2 Second end set participating 
     *                                        in the association set
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($name, 
        ResourceAssociationSetEnd $end1, 
        ResourceAssociationSetEnd $end2
    ) {

        if (is_null($end1->getResourceProperty()) 
            && is_null($end2->getResourceProperty())
        ) {
            throw new \InvalidArgumentException(
                Messages::resourceAssociationSetResourcePropertyCannotBeBothNull()
            );
        }

        if ($end1->getResourceType() == $end2->getResourceType() 
            && $end1->getResourceProperty() == $end2->getResourceProperty()
        ) {
                throw new \InvalidArgumentException(
                    Messages::resourceAssociationSetSelfReferencingAssociationCannotBeBiDirectional()
                );
        }
       
        $this->_name = $name;
        $this->_end1 = $end1;
        $this->_end2 = $end2;
    }

    /**
     * Retrieve the end for the given resource set, type and property.
     * 
     * @param ResourceSet      $resourceSet      Resource set for the end
     * @param ResourceType     $resourceType     Resource type for the end
     * @param ResourceProperty $resourceProperty Resource property for the end
     * 
     * @return ResourceAssociationSetEnd Resource association set end for the 
     *                                   given parameters
     */
    public function getResourceAssociationSetEnd(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        if ($this->_end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        return null;
    }

    /**
     * Retrieve the related end for the given resource set, type and property.
     * 
     * @param ResourceSet      $resourceSet      Resource set for the end
     * @param ResourceType     $resourceType     Resource type for the end
     * @param ResourceProperty $resourceProperty Resource property for the end
     * 
     * @return ResourceAssociationSetEnd Related resource association set end 
     *                                   for the given parameters
     */
    public function getRelatedResourceAssociationSetEnd(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    ) {
        if ($this->_end1->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end2;
        }
        
        if ($this->_end2->isBelongsTo($resourceSet, $resourceType, $resourceProperty)) {
            return $this->_end1;
        }
        
        return null;
    }

    /**
     * Get name of the association set
     * 
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Get first end of the association set
     * 
     *  @return string
     */
    public function getEnd1()
    {
        return $this->_end1;
    }

    /**
     * Get second end of the association set
     * 
     *  @return string
     */
    public function getEnd2()
    {
        return $this->_end2;
    }

    /**
     * Whether this association set represents a two way relationship between 
     * resource sets
     * 
     * @return boolean true if relationship is bidirectional, otherwise false 
     */
    public function isBidirectional()
    {
        return (!is_null($this->_end1->getResourceProperty()) 
            && !is_null($this->_end2->getResourceProperty())
        );
    }
}
?>