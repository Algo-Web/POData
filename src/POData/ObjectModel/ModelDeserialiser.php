<?php

declare(strict_types=1);

namespace POData\ObjectModel;

use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use POData\Providers\Metadata\ResourceEntityType;

/**
 * Class ModelDeserialiser.
 * @package POData\ObjectModel
 */
class ModelDeserialiser
{
    // take a supplied resourceEntityType and ODataEntry object, check that they match, and retrieve the
    // non-key properties for same

    private static $nonKeyPropertiesCache = [];

    public function __construct()
    {
    }

    /**
     * Filter supplied ODataEntry into $data array for use in resource create/update.
     *
     * @param ResourceEntityType $entityType Entity type to deserialise to
     * @param ODataEntry         $payload    Raw data to deserialise
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @return mixed[]
     */
    public function bulkDeserialise(ResourceEntityType $entityType, ODataEntry $payload)
    {
        if (!isset($payload->type)) {
            $msg = 'ODataEntry payload type not set';
            throw new InvalidArgumentException($msg);
        }

        /** @scrutinizer ignore-call */
        $payloadType = $payload->type->getTerm();
        $pay         = explode('.', $payloadType);
        $payloadType = $pay[count($pay) - 1];
        $actualType  = $entityType->getName();

        if ($payloadType !== $actualType) {
            $msg = 'Payload resource type does not match supplied resource type.';
            throw new InvalidArgumentException($msg);
        }

        if (!isset(self::$nonKeyPropertiesCache[$actualType])) {
            $rawProp    = $entityType->getAllProperties();
            $keyProp    = $entityType->getKeyProperties();
            $keyNames   = array_keys($keyProp);
            $nonRelProp = [];
            foreach ($rawProp as $prop) {
                $propName = $prop->getName();
                if (!in_array($propName, $keyNames) && !($prop->getResourceType() instanceof ResourceEntityType)) {
                    $nonRelProp[] = $propName;
                    $nonRelProp[] = strtolower($propName);
                }
            }
            self::$nonKeyPropertiesCache[$actualType] = $nonRelProp;
        }

        $nonRelProp = self::$nonKeyPropertiesCache[$actualType];

        // assemble data array
        $data = [];
        foreach ($payload->propertyContent->properties as $propName => $propSpec) {
            if (in_array($propName, $nonRelProp) || in_array(strtolower($propName), $nonRelProp)) {
                /** @var string $rawVal */
                $rawVal = $propSpec->value;
                $value  = null;
                switch ($propSpec->typeName) {
                    case 'Edm.Boolean':
                        $rawVal = trim(strtolower(strval($rawVal)));
                        $value  = 'true' == $rawVal;
                        break;
                    case 'Edm.DateTime':
                        $rawVal = trim(strval($rawVal));
                        if (1 < strlen($rawVal)) {
                            $valLen     = strlen($rawVal) - 6;
                            $offsetChek = $rawVal[$valLen];
                            $timezone   = new DateTimeZone('UTC');
                            if (18 < $valLen && ('-' == $offsetChek || '+' == $offsetChek)) {
                                $rawTz    = substr($rawVal, $valLen);
                                $rawVal   = substr($rawVal, 0, $valLen);
                                $rawBitz  = explode('.', $rawVal);
                                $rawVal   = $rawBitz[0];
                                $timezone = new DateTimeZone($rawTz);
                            }
                            $newValue = new DateTime($rawVal, $timezone);
                            // clamp assignable times to:
                            // after 1752, since OData DateTime epoch is apparently midnight 1 Jan 1753
                            // before 10000, since OData has a Y10K problem
                            if (1752 < $newValue->format('Y') && 10000 > $newValue->format('Y')) {
                                $value = $newValue;
                            }
                        }
                        break;
                    default:
                        $value = trim(strval($rawVal));
                        break;
                }
                $data[$propName] = $value;
            }
        }

        return $data;
    }

    /**
     * Reset properties cache.
     *
     * @return void
     */
    public function reset()
    {
        self::$nonKeyPropertiesCache = [];
    }
}
