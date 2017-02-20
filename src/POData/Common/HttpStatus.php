<?php

namespace POData\Common;

/**
 * Class HttpStatus.
 */
class HttpStatus
{
    const CODE_CONTINUE = 100;
    const CODE_SWITCHING_PROTOCOLS = 101;
    const CODE_OK = 200;
    const CODE_CREATED = 201;
    const CODE_ACCEPTED = 202;
    const CODE_NON_AUTHRATIVE_INFORMATION = 203;
    const CODE_NOCONTENT = 204;
    const CODE_RESET_CONTENT = 205;
    const CODE_PARTIAL_CONTENT = 206;
    const CODE_MULTIPLE_CHOICE = 300;
    const CODE_MOVED_PERMANENTLY = 301;
    const CODE_FOUND = 302;
    const CODE_SEE_OTHER = 303;
    const CODE_NOT_MODIFIED = 304;
    const CODE_USE_PROXY = 305;
    const CODE_UNUSED = 306;
    const CODE_TEMP_REDIRECT = 307;
    const CODE_BAD_REQUEST = 400;
    const CODE_UNAUTHORIZED = 401;
    const CODE_PAYMENT_REQ = 402;
    const CODE_FORBIDDEN = 403;
    const CODE_NOT_FOUND = 404;
    const CODE_METHOD_NOT_ALLOWED = 405;
    const CODE_NOT_ACCEPTABLE = 406;
    const CODE_PROXY_AUTHENTICATION_REQUIRED = 407;
    const CODE_REQUEST_TIMEOUT = 408;
    const CODE_CONFLICT = 409;
    const CODE_GONE = 410;
    const CODE_LENGTH_REQUIRED = 411;
    const CODE_PRECONDITION_FAILED = 412;
    const CODE_REQUEST_ENTITY_TOOLONG = 413;
    const CODE_REQUEST_URI_TOOLONG = 414;
    const CODE_UNSUPPORTED_MEDIATYPE = 415;
    const CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const CODE_EXPECTATION_FAILED = 417;
    const CODE_INTERNAL_SERVER_ERROR = 500;
    const CODE_NOT_IMPLEMENTED = 501;
    const CODE_BAD_GATEWAY = 502;
    const CODE_SERVICE_UNAVAILABLE = 503;
    const CODE_GATEWAY_TIMEOUT = 504;
    const CODE_HTTP_VERSION_NOT_SUPPORTED = 505;

    private static $reverse = [
        100 => self::CODE_CONTINUE,
        101 => self::CODE_SWITCHING_PROTOCOLS,
        200 => self::CODE_OK,
        201 => self::CODE_CREATED,
        202 => self::CODE_ACCEPTED,
        203 => self::CODE_NON_AUTHRATIVE_INFORMATION,
        204 => self::CODE_NOCONTENT,
        205 => self::CODE_RESET_CONTENT,
        206 => self::CODE_PARTIAL_CONTENT,
        300 => self::CODE_MULTIPLE_CHOICE,
        301 => self::CODE_MOVED_PERMANENTLY,
        302 => self::CODE_FOUND,
        303 => self::CODE_SEE_OTHER,
        304 => self::CODE_NOT_MODIFIED,
        305 => self::CODE_USE_PROXY,
        306 => self::CODE_UNUSED,
        307 => self::CODE_TEMP_REDIRECT,
        400 => self::CODE_BAD_REQUEST,
        401 => self::CODE_UNAUTHORIZED,
        402 => self::CODE_PAYMENT_REQ,
        403 => self::CODE_FORBIDDEN,
        404 => self::CODE_NOT_FOUND,
        405 => self::CODE_METHOD_NOT_ALLOWED,
        406 => self::CODE_NOT_ACCEPTABLE,
        407 => self::CODE_PROXY_AUTHENTICATION_REQUIRED,
        408 => self::CODE_REQUEST_TIMEOUT,
        409 => self::CODE_CONFLICT,
        410 => self::CODE_GONE,
        411 => self::CODE_LENGTH_REQUIRED,
        412 => self::CODE_PRECONDITION_FAILED,
        413 => self::CODE_REQUEST_ENTITY_TOOLONG,
        414 => self::CODE_REQUEST_URI_TOOLONG,
        415 => self::CODE_UNSUPPORTED_MEDIATYPE,
        416 => self::CODE_REQUESTED_RANGE_NOT_SATISFIABLE,
        417 => self::CODE_EXPECTATION_FAILED,
        500 => self::CODE_INTERNAL_SERVER_ERROR,
        501 => self::CODE_NOT_IMPLEMENTED,
        502 => self::CODE_BAD_GATEWAY,
        503 => self::CODE_SERVICE_UNAVAILABLE,
        504 => self::CODE_GATEWAY_TIMEOUT,
        505 => self::CODE_HTTP_VERSION_NOT_SUPPORTED,
    ];

