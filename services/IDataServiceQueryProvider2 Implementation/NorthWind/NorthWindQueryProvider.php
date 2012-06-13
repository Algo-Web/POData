<?php
/** 
 * Implementation of IDataServiceQueryProvider2.
 * 
 * PHP version 5.3
 * 
 * @category  Service
 * @package   NorthWind
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *  Redistributions of source code must retain the above copyright notice, this list
 *  of conditions and the following disclaimer.
 *  Redistributions in binary form must reproduce the above copyright notice, this
 *  list of conditions  and the following disclaimer in the documentation and/or
 *  other materials provided with the distribution.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A  PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)  HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN
 * IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */
use ODataProducer\UriProcessor\ResourcePathProcessor\SegmentParser\KeyDescriptor;
use ODataProducer\Providers\Metadata\ResourceSet;
use ODataProducer\Providers\Metadata\ResourceProperty;
use ODataProducer\Providers\Query\IDataServiceQueryProvider2;
use ODataProducer\Common\ODataException;
require_once "NorthWindMetadata.php";
require_once "ODataProducer\Providers\Query\IDataServiceQueryProvider2.php";
require_once 'NorthWindDSExpressionProvider.php';
define('DATABASE', 'Northwind');
// Note: The instance name of your sql server [How to find instance name]
// Start ->All progrmas->Microsoft SQL Server 2008 -> Configuration Tools -> SQL Server Configuration Manager
// In Configuration Manager -> SQL Server 2008 Services -> double click the SQL Service -> click the Service Tab.
define('SERVER', '.\SQLEXPRESS');
// Note: If your database access require credentials then un-comment 
// the following two lines [definition of UID and PWD] and provide db user name 
// as value for UID and password as value for PWD.
// define('UID',  '');
// define('PWD',  '');

/**
 * NorthWindQueryProvider implemetation of IDataServiceQueryProvider2.
 * 
 * @category  Service
 * @package   NorthWind
 * @author    Microsoft Open Technologies, Inc. <msopentech@microsoft.com>
 * @copyright Microsoft Open Technologies, Inc.
 * @license   New BSD license, (http://www.opensource.org/licenses/bsd-license.php)
 * @version   GIT: 1.2
 * @link      https://github.com/MSOpenTech/odataphpprod
 */
class NorthWindQueryProvider implements IDataServiceQueryProvider2
{
    /**
     * Handle to connection to Database     
     */
    private $_connectionHandle = null;

    /**
     * Reference to the custom expression provider
     * 
     * @var NorthWindDSExpressionProvider
     */
    private $_northWindSQLSRVExpressionProvider;

    /**
     * Constructs a new instance of NorthWindQueryProvider
     * 
     */
    public function __construct()
    {
    	$connectionInfo = array("Database" => DATABASE);
        if (defined('UID')) {
        	$connectionInfo['UID'] = UID;
        	$connectionInfo['PWD'] = PWD;    		
    	}

        $this->_connectionHandle = sqlsrv_connect(SERVER, $connectionInfo);
        if ( $this->_connectionHandle ) {        	
        } else {
            $errorAsString = self::_getSQLSRVError();
        	ODataException::createInternalServerError($errorAsString);
        }

        $this->_northWindSQLSRVExpressionProvider = null;
    }

