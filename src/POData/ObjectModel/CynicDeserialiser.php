<?php

declare(strict_types=1);

namespace POData\ObjectModel;

use Exception;
use InvalidArgumentException;
use POData\Common\InvalidOperationException;
use POData\Common\ODataException;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Metadata\Type\IType;
use POData\Providers\ProvidersWrapper;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ReflectionClass;
use ReflectionException;

/**
 * Class CynicDeserialiser.
 * @package POData\ObjectModel
 */
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

    /**
     * CynicDeserialiser constructor.
     * @param IMetadataProvider $meta
     * @param ProvidersWrapper  $wrapper
     */
    public function __construct(IMetadataProvider $meta, ProvidersWrapper $wrapper)
    {
        $this->metaProvider = $meta;
        $this->wrapper      = $wrapper;
        $this->cereal       = new ModelDeserialiser();
    }

    /**
     * @param  ODataEntry                $payload
     * @throws ODataException
     * @throws ReflectionException
     * @throws InvalidOperationException
     * @return mixed
     */
    public function processPayload(ODataEntry &$payload)
    {
        $entryOk = $this->isEntryOK($payload);
        if (!$entryOk) {
            throw new InvalidOperationException('Payload not OK');
        }
        list($sourceSet, $source) = $this->processEntryContent($payload);
        if (!$sourceSet instanceof ResourceSet) {
            throw new InvalidOperationException('$sourceSet not instanceof ResourceSet');
        }
        $numLinks = count($payload->links);
        for ($i = 0; $i < $numLinks; $i++) {
            $this->processLink($payload->links[$i], $sourceSet, $source);
        }
        if (!$this->isEntryProcessed($payload)) {
            throw new InvalidOperationException('Payload not processed');
        }
        return $source;
    }

    /**
     * Check if supplied ODataEntry is well-formed.
     *
     * @param  ODataEntry $payload
     * @return bool
     */
    protected function isEntryOK(ODataEntry $payload)
    {
        // check links
        foreach ($payload->links as $link) {
            /** @var ODataLink $link */
            $hasExpanded = null !== $link->getExpandedResult();

            $isEntry = ($link->getExpandedResult() ? $link->getExpandedResult()->getData() : null)
                       instanceof ODataEntry;

            if ($hasExpanded) {
                if ($isEntry) {
                    $this->isEntryOK($link->getExpandedResult()->getEntry());
                } else {
                    foreach ($link->getExpandedResult()->getFeed()->getEntries() as $expanded) {
                        $this->isEntryOK($expanded);
                    }
                }
            }
        }

        $set = $this->getMetaProvider()->resolveResourceSet($payload->resourceSetName);
        if (null === $set) {
            $msg = 'Specified resource set could not be resolved';
            throw new InvalidArgumentException($msg);
        }
        return true;
    }

    /**
     * @return IMetadataProvider
     */
    protected function getMetaProvider()
    {
        return $this->metaProvider;
    }

    /**
     * @param  ODataEntry                $content
     * @throws ODataException
     * @throws ReflectionException
     * @throws Exception
     * @throws InvalidOperationException
     * @return array
     */
    protected function processEntryContent(ODataEntry &$content)
    {
        assert(null === $content->id || is_string($content->id), 'Entry id must be null or string');

        $isCreate = null === $content->id || empty($content->id);
        $set      = $this->getMetaProvider()->resolveResourceSet($content->resourceSetName);
        assert($set instanceof ResourceSet, get_class($set));
        $type       = $set->getResourceType();
        $properties = $this->getDeserialiser()->bulkDeserialise($type, $content);
        $properties = (object)$properties;

        if ($isCreate) {
            $result = $this->getWrapper()->createResourceforResourceSet($set, null, $properties);
            assert(isset($result), get_class($result));
            $key     = $this->generateKeyDescriptor($type, $result);
            $keyProp = $key->getODataProperties();
            foreach ($keyProp as $keyName => $payload) {
                $content->propertyContent[$keyName] = $payload;
            }
        } else {
            $key = $this->generateKeyDescriptor($type, $content->propertyContent, $content->id);
            assert($key instanceof KeyDescriptor, get_class($key));
            $source = $this->getWrapper()->getResourceFromResourceSet($set, $key);
            assert(isset($source), get_class($source));
            $result = $this->getWrapper()->updateResource($set, $source, $key, $properties);
        }
        if (!$key instanceof KeyDescriptor) {
            throw new InvalidOperationException(get_class($key));
        }
        $content->id = $key;

        $numLinks = count($content->links);
        for ($i = 0; $i < $numLinks; $i++) {
            $this->processLink($content->links[$i], $set, $result);
        }

        return [$set, $result];
    }

    /**
     * @return ModelDeserialiser
     */
    protected function getDeserialiser()
    {
        return $this->cereal;
    }

    /**
     * @return ProvidersWrapper
     */
    protected function getWrapper()
    {
        return $this->wrapper;
    }

    /**
     * @param  ResourceEntityType          $type
     * @param  ODataPropertyContent|object $result
     * @param  string|null                 $id
     * @throws ReflectionException
     * @throws ODataException
     * @return null|KeyDescriptor
     */
    protected function generateKeyDescriptor(ResourceEntityType $type, $result, $id = null)
    {
        $isOData = $result instanceof ODataPropertyContent;
        $keyProp = $type->getKeyProperties();
        if (null === $id) {
            $keyPredicate = '';
            foreach ($keyProp as $prop) {
                $iType = $prop->getInstanceType();
                assert($iType instanceof IType, get_class($iType));
                $keyName = $prop->getName();
                $rawKey  = $isOData ? $result[$keyName]->getValue() : $result->{$keyName};
                $keyVal  = $iType->convertToOData(strval($rawKey));
                assert(isset($keyVal), 'Key property ' . $keyName . ' must not be null');
                $keyPredicate .= $keyName . '=' . $keyVal . ', ';
            }
            $keyPredicate[strlen($keyPredicate) - 2] = ' ';
        } else {
            $idBits       = explode('/', $id);
            $keyRaw       = $idBits[count($idBits) - 1];
            $rawBits      = explode('(', $keyRaw, 2);
            $rawBits      = explode(')', $rawBits[count($rawBits) - 1]);
            $keyPredicate = $rawBits[0];
        }
        $keyPredicate = trim($keyPredicate);
        /** @var KeyDescriptor|null $keyDesc */
        $keyDesc  = null;
        $isParsed = KeyDescriptor::tryParseKeysFromKeyPredicate($keyPredicate, $keyDesc);
        assert(true === $isParsed, 'Key descriptor not successfully parsed');
        $keyDesc->validate($keyPredicate, $type);
        // this is deliberate - ODataEntry/Feed has the structure we need for processing, and we're inserting
        // keyDescriptor objects in id fields to indicate the given record has been processed
        return $keyDesc;
    }

    /**
     * @param ODataLink   $link
     * @param ResourceSet $sourceSet
     * @param $source
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     */
    protected function processLink(ODataLink &$link, ResourceSet $sourceSet, $source)
    {
        $hasUrl     = null !== $link->getUrl();
        $result     = $link->getExpandedResult() ? $link->getExpandedResult()->getData() : null;
        $hasPayload = isset($result);
        assert(
            null == $result || $result instanceof ODataEntry || $result instanceof ODataFeed,
            (null === $result ? 'null' : get_class($result))
        );
        $isFeed = ($link->getExpandedResult() ? $link->getExpandedResult()->getData() : null) instanceof ODataFeed;

        // if nothing to hook up, bail out now
        if (!$hasUrl && !$hasPayload) {
            return;
        }

        if ($isFeed) {
            $this->processLinkFeed($link, $sourceSet, $source, $hasUrl, $hasPayload);
        } else {
            $this->processLinkSingleton($link, $sourceSet, $source, $hasUrl, $hasPayload);
        }
        return;
    }

    /**
     * @param ODataLink   $link
     * @param ResourceSet $sourceSet
     * @param $source
     * @param  bool                      $hasUrl
     * @param  bool                      $hasPayload
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     * @throws Exception
     */
    protected function processLinkFeed(ODataLink &$link, ResourceSet $sourceSet, $source, $hasUrl, $hasPayload)
    {
        assert(
            $link->getExpandedResult()->getData() instanceof ODataFeed,
            get_class($link->getExpandedResult()->getData())
        );
        $propName = $link->getTitle();

        // if entries is empty, bail out - nothing to do
        $numEntries = count($link->getExpandedResult()->getFeed()->getEntries());
        if (0 === $numEntries) {
            return;
        }
        // check that each entry is of consistent resource set after checking it hasn't been processed
        $first = $link->getExpandedResult()->getFeed()->getEntries()[0]->resourceSetName;
        if ($link->getExpandedResult()->getFeed()->getEntries()[0]->id instanceof KeyDescriptor) {
            return;
        }
        for ($i = 1; $i < $numEntries; $i++) {
            if ($first !== $link->getExpandedResult()->getFeed()->getEntries()[$i]->resourceSetName) {
                $msg = 'All entries in given feed must have same resource set';
                throw new InvalidArgumentException($msg);
            }
        }

        $targSet = $this->getMetaProvider()->resolveResourceSet($first);
        assert($targSet instanceof ResourceSet);
        $targType = $targSet->getResourceType();
        assert($targType instanceof ResourceEntityType);
        $instanceType = $targType->getInstanceType();
        assert($instanceType instanceof ReflectionClass);
        $targObj = $instanceType->newInstanceArgs();

        // assemble payload
        $data = [];
        $keys = [];
        for ($i = 0; $i < $numEntries; $i++) {
            $data[] = $this->getDeserialiser()->bulkDeserialise(
                $targType,
                $link->getExpandedResult()->getFeed()->getEntries()[$i]
            );
            $keys[] = $hasUrl ? $this->generateKeyDescriptor(
                $targType,
                $link->getExpandedResult()->getFeed()->getEntries()[$i]->propertyContent
            ) : null;
        }

        // creation
        if (!$hasUrl && $hasPayload) {
            $bulkResult = $this->getWrapper()->createBulkResourceforResourceSet($targSet, $data);
            assert(is_array($bulkResult));
            for ($i = 0; $i < $numEntries; $i++) {
                $targEntityInstance = $bulkResult[$i];
                $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $targEntityInstance, $propName);
                $key                                                        = $this->generateKeyDescriptor($targType, $targEntityInstance);
                $link->getExpandedResult()->getFeed()->getEntries()[$i]->id = $key;
            }
        }
        // update
        if ($hasUrl && $hasPayload) {
            $bulkResult = $this->getWrapper()->updateBulkResource($targSet, $targObj, $keys, $data);
            for ($i = 0; $i < $numEntries; $i++) {
                $targEntityInstance = $bulkResult[$i];
                $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $targEntityInstance, $propName);
                $link->getExpandedResult()->getFeed()->getEntries()[$i]->id = $keys[$i];
            }
        }
        assert(isset($bulkResult) && is_array($bulkResult));

        for ($i = 0; $i < $numEntries; $i++) {
            assert($link->getExpandedResult()->getFeed()->getEntries()[$i]->id instanceof KeyDescriptor);
            $numLinks = count($link->getExpandedResult()->getFeed()->getEntries()[$i]->links);
            for ($j = 0; $j < $numLinks; $j++) {
                $this->processLink($link->getExpandedResult()->getFeed()->getEntries()[$i]->links[$j], $targSet, $bulkResult[$i]);
            }
        }

        return;
    }

    /**
     * @param ODataLink   $link
     * @param ResourceSet $sourceSet
     * @param $source
     * @param $hasUrl
     * @param $hasPayload
     * @throws InvalidOperationException
     * @throws ODataException
     * @throws ReflectionException
     */
    protected function processLinkSingleton(ODataLink &$link, ResourceSet $sourceSet, $source, $hasUrl, $hasPayload)
    {
        /** @var ODataEntry|null $result */
        $result = $link->getExpandedResult() ? $link->getExpandedResult()->getEntry() : null;
        assert(
            null === $result || $result instanceof ODataEntry,
            (null === $result ? 'null' : get_class($result))
        );

        if ($hasUrl) {
            $urlBitz      = explode('/', $link->getUrl());
            $rawPredicate = $urlBitz[count($urlBitz) - 1];
            $rawPredicate = explode('(', $rawPredicate);
            $setName      = $rawPredicate[0];
            $rawPredicate = trim($rawPredicate[count($rawPredicate) - 1], ')');
            $targSet      = $this->getMetaProvider()->resolveResourceSet($setName);
            assert(null !== $targSet, get_class($targSet));
            $type = $targSet->getResourceType();
        } else {
            $type = $this->getMetaProvider()->resolveResourceType($result->type->getTerm());
        }

        // if link result has already been processed, bail out
        if (null !== $result || null !== $link->getUrl()) {
            $isUrlKey = $link->getUrl() instanceof KeyDescriptor;
            $isIdKey  = $result instanceof ODataEntry &&
                $result->id instanceof KeyDescriptor;
            if ($isUrlKey || $isIdKey) {
                if ($isIdKey) {
                }
                return;
            }
        }
        assert(null === $result || !$result->id instanceof KeyDescriptor);
        assert(null === $link->getUrl() || is_string($link->getUrl()));

        assert($type instanceof ResourceEntityType, get_class($type));
        $propName = $link->getTitle();

        /** @var KeyDescriptor|null $keyDesc */
        $keyDesc = null;

        if ($hasUrl) {
            assert(isset($rawPredicate));
            KeyDescriptor::tryParseKeysFromKeyPredicate($rawPredicate, $keyDesc);
            $keyDesc->validate($rawPredicate, $type);
            assert(null !== $keyDesc, 'Key description must not be null');
        }

        // hooking up to existing resource
        if ($hasUrl && !$hasPayload) {
            assert(isset($targSet));
            assert(isset($keyDesc));
            $target = $this->getWrapper()->getResourceFromResourceSet($targSet, $keyDesc);
            assert(isset($target));
            $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
            return;
        }
        // creating new resource
        if (!$hasUrl && $hasPayload) {
            list($targSet, $target) = $this->processEntryContent($result);
            assert(isset($target));
            $key        = $this->generateKeyDescriptor($type, $result->propertyContent);
            $link->setUrl($key->generateRelativeUri($targSet));
            $result->id = $key;
            $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
            return;
        }
        // updating existing resource and connecting to it
        list($targSet, $target) = $this->processEntryContent($result);
        assert(isset($target));
        $result->id = $keyDesc;
        $this->getWrapper()->hookSingleModel($sourceSet, $source, $targSet, $target, $propName);
        return;
    }

    /**
     * @param  ODataEntry $payload
     * @param  int        $depth
     * @return bool
     */
    protected function isEntryProcessed(ODataEntry $payload, $depth = 0)
    {
        assert(is_int($depth) && 0 <= $depth && 100 >= $depth, 'Maximum recursion depth exceeded');
        if (!$payload->id instanceof KeyDescriptor) {
            return false;
        }
        foreach ($payload->links as $link) {
            $expand = $link->getExpandedResult() ? $link->getExpandedResult()->getData() : null;
            if (null === $expand) {
                continue;
            }
            if ($expand instanceof ODataEntry) {
                if (!$this->isEntryProcessed($expand, $depth + 1)) {
                    return false;
                } else {
                    continue;
                }
            }
            if ($expand instanceof ODataFeed) {
                foreach ($expand->getEntries() as $entry) {
                    if (!$this->isEntryProcessed($entry, $depth + 1)) {
                        return false;
                    }
                }
                continue;
            }
            assert(false, 'Expanded result cannot be processed');
        }

        return true;
    }
}
