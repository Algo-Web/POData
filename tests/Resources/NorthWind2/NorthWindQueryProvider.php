<?php
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Query\IDataServiceQueryProvider;
require_once ("NorthWindMetadata2.php");
require_once ("ODataProducer\Providers\Query\IDataServiceQueryProvider.php");
define('DATABASE', 'Northwind');
define('SERVER', '.\sqlexpress');

class NorthWindQueryProvider1 implements IDataServiceQueryProvider
{
    /**
     * Handle to connection to Database     
     */
    private $_connectionHandle = null;

    /**
     * Constructs a new instance of NorthWindQueryProvider
     * 
     */
    public function __construct()
    {
        $this->_connectionHandle = sqlsrv_connect(SERVER, array("Database"=> DATABASE));
        if( $this->_connectionHandle ) {        	
        } else {             
             die( print_r( sqlsrv_errors(), true));
        }        
    }

    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet The entity set whose entities needs to be fetched
     * 
     * @return array(Object)
     */
    public function getResourceSet(ResourceSet $resourceSet)
    {   
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Customers' &&
        $resourceSetName !== 'Orders' &&
        $resourceSetName !== 'Order_Details' &&
        $resourceSetName !== 'Products' &&
        $resourceSetName !== 'Employees') {
            die('(NorthWindQueryProvider) Unknown resource set ' . $resourceSetName);    
        }

        if ($resourceSetName === 'Order_Details') {
            $resourceSetName = 'Order Details';
        }

        $query = "SELECT * FROM [$resourceSetName]";
        $stmt = sqlsrv_query($this->_connectionHandle, $query);
        if( $stmt === false) {            
             die( print_r( sqlsrv_errors(), true));
        }

        $returnResult = array();
        switch ($resourceSetName) {
            case 'Customers':
                $returnResult = $this->_serializeCustomers($stmt);
                break;
            case 'Orders':
                $returnResult = $this->_serializeOrders($stmt);
                break;
            case 'Order Details':
                $returnResult = $this->_serializeOrder_Details($stmt);
                break;
            case 'Products':
                $returnResult = $this->_serializeProducts($stmt);
                break;
            case 'Employees':
                $returnResult = $this->_serializeEmployees($stmt);
                break;
        }

