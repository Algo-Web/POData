<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Barnso
 * Date: 16/08/2017
 * Time: 4:57 AM.
 */
namespace POData\ObjectModel\AtomObjectModel;

/**
 * Class AtomAuthor.
 * @package POData\ObjectModel\AtomObjectModel
 */
class AtomAuthor
{
    /**
     * Title.
     *
     * @var string
     */
    public $name;

    /**
     * AtomAuthor constructor.
     * @param string $name
     */
    public function __construct($name = '')
    {
        $this->name = $name;
    }
}
