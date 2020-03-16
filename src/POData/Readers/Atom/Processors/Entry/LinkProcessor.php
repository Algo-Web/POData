<?php

declare(strict_types=1);


namespace POData\Readers\Atom\Processors\Entry;

use POData\Common\ODataConstants;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\Readers\Atom\Processors\BaseNodeHandler;

/**
 * Class LinkProcessor
 * @package POData\Readers\Atom\Processors\Entry
 */
class LinkProcessor extends BaseNodeHandler
{
    /**
     * @var ODataLink|ODataMediaLink
     */
    private $link;

    /**
     * LinkProcessor constructor.
     * @param $attributes
     */
    public function __construct($attributes)
    {
        switch ($this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, null)) {
            case ODataConstants::ATOM_EDIT_RELATION_ATTRIBUTE_VALUE:
            case ODataConstants::ODATA_RELATED_NAMESPACE .
                 $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TITLE_ELELMET_NAME, ''):
                $object = ODataLink::class;
                break;
            case ODataConstants::ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE:
                $object = ODataMediaLink::class;
                break;
            default:
                $object = ODataLink::class;
        }
        $this->link = new $object(
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_LINK_RELATION_ATTRIBUTE_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TITLE_ELELMET_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_TYPE_ATTRIBUTE_NAME, ''),
            $this->arrayKeyOrDefault($attributes, ODataConstants::ATOM_HREF_ATTRIBUTE_NAME, '')
        );
    }

    /**
     * @param $tagNamespace
     * @param $tagName
     * @param $attributes
     */
    public function handleStartNode($tagNamespace, $tagName, $attributes)
    {
        //The only sub notes that will exist will be the inline ones before we process the feed or entity.
        assert($tagNamespace  === strtolower(ODataConstants::ODATA_METADATA_NAMESPACE));
        assert($tagName === strtolower(ODataConstants::ATOM_INLINE_ELEMENT_NAME));
    }

    /**
     * @param $tagNamespace
     * @param $tagName
     */
    public function handleEndNode($tagNamespace, $tagName)
    {
        //The only sub notes that will exist will be the inline ones before we process the feed or entity.
        assert($tagNamespace  === strtolower(ODataConstants::ODATA_METADATA_NAMESPACE));
        assert($tagName === strtolower(ODataConstants::ATOM_INLINE_ELEMENT_NAME));
    }

    /**
     * @param $objectModel
     * @return mixed
     */
    public function handleChildComplete($objectModel)
    {
        assert(!$this->link instanceof ODataMediaLink);
        if ($objectModel instanceof ODataFeed) {
            $expandResult = new ODataExpandedResult(null, $objectModel);
        } else {
            assert($objectModel instanceof ODataEntry); // its an assumption but lets check it
            $expandResult = new ODataExpandedResult($objectModel, null);
        }
        $this->link->setExpandResult($expandResult);
    }

    /**
     * @return ODataLink|ODataMediaLink
     */
    public function getObjetModelObject()
    {
        return $this->link;
    }
}
