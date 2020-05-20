<?php

declare(strict_types=1);

namespace POData\Common;

/**
 * Class Url.
 */
class Url
{
    /**
     * ^asserts position at start of the string
     * 1st Capturing Group (ftp|http|https)
     * > 1st Alternative ftp
     * > > ftp matches the characters ftp literally (case sensitive)
     * > 2nd Alternative http
     * > > http matches the characters http literally (case sensitive)
     * > 3rd Alternative https
     *  > > https matches the characters https literally (case sensitive)
     * : matches the character : literally (case sensitive)
     * \/ matches the character / literally (case sensitive)
     * \/ matches the character / literally (case sensitive)
     * 2nd Capturing Group (\w+:{0,1}\w*@)?
     * > ? Quantifier — Matches between zero and one times, as many times as possible, giving back as needed (greedy)
     * > \w+ matches any word character (equal to [a-zA-Z0-9_])
     * > + Quantifier — Matches between one and unlimited times, as many times as possible, giving back as needed (greedy)
     * > :{0,1} matches the character : literally (case sensitive)
     * > {0,1} Quantifier — Matches between zero and one times, as many times as possible, giving back as needed (greedy)
     * > \w* matches any word character (equal to [a-zA-Z0-9_])
     * > * Quantifier — Matches between zero and unlimited times, as many times as possible, giving back as needed (greedy)
     * > @ matches the character @ literally (case sensitive)
     * 3rd Capturing Group (\S+)
     * > \S+ matches any non-whitespace character (equal to [^\r\n\t\f\v ])
     * > + Quantifier — Matches between one and unlimited times, as many times as possible, giving back as needed (greedy)
     * 4th Capturing Group (:[0-9]+)?
     * > ? Quantifier — Matches between zero and one times, as many times as possible, giving back as needed (greedy)
     * > : matches the character : literally (case sensitive)
     * > Match a single character present in the list below [0-9]+
     * 5th Capturing Group (\/|\/([\w#!:.?+=&%@!\-\/]))?
     * > ? Quantifier — Matches between zero and one times, as many times as possible, giving back as needed (greedy)
     * > 1st Alternative \/
     * > > \/ matches the character / literally (case sensitive)
     * > 2nd Alternative \/([\w#!:.?+=&%@!\-\/])
     * > > \/ matches the character / literally (case sensitive)
     * 6th Capturing Group ([\w#!:.?+=&%@!\-\/])
     * > Match a single character present in the list below [\w#!:.?+=&%@!\-\/]
     * > \w matches any word character (equal to [a-zA-Z0-9_])
     * > #!:.?+=&%@! matches a single character in the list #!:.?+=&%@ (case sensitive)
     * > \- matches the character - literally (case sensitive)
     * > \/ matches the character / literally (case sensitive)
     */
    const ABS_URL_REGEXP = '/^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/';
    const REL_URL_REGEXP = '/^(\/|\/([\w#!:.?+=&%@!\-\/]))?/';
    private $urlAsString = null;
    private $parts       = [];
    private $segments    = [];

    /**
     * Creates new instance of Url.
     *
     * @param string $url        The url as string
     * @param bool   $isAbsolute Whether the given url is absolute or not
     *
     * @throws UrlFormatException Exception if url is malformed
     */
    public function __construct($url, $isAbsolute = true)
    {
        if ($isAbsolute) {
            if (!preg_match(self::ABS_URL_REGEXP, $url)) {
                throw new UrlFormatException(Messages::urlMalformedUrl($url));
            }
        } else {
            if (!preg_match(self::REL_URL_REGEXP, $url)) {
                //TODO: this matches EVERYTHING!!! what's the intent here? see #77
                throw new UrlFormatException(Messages::urlMalformedUrl($url));
            }
        }

        $p = parse_url($url);
        if ($p === false) {
            throw new UrlFormatException(Messages::urlMalformedUrl($url));
        }
        $this->parts = $p;
        $path        = $this->getPath();
        if ($path != null) {
            $this->segments = explode('/', trim($path, '/'));
            foreach ($this->segments as $segment) {
                $segment = trim($segment);
                if (empty($segment)) {
                    throw new UrlFormatException(Messages::urlMalformedUrl($url));
                }
            }
        }

        $this->urlAsString = $url;
    }

    /**
     * To get the path segment.
     *
     * @return string Returns the host part of the url,
     *                if host is missing returns NULL
     */
    public function getPath()
    {
        return isset($this->parts['path']) ? $this->parts['path'] : null;
    }

    /**
     * Gets the url represented by this instance as string.
     *
     * @return string
     */
    public function getUrlAsString()
    {
        return $this->urlAsString;
    }

    /**
     * Get the query part.
     *
     * @return string|null Returns the query part of the url,
     *                     if query is missing returns NULL
     */
    public function getQuery()
    {
        return isset($this->parts['query']) ? $this->parts['query'] : null;
    }

    /**
     * Get the fragment part.
     *
     * @return string|null Returns the fragment part of the url,
     *                     if fragment is missing returns NULL
     */
    public function getFragment()
    {
        return isset($this->parts['fragment']) ? $this->parts['fragment'] : null;
    }

    /**
     * Gets number of segments, if no segment then returns zero.
     *
     * @return int
     */
    public function getSegmentCount()
    {
        return count($this->segments);
    }

    /**
     * Checks the url is relative or not.
     *
     * @return bool
     */
    public function isRelative()
    {
        return !$this->isAbsolute();
    }

    /**
     * Checks the url is absolute or not.
     *
     * @return bool Returns true if absolute url otherwise false
     */
    public function isAbsolute()
    {
        return isset($this->parts['scheme']);
    }

    /**
     * Checks this url is base uri for the given url.
     *
     * @param Url $targetUri The url to inspect the base part
     *
     * @return bool
     */
    public function isBaseOf(Url $targetUri)
    {
        if ($this->parts['scheme'] !== $targetUri->getScheme()
            || $this->parts['host'] !== $targetUri->getHost()
            || $this->getPort() !== $targetUri->getPort()
        ) {
            return false;
        }

        $srcSegmentCount    = count($this->segments);
        $targetSegments     = $targetUri->getSegments();
        $targetSegmentCount = count($targetSegments);
        if ($srcSegmentCount > $targetSegmentCount) {
            return false;
        }

        for ($i = 0; $i < $srcSegmentCount; ++$i) {
            if ($this->segments[$i] !== $targetSegments[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the scheme part of the Url.
     *
     * @return string|null Returns the scheme part of the url,
     *                     if scheme is missing returns NULL
     */
    public function getScheme()
    {
        return isset($this->parts['scheme']) ? $this->parts['scheme'] : null;
    }

    /**
     * Get the host part of the Url.
     *
     * @return string|null Returns the host part of the url,
     *                     if host is missing returns NULL
     */
    public function getHost()
    {
        return isset($this->parts['host']) ? $this->parts['host'] : null;
    }

    /**
     * Get the port number present in the url.
     *
     * @return int
     */
    public function getPort()
    {
        $port = isset($this->parts['port']) ? $this->parts['port'] : null;
        if ($port != null) {
            return $port;
        }

        $host = $this->getScheme();
        if ($host == 'https') {
            $port = 443;
        } elseif ($host == 'http') {
            $port = 80;
        }

        return $port;
    }

    /**
     * Get the segments.
     *
     * @return array Returns array of segments,
     *               if no segments then returns empty array
     */
    public function getSegments()
    {
        return $this->segments;
    }
}
