<?php
/** 
 * A type to represent Url
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
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
namespace ODataProducer\Common;
/**
 * Class for Url
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Url
{
    private $_urlAsString = null;    
    private $_parts = array();
    private $_segments = array();
    const ABS_URL_REGEXP = '/^(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/';
    const REL_URL_REGEXP = '/^(\/|\/([\w#!:.?+=&%@!\-\/]))?/';

    /**
     * Creates new instance of Url 
     * 
     * @param string  $url        The url as string
     * @param boolean $isAbsolute Whether the given url is absolute or not
     * 
     * @throws InvalidArgumentException Exception if url is malformed
     */
    public function __construct($url, $isAbsolute = true)
    {
        if ($isAbsolute) {
            if (!preg_match(self::ABS_URL_REGEXP, $url)) {
                throw new UrlFormatException(Messages::urlMalformedUrl($url));
            }
        } else {
            if (!preg_match(self::REL_URL_REGEXP, $url)) {
                throw new UrlFormatException(Messages::urlMalformedUrl($url));
            }
        }

        $this->_parts = parse_url($url);
        if ($this->_parts === false) {
            throw new UrlFormatException(Messages::urlMalformedUrl($url));
        }
        
        $path = $this->getPath();        
        if ($path != null) {
            $this->_segments = explode('/', trim($path, '/'));
            foreach ($this->_segments as $segment) {
                $segment = trim($segment);
                if (empty($segment)) {
                    throw new UrlFormatException(Messages::urlMalformedUrl($url));
                }
            }
        }

        $this->_urlAsString = $url;
    }

    /**
     * Gets the url represented by this instance as string
     * 
     * @return string
     */
    public function getUrlAsString()
    {
        return $this->_urlAsString;
    }

    /**
     * Get the scheme part of the Url
     *
     * @return string/NULL Returns the scheme part of the url, 
     * if scheme is missing returns NULL
     */
    public function getScheme()
    {
        return isset ($this->_parts['scheme']) ? $this->_parts['scheme'] : null;
    }

    /**
     * Get the host part of the Url
     *
     * @return string/NULL Returns the host part of the url, 
     * if host is missing returns NULL
     */
    public function getHost()
    {
        return isset ($this->_parts['host']) ? $this->_parts['host'] : null;
    }

    /**
     * Get the port number present in the url
     *
     * @return int
     */
    public function getPort()
    {        
        $port = isset ($this->_parts['port'])? $this->_parts['port'] : null;
        if ($port != null) {
            return $port;
        }

        $host = $this->getScheme();
        if ($host == 'https') {
            $port = 443;
        } else if ($host == 'http') {
            $port = 80;
        }

        return $port;
    }
    
    /**
     * To get the path segment
     *
     * @return string Returns the host part of the url, 
     * if host is missing returns NULL
     */
    public function getPath()
    {
        return isset ($this->_parts['path']) ? $this->_parts['path'] : null;
    }

    /**
     * Get the query part
     *
     * @return string/NULL Returns the query part of the url, 
     * if query is missing returns NULL
     */
    public function getQuery()
    {
        return isset ($this->_parts['query']) ? $this->_parts['query'] : null;
    }

    /**
     * Get the fragment part
     *
     * @return string/NULL Returns the fragment part of the url, 
     * if fragment is missing returns NULL
     */
    public function getFragment()
    {
        return isset ($this->_parts['fragment']) ? $this->_parts['fragment'] : null;
    }

    /**
     * Get the segments
     * 
     * @return array Returns array of segments,
     * if no segments then returns empty array.
     */
    public function getSegments()
    {
        return $this->_segments;
    }

    /**
     * Gets number of segments, if no segment then returns zero.
     * 
     * @return int
     */
    public function getSegmentCount()
    {
        return count($this->_segments);
    }

    /**
     * Checks the url is absolute or not
     *
     * @return boolean Returns true if absolute url otherwise false
     */
    public function isAbsolute()
    {
        return isset ($this->_parts['scheme']);
    }

    /**
     * Checks the url is relative or not
     *
     * @return boolean
     */
    public function isRelative()
    {
        return !$this->isAbsolute();
    }

    /**
     * Checks this url is base uri for the given url.
     * 
     * @param Url $targetUri The url to inspect the base part.
     * 
     * @return boolean
     */
    public function isBaseOf(Url $targetUri)
    {
        if ($this->_parts['scheme'] !== $targetUri->getScheme()  
            || $this->_parts['host'] !== $targetUri->getHost() 
            || $this->getPort() !== $targetUri->getPort()
        ) {
                return false;
        }

        $srcSegmentCount = count($this->_segments);
        $targetSegments = $targetUri->getSegments();
        $targetSegmentCount = count($targetSegments);
        if ($srcSegmentCount > $targetSegmentCount) {
            return false;
        }

        for ($i = 0; $i < $srcSegmentCount; $i++) {
            if ($this->_segments[$i] !== $targetSegments[$i]) {
                return false;
            }
        }

        return true;
    }
}
?>