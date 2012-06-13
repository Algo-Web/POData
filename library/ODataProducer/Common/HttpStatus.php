<?php
/** 
 * HTTP status codes.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
namespace ODataProducer\Common;
/**
 * HTTP status code class
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class HttpStatus
{
    const CODE_CONTINUE                        = 100;
    const CODE_SWITCHING_PROTOCOLS             = 101;
    const CODE_OK                              = 200;
    const CODE_CREATED                         = 201;
    const CODE_ACCEPTED                        = 202;
    const CODE_NON_AUTHRATIVE_INFORMATION      = 203;
    const CODE_NOCONTENT                       = 204;
    const CODE_RESET_CONTENT                   = 205;
    const CODE_PARTIAL_CONTENT                 = 206;
    const CODE_MULTIPLE_CHOICE                 = 300;
    const CODE_MOVED_PERMANENTLY               = 301;
    const CODE_FOUND                           = 302;
    const CODE_SEE_OTHER                       = 303;
    const CODE_NOT_MODIFIED                    = 304;
    const CODE_USE_PROXY                       = 305;
    const CODE_UNUSED                          = 306;
    const CODE_TEMP_REDIRECT                   = 307;
    const CODE_BAD_REQUEST                     = 400;
    const CODE_UNAUTHORIZED                    = 401;
    const CODE_PAYMENT_REQ                     = 402;
    const CODE_FORBIDDEN                       = 403;
    const CODE_NOT_FOUND                       = 404;
    const CODE_METHOD_NOT_ALLOWED              = 405;
    const CODE_NOT_ACCEPTABLE                  = 406;
    const CODE_PROXY_AUTHENTICATION_REQUIRED   = 407;
    const CODE_REQUEST_TIMEOUT                 = 408;
    const CODE_CONFLICT                        = 409;
    const CODE_GONE                            = 410;
    const CODE_LENGTH_REQUIRED                 = 411;
    const CODE_PRECONDITION_FAILED             = 412;
    const CODE_REQUEST_ENTITY_TOOLONG          = 413;
    const CODE_REQUEST_URI_TOOLONG             = 414;
    const CODE_UNSUPPORTED_MEDIATYPE           = 415;
    const CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const CODE_EXPECTATION_FAILED              = 417;
    const CODE_INTERNAL_SERVER_ERROR           = 500;
    const CODE_NOT_IMPLEMENTED                 = 501;
    const CODE_BAD_GATEWAY                     = 502;
    const CODE_SERVICE_UNAVAILABLE             = 503;
    const CODE_GATEWAY_TIMEOUT                 = 504;
    const CODE_HTTP_VERSION_NOT_SUPPORTED      = 505;

    /**
     * Get status description from status code.
     * 
     * @param int $statusCode status code
     * 
     * @return string/NULL
     */
    public static function getStatusDescription($statusCode)
    {
        switch ($statusCode) {
        case self::CODE_CONTINUE:
                return 'Continue';
        case self::CODE_SWITCHING_PROTOCOLS:
                return  'SwitchingProtocols';
        case self::CODE_OK:
                return  'OK';
        case self::CODE_CREATED;
                return  'Created';
        case self::CODE_ACCEPTED;
                return  'Accepted';
        case self::CODE_NON_AUTHRATIVE_INFORMATION;
                return  'Non-Authrative Information';
        case self::CODE_NOCONTENT;
                return  'No Content';
        case self::CODE_RESET_CONTENT;
                return 'ResetContent';
        case self::CODE_PARTIAL_CONTENT;
                return 'Partial Content';
        case self::CODE_MULTIPLE_CHOICE;
                return 'Multiple Choices';
        case self::CODE_MOVED_PERMANENTLY;
                return 'Moved Permanently';
        case self::CODE_FOUND;
                return 'Found';
        case self::CODE_SEE_OTHER;
                return 'See Other';
        case self::CODE_NOT_MODIFIED;
                return 'Not Modified';
        case self::CODE_USE_PROXY;
                return 'Use Proxy';
        case self::CODE_UNUSED;
                return 'Unused';    
        case self::CODE_TEMP_REDIRECT;
                return 'Temporary Redirect';
        case self::CODE_BAD_REQUEST;
                return 'Bad Request';
        case self::CODE_UNAUTHORIZED;
                return 'Unauthorized';
        case self::CODE_PAYMENT_REQ;
                return 'Payment Required';
        case self::CODE_FORBIDDEN;
                return 'Forbidden';
        case self::CODE_NOT_FOUND;
                return 'Not Found';
        case self::CODE_METHOD_NOT_ALLOWED;
                return 'Method Not Allowed';
        case self::CODE_NOT_ACCEPTABLE;
                return 'Not Acceptable';
        case self::CODE_PROXY_AUTHENTICATION_REQUIRED;
                return 'Proxy Authentication Required';
        case self::CODE_REQUEST_TIMEOUT;
                return 'Request Timeout';
        case self::CODE_CONFLICT;
                return 'Conflict';
        case self::CODE_GONE;
                return 'Gone';
        case self::CODE_LENGTH_REQUIRED;
                return 'Length Required';
        case self::CODE_PRECONDITION_FAILED;
                return 'Precondition Failed';
        case self::CODE_REQUEST_ENTITY_TOOLONG;
                return 'Request Entity Too Large';
        case self::CODE_REQUEST_URI_TOOLONG;
                return 'Request-URI Too Large';
        case self::CODE_UNSUPPORTED_MEDIATYPE;
                return 'Unsupported Media Type';
        case self::CODE_REQUESTED_RANGE_NOT_SATISFIABLE;
                return 'Requested Range NotSatisfiable';
        case self::CODE_EXPECTATION_FAILED;
                return 'Expectation Failed';
        case self::CODE_INTERNAL_SERVER_ERROR;
                return 'Internal Server Error';
        case self::CODE_NOT_IMPLEMENTED;
                return 'Not Implemented';
        case self::CODE_BAD_GATEWAY;
                return 'Bad Gateway';
        case self::CODE_SERVICE_UNAVAILABLE;
                return 'Service Unavailable';
        case self::CODE_GATEWAY_TIMEOUT;
                return 'Gateway Timeout';
        case self::CODE_HTTP_VERSION_NOT_SUPPORTED;
                return 'HTTP Version Not Suppoted';
        }

        return null;
    }
}