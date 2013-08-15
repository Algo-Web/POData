<?php

namespace ODataProducer\UriProcessor\ResourcePathProcessor;

use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\SegmentParser;
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\RequestTargetKind;
use ODataProducer\UriProcessor\RequestDescription;
use ODataProducer\UriProcessor\RequestCountOption;
use ODataProducer\DataService;
use ODataProducer\Common\Url;
use ODataProducer\Common\ODataConstants;
use ODataProducer\Common\Messages;
use ODataProducer\Common\ODataException;


/**
 * Class ResourcePathProcessor
 * @package ODataProducer\UriProcessor\ResourcePathProcessor
 */
class ResourcePathProcessor
{
    /**
     * Process the given request Uri and creates an instance of 
     * RequestDescription from the processed uri.
     * 
     * @param Url         &$absoluteRequestUri The absolute request uri.
     * @param DataService &$dataService        Reference to the data service
     *                                         instance.
     * 
     * @return RequestDescription
     * 
     * @throws ODataException If any exception occurs while processing the segments
     *                        or incase of any version incompatibility.
     */
    public static function process(Url &$absoluteRequestUri, 
        DataService &$dataService
    ) {
        $absoluteRequestUri = $dataService->getHost()->getAbsoluteRequestUri();
        $absoluteServiceUri = $dataService->getHost()->getAbsoluteServiceUri();

        $requestUriSegments = array_slice(
            $absoluteRequestUri->getSegments(),
            $absoluteServiceUri->getSegmentCount()
        );
        $segmentDescriptors = null;
        try {
            $segmentDescriptors = SegmentParser::parseRequestUriSegements(
                $requestUriSegments,
                $dataService->getMetadataQueryProviderWrapper(),
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
                if (!$dataService->getServiceConfiguration()->getAcceptCountRequests()
                ) {
                    ODataException::createBadRequestError(
                        Messages::dataServiceConfigurationCountNotAccepted()
                    );
                }

                $requestDescription->setRequestCountOption(
                    RequestCountOption::VALUE_ONLY
                );
                // use of $count requires request DataServiceVersion 
                // and MaxDataServiceVersion
                // greater than or equal to 2.0
                $requestDescription->raiseResponseVersion(
                    2, 
                    0, 
                    $dataService
                );
                $requestDescription->raiseMinimumVersionRequirement(
                    2, 
                    0, 
                    $dataService
                );
            } else if ($requestDescription->isNamedStream()) {
                $requestDescription->raiseMinimumVersionRequirement(
                    3, 
                    0, 
                    $dataService
                );
            } else if ($requestDescription->getTargetKind() == RequestTargetKind::RESOURCE
            ) {                    
                if (!$requestDescription->isLinkUri()) {
                    $resourceSetWrapper = $requestDescription
                        ->getTargetResourceSetWrapper();
                    //assert($resourceSetWrapper != null)
                    $hasNamedStream = $resourceSetWrapper->hasNamedStreams(
                        $dataService->getMetadataQueryProviderWrapper()
                    );

                    $hasBagProperty = $resourceSetWrapper->hasBagProperty(
                        $dataService->getMetadataQueryProviderWrapper()
                    );

                    if ($hasNamedStream || $hasBagProperty) {
                        $requestDescription->raiseResponseVersion(
                            3, 
                            0, 
                            $dataService
                        );
                    }
                }
            } else if ($requestDescription->getTargetKind() == RequestTargetKind::BAG
            ) {
                $requestDescription->raiseResponseVersion(
                    3, 
                    0, 
                    $dataService
                );
            }
        }

        return $requestDescription;
    } 
}