<?php
/**
 * Representation of a media link.
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
 * Type to represent an OData Media link.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_ObjectModel
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class ODataMediaLink
{
    /**
     *
     * Name for media link.
     * @var string
     */
    public $name;
    /**
     *
     * Edit link for media link entry
     * @var string
     */
    public $editLink;
    /**
     *
     * Src link for media link entry
     * @var string
     */
    public $srcLink;
    /**
     *
     * Content MIME type
     * @var string
     */
    public $contentType;
    /**
     *
     * Media Link ETag
     * @var string
     */
    public $eTag;
    /**
     *
     * Attribute extensions for Media Link
     * @var array<XMLAttribute>
     */
    public $AttributeExtensions;
    /**
     *
     * True if this is a MLE else (Named Stream) false
     * @var boolean
     */
    public $isMediaLinkEntry;

    /**
     * Constructor for initializing attributes.
     * 
     * @param string $name        Name for media link.
     * @param string $editLink    EditLink for media content
     * @param string $srcLink     source link for media content
     * @param string $contentType Mime type for Media content
     * @param string $eTag        eTag for media content
     */
    function __construct ($name, $editLink, $srcLink, $contentType, $eTag)
    {
        $this->contentType = $contentType;
        $this->editLink = $editLink;
        $this->eTag = $eTag;
        $this->name = $name;
        $this->srcLink = $srcLink;
    }
}
?>