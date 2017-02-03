<?php

namespace POData\ObjectModel;

/**
 * Class ODataMediaLink represents an OData Media link.
 */
class ODataMediaLink
{
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

    /**
     * Constructor for initializing attributes.
     *
     * @param string $name        Name for media link
     * @param string $editLink    EditLink for media content
     * @param string $srcLink     source link for media content
     * @param string $contentType Mime type for Media content
     * @param string $eTag        eTag for media content
     */
    public function __construct($name, $editLink, $srcLink, $contentType, $eTag)
    {
        $this->contentType = $contentType;
        $this->editLink = $editLink;
        $this->eTag = $eTag;
        $this->name = $name;
        $this->srcLink = $srcLink;
    }
}
