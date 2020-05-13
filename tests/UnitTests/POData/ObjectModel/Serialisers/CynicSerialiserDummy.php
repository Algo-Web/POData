<?php

declare(strict_types=1);

namespace UnitTests\POData\ObjectModel\Serialisers;

use POData\ObjectModel\CynicSerialiser;
use POData\UriProcessor\SegmentStack;

class CynicSerialiserDummy extends CynicSerialiser
{
    public function getCurrentExpandedProjectionNode()
    {
        return parent::getCurrentExpandedProjectionNode();
    }

    public function shouldExpandSegment($navigationPropertyName)
    {
        return parent::shouldExpandSegment($navigationPropertyName);
    }

    public function getProjectionNodes()
    {
        return parent::getProjectionNodes();
    }

    public function needNextPageLink($resultSetCount)
    {
        return parent::needNextPageLink($resultSetCount);
    }

    public function getNextLinkUri($lastObject)
    {
        return parent::getNextLinkUri($lastObject);
    }

    public function setStack(SegmentStack $stack)
    {
        $this->stack = $stack;
    }
}
