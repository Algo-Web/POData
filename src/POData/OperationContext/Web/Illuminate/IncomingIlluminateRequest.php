<?php

namespace POData\OperationContext\Web\Illuminate;

use Illuminate\Http\Request;
use POData\OperationContext\HTTPRequestMethod;
use POData\OperationContext\IHTTPRequest;

class IncomingIlluminateRequest implements IHTTPRequest
{
    /**
     * The Illuminate request.
     *
     * @var Request
     */
    private $request;

    /**
     * The request headers.
     *
     * @var array
     */
    private $_headers;

    /**
     * The incoming url in raw format.
     *
     * @var string
     */
    private $_rawUrl = null;

    /**
     * The request method (GET, POST, PUT, DELETE or MERGE).
     *
     * @var HTTPRequestMethod HttpVerb
     */
    private $_method;

    /**
     * The query options as key value.
     *
     * @var array(string, string);
     */
    private $_queryOptions;

    /**
     * A collection that represents mapping between query
     * option and its count.
     *
     * @var array(string, int)
     */
    private $_queryOptionsCount;

    /**
     * IncomingIlluminateRequest constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->_headers = null;
        $this->_queryOptions = null;
        $this->_queryOptionsCount = null;
        $this->_method = new HTTPRequestMethod($this->request->getMethod());
    }

    /**
     * @return string RequestURI called by User with the value of QueryString
     */
    public function getRawUrl()
    {
        $this->_rawUrl = $this->request->fullUrl();

        return $this->_rawUrl;
    }

    /**
     * @param string $key The header name
     *
     * @return array|null|string
     */
    public function getRequestHeader($key)
    {
        $result = $this->request->header($key);
        //Zend returns false for a missing header...POData needs a null
        if ($result === false || $result === '') {
            return;
        }

        return $result;
    }

    /**
     * Returns the Query String Parameters (QSPs) as an array of KEY-VALUE pairs.  If a QSP appears twice
     * it will have two entries in this array.
     *
     * @return array
     */
    public function getQueryParameters()
    {
        //TODO: the contract is more specific than this, it requires the name and values to be decoded
        //not sure how to test that...
        //TODO: another issue.  This may not be the right thing to return...since POData only really understands GET requests today
        //Have to convert to the stranger format known to POData that deals with multiple query strings.
        //this makes this request a bit non compliant as it doesn't expose duplicate keys, something POData will check for
        //instead whatever parameter was last in the query string is set.  IE
        //odata.svc/?$format=xml&$format=json the format will be json
        $this->_queryOptions = [];
        $this->_queryOptionsCount = 0;
        foreach ($this->request->all() as $key => $value) {
            $this->_queryOptions[] = [$key => $value];
            ++$this->_queryOptionsCount;
        }

        return $this->_queryOptions;
    }

    /**
     * @return HTTPRequestMethod
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * @return array|mixed
     */
    public function getAllInput()
    {
        return $this->request->all();
    }
}
