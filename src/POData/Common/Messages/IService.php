<?php

namespace POData\Common\Messages;

trait IService
{
    /**
     * Message to show when service implementation does not provide a valid IMetadataProvider.
     *
     * @return string The message
     */
    public static function invalidMetadataInstance()
    {
        return 'IService.getMetdataProvider returns invalid object.';
    }

    /**
     * Message to show when service implementation does not provide a valid IQueryProvider.
     *
     *
     * @return string The message
     */
    public static function invalidQueryInstance()
    {
        return 'IService.getQueryProvider returns invalid object.';
    }
}
