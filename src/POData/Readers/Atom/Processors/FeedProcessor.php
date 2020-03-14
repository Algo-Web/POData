<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

use ParseError;
use POData\Common\ODataConstants;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataTitle;

class FeedProcessor extends BaseNodeHandler
{


    /**
     * @var ODataFeed
     */
    private $oDataFeed;

    private $charData = '';

    private $titleType;

    public function __construct($attributes)
    {
        $this->oDataFeed = new ODataFeed();
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        switch (strtolower($tagNamespace)) {
            case strtolower(ODataConstants::ATOM_NAMESPACE):
                $this->handleAtomStart($tagName, $attributes);
                break;
            case strtolower(ODataConstants::ODATA_METADATA_NAMESPACE):
                $this->handleMetadataStart($tagName, $attributes);
                break;
        }
    }

    public function handleEndNode($tagNamespace, $tagName)
    {
        switch (strtolower($tagNamespace)) {
            case strtolower(ODataConstants::ATOM_NAMESPACE):
                $this->handleAtomEnd($tagName);
                break;
            case strtolower(ODataConstants::ODATA_METADATA_NAMESPACE):
                $this->handleMetadataEnd($tagName);
                break;
        }
    }

    public function handleAtomStart($tagName, $attributes)
    {
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
            case strtolower(ODataConstants::ATOM_UPDATED_ELEMENT_NAME):
                break;
            case strtolower(ODataConstants::ATOM_LINK_ELEMENT_NAME):
                $rel                      = $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, '');
                $prop                     = $rel === ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE ? 'selfLink' : 'nextPageLink';
                $this->oDataFeed->{$prop} = new ODataLink(
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, ''),
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TITLE_ELELMET_NAME, ''),
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, ''),
                    $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, '')
                );
                break;
            default:
                $this->onParseError('Atom', 'Start', $tagName);
        }
    }

    public function handleAtomEnd($tagName)
    {
        switch (strtolower($tagName)) {
            case strtolower(ODataConstants::ATOM_ID_ELEMENT_NAME):
                $this->oDataFeed->id = $this->popCharData();
                break;
            case strtolower(ODataConstants::ATOM_TITLE_ELELMET_NAME):
                $this->oDataFeed->title = new ODataTitle($this->popCharData(), $this->titleType);
                $this->titleType        = null;
                break;
            case strtolower(ODataConstants::ATOM_UPDATED_ELEMENT_NAME):
                $this->oDataFeed->updated = $this->popCharData();
                break;
            case strtolower(ODataConstants::ATOM_LINK_ELEMENT_NAME):
                break;
            default:
                $this->onParseError('Atom', 'End', $tagName);
        }
    }

    public function handleMetadataStart($tagName, $attributes)
    {
        switch (strtolower($tagName)) {
            case strtolower(ODataConstants::ROWCOUNT_ELEMENT):
                break;
            default:
                $this->onParseError('Metadata', 'Start', $tagName);
        }
    }

    public function handleMetadataEnd($tagName)
    {
        switch (strtolower($tagName)) {
            case strtolower(ODataConstants::ROWCOUNT_ELEMENT):
                $this->oDataFeed->rowCount = $this->charData;
                $this->charData            = '';
                break;
            default:
                $this->onParseError('Metadata', 'End', $tagName);
        }
    }

    public function handleChildComplete($objectModel)
    {
        //assert($objectModel instanceof ODataEntry);
        $this->oDataFeed->entries[] = $objectModel;
    }

    public function getObjetModelObject()
    {
        return $this->oDataFeed;
    }
}
