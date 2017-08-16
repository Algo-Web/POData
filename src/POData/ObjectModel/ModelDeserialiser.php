<?php

namespace POData\ObjectModel;

use POData\ObjectModel\ODataEntry;
use POData\Providers\Metadata\ResourceEntityType;

class ModelDeserialiser
{
    // take a supplied resourceEntityType and ODataEntry object, check that they match, and retrieve the
    // non-key properties for same

    private static $nonKeyPropertiesCache = [];

    public function __construct()
    {
    }

    /**
     * Filter supplied ODataEntry into $data array for use in resource create/update
     *
     * @param ResourceEntityType $entityType    Entity type to deserialise to
     * @param ODataEntry $payload               Raw data to deserialise
     *
     * @return mixed[]
     * @throws \InvalidArgumentException
     */
    public function bulkDeserialise(ResourceEntityType $entityType, ODataEntry $payload)
    {
        if (!isset($payload->type)) {
            $msg = "ODataEntry payload type not set";
            throw new \InvalidArgumentException($msg);
        }

        $payloadType = $payload->type->term;
        $actualType = $entityType->getName();

        if ($payloadType !== $actualType) {
            $msg = 'Payload resource type does not match supplied resource type.';
            throw new \InvalidArgumentException($msg);
        }

        if (!isset(self::$nonKeyPropertiesCache[$actualType])) {
            $rawProp = $entityType->getAllProperties();
            $keyProp = $entityType->getKeyProperties();
            $keyNames = array_keys($keyProp);
            $nonRelProp = [];
            foreach ($rawProp as $prop) {
                $propName = $prop->getName();
                if (!in_array($propName, $keyNames) && !($prop->getResourceType() instanceof ResourceEntityType)) {
                    $nonRelProp[$propName] = $prop;
                }
            }
            self::$nonKeyPropertiesCache[$actualType] = $nonRelProp;
        }

        $nonRelProp = self::$nonKeyPropertiesCache[$actualType];

        // assemble data array
        $data = [];
        foreach ($payload->propertyContent->properties as $propName => $propSpec) {
            if (array_key_exists($propName, $nonRelProp)) {
                $rawVal = $propSpec->value;
                $data[$propName] = $rawVal;
            }
        }

        return $data;
    }

    public function reset()
    {
        self::$nonKeyPropertiesCache = [];
    }
}
