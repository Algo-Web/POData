<?php
/** 
 * Represents a single Odata entity.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
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
namespace ODataProducer\ObjectModel;
/**
 * Represents a single Odata entity.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ODataEntry
{
    /**
     * 
     * Entry id
     * @var string
     */
    public $id;
    /**
     * 
     * Entry Self Link
     * @var string
     */
    public $selfLink;
    /**
     * 
     * Entry title
     * @var string
     */
    public $title;
    /**
     * Entry Edit Link
     * @var string
     */
    public $editLink;
    /**
     * 
     * Entry Type. This become the value of term attribute of Category element
     * @var string
     */
    public $type;
    /**
     * 
     * Instance to hold entity properties. 
     * Properties corresponding to "m:properties" under content element 
     * in the case of Non-MLE. For MLE "m:properties" is direct child of entry
     * @var ODataPropertyContent
     */
    public $propertyContent;
    /**
     * 
     * Collection of entry media links (Named Stream Links)
     * @var array<ODataMediaLink>
     */
    public $mediaLinks;
    /**
     * 
     * media link entry (MLE Link)
     * @var ODataMediaLink
     */
    public $mediaLink;
    /**
     * 
     * Collection of navigation links (can be expanded)
     * @var array<ODataLink>
     */
    public $links;
    /**
     * 
     * Entry ETag
     * @var string
     */
    public $eTag;
    /**
     * 
     * Entry IsTopLevel
     * @var string
     */
    public $isTopLevel;
    /**
     * 
     * True if this is a media link entry.
     * @var boolean
     */
    public $isMediaLinkEntry;
    
    /**
     * Constructs a new insatnce of ODataEntry
     */
    function __construct()
    {
        $this->mediaLinks = array();
        $this->links = array();
    }
}
?>