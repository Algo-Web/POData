<?php

namespace POData\OperationContext;

use POData\OperationContext\Web\IncomingRequest;

class SimpleRequestAdapter extends IncomingRequest implements IHTTPRequest
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * get the raw incoming url.
     *
     * @return string RequestURI called by User with the value of QueryString
     */
    public function getRawUrl()
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REQUEST_URI'];
    }

    /**
     * Returns the Query String Parameters (QSPs) as an array of KEY-VALUE pairs.  If a QSP appears twice
     * it will have two entries in this array.
     *
     * @return array[]
     */
    public function getQueryParameters()
    {
        $data = [];
        if (is_array($this->request)) {
            foreach ($this->request as $key => $value) {
                $data[] = [$key => $value];
            }
        }

        return $data;
    }

    /**
     * Get the HTTP method/verb of the HTTP Request.
     *
     * @return HTTPRequestMethod
     */
    public function getMethod()
    {
        return new HTTPRequestMethod($_SERVER['REQUEST_METHOD']);
    }
}
