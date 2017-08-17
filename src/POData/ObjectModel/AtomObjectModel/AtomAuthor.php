<?php
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 4:57 AM
 */

namespace POData\ObjectModel\AtomObjectModel;


class AtomAuthor
{
    /**
     * Title.
     *
     * @var string
     */
    public $name;

    public function __construct($name = "")
    {
        $this->name = $name;
    }
}
