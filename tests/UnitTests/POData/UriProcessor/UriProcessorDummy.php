<?php

declare(strict_types=1);

namespace UnitTests\POData\UriProcessor;

use POData\UriProcessor\UriProcessor;

class UriProcessorDummy extends UriProcessor
{
    public function executeGet()
    {
        parent::executeGet();
    }

    public function executePost()
    {
        parent::executePost();
    }
}
