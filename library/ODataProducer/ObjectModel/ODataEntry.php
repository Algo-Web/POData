<?php
/** 
 * Represents a single Odata entity.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\ObjectModel;
/**
 * Represents a single Odata entity.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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