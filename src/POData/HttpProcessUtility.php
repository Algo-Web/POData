<?php

namespace POData;

use POData\Common\HttpHeaderFailure;
use POData\Common\Messages;
use POData\Providers\Metadata\Type\Char;

/**
 * Class MediaType.
 *
 * The Accept request-header field can be used to specify certain
 * media types which are acceptable for the response, this class
 * is used to hold details of such media type.
 * http://www.w3.org/Protocols/rfc1341/4_Content-Type.html
 */
class MediaType
{
    /**
     * The type part of media type.
     *
     * @var string
     */
    private $_type;

    /**
     * The sub-type part of media type.
     *
     * @var string
     */
    private $_subType;

    /**
     * The parameters associated with the media type.
     *
     * @var array(array(string, string))
     */
    private $_parameters;

    /**
     * Constructs a new instance of Media Type.
     *
     * @param string $type       The type of media type
     * @param string $subType    The sub type of media type
     * @param array  $parameters The parameters associated with media type
     */
    public function __construct($type, $subType, $parameters)
    {
        $this->_type = $type;
        $this->_subType = $subType;
        $this->_parameters = $parameters;
    }

    /**
     * Gets the MIME type.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->_type . '/' . $this->_subType;
    }

    /**
     * Gets the parameters associated with the media types.
     *
     * @return array(array(string, string))
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * Gets the number of parts in this media type that matches with
     * the given candidate type.
     *
     * @param string $candidate The candidate mime type
     *
     * @return int Returns -1 if this media type does not match with the
     *             candidate media type, 0 if media type's type is '*'
     *             (accept all types), 1 if media types's type matches
     *             with the candidate MIME type's type and media type's
     *             sub-types is '*' (accept all sub-type), 2 if both
     *             type and sub-type matches
     */
    public function getMatchingRating($candidate)
    {
        $result = -1;
        if (strlen($candidate) > 0) {
            //get the odata parameter (if there is one)
            $candidateODataValue = null;
            $candidateParts = explode(';', $candidate);
            if (count($candidateParts) > 1) {
                //is it safe to assume the mime type is always the first part?
                $candidate = array_shift($candidateParts); //move off the first type matcher
                //the rest look like QSPs..kinda so we can do this
                parse_str(implode('&', $candidateParts), $candidateParts);
                if (array_key_exists('odata', $candidateParts)) {
                    $candidateODataValue = $candidateParts['odata'];
                }
            }

            //ensure that the odata parameter values match
            if ($this->getODataValue() !== $candidateODataValue) {
                return -1;
            }

            if ($this->_type == '*') {
                $result = 0;
            } else {
                $separatorIdx = strpos($candidate, '/');
                if ($separatorIdx !== false) {
                    //if there's a subtype..look further
                    $candidateType = substr($candidate, 0, $separatorIdx);
                    if (strcasecmp($this->_type, $candidateType) == 0) {
                        //If main type matches
                        if ($this->_subType == '*') {
                            //and sub type matches with wildcard
                            $result = 1;
                        } else {
                            $candidateSubType = substr($candidate, strlen($candidateType) + 1);
                            if (strcasecmp($this->_subType, $candidateSubType) == 0) {
                                //if sub type matches
                                $result = 2;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    public function getODataValue()
    {
        foreach ($this->_parameters as $parameter) {
            foreach ($parameter as $key => $value) {
                if (strcasecmp($key, 'odata') === 0) {
                    return $value;
                }
            }
        }
    }

    /**
     * Gets the quality factor associated with this media type.
     *
     * @return int The value associated with 'q' parameter (0-1000),
     *             if absent return 1000
     */
    public function getQualityValue()
    {
        foreach ($this->_parameters as $parameter) {
            foreach ($parameter as $key => $value) {
                if (strcasecmp($key, 'q') === 0) {
                    $textIndex = 0;
                    $result;
                    HttpProcessUtility::readQualityValue(
                        $value,
                        $textIndex,
                        $result
                    );

                    return $result;
                }
            }
        }

        return 1000;
    }
}

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
     * @param string[] $exactContentTypes  Preferred content type to match if an exact media type is given - this is in descending order of preference
     * @param string   $inexactContentType Preferred fallback content type for inexact matches
     *
     * @return string One of exactContentType or inexactContentType
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

        if (!is_null($acceptTypesText)) {
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

                $matchingParts
                    = $acceptType->getMatchingRating($inexactContentType);
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
     * @return string The best MIME type for the client
     */
    public static function selectMimeType($acceptTypesText, array $availableTypes)
    {
        $selectedContentType = null;
        $selectedMatchingParts = -1;
        $selectedQualityValue = 0;
        $selectedPreferenceIndex = PHP_INT_MAX;
        $acceptable = false;
        $acceptTypesEmpty = true;
        if (!is_null($acceptTypesText)) {
            $acceptTypes = self::mimeTypesFromAcceptHeaders($acceptTypesText);
            foreach ($acceptTypes as $acceptType) {
                $acceptTypesEmpty = false;
                for ($i = 0; $i < count($availableTypes); ++$i) {
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
     * @throws HttpHeaderFailure If any error occured while reading and processing
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
