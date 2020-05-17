<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataTitle.
 *
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
     * Type.
     *
     * @var string
     */
    public $type;

    /**
     * ODataTitle constructor.
     *
     * @param string $title
     * @param string $type
     */
    public function __construct(string $title, string $type = 'text')
    {
        $this
            ->setTitle($title)
            ->setType($type);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ODataTitle
     */
    public function setTitle(string $title): ODataTitle
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ODataTitle
     */
    public function setType(string $type): ODataTitle
    {
        $this->type = $type;
        return $this;
    }
}
