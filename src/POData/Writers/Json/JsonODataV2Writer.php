<?php

declare(strict_types=1);

namespace POData\Writers\Json;

use Exception;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\Version;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;

/**
 * Class JsonODataV2Writer is a writer for the json format in OData V2 AKA JSON Verbose.
 */
class JsonODataV2Writer extends JsonODataV1Writer
{
    //The key difference between 1 and 2 is that in 2 collection results
    //are wrapped in a "result" array.  this is to allow a place for collection metadata to be placed

    //IE {d : [ item1, item2, item3] }
    //is now { d : { results :[item1, item2, item3], meta1 : x, meta2 : y }
    //So we override the collection methods to shove this stuff in there

    protected $dataArrayName = ODataConstants::JSON_RESULT_NAME;

    protected $rowCountName = ODataConstants::JSON_ROWCOUNT_STRING;

    protected $nextLinkName = ODataConstants::JSON_NEXT_STRING;

    /**
     * Determines if the given writer is capable of writing the response or not.
     *
     * @param Version $responseVersion the OData version of the response
     * @param string  $contentType     the Content Type of the response
     *
     * @return bool true if the writer can handle the response, false otherwise
     */
    public function canHandle(Version $responseVersion, $contentType)
    {
        $parts = explode(';', $contentType);

        //special case, in v3 verbose is the v2 writer
        if ($responseVersion == Version::v3()) {
            return in_array(MimeTypes::MIME_APPLICATION_JSON, $parts) && in_array('odata=verbose', $parts);
        }

        if ($responseVersion != Version::v2()) {
            return false;
        }

        return in_array(MimeTypes::MIME_APPLICATION_JSON, $parts);
    }

    /**
     * Write the given OData model in a specific response format.
     *
     * @param ODataURL|ODataURLCollection|ODataPropertyContent|ODataFeed|ODataEntry $model Object of requested content
     *
     * @throws Exception
     * @return JsonODataV2Writer
     */
    public function write($model)
    {
        // { "d" :
        $this->writer
            ->startObjectScope()
            ->writeName('d')
            ->startObjectScope();

        if ($model instanceof ODataURL) {
            $this->writeUrl($model);
        } elseif ($model instanceof ODataURLCollection) {
            $this->writeUrlCollection($model);
        } elseif ($model instanceof ODataPropertyContent) {
            $this->writeProperties($model);
        } elseif ($model instanceof ODataFeed) {
            // Json Format V2:
            // "results":
            $this->writeRowCount($model->rowCount);
            $this->writeNextPageLink($model->nextPageLink);
            $this->writer
                ->writeName($this->dataArrayName)
                ->startArrayScope();
            $this->writeFeed($model);
            $this->writer->endScope();
        } elseif ($model instanceof ODataEntry) {
            $this->writeEntry($model);
        }

        $this->writer->endScope();
        $this->writer->endScope();

        return $this;
    }

    /**
     * begin write OData links.
     *
     * @param ODataURLCollection $urls url collection to write
     *
     * @throws Exception
     * @return JsonODataV2Writer
     */
    public function writeUrlCollection(ODataURLCollection $urls)
    {
        $this->writeRowCount($urls->count);
        $this->writeNextPageLink($urls->nextPageLink);

        // Json Format V2:
        // "results":
        $this->writer
            ->writeName($this->dataArrayName)
            ->startArrayScope();

        parent::writeUrlCollection($urls);

        $this->writer->endScope();

        return $this;
    }

    /**
     * Writes the row count.
     *
     * @param int $count Row count value
     *
     * @throws Exception
     * @return JsonODataV2Writer
     */
    protected function writeRowCount($count)
    {
        if ($count != null) {
            $this->writer->writeName($this->rowCountName);
            $this->writer->writeValue($count);
        }

        return $this;
    }

    /**
     * Writes the next page link.
     *
     * @param ODataLink|null $nextPageLinkUri Uri for next page link
     *
     * @throws Exception
     * @return JsonODataV2Writer
     */
    protected function writeNextPageLink(ODataLink $nextPageLinkUri = null)
    {
        // "__next" : uri
        if (null !== $nextPageLinkUri) {
            $this->writer
                ->writeName($this->nextLinkName)
                ->writeValue($nextPageLinkUri->getUrl());
        }

        return $this;
    }

    /**
     * Writes the expanded link.
     *
     * @param  ODataLink $link
     * @throws Exception
     */
    protected function writeExpandedLink(ODataLink $link)
    {
        //Difference from v1 is that expanded collection have a result: wrapper to allow for metadata to exist
        $this->writer->startObjectScope();

        if ($link->isCollection()) {
            $this->writer
                ->writeName($this->dataArrayName)
                ->startArrayScope();
            $this->writeFeed(/* @scrutinizer ignore-type */ $link->expandedResult);
            $this->writer->endScope();
        } else {
            $this->writeEntry(/* @scrutinizer ignore-type */ $link->expandedResult);
        }

        $this->writer->endScope();
    }
}
