<?php

namespace POData\Common\Messages;

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

    public static function modelPayloadOnLinkModification()
    {
        return 'Entity model should be null when altering links';
    }
}
