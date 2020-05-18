<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 9/05/20
 * Time: 5:46 PM.
 */
namespace UnitTests\POData\BatchProcessor;

use POData\BatchProcessor\IncomingChangeSetRequest;

class IncomingChangeSetRequestDummy extends IncomingChangeSetRequest
{
    /**
     * @param string $raw
     */
    public function setRawUrl(string $raw)
    {
        $this->rawUrl = $raw;
    }
}
