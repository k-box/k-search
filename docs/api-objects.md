## Data object

The `Data` object define the metadata of the file.

| Property                     | Type   | Required | Description |
| ---------------------------- | ------ | -------- | ----------- |
| `uuid`                       | String | ✔ | Universally unique identifier. |
| `url`                        | String | ✔ | The URI where the source data is stored and retrievable. |
| `hash`                       | String | ✔ | The SHA-2 (SHA-512) hash of the data content. |
| `geo_location`               | String |   | The Geo location of the data, as an escaped GeoJson string. Coordinates must be in the [<longitude, latitude> order](https://tools.ietf.org/html/rfc7946#section-3.1.1) and use the [WGS84 (EPSG:4326) reference system](https://tools.ietf.org/html/rfc7946#section-4). |
| `type`                       | String | ✔ | The general type of the provided data. Can be only 'document' or 'video'. |
| `klinks`                     | [KLink[]](#k-link-object) |  | _Read only_. The K-Links on which the data is published to |
| `properties[]`               | Object | ✔ | The metadata of a piece of data. |
| `properties[mime_type]`      | String | ✔ | The Mime type of the provided data. |
| `properties[language]`       | String | ✔ | ISO code of the main language (explicitly the abstract and title). |
| `properties[title]`          | String | ✔ | The title. Can be a cleaned version of the filename |
| `properties[filename]`       | String | ✔ | The file name of the data. |
| `properties[created_at]`     | String | ✔ | Data’s or file’s creation date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format. |
| `properties[updated_at]`     | String |   | Data’s or file’s updated date in [RFC-3339](https://www.ietf.org/rfc/rfc3339.txt) format. |
| `properties[size]`           | Integer|   | Size of the referenced file. |
| `properties[abstract]`       | String |   | A short abstract about the data or document. |
| `properties[thumbnail]`      | String |   | The URI where the a thumbnail of this data is stored. |
| `properties[tags][]`         | List   |   | User-defined tags associated to the data (multiple). |
| `properties[collections][]`  | List   |   | Search data and browse within the hierarchy (multiple). Example: List [ "COLLECTION_ID_1", "COLLECTION_ID_2" ].  |
| `authors[]`                  | List   |   | List of authors (multiple). |
| `authors[][]`                | Object | ✔ | An object containing author's information. |
| `authors[][name]`            | String | ✔ | Name of the author. |
| `authors[][email]`           | String |   | Contact email of author. |
| `authors[][contact]`         | String |   | General contact information (e.g. URL to website or postal address). |
| `copyright[]`                | Object |   | An object containing information on the copyright. |
| `copyright[owner][]`         | Object |   | The owner of the copyright for the data. |
| `copyright[owner][name]`     | String | ✔ | Name of the copyright owner. |
| `copyright[owner][email]`    | String |   | Email of the copyright owner, for inquiries. |
| `copyright[owner][website]`  | String |   | URL of the copyright owner website for inquiries. |
| `copyright[owner][address]`  | String |   | Address of the copyright owner, if available. |
| `copyright[usage][]`         | Object |   | The conditions of use of the copyrighted data. |
| `copyright[usage][short]`    | String | ✔ | The associated usage permissions, as SPDX identifier (https://spdx.org/licenses/) and C for full copyright and PD for public domain. |
| `copyright[usage][name]`     | String | ✔ | The associated usage permissions to the piece of data. "All right reserved", "GNU General Public License", …, “Public Domain”. |
| `copyright[usage][reference]`| String |   | URL of the full license text (if applicable).. |
| `uploader[]`                 | Object |   | Information about the origin of the publication of data. |
| `uploader[name]`             | String |   | Freely definable name. Can be a single user, a project or a group. |
| `uploader[organization]`     | String |   | Freely definable organization (from API v3.3).|
| `uploader[url]`              | String |   | URL to an human readable website with information about the source entity. |
| `uploader[app_url]`          | String |   | The URL of the application that triggered the data upload. This data is coming from the Application data in the K-Link Registry. [internalOnly=true] |
| `uploader[email]`            | String |   | Contact email for upload inquiries. This data is coming from the Application data in the K-Link Registry. Can be empty in case the application maintainer did not opt to share contact details [internalOnly=true] |


**Internal structure** (computed by the K-Search and not exposed through the API)

| Property | Type   | Required | Description |
| -------- | ------ | -------- | ----------- |
| `application_id`  | Integer  | ✔ | The application (ID) that added the data |


### Data object extension for video files

In case that `type`==`video`. It is expected to extend the `properties` by this data:

| Property                           | Type   | Required | Description |
| ---------------------------------- | ------ | -------- | ----------- |
| `properties[video][]`              | Object | ✔ | Object containing information on the video file. |
| `properties[video][duration]`      | String | ✔ | Duration of the video. |
| `properties[video][source]`        | Object | ✔ | Information about the source file. |
| `properties[video][source][format]`    | String | ✔ | Format of the video file. |
| `properties[video][source][resolution]`| String | ✔ | Resolution of the video. |
| `properties[video][source][bitrate]`   | String |   | Bitrate of the video file. |
| `properties[video][streaming][]`       | List   |   | Information about the streaming services. |
| `properties[video][streaming][][type]` | Object | ✔ | URL of the video stream type (youtube, dash, hls). |
| `properties[video][streaming][][url]`  | Object | ✔ | URL of the video stream. |
| `properties[audio][]`              | List   |   | Audio tracks attached to the video (multiple). |
| `properties[audio][][]`            | Object |   | Object with information on one audio track |
| `properties[audio][][language]`    | String |   | Main language(s) spoken in the audio track, free text. |
| `properties[audio][][bitrate]`     | String | ✔ | Bitrate of the audio track. |
| `properties[audio][][format]`      | String | ✔ | Format of the audio track. Example: "mp3" |
| `properties[subtitles][]`          | List   |   | Subtitles attached to the video (multiple). |
| `properties[subtitles][][]`        | Object |   | Object with information on one subtitles track. |
| `properties[subtitles][][language]`| String | ✔ | Language of the subtitles. |
| `properties[subtitles][][file]`    | String | ✔ | The URI where the subtitle file is stored and retrievable (or "built-in for wrapped subtitles"). |
| `properties[subtitles][][format]`  | String | ✔ | Format of the subtitles track. |


## Error object

| Property  | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| `code`    | Integer | ✔        | JSON-RPC inspired error codes. (minimum: -32768; maximum: -30000) |
| `message` | String  | ✔        | Human readable error message. |
| `data`    | Object  | ✔        | Additional information can optionally be provided on errors for better debugging. |


## Status object

| Property  | Type    | Required | Description |
| --------- | ------- | -------- | ----------- |
| `code`    | Integer | ✔        | JSON-RPC inspired error codes. (minimum: -32768; maximum: -30000) |
| `message` | String  | ✔        | Human readable status message. |

## Data Status object

| Property     | Type   | Required | Description |
| ------------ | ------ | -------- | ----------- |
| `status`     | String | ✔ | The status of the requested data |
| `type`       | String |   | The requested status type (since v3.4) |
| `request_id` | String |   | The request that originated the data to be in this state (since v3.4) |
| `request_received_at` | String |   | The time the originated request was made (since v3.4) |
| `message`    | String  | ✔ | Human readable status message |

The returned `status` may be:
- `index.ok`: Indexing was ok
- `index.fail` : Failure during the indexing of the Data
- `queued.ok` : The Data has been queued for downloading and indexing
- `download.fail` : The download of the data failed

## K-Link object

| Property | Type    | Description |
| -------- | ------- | ----------- |
| `id`     | String  | The K-Link identifier |
| `name`   | String  | The K-Link assigned name. |

