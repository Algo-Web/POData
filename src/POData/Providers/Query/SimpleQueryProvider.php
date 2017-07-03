<?php

namespace POData\Providers\Query;

use POData\Providers\Expression\MySQLExpressionProvider;
use POData\Providers\Metadata\ResourceProperty;
use POData\Providers\Metadata\ResourceSet;
use POData\UriProcessor\QueryProcessor\ExpressionParser\FilterInfo;
use POData\UriProcessor\QueryProcessor\OrderByParser\InternalOrderByInfo;
use POData\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;

abstract class SimpleQueryProvider implements IQueryProvider
{
    /**
     * @var Connection
     */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Query all data from DB.
     *
     * @param string     $sql        SQL query
     * @param array|null $parameters Parameters for SQL query
     *
     * @return array[]|null Array of associated arrays (column name => column value)
     */
    abstract protected function queryAll($sql, $parameters = null);

    /**
     * Query one value from DB.
     *
     * @param string     $sql        SQL query
     * @param array|null $parameters Parameters for SQL query
     *
     * @return mixed Value
     */
    abstract protected function queryScalar($sql, $parameters = null);

    /* Stubbed Implementaiton Here */
    public function getQueryProvider()
    {
        return new QueryProvider();
    }

    public function handlesOrderedPaging()
    {
        return true;
    }

    public function getExpressionProvider()
    {
        return new MySQLExpressionProvider();
    }

    /**
     * Get entity name by class name.
     *
     * @param string $entityClassName Class name
     *
     * @return string Entity name
     */
    protected function getEntityName($entityClassName)
    {
        preg_match_all('/\\\([a-zA-Z]+)/', $entityClassName, $matches);
        if (!empty($matches[1])) {
            return $matches[1][count($matches[1]) - 1];
        }

        return $entityClassName;
    }

    /**
     * Get table name by entity name.
     *
     * @param string $entityName Entity name
     *
     * @return string Table name
     */
    protected function getTableName($entityName)
    {
        $tableName = $entityName;
        preg_match_all('/[A-Z][a-z]+/', $entityName, $matches);
        if (!empty($matches[0])) {
            $tableName = implode('_', $matches[0]);
        }

        return strtolower($tableName);
    }

    /**
     * Get part of SQL query with ORDER BY condition.
     *
     * @param InternalOrderByInfo $orderBy Order by condition
     *
     * @return string ORDER BY condition
     */
    protected function getOrderByExpressionAsString(InternalOrderByInfo $orderBy)
    {
        $result = '';
        foreach ($orderBy->getOrderByInfo()->getOrderByPathSegments() as $order) {
            foreach ($order->getSubPathSegments() as $subOrder) {
                $result .= $result ? ', ' : '';
                $result .= $subOrder->getName();
                $result .= $order->isAscending() ? ' ASC' : ' DESC';
            }
        }

        return $result;
    }

    /**
     * Common method for getResourceFromRelatedResourceSet() and getResourceFromResourceSet().
     *
     * @param KeyDescriptor|null $keyDescriptor
     */
    protected function getResource(
        ResourceSet $resourceSet,
        $keyDescriptor,
        array $whereCondition = []
    ) {
        $where = '';
        $parameters = [];
        $index = 0;
        if ($keyDescriptor) {
            foreach ($keyDescriptor->getValidatedNamedValues() as $key => $value) {
                ++$index;
                //Keys have already been validated, so this is not a SQL injection surface
                $where .= $where ? ' AND ' : '';
                $where .= $key . ' = :param' . $index;
                $parameters[':param' . $index] = $value[0];
            }
        }
        foreach ($whereCondition as $fieldName => $fieldValue) {
            ++$index;
            $where .= $where ? ' AND ' : '';
            $where .= $fieldName . ' = :param' . $index;
            $parameters[':param' . $index] = $fieldValue;
        }
        $where = $where ? ' WHERE ' . $where : '';
        $entityClassName = $resourceSet->getResourceType()->getInstanceType()->name;
        $entityName = $this->getEntityName($entityClassName);
        $sql = 'SELECT * FROM ' . $this->getTableName($entityName) . $where . ' LIMIT 1';
        $result = $this->queryAll($sql, $parameters);
        if ($result) {
            $result = $result[0];
        }

        return $entityClassName::fromRecord($result);
    }

