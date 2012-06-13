<?php
/**
 * The Accept request-header field can be used to specify certain 
 * media types which are acceptable for the response, this class 
 * is used to hold details of such media type.
 * http://www.w3.org/Protocols/rfc1341/4_Content-Type.html
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS 
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN 
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */
namespace ODataProducer;
use ODataProducer\Common\Messages;
use ODataProducer\Common\HttpHeaderFailure;
use ODataProducer\Providers\Metadata\Type\Char;
/**
 * Class for Media type
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
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
     * 
     * @return void
     */
    public function  __construct($type, $subType, $parameters)
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
     * the given candiate type.
     * 
     * @param string $candidate The candiate mime type.
     * 
     * @return int Returns -1 if this media type does not match with the
     *                        candiate media type, 0 if media type's type is '*'
     *                        (accept all types), 1 if media types's type matches
     *                        with the candiate MIME type's type and media type's
     *                        sub-types is '*' (accept all sub-type), 2 if both
     *                        type and sub-type atches.
     */
    public function getMatchingParts($candidate)
    {
        $result = -1;
        if (strlen($candidate) > 0) {
            if ($this->_type == '*') {
                $result = 0;
            } else {
                $separatorIdx = strpos($candidate, '/');
                if ($separatorIdx !== false) {
                    $candidateType = substr($candidate, 0, $separatorIdx);
                    if (strcasecmp($this->_type, $candidateType) == 0) {
                        if ($this->_subType == '*') {
                            $result = 1;
                        } else {
                            $candidateSubType 
                                = substr($candidate, strlen($candidateType) + 1);
                            if (strcasecmp($this->_subType, $candidateSubType) == 0) {
                                $result = 2;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Gets the quality factor associated with this media type.
     * 
     * @return int The value associated with 'q' parameter (0-1000),
     *             if absent return 1000.
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
 * Helper methods for processing HTTP headers.
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class HttpProcessUtility
{

    /**
     * Gets the appropriate MIME type for the request, throwing if there is none.
     * 
     * @param string        $acceptTypesText    Text as it appears in an HTTP
     *                                          Accepts header.
     * @param array(String) $exactContentTypes  Preferred content type to match
     *                                          if an exact media type is given
     *                                          - this is in descending order
     *                                          of preference.
     * @param string        $inexactContentType Preferred fallback content type
     *                                          for inexact matches.
     *
     * @return string One of exactContentType or inexactContentType.
     */
    public static function selectRequiredMimeType($acceptTypesText,
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
                    = $acceptType->getMatchingParts($inexactContentType);
                if ($matchingParts < 0) {
                    continue;
                }

                if ($matchingParts > $selectedMatchingParts) {
                    // A more specific type wins.
                    $selectedContentType = $inexactContentType;
                    $selectedMatchingParts = $matchingParts;
                    $selectedQualityValue = $acceptType->getQualityValue();
                    $acceptable = $selectedQualityValue != 0;
                } else if ($matchingParts == $selectedMatchingParts) {
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
                Messages::dataServiceExceptionUnsupportedMediaType(), 
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
     * @param string        $acceptTypesText Text for Accepts header.
     * @param array(string) $availableTypes  Types that the server is willing
     *                                       to return, in descending order
     *                                       of preference.
     * 
     * @return string The best MIME type for the client.
     *
     * @throws HttpHeaderFailure
     */
    public static function selectMimeType($acceptTypesText, $availableTypes)
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
                for ($i = 0; $i < count($availableTypes); $i++) {
                    $availableType = $availableTypes[$i];
                    $matchingParts = $acceptType->getMatchingParts($availableType);
                    if ($matchingParts < 0) {
                        continue;
                    }

                    if ($matchingParts > $selectedMatchingParts) {
                        // A more specific type wins.
                        $selectedContentType = $availableType;
                        $selectedMatchingParts = $matchingParts;
                        $selectedQualityValue = $acceptType->getQualityValue();
                        $selectedPreferenceIndex = $i;
                        $acceptable = $selectedQualityValue != 0;
                    } else if ($matchingParts == $selectedMatchingParts) {
                        // A type with a higher q-value wins.
                        $candidateQualityValue = $acceptType->getQualityValue();
                        if ($candidateQualityValue > $selectedQualityValue) {
                            $selectedContentType = $availableType;
                            $selectedQualityValue = $candidateQualityValue;
                            $selectedPreferenceIndex = $i;
                            $acceptable = $selectedQualityValue != 0;
                        } else if ($candidateQualityValue == $selectedQualityValue) {
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
        } else if (!$acceptable) {
            $selectedContentType = null;
        }

        return $selectedContentType;
    }

    /**
     * Returns all MIME types from the $text.
     * 
     * @param string $text Text as it appears on an HTTP Accepts header.
     * 
     * @return array(MediaType) Array of media (MIME) type description.
     *
     * @throws HttpHeaderFailure If found any sytax error in the given text.
     */
    public static function mimeTypesFromAcceptHeaders($text)
    {
        $mediaTypes = array();
        $textIndex = 0;
        while (!self::skipWhitespace($text, $textIndex)) {
            $type = null;
            $subType = null;
            self::readMediaTypeAndSubtype($text, $textIndex, $type, $subType);

            $parameters = array();
            while (!self::skipWhitespace($text, $textIndex)) {
                if ($text[$textIndex] == ',') {
                    $textIndex++;
                    break;
                }

                if ($text[$textIndex] != ';') {
                    throw new HttpHeaderFailure(
                        Messages::httpProcessUtilityMediaTypeRequiresSemicolonBeforeParameter(), 
                        400
                    );
                }

                $textIndex++;
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
     * @param string $text       Text to scan.
     * @param int    &$textIndex Index to begin scanning from.
     * 
     * @return boolean true if the end of the string was reached, false otherwise.
     */
    public static function skipWhiteSpace($text, &$textIndex)
    {
        $textLen = strlen($text);
        while (($textIndex < $textLen) && Char::isWhiteSpace($text[$textIndex])) {
            $textIndex++;
        }

        return $textLen == $textIndex;
    }

    /**
     * Reads the type and subtype specifications for a MIME type.
     * 
     * @param string $text       Text in which specification exists.
     * @param int    &$textIndex Pointer into text.
     * @param string &$type      Type of media found.
     * @param string &$subType   Subtype of media found.
     *
     * @throws HttpHeaderFailure If failed to read type and sub-type.
     * 
     * @return nothing
     */
    public static function readMediaTypeAndSubtype($text, &$textIndex, 
        &$type, &$subType
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
        $textIndex++;

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
     * @param string $text       Text to read token from.
     * @param int    &$textIndex Index for the position being scanned on text.
     * 
     * @return boolean true if the end of the text was reached; false otherwise.
     */
    public static function readToken($text, &$textIndex)
    {
        $textLen = strlen($text);
        while (($textIndex < $textLen) && self::isHttpTokenChar($text[$textIndex])) {
            $textIndex++;
        }

        return $textLen == $textIndex;
    }

    /**
     * To check whether the given character is a HTTP token character
     * or not.
     * 
     * @param char $char The character to inspect.
     * 
     * @return boolean True if the given character is a valid HTTP token
     *                 character, False otherwise.
     */
    public static function isHttpTokenChar($char)
    {
        return ord($char) < 126 && ord($char) > 31
            && !self::isHttpSeparator($char);
    }

    /**
     * To check whether the given character is a HTTP seperator character.
     *
     * @param char $char The character to inspect.
     * 
     * @return boolean True if the given character is a valid HTTP seperator
     *                 character, False otherwise.
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
     * @param string $text        Text to read from.
     * @param int    &$textIndex  Pointer in text.
     * @param array  &$parameters Array with parameters.
     *
     * @throws HttpHeaderFailure If found parameter value missing.
     * @return nothing
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

        $textIndex++;
        $parameterValue 
            = self::readQuotedParameterValue($parameterName, $text, $textIndex);
        $parameters[] = array($parameterName => $parameterValue);
    }

    /**
     * Reads Mime type parameter value for a particular parameter in the
     * Content-Type/Accept headers.
     * 
     * @param string $parameterName Name of parameter.
     * @param string $text          Header text.
     * @param int    &$textIndex    Parsing index in $text.
     * 
     * @return string String representing the value of the $parameterName parameter.
     *
     * @throws HttpHeaderFailure
     */
    public static function readQuotedParameterValue($parameterName, $text, 
        &$textIndex
    ) {
        $parameterValue = array();
        $textLen = strlen($text);
        $valueIsQuoted = false;
        if ($textIndex < $textLen) {
            if ($text[$textIndex] == '"') {
                $textIndex++;
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

                $textIndex++;

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
            } else if (!self::isHttpTokenChar($currentChar)) {
                // If the given character is special, we stop processing.
                break;
            }

            $parameterValue[] = $currentChar;
            $textIndex++;
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
     * Reads the numeric part of a quality value substring, normalizing it to 0-1000
       rather than the standard 0.000-1.000 ranges.
     * 
     * @param string $text          Text to read qvalue from.
     * @param int    &$textIndex    Index into text where the qvalue starts.
     * @param int    &$qualityValue After the method executes, the normalized qvalue.
     *
     * @throws HttpHeaderFailure If any error occured while reading and processing
     *                           the quality factor.
     * @return nothing
     */
    public static function readQualityValue($text, &$textIndex, &$qualityValue)
    {
        $digit = $text[$textIndex++];
        if ($digit == '0') {
            $qualityValue = 0;
        } else if ($digit == '1') {
            $qualityValue = 1;
        } else {
            throw new HttpHeaderFailure(
                Messages::httpProcessUtilityMalformedHeaderValue(), 
                400
            );
        }

        $textLen = strlen($text);
        if ($textIndex < $textLen && $text[$textIndex] == '.') {
            $textIndex++;

            $adjustFactor = 1000;
            while ($adjustFactor > 1 && $textIndex < $textLen) {
                $c = $text[$textIndex];
                $charValue = self::digitToInt32($c);
                if ($charValue >= 0) {
                    $textIndex++;
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
     * @param char $c Character to convert
     *
     * @return int The Int32 value for $c, or -1 if it is an element separator.
     *
     * @throws HttpHeaderFailure If $c is not ASCII value for digit or element
     *                           seperator.
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
     * Verfies whether the specified character is a valid separator in
       an HTTP header list of element.
     * 
     * @param char $c Character to verify
     * 
     * @return boolean true if c is a valid character for separating elements;
     *                 false otherwise.
     */
    public static function isHttpElementSeparator($c)
    {
        return $c == ',' || $c == ' ' || $c == '\t';
    }
}
?>