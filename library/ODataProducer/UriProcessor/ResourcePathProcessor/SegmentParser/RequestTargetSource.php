<?php
/** 
 * An enumeration to describe the source of result for the client request.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
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
namespace ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser;
/**
 * Client request result source enumerations.
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_UriProcessor_ResourcePathProcessor_SegmentParser
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class RequestTargetSource
{
    /**
     * The source of data has not been determined yet or
     * The source of data is intrinsic to the sytem i.e Service Document, 
     * Metadata or batch requests.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::METADATA
     *  RequestTargetKind::SERVICE_DOCUMENT
     *  RequestTargetKind::BATCH
     */
    const NONE = 1;

    /**
     * An entity set provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::RESOURCE
     *  RequestTargetKind::LINK
     */
    const ENTITY_SET = 2;

    /**
     * A service operation provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::VOID_SERVICE_OPERATION
     */
    const  SERVICE_OPERATION = 3;
    
    /**
     * A property of an entity or a complex object provides the data.
     * The associated RequestTargetKind enum values are:
     *  RequestTargetKind::PRIMITIVE
     *  RequestTargetKind::PRIMITIVE_VALUE
     *  RequestTargetKind::COMPLEX_OBJECT
     *  RequestTargetKind::MEDIA_RESOURCE
     *  RequestTargetKind::BAG
     */
    const PROPERTY = 4;
}
?>