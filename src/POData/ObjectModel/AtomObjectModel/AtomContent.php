<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 4:25 AM.
 */
namespace POData\ObjectModel\AtomObjectModel;

/**
 * Class AtomContent.
 * @package POData\ObjectModel\AtomObjectModel
 */
class AtomContent
{
    /**
     * Title.
     *
     * @var string
     */
    public $type;

    /**
     * Type.
     *
     * @var string
     */
    public $src;

    public $properties;

    /**
     * AtomContent constructor.
     * @param string     $type
     * @param string     $src
     * @param mixed|null $properties
     */
    public function __construct(string $type, string $src = null, $properties = null)
    {
        $this->src        = $src;
        $this->type       = $type;
        $this->properties = $properties;
    }
}
