<?php

namespace POData\ObjectModel;

/**
 * Class ODataTitle
 * @package POData\ObjectModel
 */
class ODataTitle
{
    /**
     * Title.
     *
     * @var string
     */
    public $title;

    /**
     * Type
     *
     * @var string
     */
    public $type;

    public function __construct($title, $type = 'text')
    {
        $this->title = $title;
        $this->type = $type;
    }
}
