<?php

namespace ODataProducer\UriProcessor;

/**
 * Class RequestCountOption
 * @package ODataProducer\UriProcessor
 */
class RequestCountOption
{
    /**
     * No count option specified
     */
    const NONE = 0;

    /**
     * $count option specified
     */
    const VALUE_ONLY = 1;

    /**
     * $inlinecount option specified.
     */
    const INLINE = 2;
}