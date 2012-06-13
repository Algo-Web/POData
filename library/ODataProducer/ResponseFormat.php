<?php
/** 
 * Enum of content formats that data service supports.
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
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
namespace ODataProducer;
/**
 * Enum of content formats that data service supports.
 * 
 * @category  ODataPHPProd
 * @package   ODataPHPProd
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ResponseFormat
{
    /**
     * The application/atom+xml format.
     * Possible resources that can be serialized using this format
     *  (1) Entry
     *      e.g. /Customer('ALFKI')
     */    
    const ATOM = 1;

    /**
     * The binary format.
     * Possible resources that can be serialized using this format
     *   (1) A primitive binary property value
     *       e.g. /Employees(1)/Photo/$value
     *   (2) Stream associated with Media Link entry
     *       e.g. /Albums('fmaily')/Photos('DS187')/$value
     *   (3) Stream associated with named stream property
     *       e.g. /Employees(1)/ThimNail_48X48/$value
     */
    const BINARY = 2;

    /**
     * The application/json format.
     * Possible resources that can be serialized using this format
     *   (1) Entry
     *       e.g. /Customer('ALFKI')
     *   (2) Primitive, complex or bag property
     *       e.g. /Customer('ALFKI')/CompanyName
     *            /Customer('ALFKI')/Address
     *            /Customer('ALFKI')/EMails
     *   (3) Service document
     *       e.g. NorthWindServcie.svc?$format=json
     */
    const JSON = 3;

    /**
     * An XML document for CSDL
     * Possible resources that can be serialized using this format
     *   (1) Metadata
     *       e.g. NorthWindServcie.svc/$metadata
     */
    const METADATA_DOCUMENT = 4;

    /**
     * An XML document for primitive complex and bag types
     *   e.g. /Customer('ALFKI')/CompanyName
     *       /Customer('ALFKI')/Address
     *       /Customer('ALFKI')/EMails 
     * 
     */
    const PLAIN_XML = 5;

    /**
     * A text-based format.
     * Possible resources that can be serialized using this format
     *  (1) Primitive value
     *      e.g. /Customer('ALFKI')/CompanyName/$value
     *           /Costomers/$count
     * 
     */
    const TEXT = 6;

    /**
     * An unsupported format
     */
    const UNSUPPORTED = 7;
}
?>