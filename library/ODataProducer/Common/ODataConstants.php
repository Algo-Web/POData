<?php
/** 
 * Class which holds definition of OData constants
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
 * Class for OData constants
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Common
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ODataConstants
{
    //'DataServiceVersion' - HTTP header name for data service version.
    const ODATAVERSIONHEADER = 'DataServiceVersion';

    //'MaxDataServiceVersion' - HTTP header name for 
    //maximum understood data service version.
    const ODATAMAXVERSIONHEADER = 'MaxDataServiceVersion';

    //'1.0' - the version 1.0 text for a data service.
    const DATASERVICEVERSION_1_DOT_0 = '1.0';

    //'2.0' - the version 2.0 text for a data service.
    const DATASERVICEVERSION_2_DOT_0 = '2.0';

    // Value for $format option for response json format
    const FORMAT_JSON = 'json';

    // Value for $format option for response atom format
    const FORMAT_ATOM = 'atom';

    //HTTP name for Accept header
    const HTTP_REQUEST_ACCEPT = 'Accept';

    //HTTP name for Accept char set header.
    const HTTP_REQUEST_ACCEPT_CHARSET = 'Accept-Charset';

    //mime-type of the corresponding body
    const HTTP_CONTENTTYPE = 'Content-Type';

    //id of the corresponding body
    const HTTP_CONTENTID = 'Content-ID';

    //Content-Transfer-Encoding header for batch requests.
    const HTTP_CONTENT_TRANSFER_ENCODING = 'Content-Transfer-Encoding';

    //HTTP name for If-Match header
    const HTTP_REQUEST_IFMATCH = 'If-Match';

    //HTTP name for If-None-Match header
    const HTTP_REQUEST_IFNONEMATCH = 'If-None-Match';

    //'X-HTTP-Method' - HTTP header name for requests that want to 
    //tunnel a method through POST.
    const HTTP_X_METHOD = 'X-HTTP-Method';

    //Http Version in batching requests and response.
    const HTTP_VERSION_INBATCHING = 'HTTP/1.1';

    //HTTP method name for GET requests.
    const HTTP_METHOD_GET = 'GET';

    //HTTP method name for POST requests.
    const HTTP_METHOD_POST = 'POST';

    // Http Put Method name - basically used for updating resource.
    const HTTP_METHOD_PUT = 'PUT';

    //HTTP method name for delete requests.
    const HTTP_METHOD_DELETE = 'DELETE';

    //HTTP method name for merge requests
    const HTTP_METHOD_MERGE = 'MERGE';

    //HTTP name for ETag header
    const HTTP_RESPONSE_ETAG = 'ETag';

    //HTTP name for location header
    const HTTP_RESPONSE_LOCATION = 'Location';

    //HTTP name for Status-Code header
    const HTTP_RESPONSE_STATUSCODE = 'Status-Code';
    
    //byte-length of the corresponding body
    const HTTP_CONTENT_LENGTH = 'Content-Length';

    //'charset' - HTTP parameter name.
    const HTTP_CHARSET_PARAMETER = 'charset';
    
    //HTTP weak etag prefix
    const HTTP_WEAK_ETAG_PREFIX = "W/\"";

    //HTTP name for user agent
    const HTTP_USER_AGENT = 'User-Agent';

    //MIME type for ATOM bodies 
    //(http://www.iana.org/assignments/media-types/application/).
    const MIME_APPLICATION_ATOM = 'application/atom+xml';

    //MIME type for JSON bodies 
    //(http://www.iana.org/assignments/media-types/application/).
    const MIME_APPLICATION_JSON = 'application/json';

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
    //CUD changesets or GET batch requests.
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

    //Content-Transfer-Encoding value for batch requests.
    const BATCHREQUEST_CONTENTTRANSFER_ENCODING = 'binary';

    //'application' - MIME type for application types.
    const MIME_APPLICATION_TYPE = 'application';

    //'xml' - constant for MIME xml subtypes.
    const MIME_XML_SUBTYPE = 'xml';

    //'json' - constant for MIME JSON subtypes.
    const MIME_JSON_SUBTYPE = 'json';

    //'q' - HTTP q-value parameter name.
    const HTTP_QVALUE_PARAMETER = 'q';

    //'false' literal, as used in XML.
    const XML_FALSE_LITERAL = 'false';

    //'true' literal, as used in XML.
    const XML_TRUE_LITERAL = 'true';

    //'INF' literal, as used in XML for infinity.
    const XML_INFINITY_LITERAL = 'INF';

    //'NaN' literal, as used in XML for not-a-number values.
    const XML_NAN_LITERAL = 'NaN';

    //Name of collection element for value reader/writer.
    const COLLECTION_ELEMENT_NAME = 'element';

    //XML attribute name to pass to the XMLReader.GetValue API to get 
    //the xml:base attribute value.
    const XML_BASE_ATTRIBUTE_NAME_WITH_PREFIX = 'xml:base';

    //Edm Primitive Type Names

    //namespace for edm primitive types.
    const EDM_NAMESPACE = 'Edm';

    //edm binary primitive type name
    const EDM_BINARYTYPE_NAME = 'Edm.Binary';

    //edm boolean primitive type name
    const EDM_BOOLEANTYPE_NAME = 'Edm.Boolean';

    //edm byte primitive type name
    const EDM_BYTETYPE_NAME = 'Edm.Byte';

    //edm datetime primitive type name
    const EDM_DATETIMETYPE_NAME = 'Edm.DateTime';

    //edm decimal primitive type name
    const EDM_DECIMALTYPE_NAME = 'Edm.Decimal';

    //edm double primitive type name
    const EDM_DOUBLETYPE_NAME = 'Edm.Double';

    //edm guid primitive type name
    const EDM_GUIDTYPE_NAME = 'Edm.Guid';

    //edm single primitive type name
    const EDM_SINGLETYPE_NAME = 'Edm.Single';

    //edm sbyte primitive type name
    const EDM_SBYTETYPE_NAME = 'Edm.SByte';

    //edm int16 primitive type name
    const EDM_INT16TYPE_NAME = 'Edm.Int16';

    //edm int32 primitive type name
    const EDM_INT32TYPE_NAME = 'Edm.Int32';

    //edm int64 primitive type name
    const EDM_INT64TYPE_NAME = 'Edm.Int64';

    //edm primitive type name
    const EDM_STRINGTYPE_NAME = 'Edm.String';

    //JSON Format constants

    //JSON property name for an error.
    const JSON_ERROR = 'error';

    //JSON property name for an error code.
    const JSON_ERROR_CODE = 'code';

    //JSON property name for the inner error details.
    const JSON_ERROR_INNER = 'innererror';

    //JSON property name for an exception.
    const JSON_ERROR_INTERNAL_EXCEPTION = 'internalexception';

    //JSON property name for an error message.
    const JSON_ERROR_MESSAGE = 'message';

    //JSON property name for an exception stack trace.
    const JSON_ERROR_STACK_TRACE = 'stacktrace';

    //JSON property name for an exception type.
    const JSON_ERROR_TYPE = 'type';

    //JSON property name for an error message value.
    const JSON_ERROR_VALUE = 'value';

    //metadata element name in json payload.
    const JSON_METADATA_STRING = '__metadata';

    //uri element name in json payload.
    const JSON_URI_STRING = 'uri';

    //type element name in json payload.
    const JSON_TYPE_STRING = 'type';

    //edit_media element name in json payload.
    const JSON_EDITMEDIA_STRING = 'edit_media';

    //media_src element name in json payload.
    const JSON_MEDIASRC_STRING = 'media_src';

    //content_type element name in json payload.
    const JSON_CONTENTTYPE_STRING = 'content_type';

    //media_etag element name in json payload.
    const JSON_MEDIAETAG_STRING = 'media_etag';

    //deferred element name in json payload.
    const JSON_DEFERRED_STRING = '__deferred';

    //etag element name in json payload.
    const JSON_ETAG_STRING = 'etag';

    //row count element name in json payload
    const JSON_ROWCOUNT_STRING = '__count';

    //next page link element name in json payload
    const JSON_NEXT_STRING = '__next';

    //'results' header for Json data array
    const JSON_RESULT_NAME = 'results';

    const JSON_DATAWRAPPER_ELEMENT_NAME = 'd';

    //Atom Format constants

    // Schema Namespace For Atom.
    const ATOM_NAMESPACE = 'http://www.w3.org/2005/Atom';

    //Schema Namespace for Atom Publishing Protocol.
    const APP_NAMESPACE = 'http://www.w3.org/2007/app';

    //XML namespace for data services.
    const ODATA_NAMESPACE = 'http://schemas.microsoft.com/ado/2007/08/dataservices';

    //XML namespace for data service annotations.
    const ODATA_METADATA_NAMESPACE = 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata';

    //XML namespace for data service links.
    const ODATA_RELATED_NAMESPACE = 'http://schemas.microsoft.com/ado/2007/08/dataservices/related/';

    //ATOM Scheme Namespace For DataWeb.
    const ODATA_SCHEME_NAMESPACE = 'http://schemas.microsoft.com/ado/2007/08/dataservices/scheme';

    //'http://www.w3.org/2000/xmlns/' - namespace for namespace declarations.
    const XML_NAMESPACES_NAMESPACE = 'http://www.w3.org/2000/xmlns/';

    //Attribute use to add xml: namespaces specific attributes.
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    // Schema Namespace prefix For xmlns.
    const XMLNS_NAMESPACE_PREFIX = 'xmlns';

    // Schema Namespace prefix For xml.
    const XML_NAMESPACE_PREFIX = 'xml';

    //XML attribute value to indicate the base URI for a document or element.
    const XML_BASE_ATTRIBUTE_NAME = 'base';

    // Schema Namespace Prefix For DataWeb.
    const ODATA_NAMESPACE_PREFIX = 'd';

    //'adsm' - namespace prefix for DataWebMetadataNamespace.
    const ODATA_METADATA_NAMESPACE_PREFIX = 'm';

    //XML element name to mark feed element in Atom.
    const ATOM_FEED_ELEMENT_NAME = 'feed';

    //XML element name to mark entry element in Atom.
    const ATOM_ENTRY_ELEMENT_NAME = 'entry';

    //XML element name to mark id element in Atom.
    const ATOM_ID_ELEMENT_NAME = 'id';

    //XML element name to mark title element in Atom.
    const ATOM_TITLE_ELELMET_NAME = 'title';

    //'updated' - XML element name for ATOM 'updated' element for entries.
    const ATOM_UPDATED_ELEMENT_NAME = 'updated';

    //'author' - XML element name for ATOM 'author' element for entries.
    const ATOM_AUTHOR_ELEMENT_NAME = 'author';

    //'contributor' - XML element name for ATOM 'author' element for entries.
    const ATOM_CONTRIBUTOR_ELEMENT_NAME = 'contributor';

    //XML element name to mark author/name or contributor/name element in Atom.
    const ATOM_NAME_ELEMENT_NAME = 'name';

    //XML element name to mark author/email or contributor/email element in Atom.
    const ATOM_EMAIL_ELEMENT_NAME = 'email';

    //XML element name to mark author/uri or contributor/uri element in Atom.
    const ATOM_URI_ELEMENT_NAME = 'uri';
    
    //XML element name to mark uri collection in atom.
    const ATOM_LINKS_ELEMENT_NAME = 'links';
    
    //XML element name to mark published element in Atom.
    const ATOM_PUBLISHED_ELEMENT_NAME = 'published';

    //XML element name to mark rights element in Atom.
    const ATOM_RIGHTS_ELEMENT_NAME = 'rights';

    //XML element name to mark summary element in Atom.
    const ATOM_SUMMARY_ELEMENT_NAME = 'summary';

    //'category' - XML element name for ATOM 'category' element for entries.
    const ATOM_CATEGORY_ELEMENT_NAME = 'category';

    //'term' - XML attribute name for ATOM 'term' attribute for categories.
    const ATOM_CATEGORY_TERM_ATTRIBUTE_NAME = 'term';

    //'scheme' - XML attribute name for ATOM 'scheme' attribute for categories.
    const ATOM_CATEGORY_SCHEME_ATTRIBUTE_NAME = 'scheme';

    //XML element name to mark content element in Atom.
    const ATOM_CONTENT_ELEMENT_NAME = 'content';

    //XML element name to mark title element in Atom.
    const ATOM_TYPE_ATTRIBUTE_NAME = 'type';

    //Atom attribute that indicates the actual location for an entry's content.
    const ATOM_CONTENT_SRC_ATTRIBUTE_NAME = 'src';

    //XML element name to mark link element in Atom.
    const ATOM_LINK_ELEMENT_NAME = 'link';

    //XML element name to mark link relation attribute in Atom.
    const ATOM_LINK_RELATION_ATTRIBUTE_NAME = 'rel';

    //XML element name to mark href attribute element in Atom.
    const ATOM_HREF_ATTRIBUTE_NAME = 'href';

    // Atom link relation attribute value for edit links.
    const ATOM_EDIT_RELATION_ATTRIBUTE_VALUE = 'edit';

    // Atom link relation attribute value for self links.
    const ATOM_SELF_RELATION_ATTRIBUTE_VALUE = 'self';

    //XML element for 'next' links: [atom:link rel='next']
    const ATOM_LINK_NEXT_ATTRIBUTE_STRING = 'next';

    // Atom link relation attribute value for edit-media links.
    const ATOM_EDIT_MEDIA_RELATION_ATTRIBUTE_VALUE = 'edit-media';

    // Atom attribute which indicates the null value for the element.
    const ATOM_NULL_ATTRIBUTE_NAME = 'null';

    // Atom attribute which indicates the etag value for the declaring entry element.
    const ATOM_ETAG_ATTRIBUTE_NAME = 'etag';

    //'Inline' - wrapping element for inlined entry/feed content.
    const ATOM_INLINE_ELEMENT_NAME = 'inline';

    //Element containing property values when 'content' is used 
    //for media link entries
    const ATOM_PROPERTIES_ELEMENT_NAME = 'properties';

    //'count' element
    const ROWCOUNT_ELEMENT = 'count';

    //XML element name to mark 'collection' element in APP.
    const ATOM_PUBLISHING_COLLECTION_ELEMENT_NAME = 'collection';

    //XML element name to mark 'service' element in APP.
    const ATOM_PUBLISHING_SERVICE_ELEMENT_NAME = 'service';

    //XML value for a default workspace in APP.
    const ATOM_PUBLISHING_WORKSPACE_DEFAULT_VALUE = 'Default';

    //XML element name to mark 'workspace' element in APP.
    const ATOM_PUBLISHING_WORKSPACE_ELEMNT_NAME = 'workspace';

    //XML constants.

    //XML element name for an error.
    const XML_ERROR_ELEMENT_NAME = 'error';

    //XML element name for an error code.
    const XML_ERROR_CODE_ELEMENT_NAME = 'code';

    //XML element name for the inner error details.
    const XML_ERROR_INNER_ELEMENT_NAME = 'innererror';

    //XML element name for an exception.
    const XML_ERROR_INTERNAL_EXCEPTION_ELEMENT_NAME = 'internalexception';

    //XML element name for an exception type.
    const XML_ERROR_TYPE_ELEMENT_NAME = 'type';

    //XML element name for an exception stack trace.
    const XML_ERROR_STACK_TRACE_ELEMENT_NAME = 'stacktrace';

    //XML element name for an error message.
    const XML_ERROR_MESSAGE_ELEMENT_NAME = 'message';

    //'lang' XML attribute name for annotation language.
    const XML_LANG_ATTRIBUTE_NAME = 'lang';

    //Edmx File Constants

    // Edmx namespace in metadata document for version 1.0.
    const EDMX_NAMESPACE_1_0 = 'http://schemas.microsoft.com/ado/2007/06/edmx';

    // Edmx namespace in metadata document for version 2.0.
    const EDMX_NAMESPACE_2_0 = 'http://schemas.microsoft.com/ado/2008/10/edmx';

    //CSDL (Conceptual Schema Definition Language) version 1.0
    const CSDL_VERSION_1_0 = 'http://schemas.microsoft.com/ado/2006/04/edm';

    //CSDL (Conceptual Schema Definition Language) version 1.1
    const CSDL_VERSION_1_1 = 'http://schemas.microsoft.com/ado/2007/05/edm';

    //CSDL (Conceptual Schema Definition Language) version 1.2
    const CSDL_VERSION_1_2 = 'http://schemas.microsoft.com/ado/2008/01/edm';

    //CSDL (Conceptual Schema Definition Language) version 2.0
    const CSDL_VERSION_2_0 = 'http://schemas.microsoft.com/ado/2008/09/edm';

    //CSDL (Conceptual Schema Definition Language) version 2.0
    const CSDL_VERSION_2_2 = 'http://schemas.microsoft.com/ado/2010/02/edm';

    // Prefix for Edmx Namespace in metadata document.
    const EDMX_NAMESPACE_PREFIX = 'edmx';

    // Schema Element Name in csdl.
    const SCHEMA = 'Schema';

    // Edmx Element Name in the metadata document.
    const EDMX_ELEMENT = 'Edmx';

    //Edmx runtime element.
    const EDMX_RUNTIME_ELEMENT = 'Runtime';

    //Edmx conceptual models element.
    const EDMX_CONCEPTUAL_MODELS = 'ConceptualModels';

    // Edmx DataServices Element Name in the metadata document.
    const EDMX_DATASERVICES_ELEMENT = 'DataServices';

    //Version attribute for the root Edmx Element in the metadata document.
    const EDMX_VERSION = 'Version';

    //Value of the version attribute in the root edmx element in metadata document.
    const EDMX_VERSION_VALUE = '1.0';

    // EntityContainer Element Name in csdl.
    const ENTITY_CONTAINER = 'EntityContainer';

    // EntitySet attribute and Element Name in csdl.
    const ENTITY_SET = 'EntitySet';

    // AssociationSet Element Name in csdl.
    const ASSOCIATION_SET = 'AssociationSet';

    //FunctionImport element name in CSDL documents.
    const EDM_FUNCTION_IMPORT_ELEMENT_NAME = 'FunctionImport';

    //Parameter element name in CSDL documents.
    const EDM_PARAMETER_ELEMENT_NAME = 'Parameter';

    //ReturnType attribute name in CSDL documents.
    const EDM_RETURN_TYPE_ATTRIBUTE_NAME = 'ReturnType';

    //Format to describe a collection of a given type.
    const EDM_COLLECTION_TYPE_FORMAT = 'Collection({0})';

    //edm bag type name
    const EDM_BAG_TYPE = 'Bag';

    //Mode attribute name in CSDL documents.
    const EDM_MODE_ATTRIBUTE_NAME = 'Mode';

    //Mode attribute value for 'in' direction in CSDL documents.
    const EDM_MODELN_VALUE = 'In';

    // EntityType Element Name in csdl.
    const ENTITY_TYPE = 'EntityType';

    // BaseType attribute Name in csdl.
    const BASE_TYPE = 'BaseType';

    //Abstract attribute Name in csdl.
    const ABSTRACT1 = 'Abstract';

    // Association Element Name in csdl.
    const ASSOCIATION = 'Association';

    // ReferentialConstraint Element Name in csdl.
    const REFERENTIAL_CONSTRAINT = 'ReferentialConstraint';

    // Principal Element Name in csdl.
    const PRINCIPAL = 'Principal';

    // Dependent Element Name in csdl.
    const DEPENDENT = 'Dependent';

    // End Element Name in csdl.
    const END = 'End';

    // Role Element Name in csdl.
    const ROLE = 'Role';

    //Multiplicity attribute Name in csdl.
    const MULTIPLICITY = 'Multiplicity';

    // ComplexType Element Name in csdl.
    const COMPLEX_TYPE = 'ComplexType';

    // Key Element Name in csdl.
    const KEY = 'Key';

    // Property Element Name in csdl.
    const PROPERTY = 'Property';

    // PropetyRef Element Name in csdl.
    const PROPERTY_REF = 'PropertyRef';

    // TypeRef Element Name in csdl.
    const TYPE_REF = 'TypeRef';

    // NavigationProperty Element Name in csdl.
    const NAVIGATION_PROPERTY = 'NavigationProperty';

    // FromRole attribute Name in csdl.
    const FROM_ROLE = 'FromRole';

    //ToRole attribute Name in csdl.
    const TO_ROLE = 'ToRole';

    //OnDelete Element Name in csdl.
    const ON_DELETE = 'OnDelete';

    //Action attribute Name in csdl.
    const ACTION = 'Action';

    //Name attribute Name in csdl.
    const NAME = 'Name';

    //Namespace attribute Element Name in csdl.
    const NAMESPACE1 = 'Namespace';

    //Type attribute Name in csdl.
    const TYPE1 = 'Type';

    //Relationship attribute Name in csdl.
    const RELATIONSHIP = 'Relationship';

    //Value for Many multiplicity in csdl.
    const MANY = '*';

    //Value for One multiplicity in csdl.
    const ONE = '1';

    //Value for ZeroOrOne multiplicity in csdl.
    const ZERO_OR_ONE = '0..1';

    // Edm Facets Names and Values

    //Nullable Facet Name in csdl.
    const NULLABLE = 'Nullable';

    //MaxLength Facet Name in csdl.
    const MAX_LENGTH = 'MaxLength';

    //Unicode Facet Name in csdl.
    const UNICODE = 'Unicode';

    //FixedLength Facet Name in csdl.
    const FIXED_LENGTH = 'FixedLength';

    //Precision Facet Name in csdl.
    const PRECISION = 'Precision';

    //Scale Facet Name in csdl.
    const SCALE = 'Scale';

    //Name of the concurrency attribute.
    const CONCURRENCY_ATTRIBUTE = 'ConcurrencyMode';

    //Value of the concurrency attribute.
    const CONCURRENCY_FIXEDVALUE = 'Fixed';  

    //DataWeb Elements and Attributes.

    //'MimeType' - attribute name for property MIME type attributes.
    const DATAWEB_MIMETYPE_ATTRIBUTE_NAME = 'MimeType';

    //'OpenType' - attribute name to indicate a type is an OpenType property.
    const DATAWEB_OPENTYPE_ATTRIBUTE_NAME = 'OpenType';

    //'HasStream' - attribute name to indicate a type has a default stream property.
    const DATAWEB_ACCESS_HASSTREAM_ATTRIBUTE = 'HasStream';

    //'NamedStreams' - Element to indicate a type has a named streams
    const DATAWEB_NAMEDSTREAMS_ELEMENT = 'NamedStreams';

    //'NamedStream' - Element name for each named stream on a type
    const DATAWEB_NAMEDSTREAM_ELEMENT = 'NamedStream';

    //'true' - attribute value to indicate a type has a default stream property.
    const DATAWEB_ACCESS_DEFAULTSTREAM_PROPERTYVALUE = 'true';

    //Attribute to indicate whether this is a default entity container or not.
    const ISDEFAULT_ENTITY_CONTAINER_ATTRIBUTE = 'IsDefaultEntityContainer';

    //Attribute name in the csdl to indicate whether the service operation must be 
    //called using POST or GET verb.
    const SERVICEOPERATION_HTTP_METHODNAME = 'HttpMethod';

    //Query Expression Constants

    //'add' keyword for expressions.
    const KEYWORD_ADD = 'add';

    //'and' keyword for expressions.
    const KEYWORD_AND = 'and';

    //'asc' keyword for expressions.
    const KEYWORD_ASCENDING = 'asc';

    //'desc' keyword for expressions.
    const KEYWORD_DESCENDING = 'desc';

    //'div' keyword for expressions.
    const KEYWORD_DIVIDE = 'div';

    //'eq' keyword for expressions.
    const KEYWORD_EQUAL = 'eq';

    //'false' keyword for expressions.
    const KEYWORD_FALSE = 'false';

    //'gt' keyword for expressions.
    const KEYWORD_GREATERTHAN = 'gt';

    //'ge' keyword for expressions.
    const KEYWORD_GREATERTHAN_OR_EQUAL = 'ge';

    //'lt' keyword for expressions.
    const KEYWORD_LESSTHAN = 'lt';

    //'le' keyword for expressions.
    const KEYWORD_LESSTHAN_OR_EQUAL = 'le';

    //'mod' keyword for expressions.
    const KEYWORD_MODULO = 'mod';

    //'mul' keyword for expressions.
    const KEYWORD_MULTIPLY = 'mul';

    //'not' keyword for expressions.
    const KEYWORD_NOT = 'not';

    //'ne' keyword for expressions.
    const KEYWORD_NOT_EQUAL = 'ne';

    //'null' keyword for expressions.
    const KEYWORD_NULL = 'null';

    //'or' keyword for expressions.
    const KEYWORD_OR = 'or';

    //'sub' keyword for expressions.
    const KEYWORD_SUB = 'sub';

    //'true' keyword for expressions.
    const KEYWORD_TRUE = 'true';
   

    //Constants for Query options

    //HTTP query parameter value for selecting response format.
    const HTTPQUERY_STRING_FORMAT = '$format';

    //HTTP query parameter value for filtering.
    const HTTPQUERY_STRING_FILTER = '$filter';

    //HTTP query parameter value for ordering.
    const HTTPQUERY_STRING_ORDERBY = '$orderby';

    //HTTP query parameter value for expand.
    const HTTPQUERY_STRING_EXPAND = '$expand';

    //HTTP query parameter value for projection.
    const HTTPQUERY_STRING_SELECT = '$select';

    //HTTP query parameter value for skipping results based on paging.
    const HTTPQUERY_STRING_SKIPTOKEN = '$skiptoken';

    //HTTP query parameter value for limiting the number of elements.
    const HTTPQUERY_STRING_TOP = '$top';

    //HTTP query parameter value for skipping elements.
    const HTTPQUERY_STRING_SKIP = '$skip';

    //HTTP query parameter value for counting query result set
    const HTTPQUERY_STRING_INLINECOUNT = '$inlinecount';

    //A segment name in a URI that indicates metadata is being requested.
    const URI_METADATA_SEGMENT = '$metadata';

    //A segment name in a URI that indicates metadata is being requested.
    const URI_BATCH_SEGMENT = '$batch';

    //A segment name in a URI that indicates a plain primitive value 
    //is being requested.
    const URI_VALUE_SEGMENT = '$value';

    //A segment name in a URI that indicates that this is a link operation.
    const URI_LINK_SEGMENT = '$links';

    //HTTP query parameter value for counting query result set
    const URI_COUNT_SEGMENT = '$count';

    //A const value for the query parameter $inlinecount to set 
    //counting mode to inline
    const URI_ROWCOUNT_ALLOPTION = 'allpages';

    //A const value for the query parameter $inlinecount to set counting mode to none
    const URI_ROWCOUNT_OFFOPTION = 'none';
   

    //Constants for Expression Parsing

    //'binary' constant prefixed to binary literals.
    const LITERAL_PREFIX_BINARY   = 'binary';

    //'datetime' constant prefixed to datetime literals.
    const LITERAL_PREFIX_DATETIME = 'datetime';

    //'guid' constant prefixed to guid literals.
    const LITERAL_PREFIX_GUID     = 'guid';

    //'X': Prefix to binary type representation.
    const XML_BINARY_PREFIX       = 'X';

    //'M': Suffix for decimal type's representation
    const XML_DECIMAL_LITERAL_SUFFIX = 'M';

    //'L': Suffix for long (int64) type's representation
    const XML_INT64_LITERAL_SUFFIX  = 'L';

    //'f': Suffix for float (single) type's representation
    const XML_SINGLE_LITERAL_SUFFIX  = 'f';

    //'D': Suffix for double (Real) type's representation
    const XML_DOUBLE_LITERAL_SUFFIX  = 'D';
    
    const STRFUN_COMPARE = 'strcmp';
    const STRFUN_ENDSWITH = 'endswith';    
    const STRFUN_INDEXOF = 'indexof';
    const STRFUN_REPLACE = 'replace';
    const STRFUN_STARTSWITH = 'startswith';
    const STRFUN_TOLOWER = 'tolower';
    const STRFUN_TOUPPER = 'toupper';
    const STRFUN_TRIM = 'trim';
    const STRFUN_SUBSTRING = 'substring';
    const STRFUN_SUBSTRINGOF = 'substringof';
    const STRFUN_CONCAT = 'concat';
    const STRFUN_LENGTH = 'length';
    const GUIDFUN_EQUAL = 'guidEqual';
    const DATETIME_COMPARE = 'dateTimeCmp';
    const DATETIME_YEAR = 'year';
    const DATETIME_MONTH = 'month';
    const DATETIME_DAY = 'day';
    const DATETIME_HOUR = 'hour';
    const DATETIME_MINUTE = 'minute';
    const DATETIME_SECOND = 'second';
    const MATHFUN_ROUND = 'round';
    const MATHFUN_CEILING = 'ceiling';
    const MATHFUN_FLOOR = 'floor';
    const BINFUL_EQUAL = 'binaryEqual';

    // Headers for HTTPRequest
    // We need to use these constant with $_SERVER to get the value of the headers 
    const HTTPREQUEST_HEADER_PROTOCOL        = 'SERVER_PROTOCOL';
    const HTTPREQUEST_HEADER_PROTOCOL_HTTP     = 'http';
    const HTTPREQUEST_HEADER_PROTOCOL_HTTPS    = 'https';
    const HTTPREQUEST_HEADER_HOST            = 'HTTP_HOST';
    const HTTPREQUEST_HEADER_PORT            = 'SERVER_PORT';
    const HTTPREQUEST_HEADER_URI            = 'REQUEST_URI';    
    const HTTPREQUEST_HEADER_QUERY_STRING     = 'QUERY_STRING';
    const HTTPREQUEST_HEADER_METHOD            = 'REQUEST_METHOD';
    // const HttpRequestHeaderServerName         = 'SERVER_NAME';
    // const HttpRequestHeaderServerSoftware     = 'SERVER_SOFTWARE';
    const HTTPREQUEST_HEADER_ACCEPT            = 'HTTP_ACCEPT';
    const HTTPREQUEST_HEADER_ACCEPT_CHARSET    = 'HTTP_ACCEPT_CHARSET';
    const HTTPREQUEST_HEADER_CONTENT_LENGTH    = 'CONTENT_LENGTH';
    const HTTPREQUEST_HEADER_CONTENT_TYPE    = 'CONTENT_TYPE';
    const HTTPREQUEST_HEADER_USER_AGENT        = 'HTTP_USER_AGENT';
    const HTTPREQUEST_HEADER_IFMATCH        = 'HTTP_IF_MATCH';
    const HTTPREQUEST_HEADER_IFMODIFIED        = 'HTTP_IF_MODIFIED_SINCE';
    const HTTPREQUEST_HEADER_IFNONE            = 'HTTP_IF_NONE_MATCH';    
    const HTTPREQUEST_HEADER_IFUNMODIFIED    = 'HTTP_IF_UNMODIFIED_SINCE';

    // Headers for HTTPResponse
    // We need to use these constant with $_SERVER to get the value of the headers 
    //const HTTPRESPONSE_HEADER_CONTENTLENGTH    = 'CONTENT_LENGTH';
    //const HTTPRESPONSE_HEADER_CONTENTTYPE    = 'CONTENT_TYPE';    
    //const HTTPRESPONSE_HEADER_CACHECONTROL    = 'HTTP_CACHE_CONTROL';
    //const HTTPRESPONSE_HEADER_IFNONE        = 'HTTP_IF_NONE_MATCH';
        
    // Headers for HTTPResponse
    // We need to use these string with header() for setting-up these headers
    const HTTPRESPONSE_HEADER_CONTENTLENGTH        = 'Content-Length';
    const HTTPRESPONSE_HEADER_CONTENTTYPE        = 'Content-Type';
    const HTTPRESPONSE_HEADER_CACHECONTROL        = 'Cache-Control';
    const HTTPRESPONSE_HEADER_ETAG                = 'ETag';
    const HTTPRESPONSE_HEADER_LASTMODIFIED        = 'Last-Modified';
    const HTTPRESPONSE_HEADER_LOCATION            = 'Location';
    const HTTPRESPONSE_HEADER_STATUS            = 'Status';
    const HTTPRESPONSE_HEADER_STATUS_CODE        = 'StatusCode';
    const HTTPRESPONSE_HEADER_STATUS_DESC        = 'StatusDesc';
    
    const HTTPRESPONSE_HEADER_CACHECONTROL_NOCACHE = 'no-cache';

    const ODATASERVICEVERSION                    = 'HTTP_DATASERVICEVERSION';
    const ODATAMAXSERVICEVERSION                = 'HTTP_MAXDATASERVICEVERSION';
}
?>