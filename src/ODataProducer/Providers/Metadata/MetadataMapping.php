<?php

namespace ODataProducer\Providers\Metadata;


/**
 * Class MetadataMapping
 *
 * Object to save the mapping between Metadata-Properties and corresponding names
 * in the DB used for the same properties
 *
 * @package ODataProducer\Providers\Metadata
 */
class MetadataMapping
{
    public $mappingDetail;
    public $entityMappingInfo;
    
    /**
     * Constructs a new instance of SchemaMapping
     *
     * @return MetadataMapping
     */
    public function __construct()
    {
      $this->entityMappingInfo = Array();
        $this->mappingDetail = Array();
        return $this;
    }

    /**
     * Sets Mapping-Info for Entity
     *
     * @param String $entityName       TableName in MetaData
     * @param String $mappedEntityName TableName exist in the DB
     * 
     * @return void
     */
  public function mapEntity($entityName,$mappedEntityName)
  {
    $this->entityMappingInfo[$entityName] = $mappedEntityName;
    $this->mappingDetail[$entityName] = Array();
  }

    /**
     * Sets Mapping-Info for Entity
     *
     * @param String $entityName       TableName
     * @param String $metaPropertyName String Property-Name defined in the metadata
     * @param String $dsPropertyName   String Field-Name defined in the data-source  
     * 
     * @return void
     */
    public function mapProperty($entityName,$metaPropertyName,$dsPropertyName)
    {
        $this->mappingDetail[$entityName][$metaPropertyName] = $dsPropertyName;
    }

    /**
     * Gets the original name Defined in the DS used for the meta-property-name
     * 
     * @param String $entityName TableName
     * 
     * @return String Property-Name defined in the DS 
     */
    public function getMappedInfoForEntity($entityName)
    {
        return $this->entityMappingInfo[$entityName];
    }
  
    /**
     * Gets the original name(Defined in the DS) used for the meta-property-name
     * 
     * @param String $entityName   TableName
     * @param String $metaProperty Name being jused for the meta-data
     * 
     * @return String Property-Name defined in the DS 
     */
    public function getMappedInfoForProperty($entityName,$metaProperty)
    {
        return $this->mappingDetail[$entityName][$metaProperty];
    }
}
