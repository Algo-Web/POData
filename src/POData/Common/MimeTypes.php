<?php

namespace POData\Common;


class MimeTypes
{
    //MIME type for ATOM bodies
    //(http://www.iana.org/assignments/media-types/application/).
    const MIME_APPLICATION_ATOM = 'application/atom+xml';

    //MIME type for JSON bodies 
    //(http://www.iana.org/assignments/media-types/application/).
    const MIME_APPLICATION_JSON = 'application/json';

    const MIME_APPLICATION_JSON_MINIMAL_META = 'application/json;odata=minimalmetadata';

    const MIME_APPLICATION_JSON_NO_META = 'application/json;odata=nometadata';

    const MIME_APPLICATION_JSON_FULL_META = 'application/json;odata=fullmetadata';

    const MIME_APPLICATION_JSON_VERBOSE = 'application/json;odata=verbose';

    //MIME type for XML bodies.
    const MIME_APPLICATION_XML = 'application/xml';

    //MIME type for ATOM Service Documents 
    //(http://tools.ietf.org/html/rfc5023#section-8).
    const MIME_APPLICATION_ATOMSERVICE = 'application/atomsvc+xml';

    //MIME type for changeset multipart/mixed
    const MIME_MULTIPART_MIXED = 'multipart/mixed';

    //Boundary parameter name for multipart/mixed MIME type
    const MIME_MULTIPART_MIXED_BOUNDARY = 'boundary';

    //MIME type for batch requests - this mime type must be specified in 
    //CUD change sets or GET batch requests.
    const MIME_APPLICATION_HTTP = 'application/http';

    //MIME type for any content type.
    const MIME_ANY = '*/*';

    //MIME type for XML bodies (deprecated).
    const MIME_TEXTXML = 'text/xml';

    //MIME type for plain text bodies.
    const MIME_TEXTPLAIN = 'text/plain';

    //MIME type general binary bodies.
    const MIME_APPLICATION_OCTETSTREAM = 'application/octet-stream';
    
    //'text' - MIME type for text subtypes.
    const MIME_TEXTTYPE = 'text';

    //'application' - MIME type for application types.
    const MIME_APPLICATION_TYPE = 'application';

    //'xml' - constant for MIME xml subtypes.
    const MIME_XML_SUBTYPE = 'xml';

    //'json' - constant for MIME JSON subtypes.
    const MIME_JSON_SUBTYPE = 'json';

}