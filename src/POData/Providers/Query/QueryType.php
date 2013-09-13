<?php

namespace POData\Providers\Query;

use MyCLabs\Enum\Enum;


	/**
	 * @method static QueryType ENTITY()
	 * @method static QueryType COUNT()
	 * @method static QueryType INLINECOUNT()
	 */
class QueryType extends Enum {

	const ENTITY      = "ENTITY";
	const COUNT         = "Count";
	const INLINECOUNT   = "InlineCount";

}