<?php

namespace UnitTests\POData\IDSQP2\WordPress;

use POData\Common\Url;
use POData\Common\Version;
use POData\Providers\Metadata\Type\DateTime;
use UnitTests\POData\Facets\ServiceHostTestFake;
use UnitTests\POData\Facets\WordPress2\WordPressDataService;
use UnitTests\POData\TestCase;

class WordPressMySQLTest extends TestCase
{
    /**
     * test the generated string comparison expression in mysql.
     */
    public function testStringCompareMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=Title eq \'OData PHP Producer\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);
        $dataService->setHost($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(post_title, 'OData PHP Producer') = 0)", $mysqlexpression);
    }

    /**
     * test the generated function-call expression in sql server.
     */
    public function testFunctionCallMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=replace(Title, \'PHP\', \'Java\') eq \'OData Java Producer\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(REPLACE(post_title,'PHP','Java'), 'OData Java Producer') = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression for nullability check in sql server.
     */
    public function testNullabilityCheckMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=PostID eq  null',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(ID = NULL)', $mysqlexpression);
    }

    /**
     * test the generated expression for negation in sql server.
     */
    public function testNegationMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=-PostID eq -1',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(-(ID) = -1)', $mysqlexpression);
    }

    /**
     * test the generated expression for datetime comaprsion in sql server.
     */
    public function testDateTimeComparisionMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=Date eq datetime\'2011-12-24 19:54:00\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((post_date =  '2011-12-24 19:54:00'))", $mysqlexpression);
    }

    /**
     * test the generated expression for YEAR function call in sql server.
     */
    public function testYearFunctionCallMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=year(Date) eq  year(datetime\'1996-07-09\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(EXTRACT(YEAR from post_date) = EXTRACT(YEAR from '1996-07-09'))", $mysqlexpression);
    }

    /**
     * test the generated expression for YEAR function call with aritmetic and equality sql server.
     */
    public function testYearFunctionCallWtihAriRelMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=year(Date) add 2 eq 2013',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('((EXTRACT(YEAR from post_date) + 2) = 2013)', $mysqlexpression);
    }

    /**
     * test the generated expression for ceil and floor sql server.
     */
    public function testCeilFloorFunctionCallMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=ceiling(floor(PostID)) eq 2',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(CEIL(FLOOR(ID)) = 2)', $mysqlexpression);
    }

    /**
     * test the generated expression for round function-call for sql server.
     */
    public function testRoundFunctionCallMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=round(PostID) eq 1',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('(ROUND(ID) = 1)', $mysqlexpression);
    }

    /**
     * test the generated expression for mod operator sql server.
     */
    public function testModOperatorMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=PostID mod 5 eq 4',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals('((ID % 5) = 4)', $mysqlexpression);
    }

    /**
     * test the generated expression 2 param version of sub-string in sql server.
     */
    public function testSubString2ParamMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substring(Title, 1) eq \'Data PHP Producer\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(SUBSTRING(post_title, 1 + 1), 'Data PHP Producer') = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression 3 param version of sub-string in sql server.
     */
    public function testSubString3ParamMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
                'AbsoluteServiceUri'    => new Url($serviceUri),
                'AbsoluteRequestUri'    => new Url($requestUri),
                'QueryString'           => '$filter=substring(Title, 1, 6) eq \'Data P\'',
                'DataServiceVersion'    => new Version(3, 0),
                'MaxDataServiceVersion' => new Version(3, 0),
            ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(SUBSTRING(post_title, 1 + 1, 6), 'Data P') = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression trim in sql server.
     */
    public function testSubStringTrimMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=trim(\'  OData PHP Producer   \') eq Title',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(TRIM('  OData PHP Producer   '), post_title) = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression endswith function-call in sql server.
     */
    public function testEndsWithMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=endswith(Title, \'umer\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP('umer',RIGHT(post_title,LENGTH('umer'))) = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression startswith function-call in sql server.
     */
    public function testStartsWithMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=startswith(Title, \'OData\')',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP('OData',LEFT(post_title,LENGTH('OData'))) = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression indexof function-call in sql server.
     */
    public function testIndexOfMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=indexof(Title, \'ata\') eq 2',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(INSTR(post_title, 'ata') - 1 = 2)", $mysqlexpression);
    }

    /**
     * test the generated expression replace function-call in sql server.
     */
    public function testReplaceMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=replace(Title, \' \', \'\') eq \'ODataPHPProducer\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(REPLACE(post_title,' ',''), 'ODataPHPProducer') = 0)", $mysqlexpression);
    }

    /**
     * test the generated expression substringof function-call in sql server.
     */
    public function testSubStringOfMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substringof(\'Producer\', Title)',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(LOCATE('Producer', post_title) > 0)", $mysqlexpression);
    }

    /**
     * test the generated expression substringof and indexof function-call in sql server.
     */
    public function testSubStringOfIndexOfMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=substringof(\'Producer\', Title) and indexof(Title, \'Producer\') eq 11',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $this->assertNotNull($uriProcessor);

        $requestDescription = $uriProcessor->getRequest();
        $this->assertNotNull($requestDescription);

        $filterInfo = $requestDescription->getFilterInfo();
        $this->assertNotNull($filterInfo);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("((LOCATE('Producer', post_title) > 0) && (INSTR(post_title, 'Producer') - 1 = 11))", $mysqlexpression);
    }

    /**
     * test the generated expression concat function-call in sql server.
     */
    public function testSubConcatMySQL()
    {
        $serviceUri = 'http://localhost:8083/WordPressDataService.svc/';
        $resourcePath = 'Posts';
        $requestUri = $serviceUri.$resourcePath;
        $hostInfo = [
            'AbsoluteServiceUri'    => new Url($serviceUri),
            'AbsoluteRequestUri'    => new Url($requestUri),
            'QueryString'           => '$filter=concat(concat(Title, \', \'), \'Open source now\') eq \'OData .NET Producer, Open source now\'',
            'DataServiceVersion'    => new Version(3, 0),
            'MaxDataServiceVersion' => new Version(3, 0),
        ];

        $host = new ServiceHostTestFake($hostInfo);
        $dataService = new WordPressDataService($host);

        $uriProcessor = $dataService->handleRequest();
        $check = !is_null($uriProcessor);
        $this->assertTrue($check);

        $requestDescription = $uriProcessor->getRequest();
        $check = !is_null($requestDescription);
        $this->assertTrue($check);

        $filterInfo = $requestDescription->getFilterInfo();
        $check = !is_null($filterInfo);
        $this->assertTrue($check);

        $mysqlexpression = $filterInfo->getExpressionAsString();
        $this->AssertEquals("(STRCMP(CONCAT(CONCAT(post_title,', '),'Open source now'), 'OData .NET Producer, Open source now') = 0)", $mysqlexpression);
    }
}