    /**
     * For queries like http://localhost/NorthWind.svc/Customers.
     */
    public function getResourceSet(
        QueryType $queryType,
        ResourceSet $resourceSet,
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
        $result = new QueryResult();
        $entityClassName = $resourceSet->getResourceType()->getInstanceType()->name;
        $entityName = $this->getEntityName($entityClassName);
        $tableName = $this->getTableName($entityName);
        $option = null;
        if ($queryType == QueryType::ENTITIES_WITH_COUNT()) {
            //tell mysql we want to know the count prior to the LIMIT
            //$option = 'SQL_CALC_FOUND_ROWS';
        }
        $where = $filterInfo ? ' WHERE ' . $filterInfo->getExpressionAsString() : '';
        $order = $orderBy ? ' ORDER BY ' . $this->getOrderByExpressionAsString($orderBy) : '';
        $sqlCount = 'SELECT COUNT(*) FROM ' . $tableName . $where;
        if ($queryType == QueryType::ENTITIES() || $queryType == QueryType::ENTITIES_WITH_COUNT()) {
            $sql = 'SELECT ' . $option . ' * FROM ' . $tableName . $where . $order
                    .($top ? ' LIMIT ' . $top : '') . ($skip ? ' OFFSET ' . $skip : '');
            $data = $this->queryAll($sql);

            $rawCount = $this->queryScalar($sqlCount);
            $adjCount = QueryResult::adjustCountForPaging(
                $rawCount,
                $top,
                $skip
            );

            if ($queryType == QueryType::ENTITIES_WITH_COUNT()) {
                //get those found rows
                //$result->count = $this->queryScalar('SELECT FOUND_ROWS()');
                $result->count = $this->queryScalar($sqlCount);
            }
            $result->results = array_map($entityClassName . '::fromRecord', $data);
            $result->hasMore = $rawCount > $adjCount;
        } elseif ($queryType == QueryType::COUNT()) {
            $top = null !== $top ? intval($top) : $top;
            $skip = null !== $skip ? intval($skip) : $skip;
            $rawCount = $this->queryScalar($sqlCount);
            $result->count = intval(QueryResult::adjustCountForPaging(
                $rawCount,
                $top,
                $skip
            ));
            $result->hasMore = $rawCount > $result->count;
        }

        return $result;
    }

    /**
     * For queries like http://localhost/NorthWind.svc/Customers(‘ALFKI’).
     */
    public function getResourceFromResourceSet(
        ResourceSet $resourceSet,
        KeyDescriptor $keyDescriptor
    ) {
        return $this->getResource($resourceSet, $keyDescriptor);
    }

    /**
     * For queries like http://localhost/NorthWind.svc/Customers(‘ALFKI’)/Orders.
     */
    public function getRelatedResourceSet(
        QueryType $queryType,
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        $filterInfo = null,
        $orderBy = null,
        $top = null,
        $skip = null,
        $skipToken = null
    ) {
        // Correct filter
        $srcClass = get_class($sourceEntityInstance);
        $filterFieldName = $this->getTableName($this->getEntityName($srcClass)) . '_id';
        $navigationPropertiesUsedInTheFilterClause = null;
        $filterExpAsDataSourceExp = '';
        if ($filterInfo) {
            $navigationPropertiesUsedInTheFilterClause = $filterInfo->getNavigationPropertiesUsed();
            $filterExpAsDataSourceExp = $filterInfo->getExpressionAsString();
        }
        $filterExpAsDataSourceExp .= $filterExpAsDataSourceExp ? ' AND ' : '';
        $filterExpAsDataSourceExp .= $filterFieldName . ' = ' . $sourceEntityInstance->id;
        $completeFilterInfo = new FilterInfo($navigationPropertiesUsedInTheFilterClause, $filterExpAsDataSourceExp);

        return $this->getResourceSet($queryType, $targetResourceSet, $completeFilterInfo, $orderBy, $top, $skip, null);
    }

    /**
     * For queries like http://localhost/NorthWind.svc/Customers(‘ALFKI’)/Orders(10643).
     */
    public function getResourceFromRelatedResourceSet(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $entityClassName = $sourceResourceSet->getResourceType()->getInstanceType()->name;
        $entityName = $this->getEntityName($entityClassName);
        $fieldName = $this->getTableName($entityName) . '_id';

        return $this->getResource($targetResourceSet, $keyDescriptor, [
            $fieldName => $sourceEntityInstance->id,
        ]);
    }

    /**
     * For queries like http://localhost/NorthWind.svc/Orders(10643)/Customer.
     */
    public function getRelatedResourceReference(
        ResourceSet $sourceResourceSet,
        $sourceEntityInstance,
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $entityClassName = $targetResourceSet->getResourceType()->getInstanceType()->name;
        $entityName = $this->getEntityName($entityClassName);
        $fieldName = $this->getTableName($entityName) . '_id';

        return $this->getResource($targetResourceSet, null, [
            'id' => $sourceEntityInstance->$fieldName,
        ]);
    }
}
