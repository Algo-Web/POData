<?php
namespace POData\BatchProcessor;

use POData\BaseService;

interface IBatchParser
{
    public function __construct(BaseService $host, $data);

    public function process();
}
