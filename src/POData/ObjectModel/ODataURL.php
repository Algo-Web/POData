<?php

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

    public function isOk(&$msg = null)
    {
        if (null == $this->url || empty($this->url)) {
            $msg = 'Url value must be non-empty';
            return false;
        }
        return true;
    }
}
