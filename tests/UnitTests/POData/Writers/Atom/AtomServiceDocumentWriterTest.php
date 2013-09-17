<?php

namespace UnitTests\POData\Writers\Atom;




use POData\Providers\MetadataQueryProviderWrapper;
use POData\Writers\Atom\AtomServiceDocumentWriter;
use UnitTests\POData\BaseUnitTestCase;
use Phockito;

class AtomServiceDocumentWriterTest extends BaseUnitTestCase
{

	/**
	 * @var MetadataQueryProviderWrapper
	 */
	protected $mockProvider;

	public function testGetOutputNoResourceSets()
	{
		Phockito::when($this->mockProvider->getResourceSets())
			->return(array());

		$fakeBaseURL = "http://some/place/some/where/" . uniqid();

		$writer = new AtomServiceDocumentWriter($this->mockProvider, $fakeBaseURL);
		$actual = $writer->getOutput();

		$expected = '<service xml:base="' . $fakeBaseURL . '" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns="http://www.w3.org/2007/app"><workspace><atom:title>Default</atom:title></workspace></service>';

		$this->assertEquals($expected, $actual);
	}


	public function testGetOutputTwoResourceSets()
	{

		$fakeResourceSet1 = Phockito::mock('POData\Providers\Metadata\ResourceSetWrapper');
		Phockito::when($fakeResourceSet1->getName())->return("Name 1");

		$fakeResourceSet2 = Phockito::mock('POData\Providers\Metadata\ResourceSetWrapper');
		//TODO: this certainly doesn't seem right...see #73
		Phockito::when($fakeResourceSet2->getName())->return("XML escaped stuff \" ' <> & ?");

		$fakeResourceSets = array(
			$fakeResourceSet1,
			$fakeResourceSet2,
		);

		Phockito::when($this->mockProvider->getResourceSets())
			->return($fakeResourceSets);

		$fakeBaseURL = "http://some/place/some/where/" . uniqid();

		$writer = new AtomServiceDocumentWriter($this->mockProvider, $fakeBaseURL);
		$actual = $writer->getOutput();

		$expected = '<service xml:base="' . $fakeBaseURL . '" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns="http://www.w3.org/2007/app">';
		$expected .= '<workspace><atom:title>Default</atom:title>';
		$expected .= '<collection href="Name 1"><atom:title>Name 1</atom:title></collection>';
		$expected .= '<collection href="XML escaped stuff &quot; \' &lt;&gt; &amp; ?"><atom:title>XML escaped stuff &quot; \' &lt;&gt; &amp; ?</atom:title></collection>';
		$expected .= '</workspace></service>';

		$this->assertEquals($expected, $actual);
	}

}