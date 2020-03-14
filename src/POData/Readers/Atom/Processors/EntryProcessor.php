<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use POData\Common\ODataConstants;
use POData\ObjectModel\AtomObjectModel\AtomContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataTitle;
use POData\Readers\Atom\Processors\Entry\LinkProcessor;
use POData\Readers\Atom\Processors\Entry\PropertyProcessor;

class EntryProcessor extends BaseNodeHandler
{
    private $oDataEntry;

    /**
     * @var LinkProcessor|PropertyProcessor $subProcessor
     */
    private $subProcessor;

    /** @noinspection PhpUnusedParameterInspection */
    public function __construct()
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
        parent::handleStartNode($tagNamespace, $tagName, $attributes);
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        if (strtolower($tagNamespace) !== strtolower(ODataConstants::ATOM_NAMESPACE)) {
            $this->subProcessor->handleEndNode($tagNamespace, $tagName);
            return;
        }
        parent::handleEndNode($tagNamespace, $tagName);
    }

    protected function handleStartAtomId()
    {
        $this->enqueueEnd(function () {
            $this->oDataEntry->id = $this->popCharData();
        });
    }

    protected function handleStartAtomTitle($attributes)
    {
        $titleType = $this->arrayKeyOrDefault(
            $attributes,
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
            ''
        );
        $this->enqueueEnd(function () use ($titleType) {
            $this->oDataEntry->title = new ODataTitle($this->popCharData(), $titleType);
        });
    }

    protected function handleStartAtomSummary()
    {
        //TODO: for some reason we do not support this......
        $this->enqueueEnd($this->doNothing());
    }

    protected function handleStartAtomUpdated()
    {
        $this->enqueueEnd(function () {
            $this->oDataEntry->updated = $this->popCharData();
        });
    }

    protected function handleStartAtomLink($attributes)
    {
        $this->subProcessor = $linkProcessor = new LinkProcessor($attributes);
        $this->enqueueEnd(function () use ($linkProcessor) {
            $this->handleLink($linkProcessor->getObjetModelObject());
            $this->subProcessor = null;
        });
    }

    protected function handleStartAtomCategory($attributes)
    {
        $odataCategory = new ODataCategory(
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_CATEGORY_TERM_ATTRIBUTE_NAME, ''),
            $this->arrayKeyOrDefault(
                $attributes,
                ODataConstants::ATOM_CATEGORY_SCHEME_ATTRIBUTE_NAME,
                'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme'
            )
        );
        $this->oDataEntry->setType($odataCategory);
        $this->enqueueEnd($this->doNothing());
    }

    protected function handleStartAtomContent($attributes)
    {
        $this->subProcessor       = new PropertyProcessor();
        $atomContent              = new AtomContent(
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, 'application/xml')
        );
        $this->enqueueEnd(function () use ($atomContent) {
            $atomContent->properties = $this->subProcessor->getObjetModelObject();
            $this->oDataEntry->setAtomContent($atomContent);
            $this->subProcessor = null;
        });
    }

    protected function handleStartAtomName()
    {
        $this->enqueueEnd($this->doNothing());
    }

    protected function handleStartAtomAuthor()
    {
        $this->enqueueEnd($this->doNothing());
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

    /**
     * @param ODataLink|ODataMediaLink $link
     */
    private function handleLink($link)
    {
        switch (true) {
            case $link instanceof ODataMediaLink:
                $this->handleODataMediaLink($link);
                break;
            case $link instanceof ODataLink:
                $this->handleODataLink($link);
                break;
        }
    }

    private function handleODataLink(ODataLink $link)
    {
        if ($link->name === ODataConstants::ATOM_EDIT_RELATION_ATTRIBUTE_VALUE) {
            $this->oDataEntry->editLink = $link;
        } else {
            $this->oDataEntry->links[] = $link;
        }
    }

    private function handleODataMediaLink(ODataMediaLink $link)
    {
        if ($link->name === ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE) {
            $this->oDataEntry->mediaLink        = $link;
            $this->oDataEntry->isMediaLinkEntry = true;
        } else {
            $this->oDataEntry->mediaLinks[] = $link;
        }
    }
}
