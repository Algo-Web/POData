<?php
/** 
 * A type used to describe a segment (Uri is made up of bunch of segments, 
 * each segment is seperated by '/' character)
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
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
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
/**
 * Type to describe segment in the resource path.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class SegmentDescriptor
{

    /**
     * The identifier for this segment (string part without the keys, 
     * if key exists).
     * e.g. http://localhost/service.svc/Customers('ALFKI')/$links/Orders
     *              Segment                 identifier
     *              ---------------------------------
     *              Customers('ALFKI')      Customers
     *              $links                  $links
     *              Orders                  Orders
     * 
     * @var string
     */
    private $_identifier;

    /**
     * Describes the key for this segment 
     * 
     * @var KeyDescriptor
     */
    private $_keyDescriptor;

    /**
     * Whether the segment targets a single result or not
     * 
     * @var boolean
     */
    private $_singleResult;

    /**
     * Resource set wrapper if applicable
     * 
     * @var ResourceSetWrapper
     */
    private $_targetResourceSetWrapper;

    /**
     * Reference to an instance of ResourceType describes type of resource
     * targeted by this segment
     * 
     * @var ResourceType
     */
    private $_targetResourceType;

    /**
     * The kind of resource targeted by this segment
     * 
     * @var RequestTargetKind
     */
    private $_targetKind;

    /**
     * The kind of 'source of data' for this segment
     * 
     * @var RequestTargetSource
     */
    private $_targetSource;
    
    /**
     * The property that is being projected in this segment, if there's any
     * 
     * @var ResourceProperty 
     */
    private $_projectedProperty;

    /**
     * The data for this segment
     * 
     * @var unknown_type
     */
    private $_result;

    /**
     * Reference to next descriptor
     * 
     * @var SegmentDescriptor
     */
    private $_next;

    /**
     * Reference to previous descriptor
     * 
     * @var SegmentDescriptor
     */
    private $_previous;

    /**
     * Creates a new instance of SegmentDescriptor
     * 
     */
    public function __construct()
    {
        $this->_singleResult = false;
        $this->_targetKind = RequestTargetKind::NOTHING;
        $this->_targetSource = RequestTargetSource::NONE;
        $this->_identifier 
            = $this->_keyDescriptor 
                = $this->_projectedProperty 
                    = $this->_result 
                        = $this->_targetResourceSetWrapper 
                            = $this->_targetResourceType 
                                = null;
        $this->_previous = $this->_next = null;
    }

    /**
     * Creates a new instance of SegmentDescriptor from another 
     * SegmentDescriptor instance
     * 
     * @param SegmentDescriptor $anotherDescriptor The descriptor whose shallow copy
     *                                             to be created
     * 
     * @return SegmentDescriptor
     */
    public static function createFrom(SegmentDescriptor $anotherDescriptor)
    {
        $descriptor = new SegmentDescriptor();
        $descriptor->_identifier = $anotherDescriptor->_identifier;
        $descriptor->_keyDescriptor = $anotherDescriptor->_keyDescriptor;
        $descriptor->_projectedProperty = $anotherDescriptor->_projectedProperty;
        $descriptor->_singleResult = $anotherDescriptor->_singleResult;
        $descriptor->_targetKind = $anotherDescriptor->_targetKind;
        $descriptor->_targetResourceSetWrapper 
            = $anotherDescriptor->_targetResourceSetWrapper;
        $descriptor->_targetResourceType = $anotherDescriptor->_targetResourceType;
        $descriptor->_targetSource = $anotherDescriptor->_targetSource;
        return $descriptor;
    }

    /**
     * Gets the identifier for this segment
     * 
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * sets the identifier for this segment
     * 
     * @param string $identifier The identifier part of the segment
     * 
     * @return void
     */
    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Gets the description of the key, if any, associated with this segment
     * 
     * @return KeyDescriptor
     */
    public function getKeyDescriptor()
    {
        return $this->_keyDescriptor;
    }

    /**
     * Sets the description of the key, if any, associated with this segment
     * 
     * @param KeyDescriptor $keyDescriptor The descriptor for the key associated 
     *                                     with this segment
     * 
     * @return void
     */
    public function setKeyDescriptor($keyDescriptor)
    {
        $this->_keyDescriptor = $keyDescriptor;
    }

    /**
     * Gets the property that is being projected in this segment, if there's any
     * 
     * @return ResourceProperty
     */
    public function getProjectedProperty()
    {
        return $this->_projectedProperty;
    }

    /**
     * Sets the property that is being projected in this segment, if there's any
     * 
     * @param ResourceProperty $projectedProperty The property projected in 
     *                                            this segment
     * 
     * @return void
     */
    public function setProjectedProperty($projectedProperty)
    {
        $this->_projectedProperty = $projectedProperty;
    }

    /**
     * Whether this segment targets a single result or not
     * 
     * @return boolean
     */
    public function isSingleResult()
    {
        return $this->_singleResult;
    }

    /**
     * Sets whether this segment targets a single result or not
     * 
     * @param boolean $isSingleResult Boolean repersents whether this segment 
     *                                        targets a single result or not
     * 
     * @return void
     */
    public function setSingleResult($isSingleResult)
    {
        $this->_singleResult = $isSingleResult;
    }

    /**
     * Gets the kind of resource targeted by this segment
     * 
     * @return RequestTargetKind
     */
    public function getTargetKind()
    {
        return $this->_targetKind;
    }

    /**
     * Sets the kind of resource targeted by this segment
     * 
     * @param RequestTargetKind $targetKind The kind of resource
     * 
     * @return void
     */
    public function setTargetKind($targetKind)
    {
        $this->_targetKind = $targetKind;
    }

    /**
     * Gets the resource set wrapper (describes the resource set for this segment 
     * and its configuration) if applicable
     * 
     * @return ResourceSetWrapper
     */
    public function getTargetResourceSetWrapper()
    {
        return $this->_targetResourceSetWrapper;
    }

    /**
     * Sets the resource set wrapper (describes the resource set for this segment 
     * and its configuration) if applicable
     * 
     * @param ResourceSetWrapper $resourceSetWrapper The resource set wrapper
     * 
     * @return void
     */
    public function setTargetResourceSetWrapper($resourceSetWrapper)
    {
        $this->_targetResourceSetWrapper = $resourceSetWrapper;
    }

    /**
     * Gets reference to an instance of ResourceType describes type of resource 
     * targeted by this segment
     * 
     * @return ResourceType
     */
    public function getTargetResourceType()
    {
        return $this->_targetResourceType;
    }

    /**
     * Sets reference to an instance of ResourceType describes type of resource 
     * targeted by this segment
     * 
     * @param ResourceType $resourceType Type describing resource targeted by 
     *                                   this segment
     * 
     * @return void
     */
    public function setTargetResourceType($resourceType)
    {
        $this->_targetResourceType = $resourceType;
    }

    /**
     * Gets the kind of 'source of data' for this segment
     * 
     * @return RequestTargetSource
     */
    public function getTargetSource()
    {
        return $this->_targetSource;
    }

    /**
     * Sets the kind of 'source of data' for this segment
     * 
     * @param RequestTargetSource $targetSource The kind of 'source of data' 
     * 
     * @return void
     */
    public function setTargetSource($targetSource)
    {
        $this->_targetSource = $targetSource;
    }

    /**
     * Gets the data targeted by this segment 
     * 
     * @return var
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Sets the data targeted by this segment 
     * 
     * @param var $result The data targetted by this segment
     * 
     * @return void
     */
    public function setResult($result)
    {
        $this->_result = $result;
    }

    /**
     * Gets reference to next descriptor
     * 
     * @return SegmentDescriptor/NULL Returns reference to next descriptor, 
     *                                NULL if this is the last descriptor
     */
    public function getNext()
    {
        return $this->_next;
    }

    /**
     * Sets reference to next descriptor
     * 
     * @param SegmentDescriptor $next Reference to next descriptor
     * 
     * @return void
     */
    public function setNext(SegmentDescriptor $next)
    {
        $this->_next = $next;
    }

    /**
     * Gets reference to previous descriptor
     * 
     * @return SegmentDescriptor/NULL Returns reference to previous descriptor, 
     *                                NULL if this is the first descriptor
     */
    public function getPrevious()
    {
        return $this->_previous;
    }

    /**
     * Sets reference to previous descriptor
     * 
     * @param SegmentDescriptor $previous Reference to previous descriptor
     * 
     * @return void
     */
    public function setPrevious(SegmentDescriptor $previous)
    {
        $this->_previous = $previous;
    }

    /**
     * Returns true if this segment has a key filter with values; false otherwise
     * 
     * @return boolean
     */
    public function hasKeyValues()
    {
        return !is_null($this->_keyDescriptor);
    }
}
?>