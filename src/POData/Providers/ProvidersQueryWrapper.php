<?php

declare(strict_types=1);

namespace POData\Providers;

use POData\Common\InvalidOperationException;
use POData\Common\Messages;
use POData\Common\ODataException;
use POData\Providers\Expression\IExpressionProvider;
use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\Providers\Query\IReadQueryProvider;
use POData\Providers\Query\QueryResult;
use POData\Providers\Query\QueryType;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\QueryProcessor\SkipTokenParser\SkipTokenInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ReflectionException;

/**
 * Class ProvidersQueryWrapper.
 * @package POData\Providers
 */
class ProvidersQueryWrapper
{
    /**
     * Holds reference to IReadQueryProvider implementation.
     *
     * @var IReadQueryProvider
     */
    private $queryProvider;

    /**
     * Creates a new instance of ProvidersWrapper.
     *
     * @param IReadQueryProvider $query Reference to IReadQueryProvider implementation
     */
    public function __construct(IReadQueryProvider $query)
    {
        $this->queryProvider = $query;
    }

    /**
     * Get related resource set for a resource.
     *
     * @param QueryType          $queryType         Indicates if this is a query for a count, entities, or entities
     *                                              with a count
     * @param ResourceSet        $sourceResourceSet The entity set containing the source entity
     * @param object             $sourceEntity      The source entity instance
     * @param ResourceSet        $targetResourceSet The resource set containing the target of the navigation property
     * @param ResourceProperty   $targetProperty    The navigation property to retrieve
     * @param FilterInfo|null    $filterInfo        Represents the $filter parameter of the OData query.
     *                                              NULL if no $filter specified
     * @param mixed|null         $orderBy           sorted order if we want to get the data in some specific order
     * @param int|null           $top               The top count
     * @param int|null           $skip              The skip count
     * @param SkipTokenInfo|null $skipToken         The skip token
     *
     * @throws ODataException
     * @return QueryResult
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        FilterInfo $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
        $queryResult = $this->getQueryProvider()->getRelatedResourceSet(
            $queryType,
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken
        );

        $this->validateQueryResult($queryResult, $queryType, 'IReadQueryProvider::getRelatedResourceSet');

        return $queryResult;
    }

    /**
     * @return IReadQueryProvider
     */
    public function getQueryProvider()
    {
        return $this->queryProvider;
    }

    /**
     * @param QueryResult $queryResult
     * @param QueryType   $queryType
     * @param string      $methodName
     *
     * @throws ODataException
     */
    protected function validateQueryResult($queryResult, QueryType $queryType, $methodName)
    {
        if (!$queryResult instanceof QueryResult) {
            throw ODataException::createInternalServerError(
                Messages::queryProviderReturnsNonQueryResult($methodName)
            );
        }

        $isResultArray = is_array($queryResult->results);

        if (QueryType::hasCount($queryType)) {
            //and the provider is supposed to handle the ordered paging they must return a count!
            if ($this->handlesOrderedPaging() && !is_numeric($queryResult->count)) {
                throw ODataException::createInternalServerError(
                    Messages::queryProviderResultCountMissing($methodName, $queryType)
                );
            }

            //If POData is supposed to handle the ordered aging they must return results! (possibly empty)
            if (!$this->handlesOrderedPaging() && !$isResultArray) {
                throw ODataException::createInternalServerError(
                    Messages::queryProviderResultsMissing($methodName, $queryType)
                );
            }
        }

        if ((QueryType::hasEntities($queryType)) && !$isResultArray) {
            throw ODataException::createInternalServerError(
                Messages::queryProviderResultsMissing($methodName, $queryType)
            );
        }
    }

    /**
     * Indicates if the QueryProvider can handle ordered paging, this means respecting order, skip, and top parameters
     * If the query provider can not handle ordered paging, it must return the entire result set and POData will
     * perform the ordering and paging.
     *
     * @return bool True if the query provider can handle ordered paging, false if POData should perform the paging
     */
    public function handlesOrderedPaging()
    {
        return $this->getQueryProvider()->handlesOrderedPaging();
    }

