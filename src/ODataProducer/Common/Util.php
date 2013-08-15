<?php

namespace ODataProducer\Common;

/**
 * Class Util
 * @package ODataProducer\Common
 */
class Util
{
    /**
     * Append an escaped segment to a uri.
     * 
     * @param string $uri     The uri to append to.
     * @param string $segment The escaped segment.
     * 
     * @return string A new uri with a new segment escaped.
     */
    public static function appendEscapedSegmentToUri($uri, $segment)
    {
        return rtrim($uri, '/') . '/' . $segment;
    }

    /**
     * Append an un-escaped segment to a uri.
     * 
     * @param string $uri     The uri to append to.
     * @param string $segment The un-escaped segment.
     * 
     * @return string A new uri with a new segment escaped.
     */
    public static function appendUnEscapedSegmentToUri($uri, $segment)
    {
        return self::appendEscapedSegmentToUri($uri, urlencode($segment));
    }
}