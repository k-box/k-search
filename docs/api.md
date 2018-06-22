# K-Search-API

The K-Search API allows you to build applications that can search data, or manage the search index (push or remove data).

The K-Search API communicates over the [Hypertext Transfer Protocol](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol)
implementing simple [Remote Procedure Calls](https://en.wikipedia.org/wiki/Remote_procedure_call).

All API arguments are sent with the `POST` parameter.
The response are in [JSON](https://en.wikipedia.org/wiki/JSON) format.

## API Description and details

### Documentation

The K-Search-API definitions and documentation is available under the `/doc` URL of every deployed instance.

The live documentation of the API is accessible as:
1. an [Open API v2](https://swagger.io/specification) (formerly known as Swagger) specification (`/doc/swagger.json`);
2. or by using the SwaggerUI browser (`/doc/`).

The SwaggerUI allows to use the API without using a dedicated client or tools.

### Versioning

The exposed API is versioned according to the [Semantic Versioning](http://semver.org/).
The API version numbers use a `{MAJOR}.{MINOR}` notation.

The selection of the API version is through the url, as documented in the API definition.

* **Current major**: 3

### Authentication

The the K-Search APIs implement an authentication and permission system, where each API consumer must provide the
correct credentials to be allowed to execute an API request.

The authentication is performed by providing the `Authorization` header in the HTTP request.
As an example:
```bash
curl -H "Authorization: Bearer ZTI0NTg1MzFhODliZTZlMzM4ZWUxMGJjZTQxYzIzYjQ=" \
    http://ksearch.test/api/v3.0/data.get ...
```

The K-Search API can use, if configured to do so, a centralized registry to verify the provided credentials.
In this case both the `Authorization` and the `Origin` headers of the request are used to authenticate all API requests.

Refer to the K-Registry documentation for details about the credentials and the registration process; the registry also
provides details for the permission system.

Refer to the `.env.dist` file for details about the K-Registry integration and configuration, in particular to the
 `KLINK_REGISTRY_ENABLED` and `KLINK_REGISTRY_API_URL` variables.

### Identification

The data handled by the K-Search API (mainly the `Data` object) is identified universally by an identifier: the UUID
[Universally unique identifier](https://en.wikipedia.org/wiki/Universally_unique_identifier) format in the `v4` is
being used in the APIs.

The API allows data to be added and removed from the indexing system: by doing so the permission system also verifies
that only allowed consumers can replace or alter a data content from other consumers.

In case of UUID collision is the duty of a consumer to resolve the problem, by (as an example) generating a new UUID.

### Common deployments:

* **K-Link:**

  * Authentication is enabled.
  * Applications to use to the K-Search API are registered and managed through the K-Link-Registry (this is where they obtain the `app_secret`).

* **K-Box:**

  * Authentication is disabled.
  * Only one application, the K-Box, accesses the K-Search-API through a local channel.

## API calls

### Requests

All API endpoints shares the same base structure: requests are made to `/api/{VERSION}/{METHOD}` endpoint as `POST`,
with a `json` body content.

The following is the base structure of an API request:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the Client that MUST contain a String, Number, or NULL value if included.
    The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | Arguments for the method of the request (see below under "methods"). |

### Responses

The API response is still a `json` object, with the following base structure:
| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | [result object](./api-objects.md#result-object) | on success | REQUIRED on success; MUST NOT exist if there was an error. |
| `error`  | [error object](./api-objects.md#error-object) | on error | REQUIRED on failure; MUST NOT exist if there was no error. |


The HTTP response code returned by the APIs is usually "`200` (OK)".
Please notice that the HTTP response code does not reflect the successful (or failure) status of the RPC request, such
information is found in the `error` and `result` properties of the returned json object.

For further details about the API objects see the [response object](./api-objects.md#response-object)

## Methods

The `{METHOD}` executes to one of the offered functions provided by the K-Search API:

### data.get

Get detailed information of piece of data in the search index.

* URL: `/api/{VERSION}/data.get`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | A simple JSON object |
| `params[uuid]` | String | ✔ | The universal unique identifier of the data to be obtained. |

**Successful response**

* `status`: `200` (OK)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`Data object`](./api-objects.md#data-object) |


### data.add

Add piece of data to the search index.

* URL: `/api/{VERSION}/data.add`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | A simple JSON object |
| `params[data]` | Object | ✔ | [`Data object`](./api-objects.md#data-object) of the data to be added. |
| `params[data_textual_contents]` | String |  | A string of the textual representation of the document content as indexable information, which should only be provided for files which are either only partially indexable (such as compressed or geo files) or non-indexable files (such as video files) |

**Successful response**

* `status`: `201` (Created)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`Data object`](./api-objects.md#data-object) |

### data.status

Get the status information of a Data piece in the index or in the processing queue (since v3.4).
This API requires the `data-view` permission.

* URL: `/api/{VERSION}/data.status`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | A simple JSON object |
| `params[uuid]` | String | ✔ |  The universally unique identifier of the data piece to be handled. |
| `params[type]` | String |  | The status type, used to get the status from different stages. Use "data" for the indexed status, "processing" for the processing queue status. |

**Successful response**

* `status`: `200`

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`Data Status object`](./api-objects.md#data-status-object) |

### data.delete

Delete piece of data from the search index.

* URL: `/api/{VERSION}/data.delete`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | A simple JSON object |
| `params[uuid]` | String | ✔ | The universal unique identifier of the data to be deleted. |

**Successful response**

* `status`: `204` (No content)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`Status object`](./api-objects.md#status-object) |

### data.search

Allows to query the K-Search index and returns search results.
A full-text search is performed on a subset of the Data object properties.

The following properties are handled for full-text search, in addition to the contents of the linked document of the data:

- `data.title`
- `data.abstract`
- `data.properties.filename`

The full-text search is performed by executing a keyword search on the above fields, and returning the matching Data.
The fields are handled in a lower-case conversion, with terms/word splitting on whitespaces and punctuations.
An additional handling of composed words is in place for the filename property, where case changes are handled
as word separation too, while keeping the original word in place.
This allows to match data with `ExpenceReport.pdf` to be found when using the search keywords `expence report pdf` (or
just one of the given search keywords).

* URL: `/api/{VERSION}/data.search`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | [`SearchQuery object`](./api-search-objects.md#searchquery-object) |

**Successful response**

* `status`: `200` (OK)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`SearchResult object`](./api-search-objects.md#searchresults-object) |

## Additional endpoints

## Downloading files
If the K-Search application is configured to retain the downloaded contents (see `.env.dist` file), the following
endpoint allows to gather the downloaded files from the system:

```
  GET http://ksearch.test/files/{UUID}
```
Where `{UUID}` is the Data's UUID value.
The K-Search will use the response `E-Tag` header to provide the Data `hash`  value.

In case the retention configuration is disabled, the system will issue a redirect to the Data's `url` value, allowing
the client to fetch the file from the original source.
