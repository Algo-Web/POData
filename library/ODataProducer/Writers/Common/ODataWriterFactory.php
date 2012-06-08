<?php
/**
 * Contains ODataWriterFactory class for getting correct writer which requested.
 * 
 * PHP version 5.3
 * 
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Common
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   SVN: 1.0
 * @link      http://odataphpproducer.codeplex.com
 * 
 */
namespace ODataProducer\Writers\Common;
/** 
 * Factory for OData Writers for different content types.
 *
 * @category  ODataProducer
 * @package   ODataProducer_Writers_Common
 * @author    Yash K. Kothari <odataphpproducer_alias@microsoft.com>
 * @copyright 2011 Microsoft Corp. (http://www.microsoft.com)
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   Release: 1.0
 * @link      http://odataphpproducer.codeplex.com
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