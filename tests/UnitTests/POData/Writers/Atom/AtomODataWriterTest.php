<?php

declare(strict_types=1);

namespace UnitTests\POData\Writers\Atom;

use Carbon\Carbon as Carbon;
use Mockery as m;
use POData\Common\MimeTypes;
use POData\Common\ODataConstants;
use POData\Common\ODataException;
use POData\Common\Version;
use POData\Configuration\ServiceConfiguration;
use POData\ObjectModel\ODataBagContent;
use POData\ObjectModel\ODataCategory;
use POData\ObjectModel\ODataEntry;
use POData\ObjectModel\ODataExpandedResult;
use POData\ObjectModel\ODataFeed;
use POData\ObjectModel\ODataLink;
use POData\ObjectModel\ODataMediaLink;
use POData\ObjectModel\ODataProperty;
use POData\ObjectModel\ODataPropertyContent;
use POData\ObjectModel\ODataTitle;
use POData\ObjectModel\ODataURL;
use POData\ObjectModel\ODataURLCollection;
use POData\Providers\Metadata\ResourceFunctionType;
use POData\Providers\Metadata\ResourceSetWrapper;
use POData\Providers\Metadata\Type\DateTime;
use POData\Providers\ProvidersWrapper;
use POData\Writers\Atom\AtomODataWriter;
use UnitTests\POData\Writers\BaseWriterTest;

/**
 * Class AtomODataWriterTest.
 * @package UnitTests\POData\Writers\Atom
 */
class AtomODataWriterTest extends BaseWriterTest
{
    /**
     * Removes the updated tag from an XML string
     * IE <updated>2013-09-17T19:22:33-06:00</updated>.
     *
     * @param string     $xml
     * @param null|mixed $new
     *
     * @return string
     */
    public function removeUpdatedTags($xml, $new = null)
    {
        if (!isset($new)) {
            $new = '';
        }

        $xml = preg_replace('/<updated>.*?<\/updated>/i', '<updated>' . $new . '</updated>', $xml);

        return $xml;
    }

