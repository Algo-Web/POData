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
 * TODO: the methods should be rearranged to match theorder of the properties.
 * TODO: the properties are still public needs a lot of unpicking to work out as type hints maybe wrong.
 */
class ODataEntry extends ODataContainerBase
{
    /**
     * Entry Edit Link.
     *
     * @var ODataLink|null
     */
    public $editLink;
    /**
     * Entry Type. This become the value of term attribute of Category element.
     *
     * @var ODataCategory|null
     */
    public $type;
    /**
     * Instance to hold entity properties.
     * Properties corresponding to "m:properties" under content element
     * in the case of Non-MLE. For MLE "m:properties" is direct child of entry.
     *
     * @var ODataPropertyContent|null
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
     * @var ODataMediaLink|null
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
     * @var string|null
     */
    public $eTag;

    /**
     * True if this is a media link entry.
     *
     * @var bool|null
     */
    public $isMediaLinkEntry;

    /**
     * The name of the resource set this entry belongs to, use in metadata output.
     *
     * @var string|null
     */
    public $resourceSetName;



    /**
     * ODataEntry constructor.
     * @param string|null $id
     * @param string|null $selfLink
     * @param ODataTitle|null $title
     * @param ODataLink|null $editLink
     * @param ODataCategory|null $type
     * @param ODataPropertyContent|null $propertyContent
     * @param array $mediaLinks
     * @param ODataMediaLink|null $mediaLink
     * @param array $links
     * @param string|null $eTag
     * @param bool|null $isMediaLinkEntry
     * @param string|null $resourceSetName
     * @param string|null $updated
     * @param string|null $baseURI
     */
    public function __construct(
        ?string $id = null,
        ?string $selfLink = null,
        ?ODataTitle $title = null,
        ?ODataLink $editLink = null,
        ?ODataCategory $type = null,
        ?ODataPropertyContent $propertyContent = null,
        array $mediaLinks = [],
        ?ODataMediaLink $mediaLink = null,
        array $links = [],
        ?string $eTag = null,
        ?bool $isMediaLinkEntry = null,
        ?string $resourceSetName = null,
        ?string $updated = null,
        ?string $baseURI = null
    ) {
        $this->id = $id;
        $this->selfLink = $selfLink;
        $this->title = $title;
        $this->editLink = $editLink;
        $this->type = $type;
        $this->propertyContent = $propertyContent;
        $this->mediaLinks = $mediaLinks;
        $this->mediaLink = $mediaLink;
        $this->links = $links;
        $this->eTag = $eTag;
        $this->isMediaLinkEntry = $isMediaLinkEntry;
        $this->resourceSetName = $resourceSetName;
        $this->updated = $updated;
        $this->baseURI = $baseURI;
    }


    /**
     * @return string|null
     */
    public function getETag(): ?string
    {
        return $this->eTag;
    }

    /**
     * @param string|null $eTag
     * @return ODataEntry
     */
    public function setETag(?string $eTag): ODataEntry
    {
        $this->eTag = $eTag;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsMediaLinkEntry(): ?bool
    {
        return $this->isMediaLinkEntry;
    }

    /**
     * @param bool|null $isMediaLinkEntry
     * @return ODataEntry
     */
    public function setIsMediaLinkEntry(?bool $isMediaLinkEntry): ODataEntry
    {
        $this->isMediaLinkEntry = $isMediaLinkEntry;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getResourceSetName(): ?string
    {
        return $this->resourceSetName;
    }

    /**
     * @param string|null $resourceSetName
     * @return ODataEntry
     */
    public function setResourceSetName(?string $resourceSetName): ODataEntry
    {
        $this->resourceSetName = $resourceSetName;
        return $this;
    }

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
     * @return ODataEntry
     */
    public function setAtomContent(AtomObjectModel\AtomContent $atomContent): self
    {
        $this->setPropertyContent($atomContent->properties);
        return $this;
    }

    /**
     * @return AtomAuthor
     */
    public function getAtomAuthor(): AtomAuthor
    {
        return new AtomObjectModel\AtomAuthor();
    }

    /**
     * @return null|ODataPropertyContent
     */
    public function getPropertyContent(): ?ODataPropertyContent
    {
        if (!$this->isMediaLinkEntry) {
            return null;
        }
        return $this->propertyContent;
    }

    /**
     * @param ODataPropertyContent|null $oDataPropertyContent
     * @return ODataEntry
     */
    public function setPropertyContent(ODataPropertyContent $oDataPropertyContent = null): self
    {
        $this->propertyContent = $oDataPropertyContent;
        return $this;
    }

    /**
     * @return ODataLink
     */
    public function getEditLink(): ODataLink
    {
        return $this->editLink;
    }

    /**
     * @return ODataCategory
     */
    public function getType(): ?ODataCategory
    {
        return $this->type;
    }

    /**
     * @param ODataCategory|null $type
     * @return ODataEntry
     */
    public function setType(ODataCategory $type = null): self
    {
        $this->type = $type;
        if (null !== $type) {
            $rawTerm               = $type->getTerm();
            $termArray             = explode('.', $rawTerm);
            $final                 = $termArray[count($termArray) - 1];
            $this->resourceSetName = MetadataManager::getResourceSetNameFromResourceType($final);
        }
        return $this;
    }

    /**
     * @return ODataLink[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @param $links ODataLink[]
     * @return ODataEntry
     */
    public function setLinks(array $links): self
    {
        $this->links = [];
        foreach ($links as $link) {
            if ('edit' == $link->getName()) {
                $this->editLink        = $link;
                $this->resourceSetName = explode('(', $link->getUrl())[0];
                continue;
            }
            if ('http://schemas.microsoft.com/ado/2007/08/dataservices/related' == substr($link->getName(), 0, 61)
            ) {
                $this->links[] = $link;
                continue;
            }
        }
        return $this;
    }

    /**
     * @return ODataMediaLink[]
     */
    public function getMediaLinks(): array
    {
        return $this->mediaLinks;
    }

    /**
     * @param ODataMediaLink[] $mediaLinks
     * @return ODataEntry
     */
    public function setMediaLinks(array $mediaLinks): self
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
        return $this;
    }

    /**
     * @param ODataMediaLink      $mediaLink
     * @param ODataMediaLink|null $editLink
     */
    private function handleMediaLinkEntry(ODataMediaLink $mediaLink, ODataMediaLink &$editLink = null): void
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
    private function correctMediaLinkSrc(ODataMediaLink $editLink = null): void
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
    public function getMediaLink(): ODataMediaLink
    {
        return $this->mediaLink;
    }

    /**
     * @param  string|null $msg
     * @return bool
     */
    public function isOk(&$msg = null): bool
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