    private static $mapping = [
        self::CODE_CONTINUE                        => 'Continue',
        self::CODE_SWITCHING_PROTOCOLS             => 'Switching Protocols',
        self::CODE_OK                              => 'OK',
        self::CODE_CREATED                         => 'Created',
        self::CODE_ACCEPTED                        => 'Accepted',
        self::CODE_NON_AUTHRATIVE_INFORMATION      => 'Non-Authoritative Information',
        self::CODE_NOCONTENT                       => 'No Content',
        self::CODE_RESET_CONTENT                   => 'ResetContent',
        self::CODE_PARTIAL_CONTENT                 => 'Partial Content',
        self::CODE_MULTIPLE_CHOICE                 => 'Multiple Choices',
        self::CODE_MOVED_PERMANENTLY               => 'Moved Permanently',
        self::CODE_FOUND                           => 'Found',
        self::CODE_SEE_OTHER                       => 'See Other',
        self::CODE_NOT_MODIFIED                    => 'Not Modified',
        self::CODE_USE_PROXY                       => 'Use Proxy',
        self::CODE_UNUSED                          => 'Unused',
        self::CODE_TEMP_REDIRECT                   => 'Temporary Redirect',
        self::CODE_BAD_REQUEST                     => 'Bad Request',
        self::CODE_UNAUTHORIZED                    => 'Unauthorized',
        self::CODE_PAYMENT_REQ                     => 'Payment Required',
        self::CODE_FORBIDDEN                       => 'Forbidden',
        self::CODE_NOT_FOUND                       => 'Not Found',
        self::CODE_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
        self::CODE_NOT_ACCEPTABLE                  => 'Not Acceptable',
        self::CODE_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
        self::CODE_REQUEST_TIMEOUT                 => 'Request Timeout',
        self::CODE_CONFLICT                        => 'Conflict',
        self::CODE_GONE                            => 'Gone',
        self::CODE_LENGTH_REQUIRED                 => 'Length Required',
        self::CODE_PRECONDITION_FAILED             => 'Precondition Failed',
        self::CODE_REQUEST_ENTITY_TOOLONG          => 'Request Entity Too Large',
        self::CODE_REQUEST_URI_TOOLONG             => 'Request URI Too Large',
        self::CODE_UNSUPPORTED_MEDIATYPE           => 'Unsupported Media Type',
        self::CODE_REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::CODE_EXPECTATION_FAILED              => 'Expectation Failed',
        self::CODE_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
        self::CODE_NOT_IMPLEMENTED                 => 'Not Implemented',
        self::CODE_BAD_GATEWAY                     => 'Bad Gateway',
        self::CODE_SERVICE_UNAVAILABLE             => 'Service Unavailable',
        self::CODE_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
        self::CODE_HTTP_VERSION_NOT_SUPPORTED      => 'HTTP Version Not Supported',
    ];

    /**
     * Get status description from status code.
     *
     * @param int $statusCode status code
     *
     * @return string|null
     */
    public static function getStatusDescription($statusCode)
    {
        // if int, look up corresponding constant value - if not exists, bail out
        if (is_int($statusCode)) {
            if (!array_key_exists($statusCode, self::$reverse)) {
                return;
            }
            $statusCode = self::$reverse[$statusCode];
        }

        // here mainly to catch non-integral inputs that can't be mapped to a defined class constant
        if (!array_key_exists($statusCode, self::$mapping)) {
            return;
        }

        return self::$mapping[$statusCode];
    }
}
