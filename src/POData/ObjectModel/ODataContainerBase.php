<?php

declare(strict_types=1);


namespace POData\ObjectModel;

abstract class ODataContainerBase implements IOData
{
    /**
     * Entry id.
     *
     * @var string|null
     */
    public $id;
    /**
     * Feed title.
     *
     * @var ODataTitle
     */
    private $title;

    /**
     * Entry Self Link.
     *
     * @var ODataLink|null
     */
    private $selfLink;

    /**
     * Last updated timestamp.
     *
     * @var string|null
     */
    private $updated;

    /**
     * Service Base URI.
     *
     * @var string|null
     */
    private $baseURI;

    /**
     * ODataContainerBase constructor.
     * @param string|null $id
     * @param ODataTitle  $title
     * @param string|null $updated
     * @param string|null $baseURI
     */
    public function __construct(?string $id, ?ODataTitle $title, ?ODataLink $selfLink, ?string $updated, ?string $baseURI)
    {
        $this
            ->setId($id)
            ->setTitle($title)
            ->setSelfLink($selfLink)
            ->setUpdated($updated)
            ->setBaseURI($baseURI);
    }


    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param  string|null $id
     * @return self
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return ODataTitle|null
     */
    public function getTitle(): ?ODataTitle
    {
        return $this->title;
    }

    /**
     * @param  ODataTitle|null $title
     * @return self
     */
    public function setTitle(?ODataTitle $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return ODataLink|null
     */
    public function getSelfLink(): ?ODataLink
    {
        return $this->selfLink;
    }

    /**
     * @param  string|null $selfLink
     * @return ODataEntry
     */
    public function setSelfLink(?ODataLink $selfLink): self
    {
        $this->selfLink = $selfLink;
        return $this;
    }
    /**
     * @return string|null
     */
    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    /**
     * @param  string|null $updated
     * @return ODataEntry
     */
    public function setUpdated(?string $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBaseURI(): ?string
    {
        return $this->baseURI;
    }

    /**
     * @param  string|null $baseURI
     * @return self
     */
    public function setBaseURI(?string $baseURI): self
    {
        $this->baseURI = $baseURI;
        return $this;
    }
}
