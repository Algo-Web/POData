<?php

namespace POData\ObjectModel;

/**
 * Class ODataEntry.
 */
class ODataEntry
{
    /**
     * Entry id.
     *
     * @var string
     */
    public $id;
    /**
     * Entry Self Link.
     *
     * @var string
     */
    public $selfLink;
    /**
     * Entry title.
     *
     * @var string
     */
    public $title;
    /**
     * Entry Edit Link.
     *
     * @var string
     */
    public $editLink;
    /**
     * Entry Type. This become the value of term attribute of Category element.
     *
     * @var string
     */
    public $type;
    /**
     * Instance to hold entity properties.
     * Properties corresponding to "m:properties" under content element
     * in the case of Non-MLE. For MLE "m:properties" is direct child of entry.
     *
     * @var ODataPropertyContent
     */
    public $propertyContent;
    /**
     * Collection of entry media links (Named Stream Links).
     *
     * @var array<ODataMediaLink>
     */
    public $mediaLinks = [];
    /**
     * media link entry (MLE Link).
     *
     * @var ODataMediaLink
     */
    public $mediaLink;
    /**
     * Collection of navigation links (can be expanded).
     *
     * @var array<ODataLink>
     */
    public $links = [];
    /**
     * Entry ETag.
     *
     * @var string
     */
    public $eTag;

    /**
     * True if this is a media link entry.
     *
     * @var bool
     */
    public $isMediaLinkEntry;

    /**
     * The name of the resource set this entry belongs to, use in metadata output.
     *
     * @var string
     */
    public $resourceSetName;
}
