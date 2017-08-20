<?php

namespace POData\ObjectModel;

use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\Providers\Query\IQueryProvider;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

class CynicDeserialiser
{
    /**
     * @var IMetadataProvider
     */
    private $metaProvider;

    /**
     * @var ProvidersWrapper
     */
    private $wrapper;

    /**
     * @var ModelDeserialiser
     */
    private $cereal;

    public function __construct(IMetadataProvider $meta, ProvidersWrapper $wrapper)
    {
        $this->metaProvider = $meta;
        $this->wrapper = $wrapper;
        $this->cereal = new ModelDeserialiser();
    }

    /**
     * @param ODataEntry|ODataFeed $payload
     */
    public function processPayload(ODataEntry &$payload)
    {
        assert($this->checkEntryOK($payload));
        list($sourceSet, $source) = $this->processEntryContent($payload);
        $numLinks = count($payload->links);
        for ($i = 0; $i < $numLinks; $i++) {
            $this->processLink($payload->links[$i], $sourceSet, $source);
        }
    }

    protected function checkEntryOK(ODataEntry $payload)
    {
        // check links
        foreach ($payload->links as $link) {
            $hasUrl = isset($link->url);
            $hasExpanded = isset($link->expandedResult);
            if ($hasUrl) {
                if (!is_string($link->url)) {
                    $msg = 'Url must be either string or null';
                    throw new \InvalidArgumentException($msg);
                }
            }
            if ($hasExpanded) {
                $isGood = $link->expandedResult instanceof ODataEntry || $link->expandedResult instanceof ODataFeed;
                if (!$isGood) {
                    $msg = 'Expanded result must null, or be instance of ODataEntry or ODataFeed';
                    throw new \InvalidArgumentException($msg);
                }
            }
            $isEntry = $link->expandedResult instanceof ODataEntry;

            if ($hasExpanded) {
                if ($isEntry) {
                    $this->checkEntryOK($link->expandedResult);
                } else {
                    foreach ($link->expandedResult->entries as $expanded) {
                        $this->checkEntryOK($expanded);
                    }
                }
            }
        }

        $set = $this->getMetaProvider()->resolveResourceSet($payload->resourceSetName);
        if (null === $set) {
            $msg = 'Specified resource set could not be resolved';
            throw new \InvalidArgumentException($msg);
        }
        return true;
    }

    protected function processEntryContent(ODataEntry &$content)
    {
        assert(null === $content->id || is_string($content->id), 'Entry id must be null or string');

        $isCreate = null === $content->id;
        $set = $this->getMetaProvider()->resolveResourceSet($content->resourceSetName);
        $type = $set->getResourceType();
        $properties = $this->getDeserialiser()->bulkDeserialise($type, $content);

        if ($isCreate) {
            $result = $this->getWrapper()->createResourceforResourceSet($set, null, $properties);
            $key = $this->generateKeyDescriptor($type, $result);
        } else {
            $key = $this->generateKeyDescriptor($type, $content->propertyContent);
            $source = $this->getWrapper()->getResourceFromResourceSet($set, $key);
            $result = $this->getWrapper()->updateResource($set, $source, $key, $properties);
        }

        $content->id = $key;
        return [$set, $result];
    }

    protected function processFeedContent(ODataFeed &$content)
    {
    }


    protected function getMetaProvider()
    {
        return $this->metaProvider;
    }

    protected function getWrapper()
    {
        return $this->wrapper;
    }

    protected function getDeserialiser()
    {
        return $this->cereal;
    }

    /**
     * @param ResourceEntityType $type
     * @param ODataPropertyContent|object $result
     * @return null
     */
    protected function generateKeyDescriptor(ResourceEntityType $type, $result)
    {
        $isOData = $result instanceof ODataPropertyContent;
        $keyProp = $type->getKeyProperties();
        $keyPredicate = '';
        foreach ($keyProp as $prop) {
            $iType = $prop->getInstanceType();
            assert($iType instanceof IType, get_class($iType));
            $keyName = $prop->getName();
            $rawKey = $isOData ? $result->properties[$keyName]->value : $result->$keyName;
            $keyVal = $iType->convertToOData($rawKey);
            assert(isset($keyVal), 'Key property ' . $keyName . ' must not be null');
            $keyPredicate .= $keyName . '=' . $keyVal . ', ';
        }
        $keyPredicate[strlen($keyPredicate) - 2] = ' ';
        $keyPredicate = trim($keyPredicate);
        $keyDesc = null;
        KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDesc);
        $keyDesc->validate($keyPredicate, $type);
        // this is deliberate - ODataEntry/Feed has the structure we need for processing, and we're inserting
        // keyDescriptor objects in id fields to indicate the given record has been processed
        return $keyDesc;
    }

    protected function processLink(ODataLink &$link, ResourceSet $sourceSet, $source)
    {
        $hasUrl = isset($link->url);
        $hasPayload = isset($link->expandedResult);

        // if nothing to hook up, bail out now
        if (!$hasUrl && !$hasPayload) {
            return;
        }

        if ($hasUrl) {
            $rawPredicate = explode('(', $link->url);
            $setName = $rawPredicate[0];
            $rawPredicate = trim($rawPredicate[count($rawPredicate) - 1], ')');
            $targSet = $this->getMetaProvider()->resolveResourceSet($setName);
            assert(null !== $targSet, get_class($targSet));
            $type = $targSet->getResourceType();
        } else {
            $type = $this->getMetaProvider()->resolveResourceType($link->expandedResult->type->term);
        }
        $propName = $link->title;


        if ($hasUrl) {
            $keyDesc = null;
            KeyDescriptor::tryParseKeysFromKeyPredicate($rawPredicate, $keyDesc);
            $keyDesc->validate($rawPredicate, $type);
            assert(null !== $keyDesc, 'Key description must not be null');
        }

        // hooking up to existing resource
        if ($hasUrl && !$hasPayload) {
            $target = $this->getWrapper()->getResourceFromResourceSet($targSet, $keyDesc);
            $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
            $link->url = $keyDesc;
            return;
        }
        // creating new resource
        if (!$hasUrl && $hasPayload) {
            list($targSet, $target) = $this->processEntryContent($link->expandedResult);
            $key = $this->generateKeyDescriptor($type, $link->expandedResult->propertyContent);
            $link->url = $key;
            $link->expandedResult->id = $key;
            $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
            return;
        }
        // updating existing resource and connecting to it
        list($targSet, $target) = $this->processEntryContent($link->expandedResult);
        $link->url = $keyDesc;
        $link->expandedResult->id = $keyDesc;
        $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
        return;
    }
}
