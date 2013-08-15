<?php

namespace ODataProducer\Writers\Common;

/**
 * Class ODataWriterFactory
 * @package ODataProducer\Writers\Common
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