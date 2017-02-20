<?php

namespace POData\Common;

/**
 * Class Version Type to represents the version number of data service and edmx.
 */
class Version
{
    /**
     * The major component of the version.
     *
     * @var int
     */
    private $major;

    /**
     * The minor component of the version.
     *
     * @var int
     */
    private $minor;

    /**
     * Constructs a new instance of Version class.
     *
     * @param int $major The major component of the version
     * @param int $minor The minor component of the version
     */
    public function __construct($major, $minor)
    {
        $this->major = $major;
        $this->minor = $minor;
    }

    /**
     * Gets the major component of the version.
     *
     * @return int
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * Gets the minor component of the version.
     *
     * @return int
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * If necessary raises version to the version given.
     *
     * @param int $major The major component of the new version
     * @param int $minor The minor component of the new version
     *
     * @return bool true if the version was raised, false otherwise
     */
    public function raiseVersion($major, $minor)
    {
        if ($major > $this->major) {
            $this->major = $major;
            $this->minor = $minor;

            return true;
        } elseif ($major == $this->major && $minor > $this->minor) {
            $this->minor = $minor;

            return true;
        }

        return false;
    }

    /**
     * Compare this version with a target version.
     *
     * @param Version $targetVersion The target version to compare with
     *
     * @return int Return 1 if this version is greater than target version
     *             -1 if this version is less than the target version
     *             0 if both are equal
     */
    public function compare(Version $targetVersion)
    {
        if ($this->major > $targetVersion->major) {
            return 1;
        }

        if ($this->major == $targetVersion->major) {
            if ($this->minor == $targetVersion->minor) {
                return 0;
            }

            if ($this->minor > $targetVersion->minor) {
                return 1;
            }
        }

        return -1;
    }

    /**
     * Gets the value of the current Version object as string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->major . '.' . $this->minor;
    }

    //Is there a better way to do static const of complex type?

    /** @var Version[] */
    private static $fixedVersion = null;

    private static function fillVersions()
    {
        if (null == self::$fixedVersion) {
            self::$fixedVersion = [1 => new self(1, 0), 2 => new self(2, 0), 3 => new self(3, 0)];
        }
    }

    public static function v1()
    {
        self::fillVersions();

        return self::$fixedVersion[1];
    }

    public static function v2()
    {
        self::fillVersions();

        return self::$fixedVersion[2];
    }

    public static function v3()
    {
        self::fillVersions();

        return self::$fixedVersion[3];
    }
}
