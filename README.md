OData Producer Library for PHP V1.2
============

The OData Producer Library for PHP is a server library that allows to exposes data sources by using the OData Protocol. The OData Producer supports all Read-Only operations specified in the Protocol version 2.0:

* It provides two formats for representing resources, the XML-based Atom format and the JSON format. 
* Servers expose a metadata document that describes the structure of the service and its resources. 
* Clients can retrieve a feed, Entry or service document by issuing an HTTP GET request against its URI. 
* Servers support retrieval of individual properties within Entries. 
* It supports pagination, query validation and system query options like $format, $top, $linecount, $filter, $select, $expand, $orderby, $skip . 
* User can access the binary stream data (i.e. allows an OData server to give access to media content such as photos or documents in addition to all the structured data) 


How to use the OData Producer Library for PHP
-------------
Data is mapped to the OData Producer through three interfaces into an application. From there the data is converted to the OData structure and sent to the client.
The 3 interfaces required are:
* IDataServiceMetadataProvider: this is the interface used to map the data source structure to the Metadata format that is defined in the OData Protocol. Usually an OData service exposes a $metadata endpoint that can be used by the clients to figure out how the service exposes the data and what structures and data types they should expect. 
* IDataServiceQueryProvider: this is the interface used to map a client query to the data source. The library has the code to parse the incoming queries but in order to query the correct data from the data source the developer has to specify how the incoming OData queries are mapped to specific data in the data source. 
* IServiceProvider: this is the interface that deals with the service endpoint and allows defining features such as Page size for the OData Server paging feature, access rules to the service, OData protocol version(s) accepted and so on. 
* IDataServiceStreamProvider: This is an optional interface that can be used to enable streaming of content such as Images or other binary formats. The interface is called by the OData Service if the DataType defined in the metadata is EDM.Binary. 


If you want to learn more about the PHP Producer Library for PHP, the User Guide included with the code (\docs directory) provides detailed information on how to install and configure the library, it also show how to implement the interfaces in order to build a fully functional OData service.

The library is built using only PHP and it runs on both Windows and Linux.

