<?php

namespace POData;

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
    private $type;

    /**
     * The sub-type part of media type.
     *
     * @var string
     */
    private $subType;

    /**
     * The parameters associated with the media type.
     *
     * @var array[]
     */
    private $parameters;

    /**
     * Constructs a new instance of Media Type.
     *
     * @param string $type       The type of media type
     * @param string $subType    The sub type of media type
     * @param array  $parameters The parameters associated with media type
     */
    public function __construct($type, $subType, array $parameters)
    {
        $this->type = $type;
        $this->subType = $subType;
        $this->parameters = $parameters;
    }

    /**
     * Gets the MIME type.
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->type . '/' . $this->subType;
    }

    /**
     * Gets the parameters associated with the media types.
     *
     * @return array[]
     */
    public function getParameters()
    {
        return $this->parameters;
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

            if ($this->type == '*') {
                $result = 0;
            } else {
                $separatorIdx = strpos($candidate, '/');
                if ($separatorIdx !== false) {
                    //if there's a subtype..look further
                    $candidateType = substr($candidate, 0, $separatorIdx);
                    if (strcasecmp($this->type, $candidateType) == 0) {
                        //If main type matches
                        if ($this->subType == '*') {
                            //and sub type matches with wildcard
                            $result = 1;
                        } else {
                            $candidateSubType = substr($candidate, strlen($candidateType) + 1);
                            if (strcasecmp($this->subType, $candidateSubType) == 0) {
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

    /**
     * Retrieve OData parameter value
     *
     * @return mixed|null
     */
    public function getODataValue()
    {
        foreach ($this->parameters as $parameter) {
            foreach ($parameter as $key => $value) {
                if (strcasecmp($key, 'odata') === 0) {
                    return $value;
                }
            }
        }
        return null;
    }

    /**
     * Gets the quality factor associated with this media type.
     *
     * @return int The value associated with 'q' parameter (0-1000),
     *             if absent return 1000
     */
    public function getQualityValue()
    {
        foreach ($this->parameters as $parameter) {
            foreach ($parameter as $key => $value) {
                if (strcasecmp($key, 'q') === 0) {
                    $textIndex = 0;
                    $result = '';
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
