<?php

declare(strict_types=1);


namespace POData\ObjectModel;

use POData\Common\ODataConstants;

class ODataNextPageLink extends ODataLink
{
    public function __construct(string $url)
    {
        parent::__construct(
            ODataConstants::ATOM_LINK_NEXT_ATTRIBUTE_STRING,
            null,
            null,
            $url
        );
    }
}
