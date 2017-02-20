<?php

namespace POData\Common\Messages;

trait segmentParser
{
    /**
     * Format a message to show error when segment with
     * multiple positional keys present in the request uri.
     *
     * @param string $segment The segment with multiple positional keys
     *
     * @return string The formatted message
     */
    public static function segmentParserKeysMustBeNamed($segment)
    {
        return "Segments with multiple key values must specify them in 'name=value' form. For the segment $segment use named keys";
    }

    /**
     * Format a message to show error when a leaft segment
     * ($batch, $value, $metadata, $count, a bag property,
     * a named media resource or void service operation) is followed by a segment.
     *
     * @param string $leafSegment The leaf segment
     *
     * @return string The formatted message
     */
    public static function segmentParserMustBeLeafSegment($leafSegment)
    {
        return "The request URI is not valid. The segment '$leafSegment' must be the last segment in the URI because it is one of the following: \$batch, \$value, \$metadata, \$count, a bag property, a named media resource, or a service operation that does not return a value.";
    }

    /**
     * Format a message to show error when a segment follows a post link segment.
     *
     * @param string $postPostLinkSegment The segment following post link segment
     *
     * @return string The formatted message
     */
    public static function segmentParserNoSegmentAllowedAfterPostLinkSegment($postPostLinkSegment)
    {
        return "The request URI is not valid. The segment '$postPostLinkSegment' is not valid. Since the uri contains the \$links segment, there must be only one segment specified after that.";
    }

    /**
     * Format a message to show error when a segment otherthan
     * $value is followed by primitive segment.
     *
     * @param string $segment                  The segment follows
     *                                         primitive property segment
     * @param string $primitivePropertySegment The primitive property segment
     *
     * @return string The formatted message
     */
    public static function segmentParserOnlyValueSegmentAllowedAfterPrimitivePropertySegment($segment, $primitivePropertySegment)
    {
        return "The segment '$segment' in the request URI is not valid. Since the segment '$primitivePropertySegment' refers to a primitive type property, the only supported value from the next segment is '\$value'.";
    }

    /**
     * Format a message to show error when try to query a collection segment.
     *
     * @param string $collectionSegment The segment representing collection
     *
     * @return string The formatted message
     */
    public static function segmentParserCannotQueryCollection($collectionSegment)
    {
        return "The request URI is not valid. Since the segment '$collectionSegment' refers to a collection, this must be the last segment in the request URI. All intermediate segments must refer to a single resource.";
    }

    /**
     * Format a message to show error when a count segment is followed by singleton.
     *
     * @param string $segment The singleton segment
     *
     * @return string The formatted message
     */
    public static function segmentParserCountCannotFollowSingleton($segment)
    {
        return "The request URI is not valid, since the segment '$segment' refers to a singleton, and the segment '\$count' can only follow a resource collection.";
    }

    /**
     * Format a message to show error when a link segment is
     * followed by non-entity segment.
     *
     * @param string $segment The segment follows primitive property segment
     *
     * @return string The formatted message
     */
    public static function segmentParserLinkSegmentMustBeFollowedByEntitySegment($segment)
    {
        return "The request URI is not valid. The segment '$segment' must refer to a navigation property since the previous segment identifier is '\$links'.";
    }

    /**
     * A message to show error when no segment follows a link segment.
     *
     * @return string The message
     */
    public static function segmentParserMissingSegmentAfterLink()
    {
        return "The request URI is not valid. There must a segment specified after the '\$links' segment and the segment must refer to a entity resource.";
    }

    /**
     * Format a message to show error when a segment
     * found on the root which cannot be applied on root.
     *
     * @param string $segment The segment found
     *
     * @return string The formatted message
     */
    public static function segmentParserSegmentNotAllowedOnRoot($segment)
    {
        return "The request URI is not valid, the segment '$segment' cannot be applied to the root of the service";
    }

    /**
     * Message to show error when there is a inconsistency while parsing segments.
     *
     * @return string The message
     */
    public static function segmentParserInconsistentTargetKindState()
    {
        return 'Paring of segments failed for inconsistent target kind state, contact provider';
    }

    /**
     * Format a message to show error when expecting a
     * property kind not found while paring segments.
     *
     * @param string $expectedKind The exptected property kind as string
     *
     * @return string
     */
    public static function segmentParserUnExpectedPropertyKind($expectedKind)
    {
        return "Paring of segments failed expecting $expectedKind, contact provider";
    }

    /**
     * Format a message to show error when trying to apply count on non-resource.
     *
     * @param string $segment The non-resource segment
     *
     * @return string The message
     */
    public static function segmentParserCountCannotBeApplied($segment)
    {
        return "The request URI is not valid, \$count cannot be applied to the segment '$segment' since \$count can only follow a resource segment.";
    }
}
