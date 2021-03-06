## SearchQuery object

Describe the Search request.

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `search`  | String | ✔       | URI encoded string of the search query. If no query is specified, an empty result set will be returned |
| `filters` | String |         | Search [filters](#supported-filters) in the [Lucene query parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html) |
| `klinks` | String  |    | Filter data based on K-Links. This filter is applied in AND with the other defined `filters`. See [K-Links Filter](#k-links-filter) |
| `geo_location_filter` | [GeoLocationFilter](#geographic-location-filter) |    | Filter for data geographic location. This filter is applied in AND with the other defined `filters` |
| `aggregations` | [Aggregation](#aggregation) |    | An object containing the aggregations to be retrieved |
| `sort` (since v3.1)| [Sort](#sort) |    | A list of sort parameters |
| `limit` | Integer | | Specify the number of results to retrieve. If no value is given the default value of 10 is used. |
| `offset` | Integer | | Specify the first result to return from the complete set of retrieved documents, the value is 0-based; If no value is given the default value of 0 is used. |

#### Aggregation

Aggregations are used to retrieve summaries of the data based on a search query. A single aggregation can be seen as 
a unit-of-work that builds analytic information over a set of documents. More aggregations can be combined to obtain 
a more complex summary.

The aggregation object properties define the aggregation to be calculated. Each property is an object that contains 
the configuration for the specific aggregation. The code block below shows an example of the object.

```json
{
    "aggregations" : {
        "properties.language" : {
            "limit" : 10,
            "counts_filtered" : false,
            "min_count": "2"
        },
        "type": {
            "limit" : 5,
            "counts_filtered" : false
        }
    }
}
```
The supported aggregations are:

- `type`
- `properties.created_at`
- `properties.updated_at`
- `properties.size`
- `copyright.owner.name`
- `copyright.usage.short`
- `uploader.name`

The table below defines the aggregation configuration properties

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `limit`  | Integer | ✔ | Only retrieve a certain amount of the most common aggregations. Minimum 0|
| `counts_filtered` | Boolean | ✔ | Calculate aggregations count after applying filters (True) or before (False)|
| `min-count`| Integer | Return only aggregations having at least min-count items (available from API `v3.2`, default value `1`. Note: in previous API versions this property can not be specified, and behaves as if min-value is set to zero).|

#### Sort

Sort allows to specify the ordering of the retrieved data.
Multiple sorting can be defined, where the sequence defines the priority of each sorting.
Note: this feature is available only from API `v3.1`.

```json
{
    "sort": [
        { "field": "properties.created_at", "order": "desc"},
        { "field": "properties.title", "order": "asc"}
    ]
}
```

The following Data fields can be used:

- `_score` (a virtual field, containing the relevance of the returned data for a given search)
- `uuid`
- `type`
- `copyright.owner.name`
- `copyright.owner.email`
- `copyright.usage.short`
- `properties.created_at`
- `properties.language`
- `properties.mime_type`
- `properties.size`
- `properties.title`
- `properties.updated_at`
- `uploader.name`
- `uploader.app_url`

### Supported filters

Filters enable to explore sub-sets of all available documents

Currently supported filters are:

- `uuid`
- `type`
- `properties.language`
- `properties.created_at`
- `properties.updated_at`
- `properties.size`
- `copyright.owner.name`
- `copyright.usage.short`
- `uploader.name`

### Geographic Location Filter

The Geographic Location filter is a special filter targetting the `data.geo_location` field.
As of now it enables to retrieve document whose `geo_location` intersect with a polygon.

The polygon must be defined as a serialized [GeoJSON polygon](https://tools.ietf.org/html/rfc7946#appendix-A.3)

```json
{
    "bounding_box": "{\"type\": \"Polygon\", \"coordinates\": [[[100,0],[101,0],[101,1],[100,1],[100,0]]]}"
}
```

> Coordinates must be expressed using the WGS84 reference system in longitude, latitude order

### Example

```json
{
    "search" : "K-Link",
    "filters" : "properties.language:en AND (properties.updated_at:[\"2008-07-28T14:47:31Z\" TO NOW] OR properties.created_at:[\"2008-07-28T14:47:31Z\" TO NOW]) AND copyright.usage.short:\"MPL-2.0\")",
    "geo_location_filter": {
        "bounding_box": "{\"type\": \"Polygon\", \"coordinates\": [[[100,0],[101,0],[101,1],[100,1],[100,0]]]}"
    },
    "aggregations" : {
        "properties.language" : {
            "limit" : 10,
            "counts_filtered" : false
        },
        "type": {
            "limit" : 5,
            "counts_filtered" : false
        }
    },
    "limit" : 30,
    "offset" : 0,
}
```

## K-Links Filter

> Available from API 3.7

Filter for documents in the specified K-Links. Documents may be published on different K-Links, this filter enable to retrieve documents published under a specific K-Link or the union of multiple K-Links. All K-Links must be accessible by the application with at least `data-search` permission.

The filter is in string form and accept the following formats

- `<empty>` (empty string, default) - Indicate to search accross default K-Link for the application (backward compatibility, only valid if the application has the association with only one K-Link).
- `*` or `all` - Indicate to search accross all K-Links.
- `id1` - Indicate to search in one explicit K-Link.
- `id1,id2` - Indicate to search across selected K-Links.

## SearchResults object

Describe how search results are returned.

| Property | Type   | Description |
| -------- | ------ | ----------- |
| `query`  | [SearchQuery](#searchquery-object) | The complete [SearchQuery](#searchquery-object) object as originally requested |
| `results`  | Object | An object holding the result from the search |
| `results.query_time` | Integer | The time needed to run the search query |
| `results.total_matches` | Integer | The total amount of found items. |
| `results.aggregations` | [AggregationResult](#aggregationresult) | Object representing the result of the aggregations |
| `results.items` | List of [Data](./api-objects.md#data-object) | The list of results, as [Data objects instances](./api-objects.md#data-object), up to the specified `query.limit`. |


### AggregationResult

The aggregation object properties define the retrieved aggregations. Each property is an array containing
the most common terms for the specific aggregation. The code block below shows an example of the object.

```json
{
    "copyright.usage.short": [
        {
            "value": "MPL-2.0",
            "count": 12
        },
        {
            "value": "MPL-3.0",
            "count": 3
        }
    ],
    "properties.language": [
        {
            "value": "en",
            "count": 10
        },
        {
            "value": "ru",
            "count": 5
        }
    ]
}
```

| Property | Type   | Description |
| -------- | ------ | ----------- |
| `value` | String | The value of the field that was aggregated |
| `count` | Integer | The number of results in the set that matches the term in `value`. Calculated before or after filtering based on the search query |


### Example SearchResults object


```json
{
    "query": {
        "search": "Example",
        "filters": "properties.language:en",
        "aggregations": {
            "copyright.usage.short": {
                "limit" : 5,
                "counts_filtered" : false
            }
        },
        "limit": 12,
        "offset": 0
    },
    "results": {
        "query_time": 6,
        "total_matches": 3,
        "aggregations": {
            "copyright.usage.short": [
                {
                    "value": "MPL-2.0",
                    "count": 1
                },
                {
                    "value": "MPL-3.0",
                    "count": 0
                }
            ]
        },
        "items": [
            {
                "uuid": "cc1bbc0b-20e8-4e1f-b894-fb067e81c5dd",
                "url": "http://localhost:8000/pages/video-1.html",
                "hash": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
                "type": "video",
                "klinks": [
                    {
                        "id": "100",
                        "name": "Example K-Link"
                    }
                ],
                "copyright": {
                    "owner": {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    },
                    "usage": {
                        "short": "CC-BY-SA-4.0",
                        "name": "Creative Commons BY-SA 4.0",
                        "reference": "https://spdx.org/licenses/CC-BY-SA-4.0.html"
                    }
                },
                "properties": {
                    "title": "Example of video about healthy bread",
                    "mime_type": "video/mp4",
                    "language": "en",
                    "created_at": "2008-09-28T15:47:31Z",
                    "updated_at": "2008-09-28T15:47:31Z",
                    "size": 200000,
                    "abstract": "This will be the abstract",
                    "thumbnail": "http://localhost:8000/images/video-1-thumb.png",
                    "video" : {
                        "duration": "10:13 min"
                    }
                },
                "source": {
                    "name": "Youth Ecological Center",
                    "url": "https://yec.k-box.net"
                },
                "authors": [
                    {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    }
                ]
            },
            {
                "uuid": "cc1bbc0b-20e8-4e1f-b894-fb087e81c5dd",
                "url": "http://localhost:8000/pages/video-1.html",
                "hash": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
                "type": "video",
                "copyright": {
                    "owner": {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    },
                    "usage": {
                        "short": "CC-BY-SA-4.0",
                        "name": "Creative Commons BY-SA 4.0",
                        "reference": "https://spdx.org/licenses/CC-BY-SA-4.0.html"
                    }
                },
                "properties": {
                    "title": "Example video on Land Management",
                    "mime_type": "video/mp4",
                    "language": "en",
                    "created_at": "2008-09-28T15:47:31Z",
                    "updated_at": "2008-09-28T15:47:31Z",
                    "size": 200000,
                    "abstract": "This will be the abstract",
                    "thumbnail": "http://localhost:8000/images/video-2-thumb.png",
                    "video" : {
                        "duration": "10:13 min"
                    }
                },
                "source": {
                    "name": "Youth Ecological Center",
                    "url": "https://yec.k-box.net"
                },
                "authors": [
                    {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    }
                ]
            },
            {
                "uuid": "cc1bbc0b-20e8-4e1f-b894-fb069e81c5dd",
                "url": "http://localhost:8000/pages/video-1.html",
                "hash": "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
                "type": "video",
                "klinks": [
                    {
                        "id": "100",
                        "name": "Example K-Link"
                    }
                ],
                "copyright": {
                    "owner": {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    },
                    "usage": {
                        "short": "CC-BY-SA-4.0",
                        "name": "Creative Commons BY-SA 4.0",
                        "reference": "https://spdx.org/licenses/CC-BY-SA-4.0.html"
                    }
                },
                "properties": {
                    "title": "Best practice for better nutrition",
                    "mime_type": "video/mp4",
                    "language": "en",
                    "created_at": "2008-09-28T15:47:31Z",
                    "updated_at": "2008-09-28T15:47:31Z",
                    "size": 200000,
                    "abstract": "This will be the abstract",
                    "thumbnail": "http://localhost:8000/images/video-3-thumb.png",
                    "video" : {
                        "duration": "10:13 min"
                    }
                },
                "source": {
                    "name": "Youth Ecological Center",
                    "url": "https://yec.k-box.net"
                },
                "authors": [
                    {
                        "name": "Mavluda Ergeshova",
                        "email": "hello@hello.com",
                        "contact": ""
                    }
                ]
            }
        ]
    }
}
```
