<?php


namespace POData\Writers\Json;


use MyCLabs\Enum\Enum;

/**
 * Class JsonLightMetadataLevel
 * @package POData\Writers\Json
 *
 * @method static \POData\Writers\Json\JsonLightMetadataLevel NONE()
 * @method static \POData\Writers\Json\JsonLightMetadataLevel MINIMAL()
 * @method static \POData\Writers\Json\JsonLightMetadataLevel FULL()
 */
class JsonLightMetadataLevel extends Enum {
	const NONE = "None";

	const MINIMAL = "Minimal";

	const FULL = "Full";

}