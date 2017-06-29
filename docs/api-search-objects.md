## SearchQuery object

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `search`  | String | ✔       | URI encoded string of the search query. If no query is specified, an empty result set will be returned |
| `filters` | String |         | Search filters in the [Lucene query parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html) |
| `aggregations` | Object |    | An object containing the aggregations to be retrieved |
| `aggregations[][limit]` | Object | | Only retrieve a certain amount of the most common aggregations |
| `aggregations[][counts_filtered]` | Boolean | | Calculate aggregations count after applying filters (True) or before (False) (TODO: What is standard?)|
| `limit` | Integer | | Specify the number of results to retrieve. If no value is given the default value of 10 is used. |
| `offset` | Integer | | Specify the first result to return from the complete set of retrieved documents, the value is 0-based; If no value is given the default value of 0 is used. |

**Example**

```
{
    "search" : "K-Link",
    "filters" : "(language=en AND (type=(spreadsheet OR presentation))) AND (created_at=("2017-03-10"-"2017-03-20") OR updated_at<"2017-03-15") "
    "aggregations" : {
        {
            "language" : {
                "limit" : 10,
                "counts_filtered" : False
            }
        },
        {
            "type" : {
                "counts_filtered" : True
            }
        }
    },
    "limit" : 30,
    "offset" : 0,
}
```

## SearchResults object

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `query`  | Object | ✔       | The complete SearchQuery object from the request |
| `query[search]`  | String | ✔ | URI encoded string of the search query. If no query is specified, an empty result set will be returned |
| `query[filters]` | String | | Search filters in the [Lucene query parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html) |
| `query[aggregations][{NAME}]` | Object | | An object containing the aggregations to be retrieved |
| `query[limit]` | Integer | | Specify the number of results to retrieve. If no value is given the default value of 10 is used. |
| `query[offset]` | Integer | | Specify the first result to return from the complete set of retrieved documents, the value is 0-based; If no value is given the default value of 0 is used. |
| `results`  | Object | ✔     | An object holding the result from the search |
| `results[query_time]` | Integer | ✔ | The time needed to run the search query |
| `results[total_matches]` | Integer | ✔ | The total amount of found items. |
| `results[aggregations][]` | Object | | Results of the aggregations |
| `results[aggregations][{NAME}][count]` | Integer | | Count of the results according to aggregations |
| `results[items][][metadata][uuid]` | String | ✔ | Universally unique identifier. |
| `results[items][][metadata][url]` | String | ✔ | URI where the document is stored and retrievable. |
| `results[items][][metadata][title]` | String | ✔ | The data set or document title. |
| `results[items][][metadata][mime_type]` | String | ✔ | MimeType of the data set or document. |
| `results[items][][metadata][data_type]` | String | ✔ | Data type (PDF, Document, Presentation, ..). |
| `results[items][][metadata][hash]` | String | ✔ | The SHA-2 hash of the Document contents (SHA-512, thus 128 Chars). |
| `results[items][][metadata][owner]` | String | ✔ | The copyright owner. Field format: "Name Surname <mail@host.com>". |
| `results[items][][metadata][uploader]` | String | ✔ | User that uploaded the document Field format: "Name Surname <mail@host.com>". |
| `results[items][][metadata][uploader]` | String | ✔ | User that uploaded the document Field format: "Name Surname <mail@host.com>". |
| `results[items][][metadata][creation_date]` | DateTime [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) | ✔ | Data's or document's creation date. |
| `results[items][][metadata][update_date]` | DateTime [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) | | Data's or document's updated date. |
| `results[items][][metadata][title_aliases[]` | Array of Strings | | List of the (overrided) titles of the document or data set, as defined by users. |
| `results[items][][metadata][language]` | String | | The document language code (ISO 639-1), i|f empty this value will be set by the indexing procedure. |`
| `results[items][][metadata][thumbnail]` | String | | The URI where the a thumbnail is stored. |`|
| `results[items][][metadata][abstract]` | String | | A short abstract about the data or document |
| `results[items][][metadata][authors][]` | Array of Strings | | List of authors with email Field format: "Name Surname <mail@host.com>". |
| `results[items][][metadata][hierarchy_primary][]` | Array of Strings | | A first way of structuring content (Projects in the K-DMS) |
| `results[items][][metadata][hierarchy_secondary][]` | Array of Strings | | A second way of structuring content (Collections in the K-DMS) |
| `results[items][][metadata][source][name]` | String | | The name of the source. |
| `results[items][][metadata][source][url]` | String | | The URI where the source is described. |
