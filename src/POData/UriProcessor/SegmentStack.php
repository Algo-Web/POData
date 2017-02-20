<?php

namespace POData\UriProcessor;

use POData\Common\InvalidOperationException;
use POData\Providers\Metadata\ResourceSetWrapper;

class SegmentStack
{
    /**
     * Collection of segment names.
     *
     * @var string[]
     */
    private $segmentNames;

    /**
     * Collection of segment ResourceSetWrapper instances.
     *
     * @var ResourceSetWrapper[]
     */
    private $segmentResourceSetWrappers;

    /**
     * Description of the OData request that a client has submitted.
     *
     * @var RequestDescription
     */
    private $request;

    private $mismatch = 'Mismatch between size of names array and wrappers array';

    public function __construct(RequestDescription $request = null)
    {
        $this->request = $request;
        $this->segmentNames = [];
        $this->segmentResourceSetWrappers = [];
        assert(count($this->segmentNames) == count($this->segmentResourceSetWrappers), $this->mismatch);
    }

    /**
     * Pushes information about the segment whose instance is going to be
     * retrieved from the IDSQP implementation
     * Note: Calls to this method should be balanced with calls to popSegment.
     *
     * @param string             $segmentName         Name of segment to push
     * @param ResourceSetWrapper &$resourceSetWrapper The resource set wrapper
     *                                                to push
     *
     * @return bool true if the segment was push, false otherwise
     */
    public function pushSegment($segmentName, ResourceSetWrapper &$resourceSetWrapper)
    {
        if (!is_string($segmentName)) {
            throw new InvalidOperationException('segmentName must be a string');
        }
        $rootProjectionNode = $this->getRequest()->getRootProjectionNode();
        if (!is_null($rootProjectionNode) && $rootProjectionNode->isExpansionSpecified()) {
            array_push($this->segmentNames, $segmentName);
            array_push($this->segmentResourceSetWrappers, $resourceSetWrapper);
            assert(count($this->segmentNames) == count($this->segmentResourceSetWrappers), $this->mismatch);

            return true;
        }

        return false;
    }

    /**
     * Pops segment information from the 'Segment Stack'
     * Note: Calls to this method should be balanced with previous calls
     * to _pushSegment.
     *
     * @param bool $needPop Is a pop required. Only true if last push
     *                      was successful
     *
     * @throws InvalidOperationException If found un-balanced call
     *                                   with _pushSegment
     */
    public function popSegment($needPop)
    {
        if ($needPop) {
            if (!empty($this->segmentNames)) {
                array_pop($this->segmentNames);
                array_pop($this->segmentResourceSetWrappers);

                assert(count($this->segmentNames) == count($this->segmentResourceSetWrappers), $this->mismatch);
            } else {
                throw new InvalidOperationException(
                    'Found non-balanced call to pushSegment and popSegment'
                );
            }
        }
    }

    /**
     * Retrieve stored segment names.
     *
     * @return \string[]
     */
    public function getSegmentNames()
    {
        return $this->segmentNames;
    }

    /**
     * Retrieve stored segment wrappers.
     *
     * @return ResourceSetWrapper[]
     */
    public function getSegmentWrappers()
    {
        return $this->segmentResourceSetWrappers;
    }

    /**
     * Gets reference to the request submitted by client.
     *
     * @return RequestDescription
     */
    public function getRequest()
    {
        assert(null != $this->request, 'Request must not be null');

        return $this->request;
    }

    /**
     * Sets reference to the request submitted by client.
     *
     * @param RequestDescription $request
     */
    public function setRequest(RequestDescription $request)
    {
        $this->request = $request;
    }
}
