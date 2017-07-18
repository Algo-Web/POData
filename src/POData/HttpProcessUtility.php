<?php

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
     * @return string|null  One of exactContentType or inexactContentType
     */
    public static function selectRequiredMimeType(
        $acceptTypesText,
        $exactContentTypes,
        $inexactContentType
    ) {
        $selectedContentType = null;
        $selectedMatchingParts = -1;
        $selectedQualityValue = 0;
        $acceptable = false;
        $acceptTypesEmpty = true;
        $foundExactMatch = false;

        if (null !== $acceptTypesText) {
            $acceptTypes = self::mimeTypesFromAcceptHeaders($acceptTypesText);
            foreach ($acceptTypes as $acceptType) {
                $acceptTypesEmpty = false;
                foreach ($exactContentTypes as $exactContentType) {
                    if (strcasecmp($acceptType->getMimeType(), $exactContentType) == 0) {
                        $selectedContentType = $exactContentType;
                        $selectedQualityValue = $acceptType->getQualityValue();
                        $acceptable = $selectedQualityValue != 0;
                        $foundExactMatch = true;
                        break;
                    }
                }

                if ($foundExactMatch) {
                    break;
                }

                $matchingParts = $acceptType->getMatchingRating($inexactContentType);
                if ($matchingParts < 0) {
                    continue;
                }

                if ($matchingParts > $selectedMatchingParts) {
                    // A more specific type wins.
                    $selectedContentType = $inexactContentType;
                    $selectedMatchingParts = $matchingParts;
                    $selectedQualityValue = $acceptType->getQualityValue();
                    $acceptable = $selectedQualityValue != 0;
                } elseif ($matchingParts == $selectedMatchingParts) {
                    // A type with a higher q-value wins.
                    $candidateQualityValue = $acceptType->getQualityValue();
                    if ($candidateQualityValue > $selectedQualityValue) {
                        $selectedContentType = $inexactContentType;
                        $selectedQualityValue = $candidateQualityValue;
                        $acceptable = $selectedQualityValue != 0;
                    }
                }
            }
        }

        if (!$acceptable && !$acceptTypesEmpty) {
            throw new HttpHeaderFailure(
                Messages::unsupportedMediaType(),
                415
            );
        }

        if ($acceptTypesEmpty) {
            $selectedContentType = $inexactContentType;
        }

        return $selectedContentType;
    }

    /**
     * Selects an acceptable MIME type that satisfies the Accepts header.
     *
     * @param string   $acceptTypesText Text for Accepts header
     * @param string[] $availableTypes  Types that the server is willing to return, in descending order of preference
     *
     * @throws HttpHeaderFailure
     *
     * @return string|null The best MIME type for the client
     */
    public static function selectMimeType($acceptTypesText, array $availableTypes)
    {
        $selectedContentType = null;
        $selectedMatchingParts = -1;
        $selectedQualityValue = 0;
        $selectedPreferenceIndex = PHP_INT_MAX;
        $acceptable = false;
        $acceptTypesEmpty = true;
        if (null !== $acceptTypesText) {
            $acceptTypes = self::mimeTypesFromAcceptHeaders($acceptTypesText);
            $numAvailable = count($availableTypes);
            foreach ($acceptTypes as $acceptType) {
                $acceptTypesEmpty = false;
                for ($i = 0; $i < $numAvailable; ++$i) {
                    $availableType = $availableTypes[$i];
                    $matchRating = $acceptType->getMatchingRating($availableType);
                    if ($matchRating < 0) {
                        continue;
                    }

                    if ($matchRating > $selectedMatchingParts) {
                        // A more specific type wins.
                        $selectedContentType = $availableType;
                        $selectedMatchingParts = $matchRating;
                        $selectedQualityValue = $acceptType->getQualityValue();
                        $selectedPreferenceIndex = $i;
                        $acceptable = $selectedQualityValue != 0;
                    } elseif ($matchRating == $selectedMatchingParts) {
                        // A type with a higher q-value wins.
                        $candidateQualityValue = $acceptType->getQualityValue();
                        if ($candidateQualityValue > $selectedQualityValue) {
                            $selectedContentType = $availableType;
                            $selectedQualityValue = $candidateQualityValue;
                            $selectedPreferenceIndex = $i;
                            $acceptable = $selectedQualityValue != 0;
                        } elseif ($candidateQualityValue == $selectedQualityValue) {
                            // A type that is earlier in the availableTypes array wins.
                            if ($i < $selectedPreferenceIndex) {
                                $selectedContentType = $availableType;
                                $selectedPreferenceIndex = $i;
                            }
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
     * Returns all MIME types from the $text.
     *
     * @param string $text Text as it appears on an HTTP Accepts header
     *
     * @throws HttpHeaderFailure If found any syntax error in the given text
     *
     * @return MediaType[] Array of media (MIME) type description
     */
    public static function mimeTypesFromAcceptHeaders($text)
    {
        $mediaTypes = [];
        $textIndex = 0;
        while (!self::skipWhitespace($text, $textIndex)) {
            $type = null;
            $subType = null;
            self::readMediaTypeAndSubtype($text, $textIndex, $type, $subType);

            $parameters = [];
            while (!self::skipWhitespace($text, $textIndex)) {
                if ($text[$textIndex] == ',') {
                    ++$textIndex;
                    break;
                }

                if ($text[$textIndex] != ';') {
                    throw new HttpHeaderFailure(
                        Messages::httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter(),
                        400
                    );
                }

                ++$textIndex;
                if (self::skipWhitespace($text, $textIndex)) {
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
    public static function skipWhiteSpace($text, &$textIndex)
    {
        $textLen = strlen($text);
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
        $text,
        &$textIndex,
        &$type,
        &$subType
    ) {
        $textStart = $textIndex;
        if (self::readToken($text, $textIndex)) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeUnspecified(),
                400
            );
        }

        if ($text[$textIndex] != '/') {
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
    public static function readToken($text, &$textIndex)
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
    public static function isHttpTokenChar($char)
    {
        return ord($char) < 126 && ord($char) > 31
            && !self::isHttpSeparator($char);
    }

    /**
     * To check whether the given character is a HTTP seperator character.
     *
     * @param string $char The character to inspect
     *
     * @return bool True if the given character is a valid HTTP seperator
     *              character, False otherwise
     */
    public static function isHttpSeparator($char)
    {
        return
            $char == '(' || $char == ')' || $char == '<' || $char == '>' ||
            $char == '@' || $char == ',' || $char == ';' || $char == ':' ||
            $char == '\\' || $char == '"' || $char == '/' || $char == '[' ||
            $char == ']' || $char == '?' || $char == '=' || $char == '{' ||
            $char == '}' || $char == ' ' || ord($char) == Char::TAB;
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
    public static function readMediaTypeParameter($text, &$textIndex, &$parameters)
    {
        $textStart = $textIndex;
        if (self::readToken($text, $textIndex)) {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMediaTypeMissingValue(),
                400
            );
        }

        $parameterName = substr($text, $textStart, $textIndex - $textStart);
        if ($text[$textIndex] != '=') {
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
     *
     * @return string String representing the value of the $parameterName parameter
     */
    public static function readQuotedParameterValue(
        $parameterName,
        $text,
        &$textIndex
    ) {
        $parameterValue = [];
        $textLen = strlen($text);
        $valueIsQuoted = false;
        if ($textIndex < $textLen) {
            if ($text[$textIndex] == '"') {
                ++$textIndex;
                $valueIsQuoted = true;
            }
        }

        while ($textIndex < $textLen) {
            $currentChar = $text[$textIndex];

            if ($currentChar == '\\' || $currentChar == '"') {
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
                if ($currentChar == '"') {
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
     * Reads the numeric part of a quality value substring, normalizing it to 0-1000.
     rather than the standard 0.000-1.000 ranges.
     *
     * @param string $text          Text to read qvalue from
     * @param int    &$textIndex    Index into text where the qvalue starts
     * @param int    &$qualityValue After the method executes, the normalized qvalue
     * @param int    $textIndex
     *
     * @throws HttpHeaderFailure If any error occurred while reading and processing
     *                           the quality factor
     */
    public static function readQualityValue($text, &$textIndex, &$qualityValue)
    {
        $digit = $text[$textIndex++];
        if ($digit == '0') {
            $qualityValue = 0;
        } elseif ($digit == '1') {
            $qualityValue = 1;
        } else {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMalformedHeaderValue(),
                400
            );
        }

        $textLen = strlen($text);
        if ($textIndex < $textLen && $text[$textIndex] == '.') {
            ++$textIndex;

            $adjustFactor = 1000;
            while ($adjustFactor > 1 && $textIndex < $textLen) {
                $c = $text[$textIndex];
                $charValue = self::digitToInt32($c);
                if ($charValue >= 0) {
                    ++$textIndex;
                    $adjustFactor /= 10;
                    $qualityValue *= 10;
                    $qualityValue += $charValue;
                } else {
                    break;
                }
            }

            $qualityValue = $qualityValue *= $adjustFactor;
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
    }

    /**
     * Converts the specified character from the ASCII range to a digit.
     *
     * @param string $c Character to convert
     *
     * @throws HttpHeaderFailure If $c is not ASCII value for digit or element
     *                           seperator
     *
     * @return int The Int32 value for $c, or -1 if it is an element separator
     */
    public static function digitToInt32($c)
    {
        if ($c >= '0' && $c <= '9') {
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
     an HTTP header list of element.
     *
     * @param string $c Character to verify
     *
     * @return bool true if c is a valid character for separating elements;
     *              false otherwise
     */
    public static function isHttpElementSeparator($c)
    {
        return $c == ',' || $c == ' ' || $c == '\t';
    }

    /**
     * Get server key by header.
     *
     * @param string $headerName Name of header
     * @return string
     */
    public static function headerToServerKey($headerName)
    {
        $name = strtoupper(str_replace('-', '_', $headerName));
        switch ($name) {
            case 'HOST':
            case 'CONNECTION':
            case 'CACHE_CONTROL':
            case 'ORIGIN':
            case 'USER_AGENT':
            case 'POSTMAN_TOKEN':
            case 'ACCEPT':
            case 'ACCEPT_ENCODING':
            case 'ACCEPT_LANGUAGE':
            case 'DATASERVICEVERSION':
            case 'MAXDATASERVICEVERSION':
                return 'HTTP_' . $name;
        }

        return $name;
    }
}
