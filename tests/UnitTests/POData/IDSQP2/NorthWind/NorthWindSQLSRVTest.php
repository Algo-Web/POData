<?php

namespace UnitTests\POData\IDSQP2\NorthWind;

use POData\Common\Url;
use POData\Common\Version;
use POData\Providers\Metadata\Type\DateTime;
use UnitTests\POData\Facets\NorthWind4\NorthWindService;
use UnitTests\POData\Facets\ServiceHostTestFake;
use UnitTests\POData\TestCase;

class NorthWindSQLSRVTest extends TestCase
{
    /**
     * Test the generated string comparison expression in sql server.
     */
    public function testStringCompareSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=CustomerID gt \'ALFKI\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((CustomerID >  'ALFKI'))", $sqlexpression);
    }

    /**
     * Test the generated function-call expression in sql server.
     */
    public function testFunctionCallSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=replace(CustomerID, \'LFK\', \'RTT\') eq \'ARTTI\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $check = !is_null($uriProcessor);
        $this->assertTrue($check);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((REPLACE(CustomerID, 'LFK', 'RTT') =  'ARTTI'))", $sqlexpression);
    }

    /**
     * Test the generated expression for nullability check in sql server.
     */
    public function testNullabilityCheckSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=CustomerID eq null',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(CustomerID = NULL)', $sqlexpression);
    }

    /**
     * Test the generated expression for negation in sql server.
     */
    public function testNegationSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=-OrderID eq -10248',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(-(OrderID) = -10248)', $sqlexpression);
    }

    /**
     * Test the generated expression for datetime comaprsion in sql server.
     */
    public function testDateTimeComparisionSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=OrderDate eq datetime\'1996-07-04\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((OrderDate =  '1996-07-04'))", $sqlexpression);
    }

    /**
     * Test the generated expression for YEAR function call in sql server.
     */
    public function testYearFunctionCallSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=year(OrderDate) eq  year(datetime\'1996-07-09\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(YEAR(OrderDate) = YEAR('1996-07-09'))", $sqlexpression);
    }

    /**
     * Test the generated expression for YEAR function call with aritmetic and equality sql server.
     */
    public function testYearFunctionCallWithAriRelSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=year(OrderDate) add 2 eq 1998',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('((YEAR(OrderDate) + 2) = 1998)', $sqlexpression);
    }

    /**
     * Test the generated expression for ceil and floor sql server.
     */
    public function testCeilFloorFunctionCallSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=ceiling(floor(Freight)) eq 32',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(CEILING(FLOOR(Freight)) = 32)', $sqlexpression);
    }

    /**
     * Test the generated expression for round function-call for sql server.
     */
    public function testRoundFunctionCallSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=round(Freight) eq 34',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(ROUND(Freight, 0) = 34)', $sqlexpression);
    }

    /**
     * Test the generated expression for mod operator sql server.
     */
    public function testModOperatorSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Orders';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=Freight mod 10 eq 2.38',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('((Freight % 10) = 2.38)', $sqlexpression);
    }

    /**
     * Test the generated expression 2 param version of sub-string in sql server.
     */
    public function testSubString2ParamSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substring(CompanyName, 1) eq \'lfreds Futterkiste\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((SUBSTRING(CompanyName, 1 + 1, LEN(CompanyName)) =  'lfreds Futterkiste'))", $sqlexpression);
    }

    /**
     * Test the generated expression 3 param version of sub-string in sql server.
     */
    public function testSubString3ParamSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substring(CompanyName, 1, 6) eq \'lfreds\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((SUBSTRING(CompanyName, 1 + 1, 6) =  'lfreds'))", $sqlexpression);
    }

    /**
     * Test the generated expression trim in sql server.
     */
    public function testSubStringTrimSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=trim(\'  ALFKI  \') eq CustomerID',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((RTRIM(LTRIM('  ALFKI  ')) =  CustomerID))", $sqlexpression);
    }

    /**
     * Test the generated expression endswith function-call in sql server.
     */
    public function testEndsWithSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=endswith(CustomerID, \'KI\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(('KI') = RIGHT((CustomerID), LEN('KI')))", $sqlexpression);
    }

    /**
     * Test the generated expression startswith function-call in sql server.
     */
    public function testStartsWithSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=startswith(CustomerID, \'AL\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(('AL') = LEFT((CustomerID), LEN('AL')))", $sqlexpression);
    }

    /**
     * Test the generated expression indexof function-call in sql server.
     */
    public function testIndexOfSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=indexof(CustomerID, \'FKI\') eq 2',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((CHARINDEX('FKI', CustomerID) - 1) = 2)", $sqlexpression);
    }

    /**
     * Test the generated expression replace function-call in sql server.
     */
    public function testReplaceSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=replace(CompanyName, \' \', \'\') eq \'AlfredsFutterkiste\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((REPLACE(CompanyName, ' ', '') =  'AlfredsFutterkiste'))", $sqlexpression);
    }

    /**
     * Test the generated expression substringof function-call in sql server.
     */
    public function testSubStringOfSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substringof(\'Alfreds\', CompanyName)',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(CHARINDEX('Alfreds', CompanyName) != 0)", $sqlexpression);
    }

    /**
     * Test the generated expression substringof and indexof function-call in sql server.
     */
    public function testSubStringOfIndexOfSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substringof(\'Alfreds\', CompanyName) and indexof(CustomerID, \'FKI\') eq 2',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((CHARINDEX('Alfreds', CompanyName) != 0) AND ((CHARINDEX('FKI', CustomerID) - 1) = 2))", $sqlexpression);
    }

    /**
     * Test the generated expression concat function-call in sql server.
     */
    public function testSubConcatSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=concat(concat(CustomerID, \', \'), ContactName) eq \'ALFKI, Maria Anders\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((CustomerID + ', ' + ContactName =  'ALFKI, Maria Anders'))", $sqlexpression);
    }

    /**
     * Test the generated expression level 2 property access in sql server.
     */
    public function testLevel2PropertyAccessSQLServer()
    {
        $serviceUri = 'http://localhost:8083/NorthWindDataService.svc/';
        $resourcePath = 'Customers';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=Address/Country eq \'USA\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new NorthWindService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $sqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((Country =  'USA'))", $sqlexpression);
    }
}
