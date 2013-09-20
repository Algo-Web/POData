<?php

namespace POData\Writers;

use POData\Common\ODataException;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataProperty;


/**
 * Class IODataWriter
 * @package POData\Writers\Common
 */
interface IODataWriter
{

	/**
	 * Create odata object model from the request description and transform it to required content type form
	 *
	 *
	 * @param  ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content.
	 *
	 * @return IODataWriter
	 */
	public function write($model);



    /**
     * Get the output as string
     *  
     * @return string Result in requested format i.e. Atom or JSON.
     */
    public function getOutput();
}