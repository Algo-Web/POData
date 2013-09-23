<?php

namespace UnitTests\POData\Writers\Atom;




use POData\Providers\MetadataQueryProviderWrapper;
use POData\Writers\Json\JsonServiceDocumentWriter;
use UnitTests\POData\BaseUnitTestCase;
use Phockito;

class JsonServiceDocumentWriterTest extends BaseUnitTestCase
{

	/**
	 * @var MetadataQueryProviderWrapper
	 */
	protected $mockProvider;

	public function testGetOutputNoResourceSets()
	{
		Phockito::when($this->mockProvider->getResourceSets())
			->return(array());

		$writer = new JsonServiceDocumentWriter($this->mockProvider);
		$actual = $writer->getOutput();

		$expected = '{
    "d":{
        "EntitySet":[

        ]
    }
}';

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


		$writer = new JsonServiceDocumentWriter($this->mockProvider);
		$actual = $writer->getOutput();

		$expected = '{
    "d":{
        "EntitySet":[
            "Name 1","XML escaped stuff \" \' <> & ?"
        ]
    }
}';

		$this->assertEquals($expected, $actual);
	}

}