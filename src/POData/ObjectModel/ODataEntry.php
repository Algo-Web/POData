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
     * @var ODataTitle
     */
    public $title;
    /**
     * Entry Edit Link.
     *
     * @var ODataLink
     */
    public $editLink;
    /**
     * Entry Type. This become the value of term attribute of Category element.
     *
     * @var ODataCategory
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

    /**
     * Last updated timestamp
     *
     * @var string
     */
    public $updated;

<<<<<<< HEAD
    /**
     * Service Base URI
     *
     * @var string
     */
    public $baseURI;

	public function atomContent(){
=======
    public $atomContent;

    public function getAtomContent()
    {
>>>>>>> Most recent attempt still debugging
        if(!$this->isMediaLinkEntry){
            return new \POData\ObjectModel\AtomObjectModel\AtomContent(\POData\Common\MimeTypes::MIME_APPLICATION_XML, null, $this->propertyContent->properties);
        }
        return new \POData\ObjectModel\AtomObjectModel\AtomContent($this->mediaLink->contentType,$this->mediaLink->srcLink);
    }

    public function setAtomContent($v)
    {
    }

    public $atomAuthor;

    public function getAtomAuthor()
    {
        return new \POData\ObjectModel\AtomObjectModel\AtomAuthor();
    }

    public function setAtomAuthor($v)
    {

    }

    public function getPropertyContent()
    {

        if (!$this->isMediaLinkEntry) {
            return null;
        }
        return $this->propertyContent->properties;
    }

    public function setPropertyContent($v)
    {
//        $e = new \Exception(); dd($e->getTraceAsString());
    }
}
