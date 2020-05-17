<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 17/05/20
 * Time: 10:37 PM
 */

namespace UnitTests\POData\Writers;

use Mockery as m;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\Providers\ProvidersWrapper;
use UnitTests\POData\TestCase;

/**
 * Class BaseWriterTest
 * @package UnitTests\POData\Writers
 */
class BaseWriterTest extends TestCase
{
    protected $mockProvider;

    public function setUp()
    {
        parent::setUp();
        $this->mockProvider = m::mock(ProvidersWrapper::class)->makePartial();
    }

    /**
     * @return ODataEntry
     */
    protected function buildSingleEntry(): ODataEntry
    {
        $entry1 = new ODataEntry();
        $entry1->id = 'http://services.odata.org/OData/OData.svc/Products(0)';
        $entry1->setSelfLink(new ODataLink('entry1 self link'));
        $entry1->title = 'title of entry 1';
        $entry1->editLink = 'edit link of entry 1';
        $entry1->type = 'DataServiceProviderDemo.Product';
        $entry1->eTag = '';

        $entry1->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    100
                ),
                new ODataProperty(
                    'Name',
                    'Edm.String',
                    'Bread'
                ),
                new ODataProperty(
                    'ReleaseDate',
                    'Edm.DateTime',
                    '2012-09-07T04:07:03'
                ),
                new ODataProperty(
                    'DiscontinuedDate',
                    'Edm.DateTime',
                    null
                ),
                new ODataProperty(
                    'Price',
                    'Edm.Double',
                    2.5
                ),
            ]
        );
        return $entry1;
    }

    /**
     * @return ODataEntry
     */
    protected function buildEntryWithComplexProperties(): ODataEntry
    {
        $entry1 = new ODataEntry();
        $entry1->id = 'http://services.odata.org/OData/OData.svc/Suppliers(0)';
        $entry1->setSelfLink(new ODataLink('entry1 self link'));
        $entry1->title = new ODataTitle('title of entry 1');
        $entry1->editLink = 'edit link of entry 1';
        $entry1->type = 'ODataDemo.Supplier';
        $entry1->eTag = 'W/"0"';

        $entry1->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    0
                ),
                new ODataProperty(
                    'Name',
                    'Edm.String',
                    'Exotic Liquids'
                ),
                new ODataProperty(
                    'Address',
                    'ODataDemo.Address',
                    new ODataPropertyContent(//complex type
                        [
                            new ODataProperty(
                                'Street',
                                'Edm.String',
                                'NE 228th'
                            ),
                            new ODataProperty(
                                'City',
                                'Edm.String',
                                'Sammamish'
                            ),
                            new ODataProperty(
                                'State',
                                'Edm.String',
                                'WA'
                            ),
                            new ODataProperty(
                                'ZipCode',
                                'Edm.String',
                                '98074'
                            ),
                            new ODataProperty(
                                'Country',
                                'Edm.String',
                                'USA'
                            )
                        ]
                    )
                ),
                new ODataProperty(
                    'Concurrency',
                    'Edm.Int16',
                    0
                )
            ]
        );
        return $entry1;
    }

    /**
     * @return ODataEntry
     */
    protected function buildSecondEntryWithComplexProperties(): ODataEntry
    {
        $entry2 = new ODataEntry();
        $entry2->id = 'http://services.odata.org/OData/OData.svc/Suppliers(1)';
        $entry2->setSelfLink(new ODataLink('entry2 self link'));
        $entry2->title = 'title of entry 2';
        $entry2->editLink = 'edit link of entry 2';
        $entry2->type = 'ODataDemo.Supplier';
        $entry2->eTag = 'W/"0"';

        $entry2->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    1
                ),
                new ODataProperty(
                    'Name',
                    'Edm.String',
                    'Tokyo Traders'
                ),
                new ODataProperty(
                    'Address',
                    'ODataDemo.Address',
                    new ODataPropertyContent( //complex type
                        [
                            new ODataProperty(
                                'Street',
                                'Edm.String',
                                'NE 40th'
                            ),
                            new ODataProperty(
                                'City',
                                'Edm.String',
                                'Redmond'
                            ),
                            new ODataProperty(
                                'State',
                                'Edm.String',
                                'WA'
                            ),
                            new ODataProperty(
                                'ZipCode',
                                'Edm.String',
                                '98052'
                            ),
                            new ODataProperty(
                                'Country',
                                'Edm.String',
                                'USA'
                            )
                        ]
                    )
                ),
                new ODataProperty(
                    'Concurrency',
                    'Edm.Int16',
                    0
                )
            ]
        );
        return $entry2;
    }

    /**
     * @return ODataEntry
     */
    protected function buildTestEntry(): ODataEntry
    {
        $entry = new ODataEntry();
        $entry->id = 'http://services.odata.org/OData/OData.svc/Categories(0)';
        $entry->setSelfLink(new ODataLink('entry2 self link'));
        $entry->title = new ODataTitle('title of entry 2');
        $entry->editLink = 'edit link of entry 2';
        $entry->type = 'ODataDemo.Category';
        $entry->eTag = '';
        $entry->resourceSetName = 'resource set name';


        $entry->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    0
                ),
                new ODataProperty(
                    'Name',
                    'Edm.String',
                    'Food'
                )
            ]
        );

        //links
        $entry->links = [
            new ODataLink(
                'Products',
                'Products',
                null,
                'http://services.odata.org/OData/OData.svc/Categories(0)/Products'
            )
        ];
        return $entry;
    }

    /**
     * @return ODataPropertyContent
     */
    protected function buildComplexProperty(): ODataPropertyContent
    {
        return new ODataPropertyContent(
            [
                new ODataProperty(
                    'Address',
                    'ODataDemo.Address',
                    new ODataPropertyContent(//complex type
                        [
                            new ODataProperty(
                                'Street',
                                'Edm.String',
                                'NE 228th'
                            ),
                            new ODataProperty(
                                'City',
                                'Edm.String',
                                'Sammamish'
                            ),
                            new ODataProperty(
                                'State',
                                'Edm.String',
                                'WA'
                            ),
                            new ODataProperty(
                                'ZipCode',
                                'Edm.String',
                                '98074'
                            ),
                            new ODataProperty(
                                'Country',
                                'Edm.String',
                                'USA'
                            )
                        ]
                    )
                )
            ]
        );
    }

    /**
     * @return ODataEntry
     */
    protected function buildEntryWithBagProperty(): ODataEntry
    {
        $entry = new ODataEntry();
        $entry->id = 'http://host/service.svc/Customers(1)';
        $entry->setSelfLink(new ODataLink('entry1 self link'));
        $entry->title = new ODataTitle('title of entry 1');
        $entry->editLink = 'edit link of entry 1';
        $entry->type = 'SampleModel.Customer';
        $entry->eTag = 'some eTag';
        $entry->resourceSetName = 'resource set name';


        $entry->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    1
                ),
                $entryProp2 = new ODataProperty(
                    'Name',
                    'Edm.String',
                    'mike'
                ),
                new ODataProperty(
                    'EmailAddresses',
                    'Bag(Edm.String)',
                    new ODataBagContent(
                        'Bag(Edm.String)',    //TODO: this might not be what really happens in the code..#61
                        [
                            'mike@foo.com',
                            'mike2@foo.com'
                        ]
                    )
                ),
                new ODataProperty(
                    'Addresses',
                    'Bag(SampleModel.Address)',
                    new ODataBagContent(
                        'Bag(SampleModel.Address)',//TODO: this might not be what really happens in the code..#61
                        [
                            new ODataPropertyContent(
                                [
                                    new ODataProperty(
                                        'Street',
                                        'Edm.String',
                                        '123 contoso street'
                                    ),
                                    new ODataProperty(
                                        'Apartment',
                                        'Edm.String',
                                        '508'
                                    )
                                ]
                            ),
                            new ODataPropertyContent(
                                [
                                    new ODataProperty(
                                        'Street',
                                        'Edm.String',
                                        '834 foo street'
                                    ),
                                    new ODataProperty(
                                        'Apartment',
                                        'Edm.String',
                                        '102'
                                    )
                                ]
                            ),
                        ]
                    )
                )
            ]
        );
        return $entry;
    }

    /**
     * @return ODataEntry
     */
    protected function buildEntryWithExpandedFeed(): ODataEntry
    {
        $expandedEntry1 = new ODataEntry();
        $expandedEntry1->id = 'Expanded Entry 1';
        $expandedEntry1->title = new ODataTitle('Expanded Entry 1 Title');
        $expandedEntry1->type = 'Expanded.Type';
        $expandedEntry1->editLink = 'Edit Link URL';
        $expandedEntry1->setSelfLink(new ODataLink('Self Link URL'));

        $expandedEntry1->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry1->links = [];
        $expandedEntry1->eTag = 'Entry ETag';
        $expandedEntry1->isMediaLinkEntry = false;

        $expandedEntry1->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Expanded Entry Complex Property',
                    'Full Name',
                    new ODataPropertyContent(
                        [
                            new ODataProperty(
                                'first',
                                'string',
                                'Entry 1 Name First'
                            ),
                            new ODataProperty(
                                'last',
                                'string',
                                'Entry 1 Name Last'
                            )
                        ]
                    )
                ),
                new ODataProperty(
                    'Expanded Entry City Property',
                    'string',
                    'Entry 1 City Value'
                ),
                new ODataProperty(
                    'Expanded Entry State Property',
                    'string',
                    'Entry 1 State Value'
                ),
            ]
        );
        //End the expanded entry 1

        //First build up the expanded entry 2
        $expandedEntry2 = new ODataEntry();
        $expandedEntry2->id = 'Expanded Entry 2';
        $expandedEntry2->title = new ODataTitle('Expanded Entry 2 Title');
        $expandedEntry2->type = 'Expanded.Type';
        $expandedEntry2->editLink = 'Edit Link URL';
        $expandedEntry2->setSelfLink(new ODataLink('Self Link URL'));

        $expandedEntry2->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry2->links = [];
        $expandedEntry2->eTag = 'Entry ETag';
        $expandedEntry2->isMediaLinkEntry = false;



        $expandedEntry2->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Expanded Entry Complex Property',
                    'Full Name',
                    new ODataPropertyContent(
                        [
                            new ODataProperty(
                                'first',
                                'string',
                                'Entry 2 Name First'
                            ),
                            new ODataProperty(
                                'last',
                                'string',
                                'Entry 2 Name Last'
                            )
                        ]
                    )
                ),
                new ODataProperty(
                    'Expanded Entry City Property',
                    'string',
                    'Entry 2 City Value'
                ),
                 new ODataProperty(
                    'Expanded Entry State Property',
                    'string',
                    'Entry 2 State Value'
                ),
            ]
        );
        //End the expanded entry 2

        //build up the main entry

        $entry = new ODataEntry();
        $entry->id = 'Main Entry';
        $entry->title = new ODataTitle('Entry Title');
        $entry->type = 'Main.Type';
        $entry->editLink = 'Edit Link URL';
        $entry->setSelfLink(new ODataLink('Self Link URL'));
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag = 'Entry ETag';
        $entry->isMediaLinkEntry = false;


        $entry->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Main Entry Property 1',
                    'string',
                    'Yash'
                ),
                new ODataProperty(
                    'Main Entry Property 2',
                    'string',
                    'Kothari'
                )
            ]
        );
        //End of main entry

        //Create a the expanded feed
        $expandedFeed = new ODataFeed();
        $expandedFeed->id = 'expanded feed id';
        $expandedFeed->title = new ODataTitle('SubCollection');
        $expandedFeed->entries = [
            $expandedEntry1,
            $expandedEntry2
        ];

        $expandedFeedSelfLink = new ODataLink('self', 'SubCollection', null, 'SubCollection Self URL');

        $expandedFeed->setSelfLink($expandedFeedSelfLink);

        //Now link the expanded entry to the main entry
        $entry->links = [
            new ODataLink(
                null,
                'SubCollection',
                null,
                'SubCollectionURL',
                true,
                new ODataExpandedResult($expandedFeed),
                true
            )
        ];
        return $entry;
    }

    /**
     * @return ODataEntry
     */
    protected function buildEntryWithExpandedEntry(): ODataEntry
    {
        $expandedEntry = new ODataEntry();
        $expandedEntry->id = 'Expanded Entry 1';
        $expandedEntry->title = 'Expanded Entry Title';
        $expandedEntry->type = 'Expanded.Type';
        $expandedEntry->editLink = 'Edit Link URL';
        $expandedEntry->setSelfLink(new ODataLink('Self Link URL'));

        $expandedEntry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $expandedEntry->links = [];
        $expandedEntry->eTag = 'Entry ETag';
        $expandedEntry->isMediaLinkEntry = false;
        
        $expandedEntry->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Expanded Entry Complex Property',
                    'Full Name',
                    new ODataPropertyContent(
                        [
                            new ODataProperty(
                                'fname',
                                'string',
                                'Yash'
                            ),
                            new ODataProperty(
                                'lname',
                                'string',
                                'Kothari'
                            )
                        ]
                    )
                ),
                new ODataProperty(
                    'Expanded Entry City Property',
                    'string',
                    'Ahmedabad'
                ),
                new ODataProperty(
                    'Expanded Entry State Property',
                    'string',
                    'Gujarat'
                ),
            ]
        );
        //End the expanded entry

        //build up the main entry

        $entry = new ODataEntry();
        $entry->id = 'Main Entry';
        $entry->title = 'Entry Title';
        $entry->type = 'Main.Type';
        $entry->editLink = 'Edit Link URL';
        $entry->setSelfLink(new ODataLink('Self Link URL'));
        $entry->mediaLinks = [
            new ODataMediaLink(
                'Media Link Name',
                'Edit Media link',
                'Src Media Link',
                'Media Content Type',
                'Media ETag'
            ),
            new ODataMediaLink(
                'Media Link Name2',
                'Edit Media link2',
                'Src Media Link2',
                'Media Content Type2',
                'Media ETag2'
            ),
        ];

        $entry->eTag = 'Entry ETag';
        $entry->isMediaLinkEntry = false;


        $entry->propertyContent = new ODataPropertyContent(
            [
                new ODataProperty(
                    'Main Entry Property 1',
                    'string',
                    'Yash'
                ),
                new ODataProperty(
                    'Main Entry Property 2',
                    'string',
                    'Kothari'
                )
            ]
        );
        //End of main entry

        //Now link the expanded entry to the main entry
        $entry->links = [new ODataLink(
            null,
            'Expanded Property',
            null,
            'ExpandedURL',
            false,
            new ODataExpandedResult($expandedEntry),
            true
        )];
        return $entry;
    }
}
