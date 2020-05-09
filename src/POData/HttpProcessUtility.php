<?php

declare(strict_types=1);

namespace POData;

use POData\Common\HttpHeaderFailure;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\Char;

/**
 * Class HttpProcessUtility.
 */
class HttpProcessUtility
{
    /**
     * Gets the appropriate MIME type for the request, throwing if there is none.
     *
     * @param string   $acceptTypesText    Text as it appears in an HTTP
     *                                     Accepts header
     * @param string[] $exactContentTypes  Preferred content type to match if an exact media type is given - this is in
     *                                     descending order of preference
     * @param string   $inexactContentType Preferred fallback content type for inexact matches
     *
     * @throws HttpHeaderFailure
     * @return string|null       One of exactContentType or inexactContentType
     */
    public static function selectRequiredMimeType(
        ?string $acceptTypesText,
        array $exactContentTypes,
        $inexactContentType
    ): ?string {
        $selectedContentType   = null;
        $selectedMatchingParts = -1;
        $selectedQualityValue  = 0;

        if (null === $acceptTypesText) {
            throw new HttpHeaderFailure(Messages::unsupportedMediaType(), 415);
        }

        $acceptTypes = self::mimeTypesFromAcceptHeaders($acceptTypesText);
        foreach ($acceptTypes as $acceptType) {
            foreach ($exactContentTypes as $exactContentType) {
                if (0 == strcasecmp($acceptType->getMimeType(), $exactContentType)) {
                    $selectedContentType  = $exactContentType;
                    $selectedQualityValue = $acceptType->getQualityValue();
                    break 2;
                }
            }

            $matchingParts = $acceptType->getMatchingRating($inexactContentType);
            if ($matchingParts < 0) {
                continue;
            }

            $candidateQualityValue = $acceptType->getQualityValue();
            // A more specific type wins.
            if ($matchingParts > $selectedMatchingParts ||
                (
                    $matchingParts == $selectedMatchingParts &&
                    // A type with a higher q-value wins.
                    $candidateQualityValue > $selectedQualityValue
                )) {
                $selectedContentType = $inexactContentType;
                $selectedMatchingParts = $matchingParts;
                $selectedQualityValue = $candidateQualityValue;
            }
        }

        if ((null === $selectedContentType || 0 == $selectedQualityValue) &&
            !empty($acceptTypes)) {
            throw new HttpHeaderFailure(Messages::unsupportedMediaType(), 415);
        }

        return empty($acceptTypes) ? $inexactContentType : $selectedContentType;
    }

    /**
     * Returns all MIME types from the $text.
     *
     * @param string $text Text as it appears on an HTTP Accepts header
     *
     * @throws HttpHeaderFailure If found any syntax error in the given text
     * @return MediaType[]       Array of media (MIME) type description
     */
    public static function mimeTypesFromAcceptHeaders(string $text): array
    {
        $mediaTypes = [];
        $textIndex  = 0;
        while (!self::skipWhiteSpace($text, $textIndex)) {
            $type    = null;
            $subType = null;
            self::readMediaTypeAndSubtype($text, $textIndex, $type, $subType);

            $parameters = [];
            while (!self::skipWhitespace($text, $textIndex)) {
                if (',' == $text[$textIndex]) {
                    ++$textIndex;
                    break;
                }

                if (';' != $text[$textIndex]) {
                    throw new HttpHeaderFailure(
                        Messages::httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter(),
                        400
                    );
                }

                ++$textIndex;
                if (self::skipWhiteSpace($text, $textIndex)) {
                    break;
                }

                self::readMediaTypeParameter($text, $textIndex, $parameters);
            }

            $mediaTypes[] = new MediaType($type, $subType, $parameters);
        }

        return $mediaTypes;
    }

    /**
     * Skips whitespace in the specified text by advancing an index to
     * the next non-whitespace character.
     *
     * @param string $text       Text to scan
     * @param int    &$textIndex Index to begin scanning from
     *
     * @return bool true if the end of the string was reached, false otherwise
     */
    public static function skipWhiteSpace(string $text, int &$textIndex): bool
    {
        $textLen = strlen(strval($text));
        while (($textIndex < $textLen) && Char::isWhiteSpace($text[$textIndex])) {
            ++$textIndex;
        }

        return $textLen == $textIndex;
    }

