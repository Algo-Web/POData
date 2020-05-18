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
