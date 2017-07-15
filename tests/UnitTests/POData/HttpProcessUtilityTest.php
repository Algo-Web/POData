<?php

namespace UnitTests\POData\Common;

use POData\Common\HttpHeaderFailure;
use POData\Common\MimeTypes;
use POData\HttpProcessUtility;
use UnitTests\POData\TestCase;

class HttpProcessUtilityTest extends TestCase
{
    public function testSelectMimeTypeEmptyAvailableTypes()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM,
            [

            ]
        );

        $this->assertNull($actual);
    }

    public function testSelectMimeTypeNoQValue()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM,
            [
                MimeTypes::MIME_APPLICATION_ATOM,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeTypeNoQValueMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM,
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeType1dot0QValueMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM.';q=1.0',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeType0dot0QValueMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM.';q=0.0',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertNull($actual);
    }

    public function testSelectMimeType0dot5QValueMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeTypeMultipleValuesSameQMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOM.';q=0.5, '.MimeTypes::MIME_APPLICATION_ATOMSERVICE.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeTypeMultipleValuesSameQReversedMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOMSERVICE.';q=0.5, '.MimeTypes::MIME_APPLICATION_ATOM.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeTypeMultipleValuesDifferentQMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOMSERVICE.';q=0.5, '.MimeTypes::MIME_APPLICATION_ATOM.';q=1.0',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOM, $actual);
    }

    public function testSelectMimeTypeMultipleValuesDifferentQReversedMultipleAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_ATOMSERVICE.';q=1.0, '.MimeTypes::MIME_APPLICATION_ATOM.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_ATOMSERVICE, $actual);
    }

    public function testSelectMimeTypeMultipleValuesWithODataPartNoneAvailable()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META
            .';q=1.0, '.MimeTypes::MIME_APPLICATION_JSON_FULL_META.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_ATOM,
                MimeTypes::MIME_APPLICATION_ATOMSERVICE,
            ]
        );

        $this->assertNull($actual);
    }

    public function testSelectMimeTypeMultipleValuesWithODataPartialSomeMatches()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META
            .';q=1.0, '.MimeTypes::MIME_APPLICATION_JSON_FULL_META.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META,
                MimeTypes::MIME_APPLICATION_JSON_FULL_META,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META, $actual);
    }

    public function testSelectMimeTypeMultipleValuesWithODataPartialSomeMatchesMissingODataPart()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META
            .';q=1.0, '.MimeTypes::MIME_APPLICATION_JSON_FULL_META.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_JSON,
            ]
        );

        $this->assertNull($actual);
    }

    public function testSelectMimeTypeMultipleValuesWithODataPartialSomeMatchesMissingODataPartSomeMatch()
    {
        $actual = HttpProcessUtility::selectMimeType(
            MimeTypes::MIME_APPLICATION_JSON_MINIMAL_META
            .';q=1.0, '.MimeTypes::MIME_APPLICATION_JSON_FULL_META.';q=0.5',
            [
                MimeTypes::MIME_APPLICATION_JSON,
                MimeTypes::MIME_APPLICATION_JSON_FULL_META,
            ]
        );

        $this->assertEquals(MimeTypes::MIME_APPLICATION_JSON_FULL_META, $actual);
    }

    public function testHeaderToServerKeyNotOnList()
    {
        $input = 'name';
        $expected = 'NAME';
        $actual = HttpProcessUtility::headerToServerKey($input);
        $this->assertEquals($expected, $actual);
    }

    public function testDigitToIntActualHttpSeparators()
    {
        $this->assertEquals(-1, HttpProcessUtility::digitToInt32(','));
        $this->assertEquals(-1, HttpProcessUtility::digitToInt32(' '));
        $this->assertEquals(-1, HttpProcessUtility::digitToInt32('\t'));
    }

    public function testDigitToIntBadData()
    {
        $expected = 'Malformed value in request header.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        try {
            HttpProcessUtility::digitToInt32('a');
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testBadQualityValueFirstDigit()
    {
        $expected = 'Malformed value in request header.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $qualText = '2.000';
        $qualDex = 0;
        $qualValue = 0;

        try {
            HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testBadQualityValueTooBig()
    {
        $expected = 'Malformed value in request header.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $qualText = '1.9 ';
        $qualDex = 0;
        $qualValue = 0;

        try {
            HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testGoodQualityValueOneDigit()
    {
        $qualText = '0.9 ';
        $qualDex = 0;
        $qualValue = 0;
        HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);

        $this->assertEquals(900, $qualValue);
    }

    public function testGoodQualityValueTwoDigit()
    {
        $qualText = '0.81 ';
        $qualDex = 0;
        $qualValue = 0;
        HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);

        $this->assertEquals(810, $qualValue);
    }

    public function testGoodQualityValueThreeDigit()
    {
        $qualText = "0.729\t";
        $qualDex = 0;
        $qualValue = 0;
        HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);

        $this->assertEquals(729, $qualValue);
    }

    public function testGoodQualityValueFourDigit()
    {
        $qualText = '0.6561';
        $qualDex = 0;
        $qualValue = 0;
        HttpProcessUtility::readQualityValue($qualText, $qualDex, $qualValue);

        $this->assertEquals(656, $qualValue);
    }

    public function testReadQuotedParameterValueWithoutClosingQuote()
    {
        $expected = 'Value for MIME type parameter \'parm\' is incorrect because the closing quote character'
                    .' could not be found while the parameter value started with a quote character.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $parameterName = 'parm';
        $headerText = '"';
        $textIndex = 0;

        try {
            HttpProcessUtility::readQuotedParameterValue($parameterName, $headerText, $textIndex);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testReadQuotedParameterValueWithIntermediateQuote()
    {
        $expected = 'Value for MIME type parameter \'parm\' is incorrect because it contained escape characters'
                    .' even though it was not quoted.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $parameterName = 'parm';
        $headerText = 'a"';
        $textIndex = 0;

        try {
            HttpProcessUtility::readQuotedParameterValue($parameterName, $headerText, $textIndex);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testReadQuotedParameterValueWithEscapeCharAtEnd()
    {
        $expected = 'Value for MIME type parameter \'parm\' is incorrect because it terminated with escape character.'
                    .' Escape characters must always be followed by a character in a parameter value.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $parameterName = 'parm';
        $headerText = '"\\';
        $textIndex = 0;

        try {
            HttpProcessUtility::readQuotedParameterValue($parameterName, $headerText, $textIndex);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testReadQuotedParameterValueWithQuotesAtBothEnds()
    {
        $expected = 'quoted';
        $actual = null;

        $parameterName = 'parm';
        $headerText = '"quoted"';
        $textIndex = 0;

        $actual = HttpProcessUtility::readQuotedParameterValue($parameterName, $headerText, $textIndex);
        $this->assertEquals($expected, $actual);
    }

    public function testReadMediaTypeSubtypeOnlyHasType()
    {
        $expected = 'Media type is unspecified.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = 400;

        $mediaString = 'application';
        $textIndex = 0;
        $type = '';
        $subtype = '';

        try {
            HttpProcessUtility::readMediaTypeAndSubtype($mediaString, $textIndex, $type, $subtype);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testReadMediaTypeSubtypeBadSeparator()
    {
        $expected = 'Media type requires a \'/\' character.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = 400;

        $mediaString = 'application(';
        $textIndex = 0;
        $type = '';
        $subtype = '';

        try {
            HttpProcessUtility::readMediaTypeAndSubtype($mediaString, $textIndex, $type, $subtype);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testReadMediaTypeSubtypeCompletelyMissingSubtype()
    {
        $expected = 'Media type requires a subtype definition.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = 400;

        $mediaString = 'application/';
        $textIndex = 0;
        $type = '';
        $subtype = '';

        try {
            HttpProcessUtility::readMediaTypeAndSubtype($mediaString, $textIndex, $type, $subtype);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testSelectRequiredMimeTypeWithAllParmsNull()
    {
        $this->assertNull(HttpProcessUtility::selectRequiredMimeType(null, null, null));
    }

    public function testSelectRequiredMimeTypeWithMalformedAcceptTypes()
    {
        $expected = 'Media type is unspecified.';
        $expectedCode = 400;
        $actual = null;
        $actualCode = null;

        $acceptTypesText = 'blahblah';

        try {
            HttpProcessUtility::selectRequiredMimeType($acceptTypesText, null, null);
        } catch (HttpHeaderFailure $e) {
            $actual = $e->getMessage();
            $actualCode = $e->getStatusCode();
        }
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedCode, $actualCode);
    }

    public function testSelectRequiredMimeTypeWithOnlyAcceptTypes()
    {
        $expected = 'Invalid argument supplied for foreach()';
        $actual = null;

        $acceptTypesText =  MimeTypes::MIME_APPLICATION_ATOM;

        try {
            HttpProcessUtility::selectRequiredMimeType($acceptTypesText, null, null);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSelectRequiredMimeTypeWithAcceptTypesAndEmptyExactTypes()
    {
        $expected = 'Unsupported media type requested.';
        $actual = null;

        $acceptTypesText =  MimeTypes::MIME_APPLICATION_ATOM;

        try {
            HttpProcessUtility::selectRequiredMimeType($acceptTypesText, [], null);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testSelectRequiredMimeTypeWithAcceptTypesAndSingletonMatchExactTypes()
    {
        $expected = MimeTypes::MIME_APPLICATION_ATOM;

        $acceptTypesText =  MimeTypes::MIME_APPLICATION_ATOM;
        $exactTypes = [MimeTypes::MIME_APPLICATION_ATOM];

        $actual = HttpProcessUtility::selectRequiredMimeType($acceptTypesText, $exactTypes, null);
        $this->assertEquals($expected, $actual);
    }

    public function testSelectRequiredMimeTypeWithAcceptTypesAndSingletonNoMatchExactTypes()
    {
        $expected = 'Unsupported media type requested.';
        $actual = null;

        $acceptTypesText =  MimeTypes::MIME_APPLICATION_JSON;
        $exactTypes = [MimeTypes::MIME_APPLICATION_ATOM];

        try {
            HttpProcessUtility::selectRequiredMimeType($acceptTypesText, $exactTypes, null);
        } catch (\Exception $e) {
            $actual = $e->getMessage();
        }
        $this->assertEquals($expected, $actual);
    }
}
