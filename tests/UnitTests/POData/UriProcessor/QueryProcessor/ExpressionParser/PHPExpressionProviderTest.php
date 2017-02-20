<?php

namespace UnitTests\POData\UriProcessor\QueryProcessor\ExpressionParser;

use Mockery as m;
use POData\Providers\Metadata\IMetadataProvider;
use POData\Providers\Metadata\Type\DateTime;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionParser2;
use POData\UriProcessor\QueryProcessor\ExpressionParser\ExpressionProcessor;
use UnitTests\POData\Facets\NorthWind1\Address2;
//These are in the file loaded by above use statement
//TODO: move to own class files
use UnitTests\POData\Facets\NorthWind1\Address4;
use UnitTests\POData\Facets\NorthWind1\Customer2;
use UnitTests\POData\Facets\NorthWind1\NorthWindMetadata;
use UnitTests\POData\Facets\NorthWind1\Order2;
use UnitTests\POData\TestCase;

class PHPExpressionProviderTest extends TestCase
{
    /**
     * @var IMetadataProvider
     */
    private $northWindMetadata;

    protected function setUp()
    {
        $this->northWindMetadata = NorthWindMetadata::Create();
    }

    /**
     * Test null checks are propagated properly.
     */
    public function testNullabilityChecking()
    {
        //Relational EQUAL expression with left child as arithmetic expression, the null check should propagte from AE to LE level
        $odataUriExpression = 'Customer/Address/LineNumber add 4 eq 8';
        $parser = new ExpressionParser2(
            $odataUriExpression,
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            true
        );
        $expressionTree = $parser->parseFilter();
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actualPHPExpression = $expressionProcessor->processExpression($expressionTree);
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (($lt->Customer->Address->LineNumber + 4) == 8))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Relational EQUAL expression with both children as arithmetic expression, the null check should propagte from AE to LE level
        $odataUriExpression = 'Customer/Address/LineNumber add Customer/Address/LineNumber2 eq 8';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && !(is_null($lt->Customer->Address->LineNumber2))) && (($lt->Customer->Address->LineNumber + $lt->Customer->Address->LineNumber2) == 8))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical AND expression with both child as relational expression, with left relational expressions having arithmetic expression
        //(with nullability check) as children, null check should propagate from AE to RE to LE.
        $odataUriExpression = 'Customer/Address/LineNumber add 2 eq 4 and 6 mul 7 eq 42';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && ((($lt->Customer->Address->LineNumber + 2) == 4) && ((6 * 7) == 42)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical AND expression with both child as relational expression, with right relational expressions having arithmetic expressions
        //(with nullability check) as children, null check should propagate from AE to RE to LE.
        $odataUriExpression = '6 mul 7 eq 42 and Customer/Address/LineNumber add 2 eq 4';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (((6 * 7) == 42) && (($lt->Customer->Address->LineNumber + 2) == 4)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical AND expression with both child as relational expression, with right and left relational expressions having arithmetic expressions
        //(with nullability check) as children, null check should propagate from both AE to RE to LE.
        $odataUriExpression = 'Customer/Address/LineNumber add 2 eq 4 and Customer/Address/LineNumber2 sub 2 ne 6';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && !(is_null($lt->Customer->Address->LineNumber2))) && ((($lt->Customer->Address->LineNumber + 2) == 4) && (($lt->Customer->Address->LineNumber2 - 2) != 6)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical OR expression with both child as relational expression, with left relational expressions having arithmetic expressions
        //(with nullability check) as children, null check should propagate from AE to RE only not to LE.
        $odataUriExpression = 'Customer/Address/LineNumber add 2 eq 4 or 6 mul 7 eq 42';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (($lt->Customer->Address->LineNumber + 2) == 4)) || ((6 * 7) == 42))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical OR expression with both child as relational expression, with right relational expressions having arithmetic expressions
        //(with nullability check) as children, null check should propagate from AE to RE only not to LE.
        $odataUriExpression = '6 mul 7 eq 42 or Customer/Address/LineNumber add 2 eq 4';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((6 * 7) == 42) || (((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (($lt->Customer->Address->LineNumber + 2) == 4)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical OR expression with both child as relational expression, both having relational expression (candidate for nullability check) as children,
        //null check should navigate from AE to RE only not to LE.
        $odataUriExpression = 'Customer/Address/LineNumber add 2 eq 4 or Customer/Address/LineNumber2 sub 2 ne 6';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (($lt->Customer->Address->LineNumber + 2) == 4)) || (((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber2))) && (($lt->Customer->Address->LineNumber2 - 2) != 6)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Relational expression as root
        $odataUriExpression = 'Customer/Address/Address2/IsPrimary eq true';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->Address2))) && !(is_null($lt->Customer->Address->Address2->IsPrimary))) && ($lt->Customer->Address->Address2->IsPrimary == true))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical AND expression without relational expression
        $odataUriExpression = 'Customer/Address/Address2/IsPrimary and Customer/Address/IsValid';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->Address2))) && !(is_null($lt->Customer->Address->Address2->IsPrimary))) && !(is_null($lt->Customer->Address->IsValid))) && ($lt->Customer->Address->Address2->IsPrimary && $lt->Customer->Address->IsValid))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical OR expression without relational expression
        $odataUriExpression = 'Customer/Address/Address2/IsPrimary or Customer/Address/IsValid';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->Address2))) && !(is_null($lt->Customer->Address->Address2->IsPrimary))) && $lt->Customer->Address->Address2->IsPrimary) || (((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->IsValid))) && $lt->Customer->Address->IsValid))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'Customer/Address/Address2/IsPrimary le Customer/Address/IsValid';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->Address2))) && !(is_null($lt->Customer->Address->Address2->IsPrimary))) && !(is_null($lt->Customer->Address->IsValid))) && ($lt->Customer->Address->Address2->IsPrimary <= $lt->Customer->Address->IsValid))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Relational expression with child as logical expression
        $odataUriExpression = '(Customer/Address/IsValid and true) eq false';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->IsValid))) && (($lt->Customer->Address->IsValid && true) == false))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Nullability check for property
        $odataUriExpression = 'Customer/Address/IsValid eq null';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && is_null($lt->Customer->Address->IsValid))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Property access expression as root
        $odataUriExpression = 'Customer/Address/IsValid';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->IsValid))) && $lt->Customer->Address->IsValid)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Unary NOT with Relational expressons as child
        $odataUriExpression = 'not(Customer/Address/LineNumber eq 4)';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && !(($lt->Customer->Address->LineNumber == 4)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Unary NOT with Logical AND expressons as child
        $odataUriExpression = 'not(Customer/Address/LineNumber add 2 eq 4 and true)';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && !(((($lt->Customer->Address->LineNumber + 2) == 4) && true)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

       //Unary NOT with Logical OR expressons as child
        $odataUriExpression = 'not(Customer/Address/LineNumber add 2 eq 4 or true)';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '!(((((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->LineNumber))) && (($lt->Customer->Address->LineNumber + 2) == 4)) || true))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        //Logical AND with not as child
        $odataUriExpression = 'not(Customer/Address/IsValid) and true';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(((!(is_null($lt->Customer)) && !(is_null($lt->Customer->Address))) && !(is_null($lt->Customer->Address->IsValid))) && (!($lt->Customer->Address->IsValid) && true))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);
    }

    /**
     * Test the possible string operators defined for filter option.
     */
    public function testStringFunctions()
    {
        $odataUriExpression = 'CustomerID ge \'ALFKI\'';
        $parser = new ExpressionParser2(
            $odataUriExpression,
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            true
        );
        $expressionTree = $parser->parseFilter();
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actualPHPExpression = $expressionProcessor->processExpression($expressionTree);
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strcmp($lt->CustomerID, \'ALFKI\') >= 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'endswith(CustomerID, \'KI\')';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strcmp(substr($lt->CustomerID, strlen($lt->CustomerID) - strlen(\'KI\')), \'KI\') === 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'indexof(CustomerID, \'LFK\') eq 2';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strpos($lt->CustomerID, \'LFK\') == 2))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'replace(CustomerID, \'LFK\', \'RTT\') eq \'ARTTI\'';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strcmp(str_replace(\'LFK\', \'RTT\', $lt->CustomerID), \'ARTTI\') == 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'startswith(CustomerID, \'AL\')';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strpos($lt->CustomerID, \'AL\') === 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'tolower(\'PeRsIsTeNt\') eq \'persistent\'';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(strcmp(strtolower(\'PeRsIsTeNt\'), \'persistent\') == 0)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'toupper(\'mICRosoFT\') eq \'MICROSOFT\'';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(strcmp(strtoupper(\'mICRosoFT\'), \'MICROSOFT\') == 0)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'trim(\'  ODataPHP Producer   \') eq null';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = 'is_null(trim(\'  ODataPHP Producer   \'))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'substring(\'Red_Black_Tree\', 3) ne \'Black_Tree\'';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(strcmp(substr(\'Red_Black_Tree\', 3), \'Black_Tree\') != 0)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'substring(\'Red_Black_Tree\', 3, 5) ne \'Black\'';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(strcmp(substr(\'Red_Black_Tree\', 3, 5), \'Black\') != 0)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'substringof(CustomerID, \'MRR\')';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->CustomerID)) && (strpos(\'MRR\', $lt->CustomerID) !== false))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'length(\'Red_Black_Tree\') eq 8';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(strlen(\'Red_Black_Tree\') == 8)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);
    }

    /**
     * Test the possible datetime operators defined for filter option.
     */
    public function testDateTimeFunctions()
    {
        $odataUriExpression = 'OrderDate eq datetime\'2010-12-08\'';
        $parser = new ExpressionParser2(
            $odataUriExpression,
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            true
        );
        $expressionTree = $parser->parseFilter();
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actualPHPExpression = $expressionProcessor->processExpression($expressionTree);
        $expectedPHPExpression = '(!(is_null($lt->OrderDate)) && (POData\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, \'2010-12-08\') == 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'OrderDate gt DeliveryDate';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((!(is_null($lt->OrderDate)) && !(is_null($lt->DeliveryDate))) && (POData\Providers\Metadata\Type\DateTime::dateTimeCmp($lt->OrderDate, $lt->DeliveryDate) > 0))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'OrderDate eq null';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = 'is_null($lt->OrderDate)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'OrderDate eq null eq true';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(is_null($lt->OrderDate) == true)';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'year(OrderDate) eq 2010';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->OrderDate)) && (POData\Providers\Metadata\Type\DateTime::year($lt->OrderDate) == 2010))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'month(OrderDate) eq month(DeliveryDate)';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '((!(is_null($lt->OrderDate)) && !(is_null($lt->DeliveryDate))) && (POData\Providers\Metadata\Type\DateTime::month($lt->OrderDate) == POData\Providers\Metadata\Type\DateTime::month($lt->DeliveryDate)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'month(OrderDate) eq 12 and day(OrderDate) eq 22';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->OrderDate)) && ((POData\Providers\Metadata\Type\DateTime::month($lt->OrderDate) == 12) && (POData\Providers\Metadata\Type\DateTime::day($lt->OrderDate) == 22)))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);
    }

    /**
     * Test guid opertor (guid comparison).
     */
    public function testGuidFunctions()
    {
        $odataUriExpression = 'Customer/CustomerGuid eq guid\'05b242e752eb46bd8f0e6568b72cd9a5\'';
        $parser = new ExpressionParser2(
            $odataUriExpression,
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            true
        );
        $expressionTree = $parser->parseFilter();
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actualPHPExpression = $expressionProcessor->processExpression($expressionTree);
        $expectedPHPExpression = '((!(is_null($lt->Customer)) && !(is_null($lt->Customer->CustomerGuid))) && (POData\Providers\Metadata\Type\Guid::guidEqual($lt->Customer->CustomerGuid, \'05b242e752eb46bd8f0e6568b72cd9a5\') == true))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);
    }

    /**
     * Test the possible math functions defined for filter option.
     */
    public function testMathFunctions()
    {
        $odataUriExpression = 'round(Price) eq 200.60';
        $parser = new ExpressionParser2(
            $odataUriExpression,
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            true
        );
        $expressionTree = $parser->parseFilter();
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actualPHPExpression = $expressionProcessor->processExpression($expressionTree);
        $expectedPHPExpression = '(!(is_null($lt->Price)) && (round($lt->Price) == 200.60))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);

        $odataUriExpression = 'ceiling(floor(Price) add 5) eq 345.90';
        $parser->resetParser($odataUriExpression);
        $actualPHPExpression = $expressionProcessor->processExpression($parser->parseFilter());
        $expectedPHPExpression = '(!(is_null($lt->Price)) && (ceil((floor($lt->Price) + 5)) == 345.90))';
        $this->assertEquals($expectedPHPExpression, $actualPHPExpression);
    }

    /**
     * Test expression provider using real data.
     */
    public function testAnonymousFunction()
    {
        //Creates test data
        $data = $this->createTestData();
        //Query for Customers with 'L' as second letter of CustomerID
        $result = $this->executeExpression(
            'indexof(CustomerID, \'L\') eq 1',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $this->assertEquals('ALFKI', $result[0]->CustomerID);

        //Query for Customers with country as Germany
        $result = $this->executeExpression(
            'Country eq \'Germany\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(2, count($result));
        $this->assertEquals('Germany', $result[0]->Country);
        $this->assertEquals('Germany', $result[1]->Country);

        //Query for Customers with no address
        $result = $this->executeExpression(
            'Address eq null',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $this->assertEquals('15b242e7-52eb-46bd-8f0e-6568b72cd9a6', $result[0]->CustomerGuid);

        //Query for Customers with non-primary address
        $result = $this->executeExpression(
            'Address/Address2/IsPrimary eq false',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $this->assertEquals('Ann Devon', $result[0]->CustomerName);

        //Query for Customers with ID 'ALFKI' or 'EASTC'
        $result = $this->executeExpression(
            'CustomerID eq \'ALFKI\' or CustomerID eq \'EASTC\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(2, count($result));
        $this->assertEquals('ALFKI', $result[0]->CustomerID);
        $this->assertEquals('EASTC', $result[1]->CustomerID);

        //Query for Customers with an expression which evaluates to false
        $result = $this->executeExpression(
            '1 add 2 eq 5',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(0, count($result));

        //Query for all Orders
        $result = $this->executeExpression(
            'true',
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            $data['Orders']
        );
        $this->assertEquals(5, count($result));

        //Query for Order with ShipName as 'Speedy Express'
        $result = $this->executeExpression(
            'ShipName eq \'Speedy Express\'',
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            $data['Orders']
        );
        $this->assertEquals(2, count($result));
        foreach ($result as $order) {
            $this->assertEquals($order->ShipName, 'Speedy Express');
        }

        //Query for Order with CustomerID as 'DUMON'
        $result = $this->executeExpression(
            'Customer/CustomerID eq \'DUMON\'',
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            $data['Orders']
        );
        $this->assertEquals(3, count($result));

        //Query for Orders with year of order as 1999 or 1995
        $result = $this->executeExpression(
            'year(OrderDate) eq 1999 or year(OrderDate) add 4 eq 1999',
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            $data['Orders']
        );
        foreach ($result as $order) {
            $this->assertContains($order->OrderDate, [1999, 1995]);
        }

        //Query for Orders with date greater than 2000-11-11
        $result = $this->executeExpression(
            'OrderDate ge datetime\'2000-11-11\'',
            $this->northWindMetadata->resolveResourceSet('Orders')->getResourceType(),
            $data['Orders']
        );
        foreach ($result as $order) {
            $this->assertGreaterThanOrEqual(0, DateTime::dateTimeCmp($order->OrderDate, '2000-11-11'));
        }

        //Query for Customer using different flavours of guid
        $result = $this->executeExpression(
            'CustomerGuid eq guid\'15b242e7-52eb-46bd-8f0e-6568b72cd9a6\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $customer1 = $result[0];

        $result = $this->executeExpression(
            'CustomerGuid eq guid\'{15b242e7-52eb-46bd-8f0e-6568b72cd9a6}\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $customer2 = $result[0];

        $result = $this->executeExpression(
            'CustomerGuid eq guid\'(15b242e7-52eb-46bd-8f0e-6568b72cd9a6)\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $customer3 = $result[0];

        $result = $this->executeExpression(
            'CustomerGuid eq guid\'15b242e752eb46bd8f0e6568b72cd9a6\'',
            $this->northWindMetadata->resolveResourceSet('Customers')->getResourceType(),
            $data['Customers']
        );
        $this->assertEquals(1, count($result));
        $customer4 = $result[0];
        $this->assertEquals($customer1->CustomerID, $customer2->CustomerID);
        $this->assertEquals($customer3->CustomerID, $customer4->CustomerID);
        $this->assertEquals($customer1->CustomerID, $customer4->CustomerID);
    }

    /**
     * Parse the astoria filter expression, generate the same expression as PHP expression,
     * retrieve only the entries which satisfies this expression.
     *
     * @param string         $astoriaFilter
     * @param ResourceType   $resourceType
     * @param array<objects> $entries
     *
     * @return array<objects>
     */
    private function executeExpression($astoriaFilter, $resourceType, $entries)
    {
        //Parse the Astoria filter query option to expression tree
        $parser = new ExpressionParser2($astoriaFilter, $resourceType, true);

        $expressionTree = $parser->parseFilter();
        //emit the PHP expression corresponds to Astoria filter query
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $phpExpression = $expressionProcessor->processExpression($expressionTree);
        //create an anonymous function with the generated PHP expression in if condition
        $fun = create_function('$lt', 'if('.$phpExpression.') { return true; } else { return false;}');
        $result = [];
        foreach ($entries as $lt) {
            //Filter out only the entries which satisfies the condition
            if ($fun($lt)) {
                $result[] = $lt;
            }
        }

        return $result;
    }

    public function testProcessUnknownAbstractExpressionType()
    {
        //Currently the expression parser just ignores expression types it doesn't know
        //TODO: maybe this should throw instead??
        $unknownExpression = m::mock('POData\UriProcessor\QueryProcessor\ExpressionParser\Expressions\AbstractExpression');
        $res = m::mock(\POData\Providers\Metadata\ResourceType::class)->makePartial();
        $res->shouldReceive('getName')->andReturn('OH NOES!');
        $phpProvider = new \POData\Providers\Expression\PHPExpressionProvider('$lt');
        $phpProvider->setResourceType($res);

        $expressionProcessor = new ExpressionProcessor($phpProvider);
        $actual = $expressionProcessor->processExpression($unknownExpression);

        $this->assertNull($actual);
    }

    /**
     * Prepare test data.
     *
     * @return array<key, array<objects>>
     */
    private function createTestData()
    {
        $customers = [];
        $orders = [];

        $customer = $this->createCustomer(
            'ALFKI',
            '05b242e7-52eb-46bd-8f0e-6568b72cd9a5',
            'Alfreds Futterkiste',
            $this->createAddress('AF34', 12, 15, 'Obere Str. 57', true, true),
            'Germany',
            1
        );
        $customers[] = $customer;
        $order = $this->createOrder(123, '2000-12-12', '2000-12-12', 'Speedy Express', 23, 4, 100.44);
        $orders[] = $order;
        $this->setCustomerOrder($customer, $order);
        $this->setOrderCustomer($order, $customer);
        $order = $this->createOrder(124, '1990-07-12', '1990-10-12', 'United Package', 100, 3, 200.44);
        $orders[] = $order;
        $this->setCustomerOrder($customer, $order);
        $this->setOrderCustomer($order, $customer);

        $customer = $this->createCustomer(
            'DUMON',
            '15b242e7-52eb-46bd-8f0e-6568b72cd9a6',
            'Janine Labrune',
            null, //Address is null
            'France',
            4
        );
        $customers[] = $customer;
        $order = $this->createOrder(125, '1995-05-05', '1995-05-09', 'Federal Shipping', 100, 1, 800);
        $orders[] = $order;
        $this->setCustomerOrder($customer, $order);
        $this->setOrderCustomer($order, $customer);
        $order = $this->createOrder(126, '1999-07-16', '1999-08-20', 'Speedy Express', 80, 2, 150);
        $orders[] = $order;
        $this->setCustomerOrder($customer, $order);
        $this->setOrderCustomer($order, $customer);
        $order = $this->createOrder(127, '2008-08-16', '2009-08-22', 'United Package', 88, 6, 50);
        $orders[] = $order;
        $this->setCustomerOrder($customer, $order);
        $this->setOrderCustomer($order, $customer);

        $customer = $this->createCustomer(
            'EASTC',
            '15b242e7-52eb-46bd-8f0e-6568b72cd9a7',
            'Ann Devon',
            $this->createAddress('FF45', 15, 16, '35 King George', true, false),
            'Germany',
            3
        );
        $customers[] = $customer;

        return ['Customers' => $customers, 'Orders' => $orders];
    }

    private function createAddress($houseNumber, $lineNumber, $lineNumber2, $streetName, $isValid, $isPrimary)
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

    private function createCustomer($customerID, $customerGuid, $customerName, $address, $country, $rating)
    {
        $customer = new Customer2();
        $customer->CustomerID = $customerID;
        $customer->CustomerGuid = $customerGuid;
        $customer->CustomerName = $customerName;
        $customer->Address = $address;
        $customer->Country = $country;
        $customer->Rating = $rating;
        $customer->Orders = null;

        return $customer;
    }

    private function createOrder($orderID, $orderDate, $deliveryDate, $shipName, $itemCount, $qualityRate, $price)
    {
        $order = new Order2();
        $order->Customer = null;
        $order->DeliveryDate = $deliveryDate;
        $order->ItemCount = $itemCount;
        $order->OrderDate = $orderDate;
        $order->ShipName = $shipName;
        $order->QualityRate = $qualityRate;
        $order->Price = $price;

        return $order;
    }

    private function setCustomerOrder($customer, $order)
    {
        if (is_null($customer->Orders)) {
            $customer->Orders = [];
        }

        $customer->Orders[] = $order;
    }

    private function setOrderCustomer($order, $customer)
    {
        $order->Customer = $customer;
    }
}
