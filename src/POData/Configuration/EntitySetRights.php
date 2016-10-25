<?php

namespace POData\Configuration;

/**
 * Class EntitySetRights Enumeration to describe the rights granted on a entity set (resource set).
 */
class EntitySetRights
{
    /**
     * Specifies no rights on this entity set.
     */
    const NONE = 0;

    /**
     * Specifies the right to read one entity instance per request.
     */
    const READ_SINGLE = 1;

    /**
     * Specifies the right to read multiple entity instances per request.
     */
    const READ_MULTIPLE = 2;

    /**
     * Specifies the right to append (add) new entity instance to the entity set.
     */
    const WRITE_APPEND = 4;

    /**
     * Specifies the right to update existing entity instance in the entity set.
     */
    const WRITE_REPLACE = 8;

    /**
     * Specifies the right to delete existing entity instance in the entity set.
     */
    const WRITE_DELETE = 16;

    /**
     * Specifies the right to update existing entity instance in the entity set.
     */
    const WRITE_MERGE = 32;

    /**
     * Specifies the right to read single or multiple entity instances in a
     * single request
     * READ_SINGLE | READ_MULTIPLE.
     */
    const READ_ALL = 3;

    /**
     * Specifies the right to append, delete or update entity instances in the
     * entity set
     * WRITE_APPEND | WRITE_DELETE | WRITE_REPLACE | WRITE_MERGE.
     */
    const WRITE_ALL = 60;

    /**
     * Specifies all rights to the entity set
     * READ_ALL | WRITE_ALL.
     */
    const ALL = 63;
}