    /**
     * Gets collection of entities belongs to an entity set
     * IE: http://host/EntitySet
     *  http://host/EntitySet?$skip=10&$top=5&filter=Prop gt Value.
     *
     * @param QueryType                $queryType   Is this is a query for a count, entities, or entities-with-count
     * @param ResourceSet              $resourceSet The entity set containing the entities to fetch
     * @param FilterInfo|null          $filterInfo  The $filter parameter of the OData query.  NULL if none specified
     * @param null|InternalOrderByInfo $orderBy     sorted order if we want to get the data in some specific order
     * @param int|null                 $top         number of records which need to be retrieved
     * @param int|null                 $skip        number of records which need to be skipped
     * @param SkipTokenInfo|null       $skipToken   value indicating what records to skip
     * @param string[]|null            $eagerLoad   array of relations to eager load
     *
     * @throws ODataException
     * @return QueryResult
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        FilterInfo $filterInfo = null,
        InternalOrderByInfo $orderBy = null,
        $top = null,
        $skip = null,
        SkipTokenInfo $skipToken = null,
        array $eagerLoad = []
    ) {
        $queryResult = $this->getQueryProvider()->getResourceSet(
            $queryType,
            $resourceSet,
            $filterInfo,
            $orderBy,
            $top,
            $skip,
            $skipToken,
            $eagerLoad
        );

        $this->validateQueryResult($queryResult, $queryType, 'IReadQueryProvider::getResourceSet');

        return $queryResult;
    }

    /**
     * Puts an entity instance to entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to update
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to update
     * @param $data
     *
     * @return bool|null Returns result of executing query
     */
    public function putResource(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        $data
    ) {
        $queryResult = $this->getQueryProvider()->putResource(
            $resourceSet,
            $keyDescriptor,
            $data
        );

        return $queryResult;
    }

    /**
     * Gets the underlying custom expression provider, the end developer is
     * responsible for implementing IExpressionProvider if he choose for.
     *
     * @throws ODataException
     * @return IExpressionProvider Instance of IExpressionProvider implementation
     */
    public function getExpressionProvider()
    {
        $expressionProvider = $this->getQueryProvider()->getExpressionProvider();
        if (null === $expressionProvider) {
            throw ODataException::createInternalServerError(
                Messages::providersWrapperExpressionProviderMustNotBeNullOrEmpty()
            );
        }

        if (!$expressionProvider instanceof IExpressionProvider) {
            throw ODataException::createInternalServerError(
                Messages::providersWrapperInvalidExpressionProviderInstance()
            );
        }

        return $expressionProvider;
    }

    /**
     * @param ResourceSet $resourceSet          The entity set containing the entity to fetch
     * @param object|null $sourceEntityInstance The source entity instance
     * @param object      $data                 the New data for the entity instance
     *
     * @return object|null returns the newly created model if successful, or null if model creation failed
     */
    public function createResourceforResourceSet(
        ResourceSet $resourceSet,
        $sourceEntityInstance,
        $data
    ) {
        return $this->getQueryProvider()->createResourceforResourceSet(
            $resourceSet,
            $sourceEntityInstance,
            $data
        );
    }

