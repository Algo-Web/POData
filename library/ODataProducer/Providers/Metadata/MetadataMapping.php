<?php
/** 
 * Object to save the mapping between Metadata-Properties and corresponding names 
 * in the DB used for the same properties
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers\Metadata;
/**
 * Mapping between Metadata-Properties and DB field-names
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @author    Neelesh Vijaivargia <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class MetadataMapping
{
    public $mappingDetail;
    public $entityMappingInfo;
    
    /**
     * Constructs a new instance of SchemaMapping
     *
     * @return Object
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
?>
