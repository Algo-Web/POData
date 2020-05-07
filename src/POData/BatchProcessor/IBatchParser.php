<?php

declare(strict_types=1);

namespace POData\BatchProcessor;

use POData\BaseService;

/**
 * Interface IBatchParser.
 * @package POData\BatchProcessor
 */
interface IBatchParser
{
    /**
     * IBatchParser constructor.
     * @param BaseService $host
     * @param $data
     */
    public function __construct(BaseService $host, $data);

    public function process();

    public function getResponse();

    public function handleData();
}
