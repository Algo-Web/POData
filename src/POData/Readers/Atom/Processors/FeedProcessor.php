<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors;

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

    private $titleType;

    public function __construct()
    {
        $this->oDataFeed = new ODataFeed();
    }

    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        assert(
            strtolower(ODataConstants::ATOM_NAMESPACE) === strtolower($tagNamespace) ||
            strtolower(ODataConstants::ODATA_METADATA_NAMESPACE) === strtolower($tagNamespace)
        );
        $AtomOrMetadata = strtolower(ODataConstants::ATOM_NAMESPACE) === strtolower($tagNamespace) ? 'Atom' : 'Metadata';
        $method = 'handleStart' . $AtomOrMetadata . ucfirst(strtolower($tagName));
        if (!method_exists($this, $method)) {
            $this->onParseError('Atom', $AtomOrMetadata, $tagName);
        }
        $this->{$method}($attributes);
    }
    public function handleStartAtomId(){
        $this->enqueueEnd(function (){
            $this->oDataFeed->id = $this->popCharData();
        });
    }
    public function handleStartAtomTitle($attributes){
        $this->titleType = $this->arrayKeyOrDefault(
            $attributes,
            ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME,
            ''
        );
        $this->enqueueEnd(function (){
            $this->oDataFeed->title = new ODataTitle($this->popCharData(), $this->titleType);
            $this->titleType        = null;
        });
    }
    public function handleStartAtomUpdated(){
        $this->enqueueEnd(function(){
            $this->oDataFeed->updated = $this->popCharData();
        });
    }

    public function handleStartAtomLink($attributes){
        $rel                      = $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, '');
        $prop                     = $rel === ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE ? 'selfLink' : 'nextPageLink';
        $this->oDataFeed->{$prop} = new ODataLink(
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TITLE_ELELMET_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, '')
        );
        $this->enqueueEnd($this->doNothing());
    }

    public function handleStartMetadataCount(){
        $this->enqueueEnd(function(){
            $this->oDataFeed->rowCount =  (int)$this->popCharData();
        });
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
