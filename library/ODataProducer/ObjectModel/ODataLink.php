<?php
/**
 * Class representing Navigation link
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
 * Type to represent an OData Link.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_ObjectModel
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ODataLink
{
    /**
     * 
     * Name of the link. This becomes last segment of rel attribute value.
     * @var string
     */
    public $name;
    /**
     * 
     * Title of the link. This become value of title attribute
     * @var string
     */
    public $title;
    /**
     * 
     * Type of link
     * @var string
     */
    public $type;
    /**
     * 
     * Url to the navigation property. This become value of href attribute
     * @var string
     */
    public $url;
    /**
     * 
     * Checks is Expand result contains single entity or collection of 
     * entities i.e. feed.
     * 
     * @var boolean
     */
    public $isCollection;
    /**
     * 
     * The expanded result. This become the inline content of the link
     * @var ODataEntry/ODataFeed
     */
    public $expandedResult;
    /**
     * 
     * True if Link is Expanded, False if not.
     * @var Boolean
     */
    public $isExpanded;

    /**
     * Constructor for Initialization of Link. 
     */
    function __construct()
    {
    }
}
?>