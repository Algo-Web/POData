<?php
/** 
 * A utility class.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Common;
/**
 * Class for Utility
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Common
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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
?>