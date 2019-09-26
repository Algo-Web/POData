<?php

namespace POData\ObjectModel;

/**
 * Class ODataMediaLink represents an OData Media link.
 */
class ODataMediaLink
{
    const MEDIARESOURCE_BASE = 'http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/';

    /**
     * Name for media link.
     *
     * @var string
     */
    public $name;
    /**
     * Edit link for media link entry.
     *
     * @var string
     */
    public $editLink;
    /**
     * Src link for media link entry.
     *
     * @var string
     */
    public $srcLink;
    /**
     * Content MIME type.
     *
     * @var string
     */
    public $contentType;
    /**
     * Media Link ETag.
     *
     * @var string
     */
    public $eTag;
    /**
     * Attribute extensions for Media Link.
     *
     * @var array<XMLAttribute>
     */
    public $AttributeExtensions;
    /**
     * True if this is a MLE else (Named Stream) false.
     *
     * @var bool
     */
    public $isMediaLinkEntry;

    /*
     * Rel field for media link
     *
     * @var string
     */
    public $rel;

    /**
     * Constructor for initializing attributes.
     *
     * @param string      $name        Name for media link
     * @param string      $editLink    EditLink for media content
     * @param string      $srcLink     source link for media content
     * @param string      $contentType Mime type for Media content
     * @param string|null $eTag        eTag for media content
     * @param null|mixed  $rel
     */
    public function __construct($name, $editLink, $srcLink, $contentType, $eTag, $rel = null)
    {
        $this->contentType = $contentType;
        $this->editLink = $editLink;
        $this->eTag = $eTag;
        $this->name = $name;
        $this->srcLink = $srcLink;
        $this->rel = (null !== $rel) ? $rel : self::MEDIARESOURCE_BASE.$name;
    }
}
