<?php
/** 
 * Object to save the mapping between Metadata-Properties and corresponding names 
 * in the DB used for the same properties
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Providers\Metadata;
/**
 * Mapping between Metadata-Properties and DB field-names
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
