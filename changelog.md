# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

**Versioning schema**

The code version follows [Semantic Versioning](http://semver.org/), while the release version includes
a package number to track changes to the packaging: `{Major}.{Minor}.{Patch}-{package version}`.

The `Major`, `Minor` and `Patch` placeholders are defined as described in the Semantic Versioning
documentation, and:

 - the `{package version}` is a number that will start from `1` and increment with every changes made
   to the Docker image or repository structure.
 - The `{package version}` number is set to `1` when a new `{Major}.{Minor}.{Patch}` change is
   released (e.g. `3.0.1-1`, `3.0.1-2`, `3.0.2-1`, `3.1.0-1`).

## [Unreleased]

### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

## [3.6.0-1] - 2018-10-31

### Changed

- For respecting EU 679/2016 (GDPR) we have removed the required constraints from the following `Data` fields:
 - `authors` can now be null or an empty array
 - `copyright` can now be null. If not null both properties (`owner` and `usage`) can be null
 - `uploader` is not anymore a required field. If specified the `name` attribute is optional
- `uploader.email` data attribute can now be null in respect of EU 679/2016 (GDPR)
- Changes to required fields are marked with API 3.6. To comply with the regulation the changes have been 
  backported to previous API versions

## [3.5.1-1] - 2018-10-24

### Fixed

- Fix validation of search parameters against null values
- Fix validation of `geo_location` and `geo_location_filter` for WGS84 coordinate system (handle LUCENE-8522 bug)

## [3.5.0-1] - 2018-09-21
### Added
- `geo_location` field in the `Data` object for storing geographic information related to the data (API `3.5`)
- `geo_location_filter` to the `SearchQuery` object for filtering the data based on the `Data.geo_location` field (API `3.5`)

### Changed
- Updated Solr to v7.4.x
- Now Requires K-Search Engine v1.x

## [3.4.0-1] - 2018-07-27
### Added
- Download Data files when data has been added with `data_textual_contents` (and `KLINK_RETAIN_DOWNLOADED_CONTENTS=1`).
### Changed
- Enforce validation for the `request.id` property in JsonRPC requests

## [3.3.0-2] - 2018-07-16
### Fix
- Fixed Docker integration

## [3.3.0-1] - 2018-06-22
### Added
- Add `ksearch:data:compute-hash` command to compute the expected hash of a file
- Add `var/data.db` Database to handle Data processing queue
- Allow `data.status` to return status form the index, or from the "processing queue" state (API `v3.4`)
- Allow stored files to be served from `/files/{UUID}`
- Allow "data_textual_contents" from data.add to be retained (env variable: KLINK_RETAIN_DOWNLOADED_CONTENTS=[1|0])
- Add configuration for downloaded file retention policy (env variable: KLINK_RETAIN_DOWNLOADED_CONTENTS=[1|0])
- Add "uploader.organization" property (filterable, sortable, facetable)

### Changed
- Refactored data processing with advanced queue system (default to file, supports RabbitMQ and other message queue systems)
- Move Vagrant VM default hostname from `ksearch.dev` to `ksearch.test`
- Allow all data to be searched and get, ignoring the data.status value

### Deprecated
- Configuration of `KLINK_REGISTRY_API_URL` is deprecated when `KLINK_REGISTRY_ENABLED` is set to 0

### Fixed
- Fix Data.Search filter validation for groups
- Fix parsing of query sent in `data.search` "filters" params, handle validation and Solr errors
- Fix cache invalidation for downloaded files. The data.hash is now additionally check against the existing file

## [3.2.1-1] - 2018-03-20
### Changed
- Update Docker composer file, added docs and configurations
- Update Docker integration (ignore folders, use optimized autoloader during `composer install`)
- Enable `postbigrequest` plugin for SolariumPHP (use POST method for big requests against Solr APIs)

### Fixed
- Fix extraction of Java examples form PDF (updated solr-engine to v0.4.1)

## [3.2.0-1] - 2018-02-16
### Added
- Allow Data files to be pre-downloaded in `var/data-downloads/xx/UUID` (where `xx` are the first two chars of the
  Data UUID property). The file is checked for an indexable mime-type and kept after indexing.
  The file is deleted after the deletion of the related Data from the index.
- Add `filename` to the full-text search matching
- Add min-count parameter to search aggregations (API v3.2)

### Changed
- Update Solr submodule (use github repository, v0.4.0)
- Update Drifter submodule (use newest release with native SF4 support)

## [3.1.1-1] - 2018-01-19
### Added
- Add sorting of Data search results (API v3.1)

### Changed
- Update Solr submodule to v5.5.5
- Update Drifter to latest release (added support for Symfony4)
- Updated `CopyrightOwner`: set `name` as required, renamed `contact` to `address` (**breaking change**).
