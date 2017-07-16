<?php

namespace UnitTests\POData\Writers\Atom;

use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataProperty;
use POData\Writers\Atom\AtomODataWriter;

class AtomODataWriterDummy extends AtomODataWriter
{
    public function beforeWriteValue($value, $type = null)
    {
        return parent::beforeWriteValue($value, $type);
    }

    public function writeNullValue(ODataProperty $property)
    {
        return parent::writeNullValue($property);
    }

    /**
     * Start writing a entry.
     *
     * @param ODataEntry $entry      Entry to write
     * @param bool       $isTopLevel
     *
     * @return AtomODataWriter
     */
    public function writeBeginEntry(ODataEntry $entry, $isTopLevel)
    {
        return parent::writeBeginEntry($entry, $isTopLevel);
    }

    /**
     * @param ODataLink $link       Link to write
     *
     * @return AtomODataWriter
     */
    public function writeLink(ODataLink $link)
    {
        return parent::writeLink($link);
    }

    /**
     * Function to create link element with arguments.
     *
     * @param ODataLink $link       Link object to make link element
     * @param bool      $isExpanded Is link expanded or not
     *
     * @return AtomODataWriter
     */
    public function writeLinkNode(ODataLink $link, $isExpanded)
    {
        parent::writeLinkNode($link, $isExpanded);
    }
}
