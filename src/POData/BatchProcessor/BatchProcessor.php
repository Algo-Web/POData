<?php
namespace POData\BatchProcessor;

use POData\BaseService;

class BatchProcessor
{
    protected $_service;
    protected $_data;
    protected $batchBoundry = '';
    protected $request;
    protected $ChangeSetProcessors = [];

    public function __construct(BaseService $service, $request)
    {
        $this->_service = $service;
        $this->request = $request;
        $host = $this->_service->getHost();
        assert(substr($host->getRequestContentType(), 0, 16) === 'multipart/mixed;');
        $this->_data = trim($request->getData());
        $this->_data = $str = preg_replace('~\r\n?~', "\n", $this->_data);
        $this->batchBoundry = substr($host->getRequestContentType(), 26);

        $matches = explode('--'.$this->batchBoundry, $this->_data);
        foreach ($matches as $match) {
            $match = trim($match);
            if ('' === $match || '--' === $match) {
                continue;
            }
            $header = explode("\n\n", $match)[0];
            if (strpos($header, 'Content-Type: application/http') !== false) {
                $this->ChangeSetProcessors[] = new ChangeSetParser($this->_service, trim($match));
            } else {
                $this->ChangeSetProcessors[] = new QueryParser($this->_service, trim($match));
            }
        }
        foreach ($this->ChangeSetProcessors as $csp) {
            //    $csp->process();
        }


        dd($this->ChangeSetProcessors);
    }
}
