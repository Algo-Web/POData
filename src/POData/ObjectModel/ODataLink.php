<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataLink represents an OData Navigation Link.
 */
class ODataLink
{
      /**
     * Name of the link. This becomes last segment of rel attribute value.
     *
     * @var string
     */
    private $name;
    /**
     * Title of the link. This become value of title attribute.
     *
     * @var string
     */
    private $title;
    /**
     * Type of link.
     *
     * @var string
     */
    private $type;
    /**
     * Url to the navigation property. This become value of href attribute.
     *
     * @var string
     */
    private $url;
    /**
     * Checks is Expand result contains single entity or collection of
     * entities i.e. feed.
     *
     * @var bool
     */
    public $isCollection;
    /**
     * The expanded result. This becomes the inline content of the link.
     *
     * @var ODataEntry|ODataFeed
     */
    public $expandedResult;
    /**
     * True if Link is Expanded, False if not.
     *
     * @var bool
     */
    public $isExpanded;

    /**
     * ODataLink constructor.
     * @param string $name
     * @param string $title
     * @param string $type
     * @param string $url
     * @param bool $isCollection
     * @param ODataExpandedResult|null $expandedResult
     * @param bool|null $isExpanded
     */
    public function __construct(
        string $name = null,
        string $title = null,
        string $type = null,
        string $url = null,
        bool $isCollection = null,
        ODataExpandedResult $expandedResult = null,
        bool $isExpanded = null
    ) {
        $this
            ->setName($name)
            ->setTitle($title)
            ->setType($type)
            ->setUrl($url)
            ->setIsCollection($isCollection)
            ->setExpandResult($expandedResult)
            ->setIsExpanded($isExpanded);
    }
    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ODataLink
     */
    public function setName(?string $name): ODataLink
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ODataLink
     */
    public function setTitle(?string $title): ODataLink
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ODataLink
     */
    public function setType(?string $type): ODataLink
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return ODataLink
     */
    public function setUrl(?string $url): ODataLink
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCollection(): ?bool
    {
        return $this->isCollection;
    }

    /**
     * @param bool $isCollection
     * @return ODataLink
     */
    public function setIsCollection(?bool $isCollection): ODataLink
    {
        $this->isCollection = $isCollection;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpanded(): ?bool
    {
        return $this->isExpanded;
    }

    /**
     * @param bool $isExpanded
     * @return ODataLink
     */
    public function setIsExpanded(?bool $isExpanded): ODataLink
    {
        $this->isExpanded = $isExpanded;
        return $this;
    }

    /**
     * @return null|ODataExpandedResult
     */
    public function getExpandResult(): ?ODataExpandedResult
    {
        if (!$this->isExpanded) {
            return null;
        }
        if ($this->isCollection) {
            assert($this->expandedResult instanceof ODataFeed);
            return new ODataExpandedResult( $this->expandedResult);
        }
        assert($this->expandedResult instanceof ODataEntry);
        return new ODataExpandedResult($this->expandedResult);
    }

    /**
     * @param ODataExpandedResult $eResult
     */
    public function setExpandResult(?ODataExpandedResult $eResult)
    {
        if(null === $eResult){
            return $this;
        }
        if (null !== $eResult->getFeed()) {
            $this->isExpanded     = true;
            $this->isCollection   = true;
            $this->expandedResult = $eResult->getFeed();
        }
        if (null !== $eResult->getEntry()) {
            $this->isExpanded     = true;
            $this->isCollection   = false;
            $this->expandedResult = $eResult->getEntry();
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return !array_reduce(
            ['isExpanded', 'isCollection', 'expandedResult', 'title', 'type', 'name', 'url'],
            function ($carry, $value) {
                return $carry || isset($this->{$value});
            },
            false
        );
    }
}
