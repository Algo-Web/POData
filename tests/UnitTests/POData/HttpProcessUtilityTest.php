<?php

namespace UnitTests\POData\Common;

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
}
