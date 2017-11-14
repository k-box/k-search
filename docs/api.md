# K-Search-API

The K-Search API allows you to build applications that can search data, or manage the search index (push or remove data).

The K-Search API communicates over the [Hypertext Transfer Protocol](https://en.wikipedia.org/wiki/Hypertext_Transfer_Protocol) implementing simple [Remote Procedure Calls](https://en.wikipedia.org/wiki/Remote_procedure_call).

Arguments are passes with the `POST` parameters. The response contains a [JSON](https://en.wikipedia.org/wiki/JSON) object.


### Versioning

The exposed API is versioned according to the [Semantic Versioning](http://semver.org/). The
API version numbers use a `{MAJOR}.{MINOR}` notation. Whereas `{MINOR}` is optional, if omitted the recommended and stable release of the major version is provided.

The selection of the API version is through the url.
Version specific documentation of the K-Search-API can be found on every K-Link instance, under the `/doc`path.

* **Current version**: 3.0
* **Supported versions**: 2.2 (through converters to version 3)

### Endpoint

* **Simple base URL:** `https://{BASE_URL}/api/{MAJOR}/{METHOD}` (uses the recommended, latest stable release of the selected major version)
* **Complete base URL:** `https://{BASE_URL}/api/{MAJOR}.{MINOR}/{METHOD}`

In the following `{VERSION}` is used to express either `{MAJOR}` or `{MAJOR}.{MINOR}`.

### Identification

The central `Data object` is identified universally and unique by the use of a [Universally unique identifier](https://en.wikipedia.org/wiki/Universally_unique_identifier) (UUID). One piece of data (document) has the same `dataUUID` in a distributed systems - so to say - on the K-Box and also on the K-Link.

On any application where pieces of data (documents) are handled first, they receive immediately their UUID. When documents are pushed to a K-Link, in the unlikely case of a clash, the application needs to change the UUID and push it again.

### Authentication

The K-Search APIs are protected by authentication and each API consumer must provide an API token in the request.

The authentication is performed by providing the Authorization header in the HTTP request, as an example:
```
curl -H "Authorization: Bearer ZTI0NTg1MzFhODliZTZlMzM4ZWUxMGJjZTQxYzIzYjQ=" https://K-SEARCH-URL/api/...
```

The K-Search API uses a centralized registry to verify the provided credentials: both the Bearer token and the origin of the request are used to authenticate and validate all API requests.
Refer to the K-Registry documentation for details about the credentials and the registration process, the registry also provides details for the permission system.

The environent variable `KLINK_REGISTRY_ENABLED` controls, if a KLink Registry should be used for authenticating clients (default is `false`).
The location of the KLink Registry can be controlled with the `KLINK_REGISTRY_API_URL` variable.

### Common deployments:

* **K-Link:**

  * Authentication is enabled.
  * Applications to use to the K-Search API are registered and managed through the K-Link-Registry (this is where they obtain the `app_secret`).

* **K-Box:**

  * Authentication is disabled.
  * Only one application, the K-Box, accesses the K-Search-API through a local channel.

## API calls

### Requests

All API calls are made to `/api/{VERSION}/{METHOD}`. Arguments can be passed in the `POST` request as JSON.

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the Client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | Arguments for the method of the request (see below under "methods"). |


### Responses

The response contains a JSON [response object](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#response-object):

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | [result object](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#result-object) | on success | REQUIRED on success; MUST NOT exist if there was an error. |
| `error`  | [error object](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#error-object) | on error | REQUIRED on failure; MUST NOT exist if there was no error. |


* `status`: Usually "`200` (OK)"; but might change slightly to "`201` (Created)" or "`204` (No data)". Should be ignored by the client.


## Methods

The `{METHOD}` executes to one of the offered functions provided by the K-Search API:


### search.query

Allows to query the K-Search index and returns search results.

* URL: `/api/{VERSION}/search.query`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | [`SearchQuery object`](https://git.klink.asia/main/k-search/blob/develop/docs/api-search-objects.md#searchquery-object) |

**Successful response**

* `status`: `200` (OK)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`SearchResult object`](https://git.klink.asia/main/k-search/blob/develop/docs/api-search-objects.md#searchresults-object) |


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
| `result` | Object | ✔ | [`Data object`](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#data-object) |


### data.add

Add piece of data to the search index.

* URL: `/api/{VERSION}/data.add`

**Request**:

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | An identifier established by the client that MUST contain a String, Number, or NULL value if included. The value SHOULD normally not be Null and Numbers SHOULD NOT contain fractional parts. |
| `params` | Object | ✔ | A simple JSON object |
| `params[data]` | Object | ✔ | [`Data object`](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#data-object) of the data to be added. |
| `params[data_textual_contents]` | String |  | A string of the textual representation of the document content as indexable information, which should only be provided for files which are either only partially indexable (such as compressed or geo files) or non-indexable files (such as video files) |

**Successful response**

* `status`: `201` (Created)

| Property | Type    | Required   | Description |
| -------- | ------- | ---------- | ----------- |
| `id` | String | ✔ | The identifier established by the client in the request. |
| `result` | Object | ✔ | [`Data object`](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#data-object) |


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
| `result` | Object | ✔ | [`Status object`](https://git.klink.asia/main/k-search/blob/master/docs/api-objects.md#status-object) |