    /**
     * Test for write top level URI item.
     */
    public function testODataURLItem()
    {
        $url      = new ODataURL('http://www.odata.org/developers/protocols/atom-format');

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($url);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
		<uri xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices">http://www.odata.org/developers/protocols/atom-format</uri>';

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Test for write top level Collection of URL item.
     */
    public function testODataURLCollectionItem()
    {
        $url1      = new ODataURL('http://www.odata.org/developers/protocols/atom-format');
        $url2      = new ODataURL('http://www.odata.org/developers/protocols/json-format');

        $urls       = new ODataURLCollection(
            [$url1, $url2],
            new ODataLink('Next', '', '', 'Next Link Url'),
            10
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($urls);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<links xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices" 
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <m:count>10</m:count>
 <uri>http://www.odata.org/developers/protocols/atom-format</uri>
 <uri>http://www.odata.org/developers/protocols/json-format</uri>
 <link rel="Next" href="Next Link Url"/>
</links>';

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Test for write top level feed item.
     */
    public function testWriteFeed()
    {
        $testNow = Carbon::create(2013, 9, 17, 19, 22, 33);
        Carbon::setTestNow($testNow);

        $feed           = new ODataFeed();
        $feed->id       = 'Feed Id';
        $feed->setRowCount(50);

        $feed->setSelfLink(new ODataLink('Self Link Name', '', '', 'Self Link Url'));


        $feed->setNextPageLink(new ODataLink('Next', '', '', 'Next Link Url'));
        $feed->setTitle(new ODataTitle('Feed Title'));

        // Entry 1

        $entry1        = new ODataEntry();
        $entry1->id    = 'Entry 1';
        $entry1->setTitle(new ODataTitle('Entry Title'));

        $editLink        = new ODataLink('edit', 'Edit Link Title', 'Edit link type', 'Edit Link URL');

        $entry1->editLink = $editLink;

        $selfLink        = new ODataLink('self', 'self Link Title', '', 'Self Link URL');

        $entry1->setSelfLink($selfLink);

        $entry1->mediaLinks = [new ODataMediaLink(
            'Media Link Name',
            'Edit Media link',
            'Src Media Link',
            'Media Content Type',
            'Media ETag'
        )];
        $link        = new ODataLink('Link Name', 'Link Title', 'Link Type', 'Link URL');

        $entry1->links            = [];
        $entry1->eTag             = 'Entry ETag';
        $link->setIsExpanded(false);
        $entry1->isMediaLinkEntry = false;


        $propCont1_1 = new ODataPropertyContent(
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
        );

        $entry1->propertyContent =  new ODataPropertyContent(
            [
                new ODataProperty(
                    'name',
                    'Bag(Name)',
                    new ODataBagContent(
                        '',
                        [
                            new ODataPropertyContent(
                                [
                                    new ODataProperty(
                                        'name',
                                        null,
                                        $propCont1_1
                                    ),
                                    new ODataProperty(
                                        'name',
                                        null,
                                        $propCont1_1
                                    )
                                ]
                            )
                        ]
                    )
                )
            ]
        );
        $entry1->type            = new ODataCategory('');

        $feed->setEntries(
            [$entry1]
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($feed);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<feed xml:base="http://localhost/NorthWind.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom">
 <title type="text">Feed Title</title>
 <id>Feed Id</id>
 <updated>2013-09-17T19:22:33-06:00</updated>
 <link rel="Self Link Name" href="Self Link Url"/>
 <m:count>50</m:count>
 <entry m:etag="Entry ETag">
  <id>Entry 1</id>
  <title type="text">Entry Title</title>
  <updated>2013-09-17T19:22:33-06:00</updated>
  <author>
   <name/>
  </author>
  <link rel="edit" title="Entry Title" href="Edit Link URL"/>
  <category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
  <content type="application/xml">
   <m:properties>
    <d:name m:type="Bag(Name)">
     <d:element>
      <d:name>
       <d:fname m:type="string">Yash</d:fname>
       <d:lname m:type="string">Kothari</d:lname>
      </d:name>
      <d:name>
       <d:fname m:type="string">Yash</d:fname>
       <d:lname m:type="string">Kothari</d:lname>
      </d:name>
     </d:element>
    </d:name>
   </m:properties>
  </content>
 </entry>
 <link rel="Next" href="Next Link Url"/>
</feed>
';

        $new      = '2013-09-17T19:22:33-06:00';
        $expected = $this->removeUpdatedTags($expected, $new);
        $actual   = $this->removeUpdatedTags($actual, $new);
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Test for top level Entry Item with media link.
     */
    public function testWriteMediaEntry()
    {
        $entry1        = new ODataEntry();
        $entry1->id    = 'Entry 1';
        $entry1->setTitle(new ODataTitle('Entry Title'));

        $editLink        = new ODataLink('edit', 'Edit Link Title', 'Edit link type', 'Edit Link URL');

        $entry1->editLink = $editLink;

        $selfLink        = new ODataLink('self', 'self Link Title', '', 'Self Link URL');
        $etag            = time();
        $entry1->setSelfLink($selfLink);
        $entry1->mediaLink  = new ODataMediaLink('Thumbnail_600X450', 'http://storage.live.com/123/christmas-tree-with-presents.jpg', 'http://cdn-8.nflximg.com/US/boxshots/large/5632678.jpg', 'image/jpg', $etag);
        $entry1->mediaLinks = [new ODataMediaLink(
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
            ), ];

        $entry1->links = [];

        $entry1->eTag             = 'Entry ETag';
        $entry1->isMediaLinkEntry = true;

        $propCont                = new ODataPropertyContent([]);
        $entry1->propertyContent = $propCont;
        $entry1->type            = new ODataCategory('');
        $now                     = new Carbon('2020-05-17T08:03:24-06:00');
        Carbon::setTestNow($now);
        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($entry1);
        $this->assertSame($writer, $result);

        $actual   = $writer->getOutput();
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/NorthWind.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom" m:etag="Entry ETag">
    <id>Entry 1</id>
    <title type="text">Entry Title</title>
    <updated>2020-05-17T08:03:24-06:00</updated>
    <author>
        <name/>
    </author>
    <link rel="edit" title="Entry Title" href="Edit Link URL"/>
    <link m:etag="' . $etag . '" rel="edit-media" type="image/jpg" title="Thumbnail_600X450" href="http://storage.live.com/123/christmas-tree-with-presents.jpg"/>
    <link m:etag="Media ETag" rel="http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/Media Link Name" type="Media Content Type" title="Media Link Name" href="Edit Media link"/>
    <link m:etag="Media ETag2" rel="http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/Media Link Name2" type="Media Content Type2" title="Media Link Name2" href="Edit Media link2"/>
    <category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
    <content type="image/jpg" src="http://cdn-8.nflximg.com/US/boxshots/large/5632678.jpg"/>
    <m:properties/>
</entry>
';

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Test for top level Entry Item.
     */
    public function testWriteEntry()
    {
        $entry1        = new ODataEntry();
        $entry1->id    = 'Entry 1';
        $entry1->setTitle(new ODataTitle('Entry Title'));

        $editLink        = new ODataLink('edit', 'Edit Link Title', 'Edit link type', 'Edit Link URL');

        $entry1->editLink = $editLink;

        $selfLink        = new ODataLink('self', 'self Link Title', '', 'Self Link URL');
        $etag            = time();
        $entry1->setSelfLink($selfLink);
        $entry1->mediaLink  = new ODataMediaLink('Thumbnail_600X450', 'http://storage.live.com/123/christmas-tree-with-presents.jpg', null, 'image/jpg', $etag);
        $entry1->mediaLinks = [new ODataMediaLink(
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
            ), ];

        $link             = new ODataLink('Link Name', 'Link Title', 'Link Type', 'Link URL', null, null);
        $link->setIsExpanded(false);

        $entry1->links = [$link];

        $entry1->eTag             = 'Entry ETag';
        $entry1->isMediaLinkEntry = true;

        $prop1 = new ODataProperty(
            'name',
            'Bag(Name)',
            new ODataBagContent(
                '',
                [
                    new ODataPropertyContent(
                        [
                            'name' => new ODataProperty(
                                'name',
                                null,
                                new ODataPropertyContent(
                                    [
                                        'fname' => new ODataProperty(
                                            'fname',
                                            'string',
                                            'Yash'
                                        ),
                                        'lname' =>new ODataProperty(
                                            'lname',
                                            'string',
                                            'Kothari'
                                        )
                                    ]
                                )
                            ),
                            'name1' => new ODataProperty(
                                'name1',
                                null,
                                new ODataPropertyContent(
                                    [
                                        'fname' => new ODataProperty(
                                            'fname',
                                            'string',
                                            'Anu'
                                        ),
                                        'lname' => new ODataProperty(
                                            'lname',
                                            'string',
                                            'Chandy'
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        );

        $prop3 = new ODataProperty(
            'Address',
            'Address',
            new ODataPropertyContent(
                [
                    'House_num' => new ODataProperty(
                        'House_num',
                        'Int',
                        '31'
                    ),
                    'Street_name' => new ODataProperty(
                        'Street_name',
                        'String',
                        'Ankur Road'
                    )
                ]
            )
        );
        $prop4 =  new ODataProperty(
            'Pin_Num',
            'Int',
            '380013'
        );

        $prop5 = new ODataProperty(
            'Phon_num',
            'Int',
            '9665-043-347'
        );

        $prop6 = new ODataProperty(
            'Addresses',
            'Bag(Address)',
            new ODataBagContent(
                '',
                [
                    new ODataPropertyContent(
                        [
                            'Address' => new ODataProperty(
                                'Address',
                                '',
                                new ODataPropertyContent(
                                    [
                                        'Flat_no' => new ODataProperty(
                                            'Flat_no',
                                            '',
                                            '31'
                                        ),
                                        'Street_name' => new ODataProperty(
                                            'Street_name',
                                            '',
                                            'Ankur'
                                        ),
                                        'City' => new ODataProperty(
                                            'City',
                                            '',
                                            'Ahmedabad'
                                        )
                                    ]
                                )
                            ),
                            'Address1' => new ODataProperty(
                                'Address1',
                                '',
                                new ODataPropertyContent(
                                    [
                                        'Flat_no' => new ODataProperty(
                                            'Flat_no',
                                            '',
                                            '101'
                                        ),
                                        'Street_name' => new ODataProperty(
                                            'Street_name',
                                            '',
                                            'Nal Stop'
                                        ),
                                        'City' => new ODataProperty(
                                            'City',
                                            '',
                                            'Pune'
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        );

        $prop_address = new ODataProperty(
            'Addressess',
            'Bag(SampleModel.Address)',
            new ODataBagContent(
                '',
                [
                    new ODataPropertyContent(
                        [
                            'Addresses' => new ODataProperty(
                                'Addresses',
                                '',
                                new ODataPropertyContent(
                                    [
                                        'Street' => new ODataProperty(
                                            'Street',
                                            'String',
                                            '123 contoso street'
                                        ),
                                        'Appartments' => new ODataProperty(
                                            'Appartments',
                                            '',
                                            new ODataPropertyContent(
                                                [
                                                    'apartment1' => new ODataProperty(
                                                        'apartment1',
                                                        'String',
                                                        'taj residency'
                                                    ),
                                                    'apartment2' => new ODataProperty(
                                                        'apartment2',
                                                        'String',
                                                        'le-merdian'
                                                    )
                                                ]
                                            )
                                        )
                                    ]
                                )
                            ),
                            'Address' => new ODataProperty(
                                'Address',
                                '',
                                new ODataPropertyContent(
                                    [
                                        'Street' => new ODataProperty(
                                            'Street',
                                            'String',
                                            '834 foo street'
                                        ),
                                        'Appartment' => new ODataProperty(
                                            'Appartment',
                                            '',
                                            ''
                                        )
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        );

        $propCont             = new ODataPropertyContent(
            [
                'name' => $prop1,
                //$prop2,
                'Address' => $prop3,
                'Pin_Num' => $prop4,
                'Phon_num' => $prop5    ,
                'Addresses' => $prop6,
                'Addressess' => $prop_address
            ]
        );
        $entry1->propertyContent = $propCont;
        $entry1->type            = new ODataCategory('');

        $now = new Carbon('2020-05-17T08:03:24-06:00');
        Carbon::setTestNow($now);

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($entry1);
        $this->assertSame($writer, $result);

        $actual   = $writer->getOutput();
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/NorthWind.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom" m:etag="Entry ETag">
    <id>Entry 1</id>
    <title type="text">Entry Title</title>
    <updated>2020-05-17T08:03:24-06:00</updated>
    <author>
        <name/>
    </author>
    <link rel="edit" title="Entry Title" href="Edit Link URL"/>
    <link m:etag="' . $etag . '" rel="edit-media" type="image/jpg" title="Thumbnail_600X450" href="http://storage.live.com/123/christmas-tree-with-presents.jpg"/>
    <link m:etag="Media ETag" rel="http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/Media Link Name" type="Media Content Type" title="Media Link Name" href="Edit Media link"/>
    <link m:etag="Media ETag2" rel="http://schemas.microsoft.com/ado/2007/08/dataservices/mediaresource/Media Link Name2" type="Media Content Type2" title="Media Link Name2" href="Edit Media link2"/>
    <link rel="Link Name" type="Link Type" title="Link Title" href="Link URL"/>
    <category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
    <content type="image/jpg" src=""/>
    <m:properties>
        <d:name m:type="Bag(Name)">
            <d:element>
                <d:name>
                    <d:fname m:type="string">Yash</d:fname>
                    <d:lname m:type="string">Kothari</d:lname>
                </d:name>
                <d:name1>
                    <d:fname m:type="string">Anu</d:fname>
                    <d:lname m:type="string">Chandy</d:lname>
                </d:name1>
            </d:element>
        </d:name>
        <d:Address m:type="Address">
            <d:House_num m:type="Int">31</d:House_num>
            <d:Street_name m:type="String">Ankur Road</d:Street_name>
        </d:Address>
        <d:Pin_Num m:type="Int">380013</d:Pin_Num>
        <d:Phon_num m:type="Int">9665-043-347</d:Phon_num>
        <d:Addresses m:type="Bag(Address)">
            <d:element>
                <d:Address>
                    <d:Flat_no>31</d:Flat_no>
                    <d:Street_name>Ankur</d:Street_name>
                    <d:City>Ahmedabad</d:City>
                </d:Address>
                <d:Address1>
                    <d:Flat_no>101</d:Flat_no>
                    <d:Street_name>Nal Stop</d:Street_name>
                    <d:City>Pune</d:City>
                </d:Address1>
            </d:element>
        </d:Addresses>
        <d:Addressess m:type="Bag(SampleModel.Address)">
            <d:element>
                <d:Addresses>
                    <d:Street m:type="String">123 contoso street</d:Street>
                    <d:Appartments>
                        <d:apartment1 m:type="String">taj residency</d:apartment1>
                        <d:apartment2 m:type="String">le-merdian</d:apartment2>
                    </d:Appartments>
                </d:Addresses>
                <d:Address>
                    <d:Street m:type="String">834 foo street</d:Street>
                    <d:Appartment m:null="true"/>
                </d:Address>
            </d:element>
        </d:Addressess>
    </m:properties>
</entry>';

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Test for top level Entry Item with Expand.
     */
    public function testWriteExpandEntry()
    {
        $entry        = new ODataEntry();
        $entry->id    = 'Expand Entry';
        $entry->setTitle(new ODataTitle('Entry Title'));
        $entry->type  = new ODataCategory('');

        $editLink        = new ODataLink('edit', 'Edit Link Title', 'Edit link type', 'Edit Link URL');


        $entry->editLink = $editLink;

        $selfLink        = new ODataLink('self', 'self Link Title', '', 'Self Link URL');


        $entry->setSelfLink($selfLink);
        $entry->mediaLinks = [new ODataMediaLink(
            'Media Link Name',
            'Edit Media link',
            'Src Media Link',
            'Media Content Type',
            'Media ETag'
        ), new ODataMediaLink(
            'Media Link Name2',
            'Edit Media link2',
            'Src Media Link2',
            'Media Content Type2',
            'Media ETag2'
        )];

        $odataLink               = new ODataLink();
        $odataLink->setIsCollection(false);
        $odataLink->setIsExpanded(true);
        $odataExpandEntry        = new ODataEntry();

        $odataExpandEntry->id    = 'Entry 1';
        $odataExpandEntry->setTitle(new ODataTitle('Entry Title'));

        $editLink        = new ODataLink('edit', 'Edit Link Title', 'Edit link type', 'Edit Link URL');

        $odataExpandEntry->editLink = $editLink;

        $selfLink        = new ODataLink('self', 'self Link Title', '', 'Self Link URL');

        $odataExpandEntry->setSelfLink($selfLink);

        $odataExpandEntry->mediaLinks = [new ODataMediaLink(
            'Media Link Name',
            'Edit Media link',
            'Src Media Link',
            'Media Content Type',
            'Media ETag'
        ), new ODataMediaLink(
            'Media Link Name2',
            'Edit Media link2',
            'Src Media Link2',
            'Media Content Type2',
            'Media ETag2'
        )];

        $link             = new ODataLink('Link Name', 'Link Title', 'Link Type', 'Link URL');
        $link->setIsExpanded(false);

        $odataExpandEntry->links            = [];
        $odataExpandEntry->eTag             = 'Entry ETag';
        $odataExpandEntry->isMediaLinkEntry = false;

        $odataExpandEntry->propertyContent = new ODataPropertyContent(
            [
                'name' => new ODataProperty(
                    'name',
                    'string',
                    new ODataPropertyContent(
                        [
                            'fname' => new ODataProperty(
                                'fname',
                                'string',
                                'Yash'
                            ),
                            'lname' => new ODataProperty(
                                'lname',
                                'string',
                                'Kothari'
                            )
                        ]
                    )
                ),
                'city' => new ODataProperty(
                    'city',
                    'string',
                    'Ahmedabad'
                ),
                'state' => new ODataProperty(
                    'state',
                    'string',
                    'Gujarat'
                ),
            ]
        );
        $odataExpandEntry->type            = new ODataCategory('');

        $odataLink->setExpandedResult(new ODataExpandedResult($odataExpandEntry));

        $entry->links            = [$odataLink];
        $entry->eTag             = 'Entry ETag';
        $entry->isMediaLinkEntry = false;


        $entry->propertyContent = new ODataPropertyContent(
            [
                'name' => new ODataProperty(
                    'name',
                    'Bag(Name)',
                    new ODataBagContent(
                        '',
                        [
                            new ODataPropertyContent(
                                [
                                    new ODataProperty(
                                        'name',
                                        null,
                                        new ODataPropertyContent(
                                            [
                                                'fname' => new ODataProperty(
                                                    'fname',
                                                    'string',
                                                    'Yash'
                                                ),
                                                'lname' => new ODataProperty(
                                                    'lname',
                                                    'string',
                                                    'Kothari'
                                                )
                                            ]
                                        )
                                    ),
                                    new ODataProperty(
                                        'name',
                                        null,
                                        new ODataPropertyContent(
                                            [
                                                'fname' => new ODataProperty(
                                                    'fname',
                                                    'string',
                                                    'Anu'
                                                ),
                                                'lname' => new ODataProperty(
                                                    'lname',
                                                    'string',
                                                    'Chandy'
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        ]
                    )
                )
            ]
        );
        $entry->type            = new ODataCategory('');

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        $actual   = $writer->getOutput();
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/NorthWind.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom" m:etag="Entry ETag">
 <id>Expand Entry</id>
 <title type="text">Entry Title</title>
 <updated>2013-09-17T19:49:59-06:00</updated>
 <author>
  <name/>
 </author>
 <link rel="edit" title="Entry Title" href="Edit Link URL"/>
 <link rel="" href="">
  <m:inline>
   <entry m:etag="Entry ETag">
    <id>Entry 1</id>
    <title type="text">Entry Title</title>
    <updated>2013-09-17T19:49:59-06:00</updated>
    <author>
     <name/>
    </author>
    <link rel="edit" title="Entry Title" href="Edit Link URL"/>
    <category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
    <content type="application/xml">
     <m:properties>
      <d:name m:type="string">
       <d:fname m:type="string">Yash</d:fname>
       <d:lname m:type="string">Kothari</d:lname>
      </d:name>
      <d:city m:type="string">Ahmedabad</d:city>
      <d:state m:type="string">Gujarat</d:state>
     </m:properties>
    </content>
   </entry>
  </m:inline>
 </link>
 <category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
 <content type="application/xml">
  <m:properties>
   <d:name m:type="Bag(Name)">
    <d:element>
     <d:name>
      <d:fname m:type="string">Yash</d:fname>
      <d:lname m:type="string">Kothari</d:lname>
     </d:name>
     <d:name>
      <d:fname m:type="string">Anu</d:fname>
      <d:lname m:type="string">Chandy</d:lname>
     </d:name>
    </d:element>
   </d:name>
  </m:properties>
 </content>
</entry>
';

        $new      = '2013-09-17T19:49:59-06:00';
        $expected = $this->removeUpdatedTags($expected, $new);
        $actual   = $this->removeUpdatedTags($actual, $new);
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * test for write top level primitive property.
     */
    public function testPrimitiveProperty()
    {
        $propCont             = new ODataPropertyContent(
            [
                'Count'=> new ODataProperty('Count', null, '56')
            ]
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($propCont);
        $this->assertSame($writer, $result);

        $actual   = $writer->getOutput();
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Count xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata"
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices"
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">56</d:Count>';

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * test for write top level Complex property.
     */
    public function testComplexProperty()
    {
        $propCont             = new ODataPropertyContent(
            [
                'Address' => new ODataProperty(
                    'Address',
                    'Complex.Address',
                    new ODataPropertyContent(
                        [
                            'FlatNo.' => new ODataProperty('FlatNo.', null, '31'),
                            'StreetName' => new ODataProperty('StreetName', null, 'Ankur'),
                            'City' => new ODataProperty('City', null, 'Ahmedabad')
                        ]
                    )
                )
            ]
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($propCont);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Address m:type="Complex.Address" xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata"
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices"
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <d:FlatNo.>31</d:FlatNo.>
 <d:StreetName>Ankur</d:StreetName>
 <d:City>Ahmedabad</d:City>
</d:Address>';
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * Testing bag property.
     */
    public function testEntryWithBagProperty()
    {
        //entry
        $entry           = new ODataEntry();
        $entry->id       = 'http://host/service.svc/Customers(1)';
        $entry->setSelfLink(new ODataLink('entry2 self link'));
        $entry->setTitle(new ODataTitle('title of entry 2'));
        $entry->editLink = 'edit link of entry 2';
        $entry->type     = 'SampleModel.Customer';
        $entry->eTag     = '';


        $entry->type            = new ODataCategory('SampleModel.Customer');
        $entry->propertyContent = new ODataPropertyContent(
            [
                'ID' => new ODataProperty(
                    'ID',
                    'Edm.Int16',
                    1
                ),
                'Name' => new ODataProperty(
                    'Name',
                    'Edm.String',
                    'mike'
                ),
                'EmailAddresses' => new ODataProperty(
                    'EmailAddresses',
                    'Bag(Edm.String)',
                    new ODataBagContent(
                        '',
                        [
                            'mike@foo.com',
                            'mike2@foo.com'
                        ]
                    )
                ),
                'Addresses' => new ODataProperty(
                    'Addresses',
                    'Bag(SampleModel.Address)',
                    new ODataBagContent(
                        '',
                        [
                            new ODataPropertyContent(
                                [
                                    'Street' => new ODataProperty(
                                        'Street',
                                        'Edm.String',
                                        '123 contoso street'
                                    ),
                                    'Apartment' =>  new ODataProperty(
                                        'Apartment',
                                        'Edm.String',
                                        '508'
                                    )
                                ]
                            ),
                            new ODataPropertyContent(
                                [
                                    'Street' => new ODataProperty(
                                        'Street',
                                        'Edm.String',
                                        '834 foo street'
                                    ),
                                    'Apartment' =>  new ODataProperty(
                                        'Apartment',
                                        'Edm.String',
                                        '102'
                                    )
                                ]
                            )
                        ]
                    )
                )
            ]
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($entry);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<entry xml:base="http://localhost/NorthWind.svc/" xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" xmlns="http://www.w3.org/2005/Atom" m:etag="">
 <id>http://host/service.svc/Customers(1)</id>
 <title type="text">title of entry 2</title>
 <updated>2011-05-24T15:01:23+05:30</updated>
 <author>
  <name/>
 </author>
 <link rel="edit" title="title of entry 2" href="edit link of entry 2"/>
 <category term="SampleModel.Customer" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>
 <content type="application/xml">
  <m:properties>
   <d:ID m:type="Edm.Int16">1</d:ID>
   <d:Name m:type="Edm.String">mike</d:Name>
   <d:EmailAddresses m:type="Bag(Edm.String)">
    <d:element>mike@foo.com</d:element>
    <d:element>mike2@foo.com</d:element>
   </d:EmailAddresses>
   <d:Addresses m:type="Bag(SampleModel.Address)">
    <d:element>
     <d:Street m:type="Edm.String">123 contoso street</d:Street>
     <d:Apartment m:type="Edm.String">508</d:Apartment>
    </d:element>
    <d:element>
     <d:Street m:type="Edm.String">834 foo street</d:Street>
     <d:Apartment m:type="Edm.String">102</d:Apartment>
    </d:element>
   </d:Addresses>
  </m:properties>
 </content>
</entry>
';

        $new      = '2011-05-24T15:01:23+05:30';
        $expected = $this->removeUpdatedTags($expected, $new);
        $actual   = $this->removeUpdatedTags($actual, $new);
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * test for write top level Bag of Primitive Property.
     */
    public function testPrimitiveBagProperty()
    {
        $propCont             = new ODataPropertyContent(
            [
                'Emails' => new ODataProperty(
                    'Emails',
                    'Bag(edm.String)',
                    new ODataBagContent(
                        '',
                        [
                            'yash_kothari@persistent.co.in',
                            'v-yashk@microsoft.com',
                            'yash2712@gmail.com',
                            'y2k2712@yahoo.com'
                        ]
                    )
                )
            ]
        );

        $writer = new AtomODataWriter(PHP_EOL, true, 'http://localhost/NorthWind.svc');
        $result = $writer->write($propCont);
        $this->assertSame($writer, $result);

        $actual = $writer->getOutput();

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<d:Emails m:type="Bag(edm.String)" xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata" 
xmlns:d="http://schemas.microsoft.com/ado/2007/08/dataservices" 
xmlns:m="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <d:element>yash_kothari@persistent.co.in</d:element>
 <d:element>v-yashk@microsoft.com</d:element>
 <d:element>yash2712@gmail.com</d:element>
 <d:element>y2k2712@yahoo.com</d:element>
</d:Emails>';

        $this->assertXmlStringEqualsXmlString($this->removeUpdatedTags($expected), $this->removeUpdatedTags($actual));
    }

    /**
     * @var ProvidersWrapper
     */
    protected $mockProvider;

    public function testGetOutputNoResourceSets()
    {
        $this->mockProvider->shouldReceive('getResourceSets')->andReturn([]);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $fakeBaseURL = 'http://some/place/some/where/' . uniqid();

        $writer = new AtomODataWriter(PHP_EOL, true, $fakeBaseURL);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<service xml:base=\"{$fakeBaseURL}/\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns=\"http://www.w3.org/2007/app\">\n <workspace>\n  <atom:title>Default</atom:title>\n </workspace>\n</service>\n";

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function testGetOutputTwoResourceSets()
    {
        $fakeResourceSet1 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        $fakeResourceSet1->shouldReceive('getName')->andReturn('Name 1');

        $fakeResourceSet2 = m::mock('POData\Providers\Metadata\ResourceSetWrapper');
        //TODO: this certainly doesn't seem right...see #73
        $fakeResourceSet2->shouldReceive('getName')->andReturn("XML escaped stuff \" ' <> & ?");

        $fakeResourceSets = [
            $fakeResourceSet1,
            $fakeResourceSet2,
        ];

        $this->mockProvider->shouldReceive('getResourceSets')->andReturn($fakeResourceSets);
        $this->mockProvider->shouldReceive('getSingletons')->andReturn([]);

        $fakeBaseURL = 'http://some/place/some/where/' . uniqid();

        $writer = new AtomODataWriter(PHP_EOL, true, $fakeBaseURL);
        $actual = $writer->writeServiceDocument($this->mockProvider)->getOutput();

        $expected = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<service xml:base=\"{$fakeBaseURL}/\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns=\"http://www.w3.org/2007/app\">\n <workspace>\n  <atom:title>Default</atom:title>\n  <collection href=\"Name 1\">\n   <atom:title>Name 1</atom:title>\n  </collection>\n  <collection href=\"XML escaped stuff &quot; ' &lt;&gt; &amp; ?\">\n   <atom:title>XML escaped stuff &quot; ' &lt;&gt; &amp; ?</atom:title>\n  </collection>\n </workspace>\n</service>\n";

        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    /**
     * @dataProvider canHandleProvider
     * @param mixed $id
     * @param mixed $version
     * @param mixed $contentType
     * @param mixed $expected
     */
    public function testCanHandle($id, $version, $contentType, $expected)
    {
        $writer = new AtomODataWriter(PHP_EOL, true, 'http://yahoo.com/some.svc');

        $actual = $writer->canHandle($version, $contentType);

        $this->assertEquals($expected, $actual, strval($id));
    }

    public function canHandleProvider()
    {
        return [
            [100, Version::v1(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, true], //see #94
            [101, Version::v2(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, true],
            [102, Version::v3(), MimeTypes::MIME_APPLICATION_ATOMSERVICE, true],

            [200, Version::v1(), MimeTypes::MIME_APPLICATION_XML, true],
            //For these two see #94 it may nto be right
            [201, Version::v2(), MimeTypes::MIME_APPLICATION_XML, true],
            [202, Version::v3(), MimeTypes::MIME_APPLICATION_XML, true],

            [300, Version::v1(), MimeTypes::MIME_APPLICATION_ATOM, true], //see #94
            [301, Version::v2(), MimeTypes::MIME_APPLICATION_ATOM, true],
            [302, Version::v3(), MimeTypes::MIME_APPLICATION_ATOM, true],
        ];
    }

    public function testSerializeExceptionWithCodeSet()
    {
        $foo = new ODataException('message', 400);

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<error xmlns="http://schemas.microsoft.com/ado/2007/08/dataservices/metadata">
 <code>400</code>
 <message>message</message>
</error>
';
        $actual = AtomODataWriter::serializeException($foo, new ServiceConfiguration(null));
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function testAddSingletonsToServiceDocument()
    {
        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<service xml:base="http://localhost/odata.svc/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns="http://www.w3.org/2007/app">
 <workspace>
  <atom:title>Default</atom:title>
  <collection href="Sets">
   <atom:title>Sets</atom:title>
  </collection>
  <collection href="single">
   <atom:title>single</atom:title>
  </collection>
 </workspace>
</service>
';

        $set = m::mock(ResourceSetWrapper::class);
        $set->shouldReceive('getName')->andReturn('Sets');

        $single = m::mock(ResourceFunctionType::class);
        $single->shouldReceive('getName')->andReturn('single');

        $wrapper = m::mock(ProvidersWrapper::class);
        $wrapper->shouldReceive('getResourceSets')->andReturn([$set]);
        $wrapper->shouldReceive('getSingletons')->andReturn([$single]);

        $foo = new AtomODataWriter(PHP_EOL, true, 'http://localhost/odata.svc');
        $foo->writeServiceDocument($wrapper);

        $actual = $foo->xmlWriter->outputMemory(true);
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function testBeforeWriteDateTimeValue()
    {
        $type  = 'Edm.DateTime';
        $value = '2000-01-01 00:00:00';

        $foo      = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $expected = '2000-01-01T00:00:00';
        $actual   = $foo->beforeWriteValue($value, $type);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteNullValue()
    {
        $property = m::mock(ODataProperty::class)->makePartial();

        $foo = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $foo->writeNullValue($property);

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . PHP_EOL;
        $actual   = $foo->getOutput();
        $this->assertEquals(trim($expected), trim($actual));
    }

    public function testPreWritePropertiesWithMediaLinkEntry()
    {
        $media = new ODataMediaLink('name', 'edit', 'src', 'application/xml', 'etag');

        $type = new ODataCategory('');

        $entry                   = new ODataEntry();
        $entry->isMediaLinkEntry = true;
        $entry->mediaLink        = $media;
        $entry->type             = $type;

        $foo = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $foo->preWriteProperties($entry);

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . PHP_EOL;
        $expected .= '<category term="" scheme="http://schemas.microsoft.com/ado/2007/08/dataservices/scheme"/>'
                     . PHP_EOL;
        $expected .= '<content type="application/xml" src="src"/>' . PHP_EOL;
        $expected .= '<m:properties/>' . PHP_EOL;
        $actual   = $foo->getOutput();
        $expected = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $expected);
        $actual   = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testWriteBeginEntryWithMediaLinks()
    {
        $media = new ODataMediaLink('name', 'edit', 'src', 'application/xml', 'etag');

        $entry                   = new ODataEntry();
        $entry->isMediaLinkEntry = true;
        $entry->mediaLink        = $media;
        $entry->mediaLinks[]     = $media;
        $entry->editLink         = 'edit';
        $entry->setTitle(new ODataTitle(''));

        $foo = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $foo->writeBeginEntry($entry, false);

        $expectedStart = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . PHP_EOL;
        $expectedStart .= '<entry>' . PHP_EOL;
        $expectedStart .= '    <id></id>' . PHP_EOL;
        $expectedStart .= '    <title type="text"></title>' . PHP_EOL;

        $expectedEnd = '    <author>' . PHP_EOL;
        $expectedEnd .= '        <name/>' . PHP_EOL;
        $expectedEnd .= '    </author>' . PHP_EOL;
        $expectedEnd .= '    <link rel="edit" title="" href="edit"/>' . PHP_EOL;
        $expectedEnd .= '    <link m:etag="etag" rel="edit-media" type="application/xml" title="name" href="edit"/>'
                        . PHP_EOL;
        $expectedEnd .= '    <link m:etag="etag" rel="http://schemas.microsoft.com/ado/2007/08/dataservices/'
                        . 'mediaresource/name" type="application/xml" title="name" href="edit"/>' . PHP_EOL;
        $expectedEnd .= '</entry>' . PHP_EOL;

        $actual        = $foo->getOutput();
        $expectedStart = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $expectedStart);
        $actual        = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $actual);
        $expectedEnd   = preg_replace('~(*BSR_ANYCRLF)\R~', "\r\n", $expectedEnd);
        $this->assertStringStartsWith($expectedStart, $actual);
        $this->assertStringEndsWith($expectedEnd, $actual);
    }

    public function testWriteUnexpandedLinkNode()
    {
        $link        = new ODataLink(null, 'Title', 'linkType');

        $foo = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $foo->writeLinkNode($link, false);

        $expected = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . PHP_EOL;
        $expected .= '<link rel="" type="linkType" title="Title" href=""/>' . PHP_EOL;
        $actual = $foo->getOutput();
        $this->assertXmlStringEqualsXmlString($expected, $actual);
    }

    public function testWriteEmptyODataEntry()
    {
        $entry                  = new ODataEntry(null, null, new ODataTitle(''));
        $entry->resourceSetName = 'Foobars';

        $foo = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');

        $actual   = $foo->write($entry)->getOutput();
        $expected = '<link rel="edit" title="" href=""/>';
        $this->assertTrue(false !== strpos($actual, $expected));
        $expected = '<m:properties/>';
        $this->assertTrue(false !== strpos($actual, $expected));
    }

    public function testWriteEmptyODataFeed()
    {
        $feed                  = new ODataFeed();
        $feed->id              = 'http://localhost/odata.svc/feedID';
        $feed->setTitle(new ODataTitle('title'));
        $feed->setSelfLink(new ODataLink(
            ODataConstants::ATOM_SELF_RELATION_ATTRIBUTE_VALUE,
            'Feed Title',
            null,
            'feedID'
        ));

        $foo      = new AtomODataWriterDummy(PHP_EOL, true, 'http://localhost/odata.svc');
        $expected = '<link rel="self" title="Feed Title" href="feedID"/>';
        $actual   = $foo->write($feed)->getOutput();
        $this->assertTrue(false !== strpos($actual, $expected));
        $expected = '<m:properties/>';
        $this->assertTrue(false === strpos($actual, $expected));
    }
}
