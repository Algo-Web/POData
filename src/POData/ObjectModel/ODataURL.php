<?php

declare(strict_types=1);

namespace POData\ObjectModel;

/**
 * Class ODataURL Represents top level link.
 */
class ODataURL
{

    /**
     * contains the url value.
     *
     * @var string
     */
    public $url;

    /**
     * ODataURL constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this
            ->setUrl($url);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return ODataURL
     */
    public function setUrl(string $url): ODataURL
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param  string|null $msg
     * @return bool
     */
    public function isOk(&$msg = null): bool
    {
        if (null == $this->url || empty($this->url)) {
            $msg = 'Url value must be non-empty';
            return false;
        }
        return true;
    }
}