    /**
     * (non-PHPdoc)
     * @see ODataProducer\Providers\Query.IDataServiceQueryProvider2::canApplyQueryOptions()
     */
    public function canApplyQueryOptions()
    {
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see ODataProducer\Providers\Query.IDataServiceQueryProvider2::getExpressionProvider()
     */
    public function getExpressionProvider()
    {
    	if (is_null($this->_northWindSQLSRVExpressionProvider)) {
    		$this->_northWindSQLSRVExpressionProvider = new NorthWindDSExpressionProvider();
    	}

    	return $this->_northWindSQLSRVExpressionProvider;
    }

    /**
     * Gets collection of entities belongs to an entity set
     * 
     * @param ResourceSet $resourceSet        The entity set whose entities 
     *                                        needs to be fetched.
     * @param string           $filterOption  Contains the filter condition
     * @param string           $select        For future purpose,no need to pass it
     * @param string           $orderby       For future purpose,no need to pass it
     * @param string           $top           For future purpose,no need to pass it
     * @param string           $skip          For future purpose,no need to pass it
     * 
     * @return array(Object)
     */
    public function getResourceSet(ResourceSet $resourceSet, $filterOption = null, 
        $select=null, $orderby=null, $top=null, $skip=null
    ) {
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Customers' 
            && $resourceSetName !== 'Orders' 
            && $resourceSetName !== 'Order_Details'
            && $resourceSetName !== 'Employees'
        ) {
        	ODataException::createInternalServerError('(NorthWindQueryProvider) Unknown resource set ' . $resourceSetName . '! Contact service provider');        
        }

        if ($resourceSetName === 'Order_Details') {
            $resourceSetName = 'Order Details';
        }

        $query = "SELECT * FROM [$resourceSetName]";
        if ($filterOption != null) {
            $query .= ' WHERE ' . $filterOption;
        }
        $stmt = sqlsrv_query($this->_connectionHandle, $query);
        if ($stmt === false) {
            $errorAsString = self::_getSQLSRVError();
        	ODataException::createInternalServerError($errorAsString);
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
            $returnResult = $this->_serializeOrderDetails($stmt);
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
     * @param ResourceSet   $resourceSet   The entity set from which 
     *                                     an entity needs to be fetched
     * @param KeyDescriptor $keyDescriptor The key to identify the entity to be fetched
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
    public function getResourceFromResourceSet(ResourceSet $resourceSet, KeyDescriptor $keyDescriptor)
    {   
        $resourceSetName =  $resourceSet->getName();
        if ($resourceSetName !== 'Customers' 
            && $resourceSetName !== 'Orders' 
            && $resourceSetName !== 'Order_Details' 
            && $resourceSetName !== 'Products' 
            && $resourceSetName !== 'Employees'
        ) {
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
        if ($stmt === false) {
            $errorAsString = self::_getSQLSRVError();
        	ODataException::createInternalServerError($errorAsString);
        }

        //If resource not found return null to the library
        if (!sqlsrv_has_rows($stmt)) {
            return null;
        }

        $result = null;
        while ( $record = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            switch ($resourceSetName) {
            case 'Customers':
                $result = $this->_serializeCustomer($record);
                break;
            case 'Orders':                    
                $result = $this->_serializeOrder($record);
                break;
            case 'Order Details':                    
                $result = $this->_serializeOrderDetail($record);
                break;
            case 'Employees':
                $result = $this->_serializeEmployee($record);
                break;
            }
        }
        sqlsrv_free_stmt($stmt);
        return $result;
    }
    
    /**
     * Gets a related entity instance from an entity set identifed by a key
     * 
     * @param ResourceSet      $sourceResourceSet    The entity set related to
     *                                               the entity to be fetched.
     * @param object           $sourceEntityInstance The related entity instance.
     * @param ResourceSet      $targetResourceSet    The entity set from which
     *                                               entity needs to be fetched.
     * @param ResourceProperty $targetProperty       The metadata of the target 
     *                                               property.
     * @param KeyDescriptor    $keyDescriptor        The key to identify the entity 
     *                                               to be fetched.
     * 
     * @return Object/NULL Returns entity instance if found else null
     */
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
        if ($srcClass === 'Customer') {
            if ($navigationPropName === 'Orders') {                
                $query = "SELECT * FROM Orders WHERE CustomerID = '$sourceEntityInstance->CustomerID' and $key";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if ($stmt === false) {            
                    $errorAsString = self::_getSQLSRVError();
        	        ODataException::createInternalServerError($errorAsString);
                }

                $result = $this->_serializeOrders($stmt);
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order') {
            if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE OrderID = $sourceEntityInstance->OrderID";
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if ($stmt === false) {            
                    $errorAsString = self::_getSQLSRVError();
        	        ODataException::createInternalServerError($errorAsString);
                }

                $result = $this->_serializeOrderDetails($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        } 

        return empty($result) ? null : $result[0];
        
    }

    /**
     * Get related resource set for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     * @param string           $filterOption         Contains the filter condition 
     *                                               to append with query.
     * @param string           $select               For future purpose,no need to pass it
     * @param string           $orderby              For future purpose,no need to pass it
     * @param string           $top                  For future purpose,no need to pass it
     * @param string           $skip                 For future purpose,no need to pass it
     *                                                
     * @return array(Objects)/array() Array of related resource if exists, if no 
     *                                related resources found returns empty array
     */
    public function  getRelatedResourceSet(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty, 
        $filterOption = null,
        $select=null, $orderby=null, $top=null, $skip=null
    ) {    
        $result = array();
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        if ($srcClass === 'Customer') {
            if ($navigationPropName === 'Orders') {                
                $query = "SELECT * FROM Orders WHERE CustomerID = '$sourceEntityInstance->CustomerID'";
                if ($filterOption != null) {
                    $query .= ' AND ' . $filterOption;
                }
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if ($stmt === false) {
                    $errorAsString = self::_getSQLSRVError();
        	        ODataException::createInternalServerError($errorAsString);
                }

                $result = $this->_serializeOrders($stmt);
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order') {
            if ($navigationPropName === 'Order_Details') {
                $query = "SELECT * FROM [Order Details] WHERE OrderID = $sourceEntityInstance->OrderID";
                if ($filterOption != null) {
                    $query .= ' AND ' . $filterOption;
                }
                $stmt = sqlsrv_query($this->_connectionHandle, $query);
                if ($stmt === false) {            
                    $errorAsString = self::_getSQLSRVError();
        	        ODataException::createInternalServerError($errorAsString);
                }

                $result = $this->_serializeOrderDetails($stmt);
            } else {
                die('Order does not have navigation porperty with name: ' . $navigationPropName);
            }
        }

        return $result;
    }

    /**
     * Get related resource for a resource
     * 
     * @param ResourceSet      $sourceResourceSet    The source resource set
     * @param mixed            $sourceEntityInstance The source resource
     * @param ResourceSet      $targetResourceSet    The resource set of 
     *                                               the navigation property
     * @param ResourceProperty $targetProperty       The navigation property to be 
     *                                               retrieved
     * 
     * @return Object/null The related resource if exists else null
     */
    public function getRelatedResourceReference(ResourceSet $sourceResourceSet, 
        $sourceEntityInstance, 
        ResourceSet $targetResourceSet,
        ResourceProperty $targetProperty
    ) {
        $result = null;
        $srcClass = get_class($sourceEntityInstance);
        $navigationPropName = $targetProperty->getName();
        if ($srcClass === 'Order') {
            if ($navigationPropName === 'Customer') {
                if (empty($sourceEntityInstance->CustomerID)) {
                    $result = null;
                } else {                    
                    $query = "SELECT * FROM Customers WHERE CustomerID = '$sourceEntityInstance->CustomerID'";                
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if ($stmt === false) {
                        $errorAsString = self::_getSQLSRVError();
        	            ODataException::createInternalServerError($errorAsString);
                    }

                    if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }

                    $result = $this->_serializeCustomer(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
                }
            } else {
                die('Customer does not have navigation porperty with name: ' . $navigationPropName);
            }            
        } else if ($srcClass === 'Order_Details') {
            if ($navigationPropName === 'Order') {
                if (empty($sourceEntityInstance->OrderID)) {
                    $result = null;
                } else {
                    $query = "SELECT * FROM Orders WHERE OrderID = $sourceEntityInstance->OrderID";
                    $stmt = sqlsrv_query($this->_connectionHandle, $query);
                    if ($stmt === false) {
                        $errorAsString = self::_getSQLSRVError();
        	            ODataException::createInternalServerError($errorAsString);
                    }
                    
                    if (!sqlsrv_has_rows($stmt)) {
                        $result =  null;
                    }
                    
                    $result = $this->_serializeOrder(sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
                }
            } else {
                die('Order_Details does not have navigation porperty with name: ' . $navigationPropName);
            }
        } 

        return $result;
    }

    /**
     * Serialize the sql result array into Customer objects
     * 
     * @param array(array) $result result of the sql query
     * 
     * @return array(Object)
     */
    private function _serializeCustomers($result)
    {
        $customers = array();
        while ($record = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {         
             $customers[] = $this->_serializeCustomer($record);
        }

        return $customers;
    }

    /**
     * Serialize the sql row into Customer object
     * 
     * @param array $record each row of customer
     * 
     * @return Object
     */
    private function _serializeCustomer($record)
    {
        $customer = new Customer();
        $customer->CustomerID = $record['CustomerID'];
        $customer->CompanyName = $record['CompanyName'];
        $customer->ContactName = $record['ContactName'];
        $customer->ContactTitle = $record['ContactTitle'];
        $customer->Phone = $record['Phone'];
        $customer->Fax = $record['Fax'];        
        $customer->Address = new Address();
        $customer->Address->StreetName = ($record['Address']);
        $customer->Address->City = $record['City'];
        $customer->Address->Region = $record['Region'];
        $customer->Address->PostalCode = $record['PostalCode'];
        $customer->Address->Country = $record['Country'];
        //Set alternate address
        $customer->Address->AltAddress = new Address();
        $customer->Address->AltAddress->StreetName = 'ALT_' . $customer->Address->StreetName;
        $customer->Address->AltAddress->City = 'ALT_' . $customer->Address->City;
        $customer->Address->AltAddress->Region = 'ALT_' . $customer->Address->Region;
        $customer->Address->AltAddress->PostalCode = 'ALT_' . $customer->Address->PostalCode;
        $customer->Address->AltAddress->Country = 'ALT_' . $customer->Address->Country;
        $customer->EmailAddresses = array();
        for ($i = 1; $i < 4; $i++) {
            $customer->EmailAddresses[] = $customer->CustomerID . $i . '@live.com'; 
        }

        $customer->OtherAddresses = array();
        for ($i = 0; $i < 2; $i++) {
            $customer->OtherAddresses[$i] = new Address();
            $this->_copyAddress($customer->Address, $customer->OtherAddresses[$i], $i + 1);
        }
		
        return $customer;
    }
    
     /**
     * copy address
     * 
     * @param Object &$src    source
     * @param Object &$target target
     * @param Object $tag     tag
     * 
     * @return void
     */
    private function _copyAddress(&$src, &$target, $tag)
    {
        $target->StreetName = $src->StreetName . $tag;
        $target->City = $src->City . $tag;
        $target->Region = $src->Region . $tag;
        $target->PostalCode = $src->PostalCode . $tag;
        $target->Country = $src->Country . $tag;
        
        $target->AltAddress = new Address();
        $target->AltAddress->StreetName = $target->AltAddress->StreetName . $tag;
        $target->AltAddress->City = $target->AltAddress->City . $tag;
        $target->AltAddress->Region = $target->AltAddress->Region . $tag;
        $target->AltAddress->PostalCode = $target->AltAddress->PostalCode . $tag;
        $target->AltAddress->Country = $target->AltAddress->Country . $tag;
    }

    /**
     * Serialize the sql result array into Order objects
     * 
     * @param array(array) $result result of the sql query
     * 
     * @return array(Object)
     */
    private function _serializeOrders($result)
    {
        $orders = array();
        while ( $record = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
             $orders[] = $this->_serializeOrder($record);
        }

        return $orders;
    }

    /**
     * Serialize the sql row into Order object
     * 
     * @param array $record each row of customer
     * 
     * @return Object
     */
    private function _serializeOrder($record)
    {
        $order = new Order();
        $order->OrderID = $record['OrderID'];
        $order->CustomerID = $record['CustomerID'];
        $order->EmployeeID = $record['EmployeeID'];
        $order->OrderDate = !is_null($record['OrderDate']) ? $record['OrderDate']->format('Y-m-d\TH:i:s'): null;
        $order->RequiredDate = !is_null($record['RequiredDate']) ? $record['RequiredDate']->format('Y-m-d\TH:i:s'): null;
        $order->ShippedDate = !is_null($record['ShippedDate']) ? $record['ShippedDate']->format('Y-m-d\TH:i:s'): null;
        $order->ShipVia = $record['ShipVia'];
        $order->Freight = $record['Freight'];
        $order->ShipName = $record['ShipName'];
        $order->ShipAddress = $record['ShipAddress'];
        $order->ShipCity = $record['ShipCity'];
        $order->ShipRegion = $record['ShipRegion'];
        $order->ShipPostalCode = $record['ShipPostalCode'];
        $order->ShipCountry = $record['ShipCountry'];
        return $order;
    }

    /**
     * Serialize the sql result array into Employee objects
     * 
     * @param array(array) $result result of the sql query
     * 
     * @return array(Object)
     */
    private function _serializeEmployees($result)
    {
        $employees = array();
        while ($record = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
             $employees[] = $this->_serializeEmployee($record);
        }

        return $employees;
    }

    /**
     * Serialize the sql row into Employee object
     * 
     * @param array $record each row of employee
     * 
     * @return Object
     */
    private function _serializeEmployee($record)
    {
        $employee = new Employee();
        $employee->EmployeeID = $record['EmployeeID'];
        $employee->FirstName = $record['FirstName'];
        $employee->LastName = $record['LastName'];
        $employee->Title = $record['Title'];
        $employee->TitleOfCourtesy = $record['TitleOfCourtesy'];
        $employee->BirthDate = !is_null($record['BirthDate']) ? $record['BirthDate']->format('Y-m-d\TH:i:s'): null;
        $employee->HireDate = !is_null($record['HireDate']) ? $record['HireDate']->format('Y-m-d\TH:i:s'): null;        
        $employee->Address = $record['Address'];
        $employee->City = $record['City'];
        $employee->Region = $record['Region'];
        $employee->PostalCode = $record['PostalCode'];
        $employee->Country = $record['Country'];
        $employee->HomePhone = $record['HomePhone'];
        $employee->Extension = $record['Extension'];
        $employee->Notes = $record['Notes'];
        $employee->ReportsTo = $record['ReportsTo'];
        //$employee->Photo = $record['Photo'];
        $employee->Emails = array ($employee->FirstName . '@hotmail.com', $employee->FirstName . '@live.com');
        return $employee;
    }

    /**
     * Serialize the sql result array into Order_Details objects
     * 
     * @param array(array) $result result of the sql query
     * 
     * @return array(Object)
     */
    private function _serializeOrderDetails($result)
    {
        $order_details = array();
        while ($record = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {        
            $order_details[] = $this->_serializeOrderDetail($record);
        }

        return $order_details;
    }

    /**
     * Serialize the sql row into Order_Details object
     * 
     * @param array $record each row of order detail
     * 
     * @return Object
     */
    private function _serializeOrderDetail($record)
    {
        $order_details = new Order_Details();
        $order_details->Discount = $record['Discount'];
        $order_details->OrderID = $record['OrderID'];
        $order_details->ProductID = $record['ProductID'];
        $order_details->Quantity = $record['Quantity'];
        $order_details->UnitPrice = $record['UnitPrice'];
        return $order_details; 
    }

    
    /**
     * Gets the last sql server error as a string.
     *
     * @return string
     */
    private static function _getSQLSRVError()
    {
    	$result = null;
    	$errors = sqlsrv_errors();
    	self::_getSQLSRVError1($errors, $result);
    	return $result;
    }
    
    /**
     * Rescursive function to get the sql server error as string.
     *
     * @param array/string $errors
     * @param string $result
     */
    private static function _getSQLSRVError1($errors, &$result)
    {
    	if (is_array($errors)) {
    		foreach ($errors as $error) {
    			self::_getSQLSRVError1($error, $result);
    		}
    	} else {
    		$result .= $errors;
    	}
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