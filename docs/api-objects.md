## Data object

| Property                     | Type   | Required | Description |
| ---------------------------- | ------ | -------- | ----------- |
| `uuid`                       | String | ✔ | Universally unique identifier. |
| `source[]`                   | Object | ✔ | The source where the data is stored. |
| `source[type]`               | String | ✔ | The type of the source data (accepted values are `file` or `page`). |
| `source[value]`              | String | ✔ | The source url. |
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
| `uploader[]`                   | Object |✔  | Who pushed the data Data, it can be a User, an Organization or a Project (composed by multiple organizations). |
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
| `properties[video][duration]`      | String | ✔ | Duration of the video. |
| `properties[video][source]`        | Object | ✔ | Information about the source file. |
| `properties[video][source][format]`    | String | ✔ | Format of the video file. |
| `properties[video][source][resolution]`| String | ✔ | Resolution of the video. |
| `properties[video][source][bitrate]`   | String |   | Bitrate of the video file. |
| `properties[video][streaming][]`       | List   |   | Information about the streaming services. |
| `properties[video][streaming][][type]` | Object |   | URL of the video stream type (youtube, dash, hls). |
| `properties[video][streaming][][url]`  | Object |   | URL of the video stream. |
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

