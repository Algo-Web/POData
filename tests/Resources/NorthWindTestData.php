<?php
require_once ("NorthWindMetadata.php");

$customers = array();
$orders = array();

$customer = createCustomer('ALFKI', 
                    '05b242e7-52eb-46bd-8f0e-6568b72cd9a5', 
                	'Alfreds Futterkiste', 
                    createAddress('AF34', 12, 15, 'Obere Str. 57', true, true), 
                	'Germany', 1);
$customers[] = $customer;                
$order = createOrder(123, '2000-12-12', '2000-12-12', 'Speedy Express', 23, 4, 100.44);
$orders[] = $order;
setCustomerOrder($customer, $order);
setOrderCustomer($order, $customer);
$order = createOrder(124, '1990-07-12', '1990-10-12', 'United Package', 100, 3, 200.44);
$orders[] = $order;
setCustomerOrder($customer, $order);
setOrderCustomer($order, $customer);

$customer = createCustomer('DUMON', 
                    '15b242e7-52eb-46bd-8f0e-6568b72cd9a6', 
                	'Janine Labrune', 
                    null, //Address is null
                	'France', 4);
$customers[] = $customer;                
$order = createOrder(125, '1995-05-05', '1995-05-09', 'Federal Shipping', 100, 1, 800);
$orders[] = $order;
setCustomerOrder($customer, $order);
setOrderCustomer($order, $customer);
$order = createOrder(126, '1999-07-16', '1999-08-20', 'Speedy Express', 80, 2, 150);
$orders[] = $order;
setCustomerOrder($customer, $order);
setOrderCustomer($order, $customer);
$order = createOrder(126, '2008-08-16', '2009-08-22', 'United Package', 88, 6, 50);
$orders[] = $order;
setCustomerOrder($customer, $order);
setOrderCustomer($order, $customer);


$customer = createCustomer('EASTC', 
                    '15b242e7-52eb-46bd-8f0e-6568b72cd9a7', 
                	'Ann Devon', 
                    createAddress('FF45', 15, 16, '35 King George', true, false), 
                	'Germany', 3);
$customers[] = $customer;                

print_r($customers);


function createAddress($houseNumber, $lineNumber, $lineNumber2, $streetName, $isValid, $isPrimary)
{
    $address = new Address4();
    $address->Address2 = new Address2();
    $address->Address2->IsPrimary = $isPrimary;
    $address->HouseNumber = $houseNumber;
    $address->IsValid = $isValid;
    $address->LineNumber = $lineNumber;
    $address->LineNumber2 = $lineNumber2;
    $address->StreetName = $streetName;
    return $address;
}

function createCustomer($customerID, $customerGuid, $customerName, $address, $country, $rating)
{
    $customer = new Customer2();
    $customer->CustomerID = $customerID;
    $customer->CustomerGuid = $customerGuid;
    $customer->CustomerName = $customerName;
    $customer->Address4 = $address;
    $customer->Country = $country;
    $customer->Rating = $rating;
    $customer->Orders = null;
    return $customer;
}

function createOrder($orderID, $orderDate, $deliveryDate, $shipName, $itemCount, $qualityRate, $price)
{
    $order = new Order2();
    $order->Customer2 = null;
    $order->DeliveryDate = $deliveryDate;
    $order->ItemCount = $itemCount;
    $order->OrderDate = $orderDate;
    $order->ShipName = $shipName;
    $order->QualityRate = $qualityRate;
    $order->Price = $price;
    return $order;
}

function setCustomerOrder($customer, $order)
{
    if (is_null($customer->Orders)) {
        $customer->Orders = array();
    }
    
    $customer->Orders[] = $order;
}


function setOrderCustomer($order, $customer)
{    
    $order->Customer = $customer;
}
?>