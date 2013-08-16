<?php

namespace POData\Common;

/**
 * Class Version Type to represents the version number of data service and edmx
 * @package POData\Common
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