    /**
     * Delete resource from a resource set.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     *
     * @return bool true if resources successfully deleted, otherwise false
     */
    public function deleteResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance
    ) {
        return $this->getQueryProvider()->deleteResource(
            $sourceResourceSet,
            $sourceEntityInstance
        );
    }

    /**
     * Updates a resource.
     *
     * @param ResourceSet   $sourceResourceSet    The entity set containing the source entity
     * @param object        $sourceEntityInstance The source entity instance
     * @param KeyDescriptor $keyDescriptor        The key identifying the entity to fetch
     * @param object        $data                 the New data for the entity instance
     * @param bool          $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object|null the new resource value if it is assignable, or throw exception for null
     */
    public function updateResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        KeyDescriptor $keyDescriptor,
        $data,
        $shouldUpdate = false
    ) {
        return $this->getQueryProvider()->updateResource(
            $sourceResourceSet,
            $sourceEntityInstance,
            $keyDescriptor,
            $data,
            $shouldUpdate
        );
    }

    /**
     * Create multiple new resources in a resource set.
     * @param ResourceSet $sourceResourceSet The entity set containing the entity to fetch
     * @param object[]    $data              The new data for the entity instance
     *
     * @return object[]|null returns the newly created model if successful, or null if model creation failed
     */
    public function createBulkResourceforResourceSet(
        ResourceSet $sourceResourceSet,
        array $data
    ) {
        return $this->getQueryProvider()->createBulkResourceforResourceSet(
            $sourceResourceSet,
            $data
        );
    }

    /**
     * Updates a group of resources in a resource set.
     *
     * @param ResourceSet     $sourceResourceSet    The entity set containing the source entity
     * @param object          $sourceEntityInstance The source entity instance
     * @param KeyDescriptor[] $keyDescriptor        The key identifying the entity to fetch
     * @param object[]        $data                 The new data for the entity instances
     * @param bool            $shouldUpdate         Should undefined values be updated or reset to default
     *
     * @return object[]|null the new resource value if it is assignable, or throw exception for null
     */
    public function updateBulkResource(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        array $keyDescriptor,
        array $data,
        $shouldUpdate = false
    ) {
        return $this->getQueryProvider()->updateBulkResource(
            $sourceResourceSet,
            $sourceEntityInstance,
            $keyDescriptor,
            $data,
            $shouldUpdate
        );
    }

    /**
     * Get related resource for a resource.
     *
     * @param ResourceSet      $sourceResourceSet The source resource set
     * @param object           $sourceEntity      The source resource
     * @param ResourceSet      $targetResourceSet The resource set of the navigation
     *                                            property
     * @param ResourceProperty $targetProperty    The navigation property to be
     *                                            retrieved
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     * @throws ODataException
     * @return object|null               The related resource if exists, else null
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $entityInstance = $this->getQueryProvider()->getRelatedResourceReference(
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty
        );

        // we will not throw error if the resource reference is null
        // e.g. Orders(1234)/Customer => Customer can be null, this is
        // allowed if Customer is last segment. consider the following:
        // Orders(1234)/Customer/Orders => here if Customer is null then
        // the UriProcessor will throw error.
        if (null !== $entityInstance) {
            $methodName = 'IReadQueryProvider::getRelatedResourceReference';

            $targetResourceType = $this->verifyResourceType($methodName, $entityInstance, $targetResourceSet);
            foreach ($targetProperty->getResourceType()->getKeyProperties() as $keyName => $resourceProperty) {
                try {
                    $keyValue = $targetResourceType->getPropertyValue($entityInstance, $keyName);
                    if (null === $keyValue) {
                        throw ODataException::createInternalServerError(
                            Messages::providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties(
                                'IDSQP::getRelatedResourceReference'
                            )
                        );
                    }
                } catch (ReflectionException $reflectionException) {
                    // Left blank - we're simply squashing reflection exceptions
                }
            }
        }

        return $entityInstance;
    }

    /**
     * @param string $methodName
     * @param $entityInstance
     * @param ResourceSet $resourceSet
     *
     * @throws ReflectionException
     * @throws ODataException
     * @return ResourceEntityType
     */
    private function verifyResourceType($methodName, $entityInstance, ResourceSet $resourceSet)
    {
        $resourceType = $resourceSet->getResourceType();
        $entityName   = $resourceType->getInstanceType()->getName();
        if (!($entityInstance instanceof $entityName)) {
            throw ODataException::createInternalServerError(
                Messages::providersWrapperIDSQPMethodReturnsUnExpectedType(
                    $entityName,
                    $methodName
                )
            );
        }

        return $resourceType;
    }

    /**
     * Gets a related entity instance from an entity set identified by a key.
     *
     * @param ResourceSet      $sourceResourceSet The entity set related to the entity to be fetched
     * @param object           $sourceEntity      The related entity instance
     * @param ResourceSet      $targetResourceSet The entity set from which entity needs to be fetched
     * @param ResourceProperty $targetProperty    The metadata of the target property
     * @param KeyDescriptor    $keyDescriptor     The key to identify the entity to be fetched
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     * @throws ODataException
     * @return object|null               Returns entity instance if found, else null
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntity,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $entityInstance = $this->getQueryProvider()->getResourceFromRelatedResourceSet(
            $sourceResourceSet,
            $sourceEntity,
            $targetResourceSet,
            $targetProperty,
            $keyDescriptor
        );

        $this->validateEntityInstance(
            $entityInstance,
            $targetResourceSet,
            $keyDescriptor,
            'IReadQueryProvider::getResourceFromRelatedResourceSet'
        );

        return $entityInstance;
    }

    /**
     * Validate the given entity instance.
     *
     * @param object|null   $entityInstance Entity instance to validate
     * @param ResourceSet   &$resourceSet   Resource set to which the entity
     *                                      instance belongs to
     * @param KeyDescriptor &$keyDescriptor The key descriptor
     * @param string        $methodName     Method from which this function
     *                                      invoked
     *
     * @throws ODataException
     * @throws InvalidOperationException
     * @throws ReflectionException
     */
    protected function validateEntityInstance(
        $entityInstance,
        ResourceSet &$resourceSet,
        KeyDescriptor &$keyDescriptor,
        $methodName
    ) {
        if (null === $entityInstance) {
            throw ODataException::createResourceNotFoundError($resourceSet->getName());
        }

        $resourceType = $this->verifyResourceType($methodName, $entityInstance, $resourceSet);

        foreach ($keyDescriptor->getValidatedNamedValues() as $keyName => $valueDescription) {
            try {
                $keyValue = $resourceType->getPropertyValue($entityInstance, $keyName);
                if (null === $keyValue) {
                    throw ODataException::createInternalServerError(
                        Messages::providersWrapperIDSQPMethodReturnsInstanceWithNullKeyProperties($methodName)
                    );
                }

                $convertedValue = $valueDescription[1]->convert($valueDescription[0]);
                if ($keyValue != $convertedValue) {
                    throw ODataException::createInternalServerError(
                        Messages::providersWrapperIDSQPMethodReturnsInstanceWithNonMatchingKeys($methodName)
                    );
                }
            } catch (ReflectionException $reflectionException) {
                // Left blank - we're simply squashing reflection exceptions
            }
        }
    }

    /**
     * Gets an entity instance from an entity set identified by a key.
     *
     * @param ResourceSet   $resourceSet   The entity set containing the entity to fetch
     * @param KeyDescriptor $keyDescriptor The key identifying the entity to fetch
     * @param string[]|null $eagerLoad     array of relations to eager load
     *
     * @throws InvalidOperationException
     * @throws ReflectionException
     * @throws ODataException
     * @return object|null               Returns entity instance if found, else null
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        array $eagerLoad = []
    ) {
        $entityInstance = $this->getQueryProvider()
            ->getResourceFromResourceSet($resourceSet, $keyDescriptor, $eagerLoad);
        $this->validateEntityInstance(
            $entityInstance,
            $resourceSet,
            $keyDescriptor,
            'IReadQueryProvider::getResourceFromResourceSet'
        );

        return $entityInstance;
    }

    /**
     * Attaches child model to parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function hookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        return $this->getQueryProvider()->hookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * Removes child model from parent model.
     *
     * @param ResourceSet $sourceResourceSet
     * @param object      $sourceEntityInstance
     * @param ResourceSet $targetResourceSet
     * @param object      $targetEntityInstance
     * @param $navPropName
     *
     * @return bool
     */
    public function unhookSingleModel(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        $targetEntityInstance,
        $navPropName
    ) {
        return $this->getQueryProvider()->unhookSingleModel(
            $sourceResourceSet,
            $sourceEntityInstance,
            $targetResourceSet,
            $targetEntityInstance,
            $navPropName
        );
    }

    /**
     * Start database transaction.
     *
     * @param  bool $isBulk Is this transaction inside a batch request?
     * @return void
     */
    public function startTransaction($isBulk = false)
    {
        $this->getQueryProvider()->startTransaction($isBulk);
    }

    /**
     * Commit database transaction.
     *
     * @return void
     */
    public function commitTransaction()
    {
        $this->getQueryProvider()->commitTransaction();
    }

    /**
     * Abort database transaction.
     *
     * @return void
     */
    public function rollBackTransaction()
    {
        $this->getQueryProvider()->rollBackTransaction();
    }
}
