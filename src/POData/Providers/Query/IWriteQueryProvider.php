<?php

declare(strict_types=1);


namespace POData\Providers\Query;

use POData\Providers\Metadata\ResourceEntityType;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

interface IWriteQueryProvider
{
    public function supportsTransactions(): bool;

    /**
     * Start database transaction.
     *
     * @param  bool $isBulk Is this transaction inside a batch request?
     * @return void
     */
    public function startTransaction($isBulk = false);

    /**
     * Commit database transaction.
     *
     * @return void
     */
    public function commitTransaction();

    /**
     * Abort database transaction.
     *
     * @return void
     */
    public function rollBackTransaction();

    /**
     * @param  ResourceEntityType $entityType
     * @return object             an empty container object with appropriate properties to hold the new Entity
     */
    public function getEmptyContainer(ResourceEntityType $entityType); //: object;

    /**
     *  responsible for creating the new entity in the data source.
     *
     * @param  object      $newEntity      the entity to save
     * @param  array       $bindProperties key value pair of associated objects to hook up at creation (<string propertyName,<object $entities>>)
     * @return object|null On sucess it would return the new object (including new default and key fields) on failure it would return null
     */
    public function saveNewEntity($newEntity, array $bindProperties);//: ?object

    /**
     * Update an Entity.
     *
     * @param  object      $entity         the entity to save
     * @param  array       $bindProperties key value pair of associated objects to hook up at creation (<string propertyName,<object $entities>>)
     * @return object|null the updated entity on sucess or null on failure
     */
    public function updateEntity($entity, array $bindProperties);// : ?object

    /**
     * Delete an Entity.
     *
     * @param  ResourceSet   $resourceSet
     * @param  KeyDescriptor $keyDescriptor
     * @return bool          true on sucess and false on failure
     */
    public function deleteEntity(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor): bool;

    /**
     * @param  ResourceSet   $primaryResourceSet
     * @param  KeyDescriptor $primaryKeyDescriptor
     * @param  ResourceSet   $secondaryResourceSet
     * @param  KeyDescriptor $secondaryKeyDescriptor
     * @param  string        $propertyOnPrimary
     * @return bool          true on success and false on failure
     */
    public function associate(
        ResourceSet $primaryResourceSet,
        KeyDescriptor $primaryKeyDescriptor,
        ResourceSet $secondaryResourceSet,
        KeyDescriptor $secondaryKeyDescriptor,
        string $propertyOnPrimary
    ): bool;

    /**
     * @param  ResourceSet   $primaryResourceSet
     * @param  KeyDescriptor $primaryKeyDescriptor
     * @param  ResourceSet   $secondaryResourceSet
     * @param  KeyDescriptor $secondaryKeyDescriptor
     * @param  string        $propertyOnPrimary
     * @return bool          true on success and false on failure
     */
    public function dissociate(
        ResourceSet $primaryResourceSet,
        KeyDescriptor $primaryKeyDescriptor,
        ResourceSet $secondaryResourceSet,
        KeyDescriptor $secondaryKeyDescriptor,
        string $propertyOnPrimary
    ): bool;

    /**
     * @param ResourceSet   $resourceSet
     * @param KeyDescriptor $keyDescriptor
     * @param string        $propertyName
     * @param $propertyValue
     * @return bool
     */
    public function updateProperty(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor,
        string $propertyName,
        $propertyValue
    ): bool ;
}
