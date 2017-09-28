<?php

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\CynicDeserialiser;
use POData\ObjectModel\ODataEntry;

class CynicDeserialiserDummy extends CynicDeserialiser
{
    public function isEntryProcessed(ODataEntry $payload, $depth = 0)
    {
        return parent::isEntryProcessed($payload);
    }
}
