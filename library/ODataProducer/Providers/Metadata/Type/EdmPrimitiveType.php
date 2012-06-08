<?php
/** 
 * Enumeration for EDM primitive types.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Providers\Metadata\Type;
/**
 * Enumeration for EDM primitive types.
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Providers_Metadata_Type
 * @author    Anu T Chandy <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
 */
class EdmPrimitiveType
{
    const BINARY   = TypeCode::BINARY;
    const BOOLEAN  = TypeCode::BOOLEAN;
    const BYTE     = TypeCode::BYTE;
    const DATETIME = TypeCode::DATETIME;
    const DECIMAL  = TypeCode::DECIMAL;
    const DOUBLE   = TypeCode::DOUBLE;
    const GUID     = TypeCode::GUID;
    const INT16    = TypeCode::INT16;
    const INT32    = TypeCode::INT32;
    const INT64    = TypeCode::INT64;
    const SBYTE    = TypeCode::SBYTE;
    const SINGLE   = TypeCode::SINGLE;
    const STRING   = TypeCode::STRING;
}
?>