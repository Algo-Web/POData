<?php

declare(strict_types=1);

namespace POData\Common\Messages;

/**
 * Trait responseWriter.
 * @package POData\Common\Messages
 */
trait responseWriter
{
    /**
     * Returned when there's no writer available matching supplied constraints.
     *
     * @return string
     */
    public static function noWriterToHandleRequest()
    {
        return 'No writer can handle the request.';
    }

    /**
     * Returned when there's an entity model while altering links.
     *
     * @return string
     */
    public static function modelPayloadOnLinkModification()
    {
        return 'Entity model should be null when altering links';
    }
}
