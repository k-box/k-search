# K-Core API

The following sections describes the set of APIs provided by the K-Core component.

The API is provided using the [RESTful](https://en.wikipedia.org/wiki/Representational_state_transfer) paradigm. 

The features of the Core component have been divided into different groups of API,
each one dealing with specific aspects of the K-Link network communication:

1. [Search](#searching)
2. [Document Management](#document-management)
3. [Institution Management](#institution-management)

The word _Management_ refers to the CRUD operations (create, retrieve, update and delete). 

A live-documentation of the K-Core APIs is available online on each [deployed K-Core](#available-k-core-instances)
under the `/doc` path.
The live-documentation also hosts a sandbox for you to live test each endpoint.

The exposed APIs are versioned according to the [Semantic Versioning](http://semver.org/), for more 
information see the [Versioning](#versioning) section.

## Available K-Core instances

The K-Link Team maintain 3 K-Core instances available: one is reserved to the K-Link Public Network, 
while the other two are for testing and internal usage purposes.

For development purposes only the two testing instances must be used.

Access to each instance is subject to [Authentication](#authentication)

#### K-Link Public Network 

This is the official K-Link Network entrypoint. Usage of this entrypoint is restricted to joined 
organizations and cannot be used for testing purposes.

- Base URL: `https://public.klink.asia/kcore/`
- Supported Version: `2.1`

#### Demo Public Network 

This is the internal instance used for testing and release validation. On this instance is 
deployed the latest stable build of the K-Core.

- Base URL: `https://dms.klink.asia/kcore/`
- Supported Versions: `2.1`, `2.2`
- Sandbox: [`https://dms.klink.asia/kcore/doc`](https://dms.klink.asia/kcore/doc)

#### Testing Public Network

This is the [canary](http://whatis.techtarget.com/definition/canary-canary-testing) instance used 
for testing of new features. This instance cannot be considered reliable as it is subject to  
breaking changes without notice.

- Base URL: `https://test.klink.asia/kcore/`
- Supported Versions: `2.1`, `2.2`
- Sandbox: [`https://test.klink.asia/kcore/doc`](https://test.klink.asia/kcore/doc)

## Versioning

The K-Core API as multiple versions to enable a continuous development and improvement 
of the service. The proccess follow the [Semantic Versioning](http://semver.org/) approach.

The API version numbers use a `{MAJOR}.{MINOR}` notation.
The currently available versions are: `2.1` and `2.2`.

The selection of the API version is done by sending the HTTP `Accept-Version` Header.
If sent, the header must contain a string value in the format `{MAJOR}.{MINOR}`, when 
omitted the default API version `2.1` is used.

The availability of of both API versions depends on the K-Core instance you would 
like to use.
Please consult the [available K-Core instances](#available-k-core-instances) list 
to verify witch version is available and exposed from a selected API endpoint.

## Authentication

The K-Core APIs, when required, must be invoked using the HTTPS protocol with 
[Http Basic Authentication](https://en.wikipedia.org/wiki/Basic_access_authentication), 
**access credentials will be provided upon request by the K-Link Developmen Team**.

Furthermore, the K-Core API requires each document publisher to be uniquely identified
in the KLink network, such identification ID is called `InstitutionID`
and allows to correctly associate published documents to the owning
institution.
The access credentials and the `InstitutionID` are verified
on each API invocation for permissions.

As for the access credentials, the `InstitutionID` will be provided with
the K-Core API access credentials mentioned before.

## K-Core APIs

In this document some K-Link specific terms are used, here below a short definition of the commonly
used terms in the K-Core APIs: 

- *Institution* the registered organization that is joined to the K-Link Network;
- *File* the physical document (an image, a PDF, a MS Word document) the contents of which that must be searchable;
- *DocumentDescriptor* a set of metadata that describes a File (from the K-Link perspective), 
  Such metadata is described in the [DocumentDescriptor data type](#documentdescriptor-object) section.
- *Document* the pair of <File, DocumentDescriptor>

The API expects and return data in JSON format, so be sure to use the correct HTTP headers.

### Document Management

The Documents Management API enables the indexing of documents, while providing a
simple set of functions to retrieve and delete the documents already indexed.

It is important to notice that the K-Core component is not responsible of the
physical storage of any document: the data available through the K-Core API is
the Document Descriptor data type, which consists of a set of information
related to a specific file.

**The Document Descriptor does not include the original file, nor its plain text contents.**

The file must be sent, in any case, when the Document Descriptor
is sent for indexing.

#### Adding Documents

API endpoint to add a Document to the K-Link:

`POST kcore/descriptors/`

**parameter**

The [`Document`](#document-object). Parameters must be send as JSON (application/json) 
in the body of the POST request.  

**return**

The [`DocumentDescriptor`](#documentdescriptor-object) that has been indexed.

Please note that if you didn't specify the `language` field the returned DocumentDescriptor will have 
a value set for the attribute.

Values can be a 2 digit ISO 639-1 language code or `Unknown` if 
the K-Core was not able to estimate the language of the document content.

**HTTP status codes of the response**

- `201 Created` in case of successful document indexing, while a response
- `400 Bad Request` in case of invalid attribute in the request parameters
- `403 Forbidden` is returned if the institution is not allowed to index
 the document (e.g., posting private documents to the public Core, etc.).


**Note**
Every client of the K-Core API must ensure the uniqueness of the `<InstitutionId, LocalDocumentId>` 
pair when constructing the `DocumentDescriptor` and  invoking the Document API endpoint.

As an example, when the K-Core APIs are invoked from multiple locations for
the same Institution (a website and a local document management system) the
InstitutionID would be the same, but the clients must be configured to not
create the same LocalDocumentId for different documents.


#### Retrieving a single known document

Retrieves a DocumentDescriptor:

`GET kcore/descriptors/{visibility}/{institutionID}/{localDocumentId}` 

 
**URL Parameters**

- `{visibility}`. The document descriptor visibility. Can be `public` or `private`. 
  `public` must be used when interacting with The K-Link Public Network. (used for backward compatibility);
- `{institutionID}` the identifier of the institution that added the document;
- `{localDocumentId}` the identifier of the document descriptor.

**return**

The [`DocumentDescriptor`](#documentdescriptor-object)

- `200 OK` Returned when successful
- `404 Not Found` Returned when the document is not found
- `400 Bad Request` in case of invalid attribute in the request parameters

#### Deleting a document

Removes the Document Descriptor. Only the institution that uploaded the 
document is able to perform the removal.

`DELETE kcore/descriptors/{visibility}/{institutionID}/{localDocumentId}`

**URL Parameters**

- `{visibility}`. The document descriptor visibility. Can be `public` or `private`. 
  `public` must be used when interacting with The K-Link Public Network
- `{institutionID}` the identifier of the institution that added the document
- `{localDocumentId}` the identifier of the document descriptor

**Response Status Codes**

- `204 No Content` Returned when successful
- `404 Not Found` Returned when the DocumentDescriptor is not found
- `400 Bad Request` in case of invalid attribute in the request parameters

### Searching

The search feature is exposed by the Search-API, which is accessible at the
single entry point 

`GET search/{visibility}/`

**Parameters**

The URL parameter `{visibility}` accepts the values:

- `public`: when the documents to search are the one available in the K-Link Public Network
- or `private`: when the documents to search are available in the private K-Core of an Institution

Parameters provided at the GET method further defines the search that have
to be executed, from the set of search keywords, to further search filtering
and refinements.

The **following parameters are required** to invoke the SearchAPIs:

- `query` : URI encoded string of  the search query.
    If no query is specified, an empty result set will be returned
- `numResults` : specify the number of results to retrieve, if no value is
    given the default value of 10 is used.
- `startResult` : specify the first result to return from the complete set of
    retrieved documents, the value is 0-based; the default value is 0.

The following URL parameters allow to enable and modify the behaviour of the
Faceting and filtering components of the Search API:

- `facets`: specify the list of facets to retrieve in the query; specify a
    comma-separated list of Facet name.
    No facets are active by default; don’t forget to activate the facet that
    has been configured!
- `facet_{facetName}_count`: configure the number of terms to return for the
    given {facetName}, default value for an active facet is 10.
- `facet_{facetName}_mincount`: specify the minimun number of items the
    facet to be returned for the given {facetName}, default value for an
    active facet is 2.
- `facet_{facetName}_prefix`: specify to retrieve the facet items that have
    such prefix in the text (usefull for documentGroups faceting)
- `filter_{facetName}`: specify the filtering value to applied to the search
    for the given {facetName} facet (a comma separated values could be specified)

For more information and examples go to the [Facets and filters](#facets-and-filters) section.

**Response**

The API returns a [`ResultSet`](#resultset-data-type) data type with the Document descriptors 
that matches the provided query.

#### Facets and filters

The available facets and filters are based on some of the DocumentDescriptor fields; the following
lists present the general set filters and facets as exposed by the KCore APIs:

**Filters**

- `language` Filter documents based on the Language field
- `documentType` Filter based on the [K-Link Document type](#mimetype-to-document-type-conversion-table)
- `institutionId` Filter based on the InstitutionId identifier
- `locationsString` Filter based on the geo-referenced locations extracted byt the KCore (based on the Location String field)
- `documentId` Filter by the full document identifier `{institutionid}-{localDocumentId}`
- `localDocumentId` Filter by the local document identifier
- `documentHash` (from version `2.2`)

**Facets**
- `language` Facet of document's languages (based on the Language field)
- `documentType` Facet on document's types (based on the DocumentType field)
- `institutionId` Facet on institution IDs (based on the InstitutionID field)
- `locationsString` Facet of the geo-referenced locations extracted byt the KCore (based on the Location String field)


Other filters and facets exists, but are relevant only for the DMS:
- `projectId` (from version `2.2`) Filter by the ProjectID of the document (oly relevant for internal KCore APIS, used by the DMS)
- `documentGroups` Filter by the Groups the document is in (oly relevant for internal KCore APIS, used by the DMS)

Please take into consideration that filters:

- are always evaluated in `AND`.
- are always applied before facets. The filters will narrow the result set, 
  while facets are used to extract aggregated data about particular field. 

The data coming from facets can be used to filter again the results.


##### Faceting and filtering examples

In the following will be use the canary instance `test.klink.asia` and the query terms `*`. 
With `*` the search engine will match all the indexed documents.

**Enable a facet**

Enable the facet `language` to see the aggregated information in the results.

```
https://test.klink.asia/kcore/search/public/?query=*&facets=language
```

**enabling the “language” and the “institutionId” facet:**

```
https://test.klink.asia/kcore/search/public/?query=*&facets=language,institutionID&facet_language_count=3
```

**enabling the “language” facet and retrieve only the 3 most frequent facets of such field**

```
https://test.klink.asia/kcore/search/public/?query=*&facets=language&facet_language_count=3
```

**filtering the documents by documentType “presentation”**

```
https://test.klink.asia/kcore/search/public/?query=*&filter_documentType=presentation
```

**filtering the documents by documentType “presentation” or “document”:**

```
https://test.klink.asia/kcore/search/public/?query=*&filter_documentType=presentation,document
```

**combining facets and filters**

Filtering documents by documentGroup whose user_id is `9` and language is russian (`ru` language code)
while getting the facets for `documentType` and `documentGroups`, that correspond only to the set of
filtered documents.

To build the query string let's start with enabling the facets we want to obtain:

```
?facets=documentGroups,documentType
```

Then we add the filter for the russian language (language code: `ru`):

```
?...&filter_language=ru
```

Then we will add the documentGroups filtering: 

```
?...&filter_documentGroups=9:*
```

The full parameter list will be

```
?facets=documentGroups,documentType&filter_language=ru&filter_documentGroups=9:*
```

### Institution Management

The institution management part of the API enables to create, retrieve, update and delete 
Institution/Organization details.

Institution are always managed by the K-Link Public Network.

#### Add a new institution

`POST /institutions`

**parameters**

The [Institution object](#institution-object) in JSON format in the body of the request

Please make sure that the institution id used is unique in the whole K-Link Network.

**response**

No object is returned, see the status codes.

**HTTP status codes**

- `201 Created` Returned when successfully created
- `401 Unauthorized` Returned when the invocation is Not Authorized
- `403 Forbidden` Returned when the invocation is Denied


#### Get an institution

The retrieval of an Institution can be performed by its identifier.

`GET /institutions/{id}`

**parameters**

- `{id}` the institution identifier in the K-Link Public Network

**response**

The [Institution object](#institution-object) in case the institution can be found.

**HTTP status codes**

- `200 OK` Returned when successfully retrieved
- `401 Unauthorized` Returned when the invocation is Not Authorized
- `403 Forbidden` Returned when the invocation is Denied
- `404 Not found` Returned when the Institution is Not Found

#### Get all registered institutions

Is possible to retrieve all the registered institutions with:

`GET /institutions`


**response**

An array of [Institution object](#institution-object), which represents all the registered
institutions in the K-Link Network.

**HTTP status codes**

- `200 OK` Returned when successfully retrieved
- `401 Unauthorized` Returned when the invocation is Not Authorized
- `403 Forbidden` Returned when the invocation is Denied


#### Delete an institution


`DELETE /institutions/{id}`

**parameters**

- `{id}` the institution identifier in the K-Link Public Network

**HTTP status codes**

- `204 No Content` Returned when successfully deleted
- `401 Unauthorized` Returned when the invocation is Not Authorized
- `403 Forbidden` Returned when the invocation is Denied
- `404 Not found` Returned when the Institution is Not Found




## Plain Objects

### DocumentDescriptor object

The `DocumentDescriptor` object describe a document entry in the K-Link network and contains all the metadata associated to it.

A Document Descriptor is identified, in the K-LINK network, by the pair `<institutionID, localDocumentID>`.
For better usability, other than the `mimeType`, the KCore allows API clients to define a human-readable version
of the type of the indexed document, such data should be placed in the `documentType` attribute.
No constraints have been defined for the `documentType` values, but its usage must be oriented to a better
classification of the document typology. Examples are "document", "video", "presentation".
This field *must* not be used as a set of tags or labels associated to documents.

As for the live documentation, the DocumentDescriptor allows to include more details related to the Document.
Despite of that, some attributes make sense only when a "private" KCore is used and invoked.

Please be sure to not include sensitive data any DocumentDescriptor sent to the "public" KLink network, as the whole
DocumentDescriptor will be available and readable.  

Here below some examples of attributes that should not be set (as they will not provide further details) for
the "public" network:

- `documentGroups`: the data contained here is relevant only for internal use (eg. KCore used in DMSes) 
- `projectId`: the K-Core is not keeping any connection with ProjectIDs of the KLink network.

[All the attributes can be seen here](https://public.klink.asia/kcore/doc/#get--descriptors-{visibility}-{institutionId}-{localDocumentId})

### Document object

See [Document add endpoint live documentation](https://public.klink.asia/kcore/doc/#post--descriptors-)

### ResultSet data type

See [Search endpoint live documentation](https://public.klink.asia/kcore/doc/#get--search-{visibility}-)


### Institution object

The `InstitutionDescriptor` object describe an institution joined in the K-Link network.

See [Institution endpoint live documentation](https://public.klink.asia/kcore/doc/#get--institutions-{id})


## MimeType to Document Type conversion table

Here is the conversion table from mime type to document type. 
The Document Type is meant to be a human friendly version of the mime type, which is machine/developer oriented.


| Mime type                                                                   | Document Type   | Remarks                                                                                |
| --------------------------------------------------------------------------- | --------------- | -------------------------------------------------------------------------------------- |
| (Wordpress) `post`                                                          | `web-page`      | Wordpress post type                                                                    |
| (Wordpress) `page`                                                          | `web-page`      | Wordpress page type                                                                    |
| (Drupal) `node`                                                             | `web-page`      | Drupal node type                                                                       |
| `text/html`                                                                 | `web-page`      |                                                                                        |
| `application/msword`                                                        | `document`      | Office 2003 Word Document                                                              |
| `application/vnd.ms-excel`                                                  | `spreadsheet`   | Office 2003 Excel Spreadsheet                                                          |
| `application/vnd.ms-powerpoint`                                             | `presentation`  | Office 2003 Powerpoint Presentation                                                    |
| `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`         | `spreadsheet`   | Office 2007-2016 Excel Spreadsheet                                                     |
| `application/vnd.openxmlformats-officedocument.presentationml.presentation` | `presentation`  | Office 2007-2016 Powerpoint Presentation                                               |
| `application/vnd.openxmlformats-officedocument.wordprocessingml.document`   | `document`      | Office 2007-2016 Word Document                                                         |
| `application/pdf`                                                           | `document`      |                                                                                        |
| `text/uri-list`                                                             | `uri-list`      | List of URIs according to the [RFC 2483](http://tools.ietf.org/html/rfc2483#section-5) |
| `image/jpg`                                                                 | `image`         |                                                                                        |
| `image/jpeg`                                                                | `image`         |                                                                                        |
| `image/gif`                                                                 | `image`         |                                                                                        |
| `image/png`                                                                 | `image`         |                                                                                        |
| `image/tiff`                                                                | `image`         |                                                                                        |
| `text/plain`                                                                | `text-document` | Plain text (ASCII or UTF-8 encoded)                                                    |
| `application/rtf`                                                           | `text-document` | Rich Text Format                                                                       |
| `text/x-markdown`                                                           | `text-document` | Markdown format                                                                        |
| `application/vnd.google-earth.kmz`, `application/vnd.google-earth.kml+xml`  | `geodata`       | Google Earth file (aka Keyhole Markup Language)                                        |
| `application/vnd.google-apps.document`                                      | `document`      | Google Document                                                                        |
| `application/vnd.google-apps.presentation`                                  | `presentation`  | Google Slides                                                                          |
| `application/vnd.google-apps.spreadsheet`                                   | `spreadsheet`   | Google Spreadsheet                                                                     |
| `application/rar`                                                           | `archive`       |  |
| `application/zip`                                                           | `archive`       |  |
| `application/x-tar`                                                         | `archive`       |  |
| `application/x-bzip2`                                                       | `archive`       |  |
| `application/gzip`                                                          | `archive`       |  |
| `application/x-mimearchive`                                                 | `web-page`      |  |
| `video/x-ms-vob`                                                            | `dvd`           |  |
| `content/DVD`                                                               | `dvd`           |  |
| `video/x-ms-wmv`                                                            | `video`         |  |
| `video/x-ms-wmx`                                                            | `video`         |  |
| `video/x-ms-wm`                                                             | `video`         |  |
| `video/avi`                                                                 | `video`         |  |
| `video/divx`                                                                | `video`         |  |
| `video/x-flv`                                                               | `video`         |  |
| `video/quicktime`                                                           | `video`         |  |
| `video/mpeg`                                                                | `video`         |  |
| `video/mp4`                                                                 | `video`         |  |
| `video/ogg`                                                                 | `video`         |  |
| `video/webm`                                                                | `video`         |  |
| `video/x-matroska`                                                          | `video`         |  |
| `video/3gpp`                                                                | `video`         |  |
| `video/3gpp2`                                                               | `video`         |  |
| `text/csv`                                                                  | `spreadsheet`   |  |
| `message/rfc822`                                                            | `email`         |  |
| `application/vnd.ms-outlook`                                                | `email`         |  |
