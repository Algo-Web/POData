<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use POData\Common\ODataConstants;
use POData\ObjectModel\AtomObjectModel\AtomContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataTitle;
use POData\Readers\Atom\Processors\Entry\LinkProcessor;
use POData\Readers\Atom\Processors\Entry\PropertyProcessor;

class EntryProcessor extends BaseNodeHandler
{
    private $oDataEntry;
    private $titleType;

    /**
     * @var AtomContent|ODataCategory
     */
    private $objectModelSubNode;

    /**
     * @var LinkProcessor|PropertyProcessor $subProcessor
     */
    private $subProcessor;

    public function __construct($attributes)
    {
        $this->oDataEntry                   = new ODataEntry();
        $this->oDataEntry->isMediaLinkEntry = false;
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        if (strtolower($tagNamespace) !== strtolower(ODataConstants::ATOM_NAMESPACE)) {
            $this->subProcessor->handleStartNode($tagNamespace, $tagName, $attributes);
            return;
        }
        switch (strtolower($tagName)) {
            case strtolower(ODataConstants::ATOM_ID_ELEMENT_NAME):
                break;
            case strtolower(ODataConstants::ATOM_TITLE_ELELMET_NAME):
                $this->titleType = $this->arrayKeyOrDefault(
                    $attributes,
                    ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
                    ''
                );
                break;
            case strtolower(ODataConstants::ATOM_SUMMARY_ELEMENT_NAME):
                //TODO: for some reason we do not support this......
                break;
            case strtolower(ODataConstants::ATOM_UPDATED_ELEMENT_NAME):
                break;

            case strtolower(ODataConstants::ATOM_LINK_ELEMENT_NAME):
                $this->subProcessor = new LinkProcessor($attributes);
                break;
            case strtolower(ODataConstants::ATOM_CATEGORY_ELEMENT_NAME):
                $this->objectModelSubNode = new ODataCategory(
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_CATEGORY_TERM_ATTRIBUTE_NAME, ''),
                    $this->arrayKeyOrDefault(
                        $attributes,
                        ODataConstants::ATOM_CATEGORY_SCHEME_ATTRIBUTE_NAME,
                        'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme'
                    )
                );
                break;
            case strtolower(ODataConstants::ATOM_CONTENT_ELEMENT_NAME):
                $this->subProcessor       = new PropertyProcessor($attributes);
                $this->objectModelSubNode = new AtomContent(
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 'application/xml')
                );
                break;
            case strtolower(ODataConstants::ATOM_NAME_ELEMENT_NAME):
            case strtolower(ODataConstants::ATOM_AUTHOR_ELEMENT_NAME):
                break;
            default:
                if (null === $this->subProcessor) {
                    dd($tagName);
                }
                $this->subProcessor->handleStartNode($tagNamespace, $tagName, $attributes);
                break;
        }
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        if (strtolower($tagNamespace) !== strtolower(ODataConstants::ATOM_NAMESPACE)) {
            $this->subProcessor->handleEndNode($tagNamespace, $tagName);
            return;
        }
        switch (strtolower($tagName)) {
            case strtolower(ODataConstants::ATOM_ID_ELEMENT_NAME):
                $this->oDataEntry->id = $this->popCharData();
                break;
            case strtolower(ODataConstants::ATOM_TITLE_ELELMET_NAME):
                $this->oDataEntry->title = new ODataTitle($this->popCharData(), $this->titleType);
                $this->titleType         = null;
                break;
            case strtolower(ODataConstants::ATOM_SUMMARY_ELEMENT_NAME):
                //TODO: for some reason we do not support this......
                break;
            case strtolower(ODataConstants::ATOM_UPDATED_ELEMENT_NAME):
                $this->oDataEntry->updated = $this->popCharData();
                break;
            case strtolower(ODataConstants::ATOM_LINK_ELEMENT_NAME):
                 $this->handleLink($this->subProcessor->getObjetModelObject());
                 $this->subProcessor = null;
                break;
            case strtolower(ODataConstants::ATOM_CATEGORY_ELEMENT_NAME):
                $this->oDataEntry->setType($this->objectModelSubNode);
                break;
            case strtolower(ODataConstants::ATOM_CONTENT_ELEMENT_NAME):
                $this->objectModelSubNode->properties = $this->subProcessor->getObjetModelObject();
                $this->oDataEntry->setAtomContent($this->objectModelSubNode);
                $this->subProcessor = null;
                break;
            case strtolower(ODataConstants::ATOM_NAME_ELEMENT_NAME):
            case strtolower(ODataConstants::ATOM_AUTHOR_ELEMENT_NAME):
                break;
            default:
                $this->subProcessor->handleEndNode($tagNamespace, $tagName);
                break;
        }
    }

    public function handleChildComplete($objectModel)
    {
        $this->subProcessor->handleChildComplete($objectModel);
    }

    public function getObjetModelObject()
    {
        return $this->oDataEntry;
    }

    public function handleCharacterData($characters)
    {
        if (null === $this->subProcessor) {
            parent::handleCharacterData($characters);
        } else {
            $this->subProcessor->handleCharacterData($characters);
        }
    }

    private function handleLink(ODataLink $link)
    {
        switch ($link->name) {
            case ODataConstants::ATOM_EDIT_RELATION_ATTRIBUTE_VALUE:
                $this->oDataEntry->editLink = $link;
                break;
            case ODataConstants::ODATA_ASSOCIATION_NAMESPACE . $link->title:
            case ODataConstants::ODATA_RELATED_NAMESPACE . $link->title:
                $this->oDataEntry->links[] = $link;
                break;
            case ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE:
                $this->oDataEntry->mediaLink = $link;
                $this->oDataEntry            = true;
                break;
            default:
                $this->oDataEntry->mediaLinks[] = $link;
        }
    }
}
