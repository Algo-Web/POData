<?php
/** 
 * The class which implements this interface is responsible for describing the
 * shape or "model" of the information in custom data source
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
 * Data source model interface.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Providers_Metadata
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
interface IDataServiceMetadataProvider
{
    /**
     * To get the Container name for the data source.
     * 
     * @return string that contains the name of the container
     */
    public function getContainerName();

    /**
     * To get Namespace name for the data source.
     * 
     * @return string that contains the namespace name.
     */
    public function getContainerNamespace();

    /**
     *  To get all entity set information
     *  
     *  @return array(ResourceSet)
     */
    public function getResourceSets();

    /**
     * To get all resource types in the data source.
     * 
     * @return array(ResourceType)
     */
    public function getTypes();

    /**
     * To get a resource set based on the specified resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceSet/NULL resource set with the given name if found 
     *                          else NULL
     */
    public function resolveResourceSet($name);

    /**
     * To get a resource type based on the resource set name.
     * 
     * @param string $name Name of the resource set
     * 
     * @return ResourceType/NULL resource type with the given resource set
     *                           name if found else NULL
     */
    public function resolveResourceType($name);

    /**
     * The method must return a collection of all the types derived from 
     * $resourceType The collection returned should NOT include the type 
     * passed in as a parameter An implementer of the interface should 
     * return null if the type does not have any derived types. 
     *
     * @param ResourceType $resourceType Resource to get derived resource 
     *                                   types from
     * 
     * @return array(ResourceType)/NULL
     */
    public function getDerivedTypes(ResourceType $resourceType);

    /**
     * Returns true if $resourceType represents an Entity Type which has derived
     *                               Entity Types, else false.
     *
     * @param ResourceType $resourceType Resource to check for derived resource 
     *                                   types.
     * 
     * @return boolean
     */
    public function hasDerivedTypes(ResourceType $resourceType);

    /**
     * Gets the ResourceAssociationSet instance for the given source 
     * association end.
     * 
     * @param ResourceSet      $resourceSet      Resource set of the source
     *                                           association end
     * @param ResourceType     $resourceType     Resource type of the source
     *                                           association end
     * @param ResourceProperty $resourceProperty Resource property of the source
     *                                           association end
     * 
     * @return ResourceAssociationSet
     */
    public function getResourceAssociationSet(ResourceSet $resourceSet, 
        ResourceType $resourceType, ResourceProperty $resourceProperty
    );
}
?>