    /**
     * Reads the type and subtype specifications for a MIME type.
     *
     * @param string $text       Text in which specification exists
     * @param int    &$textIndex Pointer into text
     * @param string &$type      Type of media found
     * @param string &$subType   Subtype of media found
     *
     * @throws HttpHeaderFailure If failed to read type and sub-type
     */
    public static function readMediaTypeAndSubtype(
        string $text,
        int &$textIndex,
        &$type,
        &$subType
    ): void {
        $textStart = $textIndex;
        if (self::readToken($text, $textIndex)) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeUnspecified(),
                400
            );
        }

        if ('/' != $text[$textIndex]) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeRequiresSlash(),
                400
            );
        }

        $type = substr($text, $textStart, $textIndex - $textStart);
        ++$textIndex;

        $subTypeStart = $textIndex;
        self::readToken($text, $textIndex);
        if ($textIndex == $subTypeStart) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeRequiresSubType(),
                400
            );
        }

        $subType = substr($text, $subTypeStart, $textIndex - $subTypeStart);
    }

    /**
     * Reads a token on the specified text by advancing an index on it.
     *
     * @param string $text       Text to read token from
     * @param int    &$textIndex Index for the position being scanned on text
     *
     * @return bool true if the end of the text was reached; false otherwise
     */
    public static function readToken(string $text, int &$textIndex): bool
    {
        $textLen = strlen($text);
        while (($textIndex < $textLen) && self::isHttpTokenChar($text[$textIndex])) {
            ++$textIndex;
        }

        return $textLen == $textIndex;
    }

    /**
     * To check whether the given character is a HTTP token character
     * or not.
     *
     * @param string $char The character to inspect
     *
     * @return bool True if the given character is a valid HTTP token
     *              character, False otherwise
     */
    public static function isHttpTokenChar(string $char): bool
    {
        return 126 > ord($char) && 31 < ord($char) && !self::isHttpSeparator($char);
    }

    /**
     * To check whether the given character is a HTTP separator character.
     *
     * @param string $char The character to inspect
     *
     * @return bool True if the given character is a valid HTTP separator
     *              character, False otherwise
     */
    public static function isHttpSeparator(string $char): bool
    {
        $httpSeperators = ['(', ')', '<', '>', '@', ',', ';', ':', '\\', '"', '/', '[', ']', '?', '=', '{', '}', ' '];
        return in_array($char, $httpSeperators) || ord($char) == Char::TAB;
    }

    /**
     * Read a parameter for a media type/range.
     *
     * @param string $text        Text to read from
     * @param int    &$textIndex  Pointer in text
     * @param array  &$parameters Array with parameters
     *
     * @throws HttpHeaderFailure If found parameter value missing
     */
    public static function readMediaTypeParameter(string $text, int &$textIndex, array &$parameters)
    {
        $textStart = $textIndex;
        if (self::readToken($text, $textIndex)) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeMissingValue(),
                400
            );
        }

        $parameterName = substr($text, $textStart, $textIndex - $textStart);
        if ('=' != $text[$textIndex]) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeMissingValue(),
                400
            );
        }

        ++$textIndex;
        $parameterValue
                      = self::readQuotedParameterValue($parameterName, $text, $textIndex);
        $parameters[] = [$parameterName => $parameterValue];
    }

    /**
     * Reads Mime type parameter value for a particular parameter in the
     * Content-Type/Accept headers.
     *
     * @param string $parameterName Name of parameter
     * @param string $text          Header text
     * @param int    &$textIndex    Parsing index in $text
     *
     * @throws HttpHeaderFailure
     * @return string            String representing the value of the $parameterName parameter
     */
    public static function readQuotedParameterValue(
        string $parameterName,
        string $text,
        int &$textIndex
    ): ?string {
        $parameterValue = [];
        $textLen        = strlen($text);
        $valueIsQuoted  = false;
        if ($textIndex < $textLen) {
            if ('"' == $text[$textIndex]) {
                ++$textIndex;
                $valueIsQuoted = true;
            }
        }

        while ($textIndex < $textLen) {
            $currentChar = $text[$textIndex];

            if ('\\' == $currentChar || '"' == $currentChar) {
                if (!$valueIsQuoted) {
                    throw new HttpHeaderFailure(
                        Messages::httpProcessUtilityEscapeCharWithoutQuotes(
                            $parameterName
                        ),
                        400
                    );
                }

                ++$textIndex;

                // End of quoted parameter value.
                if ('"' == $currentChar) {
                    $valueIsQuoted = false;
                    break;
                }

                if ($textIndex >= $textLen) {
                    throw new HttpHeaderFailure(
                        Messages::httpProcessUtilityEscapeCharAtEnd($parameterName),
                        400
                    );
                }

                $currentChar = $text[$textIndex];
            } elseif (!self::isHttpTokenChar($currentChar)) {
                // If the given character is special, we stop processing.
                break;
            }

            $parameterValue[] = $currentChar;
            ++$textIndex;
        }

        if ($valueIsQuoted) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityClosingQuoteNotFound($parameterName),
                400
            );
        }

        return empty($parameterValue) ? null : implode('', $parameterValue);
    }

    /**
     * Selects an acceptable MIME type that satisfies the Accepts header.
     *
     * @param string   $acceptTypesText Text for Accepts header
     * @param string[] $availableTypes  Types that the server is willing to return, in descending order of preference
     *
     * @throws HttpHeaderFailure
     * @return string|null       The best MIME type for the client
     */
    public static function selectMimeType(string $acceptTypesText, array $availableTypes): ?string
    {
        $selectedContentType     = null;
        $selectedMatchingParts   = -1;
        $selectedQualityValue    = 0;
        $selectedPreferenceIndex = PHP_INT_MAX;
        $acceptable              = false;
        $acceptTypesEmpty        = true;

        $acceptTypes  = self::mimeTypesFromAcceptHeaders($acceptTypesText);
        $numAvailable = count($availableTypes);
        foreach ($acceptTypes as $acceptType) {
            $acceptTypesEmpty = false;
            for ($i = 0; $i < $numAvailable; ++$i) {
                $availableType = $availableTypes[$i];
                $matchRating   = $acceptType->getMatchingRating($availableType);
                if (0 > $matchRating) {
                    continue;
                }

                $candidateQualityValue = $acceptType->getQualityValue();
                if ($matchRating > $selectedMatchingParts) {
                    // A more specific type wins.
                    $selectedContentType     = $availableType;
                    $selectedMatchingParts   = $matchRating;
                    $selectedQualityValue    = $candidateQualityValue;
                    $selectedPreferenceIndex = $i;
                    $acceptable              = 0 != $selectedQualityValue;
                } elseif ($matchRating == $selectedMatchingParts) {
                    // A type with a higher q-value wins.
                    if ($candidateQualityValue > $selectedQualityValue) {
                        $selectedContentType     = $availableType;
                        $selectedQualityValue    = $candidateQualityValue;
                        $selectedPreferenceIndex = $i;
                        $acceptable              = 0 != $selectedQualityValue;
                    } elseif ($candidateQualityValue == $selectedQualityValue) {
                        // A type that is earlier in the availableTypes array wins.
                        if ($i < $selectedPreferenceIndex) {
                            $selectedContentType     = $availableType;
                            $selectedPreferenceIndex = $i;
                        }
                    }
                }
            }
        }

        if ($acceptTypesEmpty) {
            $selectedContentType = $availableTypes[0];
        } elseif (!$acceptable) {
            $selectedContentType = null;
        }

        return $selectedContentType;
    }

    /**
     * Reads the numeric part of a quality value substring, normalizing it to 0-1000.
     * rather than the standard 0.000-1.000 ranges.
     *
     * @param string $text       Text to read qvalue from
     * @param int    &$textIndex Index into text where the qvalue starts
     *
     * @throws HttpHeaderFailure If any error occurred while reading and processing
     *                           the quality factor
     * @return int               The normalised qvalue
     */
    public static function readQualityValue(string $text, int &$textIndex): int
    {
        $digit = $text[$textIndex++];
        if ('0' == $digit) {
            $qualityValue = 0;
        } elseif ('1' == $digit) {
            $qualityValue = 1;
        } else {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMalformedHeaderValue(),
                400
            );
        }

        $textLen = strlen($text);
        if ($textIndex < $textLen && '.' == $text[$textIndex]) {
            ++$textIndex;

            $adjustFactor = 1000;
            while (1 < $adjustFactor && $textIndex < $textLen) {
                $c         = $text[$textIndex];
                $charValue = self::digitToInt32($c);
                if (0 <= $charValue) {
                    ++$textIndex;
                    $adjustFactor /= 10;
                    $qualityValue *= 10;
                    $qualityValue += $charValue;
                } else {
                    break;
                }
            }

            $qualityValue *= $adjustFactor;
            if ($qualityValue > 1000) {
                // Too high of a value in qvalue.
                throw new HttpHeaderFailure(
                    Messages::httpProcessUtilityMalformedHeaderValue(),
                    400
                );
            }
        } else {
            $qualityValue *= 1000;
        }

        return $qualityValue;
    }

    /**
     * Converts the specified character from the ASCII range to a digit.
     *
     * @param string $c Character to convert
     *
     * @throws HttpHeaderFailure If $c is not ASCII value for digit or element separator
     * @return int               The Int32 value for $c, or -1 if it is an element separator
     */
    public static function digitToInt32(string $c): int
    {
        if ('0' <= $c && '9' >= $c) {
            return intval($c);
        } else {
            if (self::isHttpElementSeparator($c)) {
                return -1;
            } else {
                throw new HttpHeaderFailure(
                    Messages::httpProcessUtilityMalformedHeaderValue(),
                    400
                );
            }
        }
    }

    /**
     * Verifies whether the specified character is a valid separator in.
     * an HTTP header list of element.
     *
     * @param string $c Character to verify
     *
     * @return bool true if c is a valid character for separating elements;
     *              false otherwise
     */
    public static function isHttpElementSeparator(string $c): bool
    {
        return ',' == $c || ' ' == $c || '\t' == $c;
    }

    /**
     * Get server key by header.
     *
     * @param  string $headerName Name of header
     * @return string
     */
    public static function headerToServerKey(string $headerName): string
    {
        $name = strtoupper(str_replace('-', '_', $headerName));
        $prefixableKeys = ['HOST', 'CONNECTION', 'CACHE_CONTROL', 'ORIGIN', 'USER_AGENT', 'POSTMAN_TOKEN', 'ACCEPT',
            'ACCEPT_ENCODING', 'ACCEPT_LANGUAGE', 'DATASERVICEVERSION', 'MAXDATASERVICEVERSION'];
        return in_array($name, $prefixableKeys) ? 'HTTP_' . $name : $name;
    }
}