        sqlsrv_free_stmt($stmt);
        return $returnResult;
    }

    /**
     * Gets an entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet $resourceSet     The entity set from which an entity needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be fetched
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {   
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Customers' &&
        $resourceSetName !== 'Orders' &&
        $resourceSetName !== 'Order_Details' &&
        $resourceSetName !== 'Products' &&
        $resourceSetName !== 'Employees') {
            die('(NorthWindQueryProvider) Unknown resource set ' . $resourceSetName);    
        }

        if ($resourceSetName === 'Order_Details') {
            $resourceSetName = 'Order Details';
        }

        $namedKeyValues = $keyDescriptor->getValidatedNamedValues();
        $condition = null;
        foreach ($namedKeyValues as $key => $value) {
            $condition .= $key . ' = ' . $value[0] . ' and ';
        }

        $len = strlen($condition);
        $condition = substr($condition, 0, $len - 5); 
        $query = "SELECT * FROM [$resourceSetName] WHERE $condition";
        $stmt = sqlsrv_query($this->_connectionHandle, $query);
        if( $stmt === false) {            
             die( print_r( sqlsrv_errors(), true));
        }

        //If resource not found return null to the library
        if (!sqlsrv_has_rows($stmt)) {
            return null;
        }

        $result = null;
        while( $record = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC)) {
            switch ($resourceSetName) {
                case 'Customers':
                    $result = $this->_serializeCustomer($record);
                    break;
                case 'Orders':                    
                    $result = $this->_serializeOrder($record);
                    break;
                case 'Order Details1':                    
                    $result = $this->_serializeOrder_Detail($record);
                    break;
                case 'Products':
                    $result = $this->_serializeProduct($record);
                    break;
                case 'Employees':
                    $result = $this->_serializeEmployee($record);
                    break;
            }
        }
        
        sqlsrv_free_stmt($stmt);
        return $result;
    }

    public function  getResourceFromRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty,
        KeyDescriptor $keyDescriptor
    ) {
        $result = array();
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        $key = null;
        foreach ($keyDescriptor->getValidatedNamedValues() as $keyName => $valueDescription) {
            $key = $key . $keyName . '=' . $valueDescription[0] . ' and ';
        }

        $key = rtrim($key, ' and ');
        if ($srcClass === 'Customer1') {
            if ($navigationPropName === 'Orders') {                
                $query = "SELECT * FROM Orders WHERE CustomerID = '$sourceEntityInstance->CustomerID' and $key";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrders($stmt);
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order1') {
             if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE OrderID = $sourceEntityInstance->OrderID";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrder_Details($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        } else if ($srcClass === 'Product1') {
             if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE ProductID = $sourceEntityInstance->ProductID  and $key";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrder_Details($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        } else if ($srcClass === 'Employee1') {
             if ($navigationPropName === 'Subordinates') {
                $query = "SELECT * FROM Employees WHERE ReportsTo = $sourceEntityInstance->EmployeeID  and $key";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeEmployees($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        }

        return empty($result) ? null : $result[0];
        
    }

    /**
     * TODO
     * 
     * @return array(Objects)/array() Array of related resource if exists, if no related resources found returns empty array
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ){    
        $result = array();
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        if ($srcClass === 'Customer1') {
            if ($navigationPropName === 'Orders') {                
                $query = "SELECT * FROM Orders WHERE CustomerID = '$sourceEntityInstance->CustomerID'";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrders($stmt);
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order1') {
             if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE OrderID = $sourceEntityInstance->OrderID";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrder_Details($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        } else if ($srcClass === 'Product1') {
             if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE ProductID = $sourceEntityInstance->ProductID";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeOrder_Details($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        } else if ($srcClass === 'Employee1') {
             if ($navigationPropName === 'Subordinates') {
                $query = "SELECT * FROM Employees WHERE ReportsTo = $sourceEntityInstance->EmployeeID";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if( $stmt === false) {            
                     die( print_r( sqlsrv_errors(), true));
                }

                $result = $this->_serializeEmployees($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        }

        return $result;
    }

    /**
     * TODO
     * 
     * @return Object/null 
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    )
    {
        $result = null;
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        if ($srcClass === 'Order1') {
            if ($navigationPropName === 'Customer') {
                if (empty($sourceEntityInstance->CustomerID)) {
                    $result = null;
                } else {                    
                    $query = "SELECT * FROM Customers WHERE CustomerID = '$sourceEntityInstance->CustomerID'";                
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if( $stmt === false) {            
                         die( print_r( sqlsrv_errors(), true));
                    }

                    if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }

                    $result = $this->_serializeCustomer(sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC));
                }
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order_Details1') {
             if ($navigationPropName === 'Order') {
                if (empty($sourceEntityInstance->OrderID)) {
                    $result = null;
                } else {
                    $query = "SELECT * FROM Orders WHERE OrderID = $sourceEntityInstance->OrderID";
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if( $stmt === false) {            
                         die( print_r( sqlsrv_errors(), true));
                    }
                    
                    if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }
                    
                    $result = $this->_serializeOrder(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
                }
            } else if ($navigationPropName === 'Product') {
                if (empty($sourceEntityInstance->ProductID)) {
                    $result =  null;
                } else {
                    $query = "SELECT * FROM Products WHERE ProductID = $sourceEntityInstance->ProductID";                
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if( $stmt === false) {            
                         die( print_r( sqlsrv_errors(), true));
                    }

                    if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }

                    $result = $this->_serializeProduct(sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC));
                }
            } else {
                die('Order_Details does not have navigation porperty with name: ' . $navigationPropName);
            }
        } else if ($srcClass === 'Employee1') {
             if ($navigationPropName === 'Manager') {
                if (empty($sourceEntityInstance->ReportsTo)) {
                    $result =  null;
                } else {
                    $query = "SELECT * FROM Employees WHERE EmployeeID = $sourceEntityInstance->ReportsTo";
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if( $stmt === false) {            
                         die( print_r( sqlsrv_errors(), true));
                    }

                   if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }

                    $result = $this->_serializeEmployee(sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC));
                }
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        }

        return $result;
    }

    /**
     * Serialize the sql result array into Customer objects
     * 
     * @param array(array) $result
     * 
     * @return array(Object)
     */
    private function _serializeCustomers($result)
    {
        $customers = array();
        while( $record = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
        {         
             $customers[] = $this->_serializeCustomer($record);
        }

        return $customers;
    }

    /**
     * Serialize the sql row into Customer object
     * 
     * @param array $record
     * 
     * @return Object
     */
    private function _serializeCustomer($record)
    {
        $customer = new Customer1();
        $customer->CustomerID = $record['CustomerID'];
        $customer->CompanyName = $record['CompanyName'];
        $customer->ContactName = $record['ContactName'];
        $customer->Phone = $record['Phone'];
        $customer->Address = new Address1();
        $customer->Address->LineNumber = 12;
        $customer->Address->City = $record['City'];
        $customer->Address->Region = $record['Region'];
        $customer->Address->PostalCode = $record['PostalCode'];
        $customer->Address->Country = $record['Country'];
        $customer->Address->Address2 = new Address3();
        $customer->Address->Address2->LineNumber2 = 14;
        return $customer;
    }

    /**
     * Serialize the sql result array into Order objects
     * 
     * @param array(array) $result
     * 
     * @return array(Object)
     */
    private function _serializeOrders($result)
    {
        $orders = array();
        while( $record = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
        {
             $orders[] = $this->_serializeOrder($record);
        }

        return $orders;
    }

    /**
     * Serialize the sql row into Order object
     * 
     * @param array $record
     * 
     * @return Object
     */
    private function _serializeOrder($record)
    {
        $order = new Order1();
        $order->OrderID = $record['OrderID'];
        $order->CustomerID = $record['CustomerID'];
        $order->Freight = doubleval($record['Freight']);
        $order->OrderDate = $record['OrderDate'];
        $order->ShippedDate = $record['ShippedDate'];
        $order->ShipName = $record['ShipName'];        
        return $order;
    }

    /**
     * Serialize the sql result array into Employee objects
     * 
     * @param array(array) $result
     * 
     * @return array(Object)
     */
    private function _serializeEmployees($result)
    {
        $employees = array();
        while( $record = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
        {
             $employees[] = $this->_serializeEmployee($record);
        }

        return $employees;
    }

    /**
     * Serialize the sql row into Employee object
     * 
     * @param array $record
     * 
     * @return Object
     */
    private function _serializeEmployee($record)
    {
        $employee = new Employee1();
        $employee->EmployeeID = $record['EmployeeID'];
        $employee->FirstName = $record['FirstName'];
        $employee->LastName = $record['LastName'];
        $employee->ReportsTo = $record['ReportsTo'];
        $employee->Emails = array ($employee->FirstName . '@hotmail.com', $employee->FirstName . '@live.com');
        return $employee;
    }

    /**
     * Serialize the sql result array into Order_Details objects
     * 
     * @param array(array) $result
     * 
     * @return array(Object)
     */
    private function _serializeOrder_Details($result)
    {
        $order_details = array();
        while( $record = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
        {        
            $order_details[] = $this->_serializeOrder_Detail($record);
        }

        return $order_details;
    }

    /**
     * Serialize the sql row into Order_Details object
     * 
     * @param array $record
     * 
     * @return Object
     */
    private function _serializeOrder_Detail($record)
    {
        $order_details = new Order_Details1();
        $order_details->Discount = $record['Discount'];
        $order_details->OrderID = $record['OrderID'];
        $order_details->ProductID = $record['ProductID'];
        $order_details->Quantity = $record['Quantity'];
        $order_details->UnitPrice = $record['UnitPrice'];
        return $order_details; 
    }

    /**
     * Serialize the sql result array into Product objects
     * 
     * @param array(array) $result
     * 
     * @return array(Object)
     */
    private function _serializeProducts($result)
    {
        $products = array();
        while( $record = sqlsrv_fetch_array( $result, SQLSRV_FETCH_ASSOC))
        {
             $products[] = $this->_serializeProduct($record);
        }

        return $products;
    }

    /**
     * Serialize the sql row into Product object
     * 
     * @param array $record
     * 
     * @return Object
     */
    private function _serializeProduct($record)
    {
        $product = new Product1();
        $product->ProductID = $record['ProductID'];
        $product->ProductName = $record['ProductName'];
        $product->UnitPrice =  $record['UnitPrice'];
        $product->UnitsInStock = $record['UnitsInStock'];
        $product->UnitsOnOrder = $record['UnitsOnOrder'];
        return $product;
    }

    /**
     * The destructor     
     */
    public function __destruct()
    {
        if ($this->_connectionHandle) {
			sqlsrv_close($this->_connectionHandle);            
        }
    }
}
?>