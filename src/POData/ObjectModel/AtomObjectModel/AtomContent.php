<?php
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 4:25 AM
 */

namespace POData\ObjectModel\AtomObjectModel;


class AtomContent
{
    /**
     * Title.
     *
     * @var string
     */
    public $type;

    /**
     * Type
     *
     * @var string
     */
    public $src;

    public $properties;

    public function __construct($type, $src, $properties = null)
    {
        $this->src = $src;
        $this->type = $type;
        $this->properties = $properties;
    }
}