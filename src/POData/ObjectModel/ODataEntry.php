<?php

declare(strict_types=1);

namespace POData\ObjectModel;

use AlgoWeb\ODataMetadata\MetadataManager;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\ObjectModel\AtomObjectModel\AtomAuthor;
use POData\ObjectModel\AtomObjectModel\AtomContent;

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
     * Last updated timestamp.
     *
     * @var string
     */
    public $updated;

    /**
     * Service Base URI.
     *
     * @var string
     */
    public $baseURI;

    /**
     * @var AtomObjectModel\AtomContent
     */
    public $atomContent;
    /**
     * @var AtomObjectModel\AtomAuthor
     */
    public $atomAuthor;

    /**
     * @return AtomContent
     */
    public function getAtomContent()
    {
        if (!$this->isMediaLinkEntry) {
            return new AtomObjectModel\AtomContent(
                MimeTypes::MIME_APPLICATION_XML,
                null,
                $this->propertyContent
            );
        }
        return new AtomObjectModel\AtomContent($this->mediaLink->contentType, $this->mediaLink->srcLink);
    }

    /**
     * @param AtomContent $atomContent
     */
    public function setAtomContent(AtomObjectModel\AtomContent $atomContent)
    {
        $this->setPropertyContent($atomContent->properties);
        $this->atomContent = $atomContent;
    }

    /**
     * @return AtomAuthor
     */
    public function getAtomAuthor()
    {
        return new AtomObjectModel\AtomAuthor();
    }

    /**
     * @return null|ODataPropertyContent
     */
    public function getPropertyContent()
    {
        if (!$this->isMediaLinkEntry) {
            return null;
        }
        return $this->propertyContent;
    }

    /**
     * @param ODataPropertyContent|null $oDataPropertyContent
     */
    public function setPropertyContent(ODataPropertyContent $oDataPropertyContent = null)
    {
        $this->propertyContent = $oDataPropertyContent;
    }

    /**
     * @return ODataLink
     */
    public function getEditLink()
    {
        return $this->editLink;
    }

    /**
     * @return ODataCategory
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param ODataCategory|null $type
     */
    public function setType(ODataCategory $type = null)
    {
        $this->type = $type;
        if (null !== $type) {
            $rawTerm               = $type->getTerm();
            $termArray             = explode('.', $rawTerm);
            $final                 = $termArray[count($termArray) - 1];
            $this->resourceSetName = MetadataManager::getResourceSetNameFromResourceType($final);
        }
    }

    /**
     * @return ODataLink[]
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param $links ODataLink[]
     */
    public function setLinks(array $links)
    {
        $this->links = [];
        foreach ($links as $link) {
            if ('edit' == $link->name) {
                $this->editLink        = $link;
                $this->resourceSetName = explode('(', $link->url)[0];
                continue;
            }
            if ('http://schemas.microsoft.com/ado/2007/08/dataservices/related' == substr($link->name, 0, 61)
            ) {
                $this->links[] = $link;
                continue;
            }
        }
    }

    /**
     * @return ODataMediaLink[]
     */
    public function getMediaLinks()
    {
        return $this->mediaLinks;
    }

    /**
     * @param ODataMediaLink[] $mediaLinks
     */
    public function setMediaLinks(array $mediaLinks)
    {
        $this->mediaLinks = [];
        $editLink         = null;
        foreach ($mediaLinks as $mediaLink) {
            $this->handleMediaLinkEntry($mediaLink, $editLink);
        }
        $this->correctMediaLinkSrc($editLink);
        if (null === $this->mediaLink) {
            $this->isMediaLinkEntry = false;
        }
    }

    /**
     * @param ODataMediaLink      $mediaLink
     * @param ODataMediaLink|null $editLink
     */
    private function handleMediaLinkEntry(ODataMediaLink $mediaLink, ODataMediaLink &$editLink = null)
    {
        if ('edit-media' == $mediaLink->rel) {
            $this->isMediaLinkEntry = true;
            $this->mediaLink        = $mediaLink;
        }
        if (ODataConstants::ATOM_MEDIA_RESOURCE_RELATION_ATTRIBUTE_VALUE == substr($mediaLink->rel, 0, 68)) {
            $this->mediaLinks[] = $mediaLink;
        }
        if ('edit' == $mediaLink->rel) {
            $editLink = $mediaLink;
        }
    }

    /**
     * @param ODataMediaLink|null $editLink
     */
    private function correctMediaLinkSrc(ODataMediaLink $editLink = null)
    {
        if (null !== $this->mediaLink && null !== $editLink) {
            $this->mediaLink->srcLink = $editLink->editLink . $this->mediaLink->editLink;
            foreach ($this->mediaLinks as $mediaLink) {
                $mediaLink->srcLink = $editLink->editLink . '/' . $mediaLink->name;
            }
        }
    }

    /**
     * @return ODataMediaLink
     */
    public function getMediaLink()
    {
        return $this->mediaLink;
    }

    /**
     * @param  string|null $msg
     * @return bool
     */
    public function isOk(&$msg = null)
    {
        if (!$this->propertyContent instanceof ODataPropertyContent) {
            $msg = 'Property content must be instanceof ODataPropertyContent.';
            return false;
        }
        if (0 === count($this->propertyContent->properties)) {
            $msg = 'Must have at least one property present.';
            return false;
        }

        return true;
    }
}
