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
- Add configuration for downloaded file retention policy (env variable: KLINK_RETAIN_DOWNLOADED_FILES=[1|0])
- Add "uploader.organization" property (filterable, sortable, facetable)

### Changed
- Allow all data to be searched and get, ignoring the data.status value

### Deprecated
### Removed
### Fixed
- Fix parsing of query sent in `data.search` "filters" params, handle validation and Solr errors
- Fix cache invalidation for downloaded files. The data.hash is now additionally check against the existing file

### Security

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
