## Data object

| Property                     | Type   | Required | Description |
| ---------------------------- | ------ | -------- | ----------- |
| `uuid`                       | String | ✔ | Universally unique identifier. |
| `source[]`                   | List   | ✔ | The sources where the data is stored and retrievable. |
| `source[][type]`             | String | ✔ | The type of the source data (accepted values are mime types and `youtube`). |
| `source[][value]`            | String | ✔ | the source value, e.g. HTTPS url, a Youtube video id (if type is set to `youtube`),... |
| `type`                       | String | ✔ | The general type of the provided data. Can be only 'document' or 'video'. |
| `properties[]`               | Object | ✔ | The metadata of a piece of data. |
| `properties[mime_type]`      | String | ✔ | The Mime type of the provided data. |
| `properties[language]`       | String | ✔ | ISO code of the main language (explicitly the abstract and title). |
| `properties[title]`          | String | ✔ | The data set or document title. |
| `properties[filename]`       | String | ✔ | The file name of the data. |
| `properties[created_at]`     | String | ✔ | Data’s or document’s creation date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format. |
| `properties[updated_at]`     | String |   | Data’s or document’s updated date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format. |
| `properties[size]`           | Integer|   | The file size of the data. |
| `properties[abstract]`       | String |   | A short abstract about the data or document. |
| `properties[thumbnail]`      | String |   | The URI where the a thumbnail of this data is stored. |
| `properties[tags][]`         | List   |   | User-definet tags associated to the data (multiple). |
| `properties[hierarchy][]`    | List   |   | Search data and browse within the hierarchy (multiple). However be careful to expose the hierarchy to a public search index, as it may contain confidential data. Example: List [ "prj01/forestry/", "prj04/forestry/foobar/" ]. |
| `author[]`                   | List   | ✔ | List of authors (multiple). |
| `author[][]`                 | Object | ✔ | An object containing author's information. |
| `author[][name]`             | String | ✔ | Name of the author. |
| `author[][email]`            | String |   | Contact email of author. |
| `author[][contact]`          | String |   | General contact information (e.g. URL to website or postal address). |
| `copyright[]`                | Object | ✔ | An object containing information on the copyright. |
| `copyright[owner][]`         | Object | ✔ | The copyright owner and information on how to contact for any inquiries. |
| `copyright[owner][name]`     | String |   | Name of the copyright owner. |
| `copyright[owner][email]`    | String |   | Email of the copyright owner. |
| `copyright[owner][contact]`  | String | ✔ | General contact information (e.g. URL to website or postal address). |
| `copyright[usage][]`         | Object | ✔ | The conditions of use of the copyrighted data. |
| `copyright[usage][short]`    | String | ✔ | The associated usage permissions, as SPDX identifier (https://spdx.org/licenses/) and C for full copyright and PD for public domain. |
| `copyright[usage][name]`     | String | ✔ | The associated usage permissions to the piece of data. "All right reserved", "GNU General Public License", …, “Public Domain”. |
| `copyright[usage][reference]`| String |   | URL of the full license text (if applicable).. |
| `uploader[]`                   | Object | ✔ | The entity that pushed the Data, ideally is also the entity where the data has been uploaded or created. |
| `uploader[name]`               | String | ✔  | Freely definable source (could be an organization or project). |
| `uploader[url]`                | String |   | URL to an human readable website with information about the source entity. |
| `uploader[app_url]`            | String | ✔  | The URL of the application that triggered the data upload. [readOnly=true] |
| `uploader[email]`              | String |   | Contact email to of an administrator, who can be contacted in case of any issues related to uploaded documents. This data is coming from the Application data in the K-Link Registry. [readOnly=true] |
| `uploader[upload_reference]`   | String |   | Information which lets the source contact track back internally the origin of the data. It is suggested to save this information on the client side together with the id of the API request. In easier setups it could also just be the encoded or encrypted “user id” value on the client side. We recommend not to expose personal data here.. |


**Internal structure** (computed by the K-Search and not exposed through the API)

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `application_id`  | Integer | ✔ | The application (ID) that added the data |


## Data object extension for video files

In case that `type`==`video`. It is expected to extend the `properties` by this data:

| Property                           | Type   | Required | Description |
| ---------------------------------- | ------ | -------- | ----------- |
| `properties[video][]`              | Object | ✔ | Object containing information on the video file. |
| `properties[video][format]`        | String | ✔ | Format of the video file. |
| `properties[video][duration]`      | String | ✔ | Duration of the video. |
| `properties[video][resolution]`    | String | ✔ | Resolution of the video. |
| `properties[video][bitrate]`       | String |   | Bitrate of the video file. |
| `properties[audio][]`              | List   |   | Audio tracks attached to the video (multiple). |
| `properties[audio][][]`            | Object |   | Object with information on one audio track |
| `properties[audio][][language]`    | String |   | Main language(s) spoken in the audio track, free text. |
| `properties[audio][][bitrate]`     | String |   | Bitrate of the audio track. |
| `properties[audio][][format]`      | String |   | Format of the audio track. |
| `properties[subtitles][]`          | List   |   | Subtitles attached to the video (multiple). |
| `properties[subtitles][][]`        | Object |   | Object with information on one subtitles track. |
| `properties[subtitles][][language]`| String |   | Language of the subtitles. |
| `properties[subtitles][][file]`    | String |   | The URI where the subtitle file is stored and retrievable (or "built-in for wrapped subtitles"). |
| `properties[subtitles][][format]`  | String |   | Format of the subtitles track. |


## Error object

| Property  | Type    | Required   | Description |
| --------- | ------- | ---------- | ----------- |
| `code`    | Integer | ✔          | JSON-RPC inspired error codes. (minimum: -32768; maximum: -30000) |
| `message` | String  | ✔          | Human readable error message. |
| `data[]`  | Object  | ✔          | Additional information can optionally be provided on errors for better debugging. |


## Status object

| Property  | Type    | Required   | Description |
| --------- | ------- | ---------- | ----------- |
| `code`    | Integer | ✔          | JSON-RPC inspired error codes. (minimum: -32768; maximum: -30000) |
| `message` | String  | ✔          | Human readable status message. |


## SearchQuery object

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `search`                  | String  | ✔ | The main terms to search for. If nothing is specified, an empty result set will be returned. |
| `filters`                 | String  |   | Search filters in the [Lucene query parser syntax](https://lucene.apache.org/core/2_9_4/queryparsersyntax.html) |
| `aggregations[]`          | List    |   | An object containing the aggregations to be retrieved |
| `aggregations[][]`        | Object  |   | An object containing the aggregations to be retrieved |
| `aggregations[][name]`    | Object  |   | Only retrieve a certain amount of the most common aggregations |
| `aggregations[][count]`   | Object  |   | Only retrieve a certain amount of the most common aggregations |
| `aggregations[][filtered]`| Boolean |   | Calculate aggregations count after applying filters (True) or before (False) (TODO: What is standard?)|
| `limit`                   | Integer |   | Specify the number of results to retrieve. If no value is given the default value of 10 is used. |
| `offset`                  | Integer |   | Specify the first result to return from the complete set of retrieved documents, the value is 0-based; If no value is given the default value of 0 is used. |

**Example**

```
{
    "search" : "K-Link",
    "filters" : "(language=en AND (type=(spreadsheet OR presentation))) AND (created_at=("2017-03-10"-"2017-03-20") OR updated_at<"2017-03-15") "
    "aggregations" : [
        {
            "name": "language",
            "limit" : 10,
            "filtered" : False
        },
        {
            "name": "type",
            "filtered" : True
        }
    ],
    "limit" : 30,
    "offset" : 0,
}
```

## SearchResults object

| Property | Type   | Required | Description |
| --------- | ------ | -------- | ----------- |
| `query`                | Object  | ✔ | [`SearchQuery object`](https://git.klink.asia/main/k-search/blob/master/docs/api-search-objects.md#searchquery-object) |
| `query_time`           | Integer | ✔ | The time needed to run the search query |
| `total_matches`        | Integer | ✔ | The total amount of found items. |
| `aggregations[]`       | List    |   | Results of the aggregations |
| `aggregations[][name]` | String  |   | Count of the results according to aggregations |
| `aggregations[][count]`| Integer |   | Count of the results according to aggregations |
| `items[]`              | List    | ✔ | Ordered list of search results (multiple). |
| `items[data]`          | Object  | ✔ | [`Data object`](https://git.klink.asia/main/k-search/blob/master/docs/api-data-objects.md#data-object). |
