<?php

namespace POData\UriProcessor\ResourcePathProcessor;

use POData\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use POData\UriProcessor\RequestDescription;
use POData\UriProcessor\RequestCountOption;
use POData\IService;
use POData\Common\Url;
use POData\Common\ODataConstants;
use POData\Common\Messages;
use POData\Common\ODataException;


/**
 * Class ResourcePathProcessor
 * @package POData\UriProcessor\ResourcePathProcessor
 */
class ResourcePathProcessor
{
    /**
     * Process the given request Uri and creates an instance of 
     * RequestDescription from the processed uri.
     * 
     * @param Url         &$absoluteRequestUri The absolute request uri.
     * @param IService $service        Reference to the data service instance.
     *
     * @return RequestDescription
     * 
     * @throws ODataException If any exception occurs while processing the segments
     *                        or incase of any version incompatibility.
     */
    public static function process(Url &$absoluteRequestUri, IService $service
    ) {
        $absoluteRequestUri = $service->getHost()->getAbsoluteRequestUri();
        $absoluteServiceUri = $service->getHost()->getAbsoluteServiceUri();

        $requestUriSegments = array_slice(
            $absoluteRequestUri->getSegments(),
            $absoluteServiceUri->getSegmentCount()
        );
        $segmentDescriptors = null;
        try {
            $segmentDescriptors = SegmentParser::parseRequestUriSegements(
                $requestUriSegments,
                $service->getMetadataQueryProviderWrapper(),
                true
            );
        } catch (ODataException $odataException) {
            throw $odataException;
        }

        $requestDescription = new RequestDescription(
            $segmentDescriptors,
            $absoluteRequestUri
        );
        $requestTargetKind = $requestDescription->getTargetKind();
        if ($requestTargetKind != RequestTargetKind::METADATA 
            && $requestTargetKind != RequestTargetKind::BATCH 
            && $requestTargetKind != RequestTargetKind::SERVICE_DIRECTORY
        ) {

            if ($requestTargetKind != RequestTargetKind::PRIMITIVE_VALUE 
                && $requestTargetKind != RequestTargetKind::MEDIA_RESOURCE
            ) {
                    $requestDescription->setContainerName($requestDescription->getIdentifier());
            } else {
                    // http://odata/NW.svc/Orders/$count
                    // http://odata/NW.svc/Orders(123)/Customer/CustomerID/$value
                    // http://odata/NW.svc/Employees(1)/$value
                    // http://odata/NW.svc/Employees(1)/ThumbNail_48X48/$value
                $requestDescription->setContainerName(
                    $segmentDescriptors[count($segmentDescriptors) - 2]->getIdentifier()
                );
            }

            if ($requestDescription->getIdentifier() === ODataConstants::URI_COUNT_SEGMENT
            ) {
                if (!$service->getServiceConfiguration()->getAcceptCountRequests()
                ) {
                    ODataException::createBadRequestError(
                        Messages::configurationCountNotAccepted()
                    );
                }

                $requestDescription->setRequestCountOption( RequestCountOption::VALUE_ONLY() );
                // use of $count requires request DataServiceVersion 
                // and MaxDataServiceVersion greater than or equal to 2.0

                $requestDescription->raiseResponseVersion( 2, 0, $service );
                $requestDescription->raiseMinVersionRequirement(2, 0, $service );

            } else if ($requestDescription->isNamedStream()) {
                $requestDescription->raiseMinVersionRequirement(3, 0, $service );
            } else if ($requestDescription->getTargetKind() == RequestTargetKind::RESOURCE
            ) {                    
                if (!$requestDescription->isLinkUri()) {
                    $resourceSetWrapper = $requestDescription
                        ->getTargetResourceSetWrapper();
                    //assert($resourceSetWrapper != null)
                    $hasNamedStream = $resourceSetWrapper->hasNamedStreams(
                        $service->getMetadataQueryProviderWrapper()
                    );

                    $hasBagProperty = $resourceSetWrapper->hasBagProperty(
                        $service->getMetadataQueryProviderWrapper()
                    );

                    if ($hasNamedStream || $hasBagProperty) {
                        $requestDescription->raiseResponseVersion( 3, 0, $service );
                    }
                }
            } else if ($requestDescription->getTargetKind() == RequestTargetKind::BAG
            ) {
                $requestDescription->raiseResponseVersion( 3, 0, $service );
            }
        }

        return $requestDescription;
    } 
}