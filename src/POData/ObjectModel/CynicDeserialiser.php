<?php

namespace POData\ObjectModel;

use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
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
    public function processPayload(ODataEntry $payload)
    {
        assert($this->checkEntryOK($payload));
        $this->processEntryContent($payload);
    }

    protected function checkEntryOK(ODataEntry $payload)
    {
        // check links
        foreach ($payload->links as $link) {
            $hasUrl = isset($link->url);
            $hasExpanded = isset($link->expandedResult);
            if (!$hasUrl && !$hasExpanded) {
                $msg = 'ODataEntry must have at least one of supplied url and/or expanded result';
                throw new \InvalidArgumentException($msg);
            }
            if ($hasExpanded) {
                $isGood = $link->expandedResult instanceof ODataEntry || $link->expandedResult instanceof ODataFeed;
                if (!$isGood) {
                    $msg = 'Expanded result must be instance of ODataEntry or ODataFeed';
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
        return;
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
}
