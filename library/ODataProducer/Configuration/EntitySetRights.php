<?php
/** 
 * Enumeration to describe the rights granded on a entity set (resource set)
 * 
 * PHP version 5.3
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Configuration
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
namespace ODataProducer\Configuration;
/**
 * Enumeration to describe the rights granded on a entity set (resource set)
 * 
 * @category  ODataPHPProd
 * @package   ODataProducer_Configuration
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class EntitySetRights
{
    /**
     * Specifies no rights on this entity set
     */
    const NONE = 0;

    /**
     * Specifies the right to read one entity instance per request
     */
    const READ_SINGLE = 1;

    /**
     * Specifies the right to read multiple entity instances per request
     */
    const READ_MULTIPLE = 2;

    /**
     * Specifies the right to append (add) new entity instance to the entity set
     */
    const WRITE_APPEND = 4;

    /**
     * Specifies the right to update existing entity instance in the entity set
     */
    const WRITE_REPLACE = 8;

    /**     
     * Specifies the right to delete existing entity instance in the entity set
     */
    const WRITE_DELETE = 16;

    /**
     * Specifies the right to update existing entity instance in the entity set
     */
    const WRITE_MERGE = 32;

    /**
     * Specifies the right to read single or multiple entity instances in a 
     * single request
     * READ_SINGLE | READ_MULTIPLE
     */
    const READ_ALL = 3;

    /**
     * Specifies the right to append, delete or update entity instances in the 
     * entity set
     * WRITE_APPEND | WRITE_DELETE | WRITE_REPLACE | WRITE_MERGE
     */
    const WRITE_ALL = 60;

    /**
     * Specifies all rights to the entity set
     * READ_ALL | WRITE_ALL
     */
    const ALL = 63;
}
?>