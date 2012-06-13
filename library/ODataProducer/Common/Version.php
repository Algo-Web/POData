<?php
/** 
 * Type to represents the version number of data service and edmx 
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
 * Class for dataservice version
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class Version
{
    /**
     * The major component of the version
     * 
     * @var int
     */
    private $_major;

    /**
     * The minor component of the version
     * 
     * @var int
     */
    private $_minor;

    /**
     * Constructs a new instance of Version class
     * 
     * @param int $major The major component of the version
     * @param int $minor The minor component of the version
     */
    public function __construct($major, $minor) 
    {
        $this->_major = $major;
        $this->_minor = $minor;
    }

    /**
     * Gets the major component of the version
     * 
     * @return int
     */
    public function getMajor() 
    {
        return $this->_major;
    }

    /**
     * Gets the minor component of the version
     * 
     * @return int
     */
    public function getMinor() 
    {
        return $this->_minor;
    }

    /**
     * If necessary raises version to the version given 
     * 
     * @param int $major The major component of the new version
     * @param int $minor The minor component of the new version
     * 
     * @return void
     */
    public function raiseVersion($major, $minor) 
    {
        if ($major > $this->_major) {
            $this->_major = $major;
            $this->_minor = $minor;
        } else if ($major == $this->_major && $minor > $this->_minor) {
            $this->_minor = $minor;
        }    
    }

    /**
     * Compare this version with a target version.
     * 
     * @param Version $targetVersion The target version to compare with.
     * 
     * @return int Return 1 if this version is greater than target version
     *                 -1 if this version is less than the target version
     *                  0 if both are equal.
     */
    public function compare(Version $targetVersion)
    {
        if ($this->_major > $targetVersion->_major) {
            return 1;
        }

        if ($this->_major == $targetVersion->_major) {
            if ($this->_minor == $targetVersion->_minor) {
                return 0;
            }

            if ($this->_minor > $targetVersion->_minor) {
                return 1;
            }
        }

        return -1;
    }

    /**
     * Gets the value of the current Version object as string
     * 
     * @return string
     */
    public function toString()
    {
        return $this->_major . '.' . $this->_minor;
    }
}
?>