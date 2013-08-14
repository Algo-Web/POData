<?php
/**
 * Contains ODataWriterFactory class for getting correct writer which requested.
 * 
*/
namespace ODataProducer\Writers\Common;
/** 
 * Factory for OData Writers for different content types.
 *
 * @category  ODataPHPProd
 * @package   ODataProducer
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class ODataWriterFactory
{
    static $WRITERS = array();
    
    /** 
     * To instanciate correct writer from content type.
     *  
     * @param string $contentType  Writer Type 
     * (Atom, json etc..) which implements IOdataWriter interface.
     * @param array  $writerParams Parameters to writer
     * 
     * @return Returns an instance of writer specialized for 
     */
    public static function getWriter ($contentType, $writerParams) 
    {
        if (!array_key_exists(WRITERS, $contentType)) {
            if ($contetType == 'atom') {
                self::$WRITERS[$contetType] = new AtomODataWriter($writerParams);
            } else if ($contetType == 'json') {
                self::$WRITERS[$contetType] = new JSONODataWriter($writerParams);
            } else {
                ODataException::CreateInternalServerError('unsupported type');
            }
            return $self::$writers[$contentType];
        }
    }
}
?>