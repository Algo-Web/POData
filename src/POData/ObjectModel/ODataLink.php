<?php

namespace POData\ObjectModel;

/**
 * Class ODataLink represents an OData Navigation Link.
 */
class ODataLink
{
    /**
     * Name of the link. This becomes last segment of rel attribute value.
     *
     * @var string
     */
    public $name;
    /**
     * Title of the link. This become value of title attribute.
     *
     * @var string
     */
    public $title;
    /**
     * Type of link.
     *
     * @var string
     */
    public $type;
    /**
     * Url to the navigation property. This become value of href attribute.
     *
     * @var string
     */
    public $url;
    /**
     * Checks is Expand result contains single entity or collection of
     * entities i.e. feed.
     *
     * @var bool
     */
    public $isCollection;
    /**
     * The expanded result. This becomes the inline content of the link.
     *
     * @var ODataEntry|ODataFeed
     */
    public $expandedResult;
    /**
     * True if Link is Expanded, False if not.
     *
     * @var bool
     */
    public $isExpanded;
}
