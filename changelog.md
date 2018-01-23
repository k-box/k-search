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
- Update Solr submodule (use github repository)
- Update Drifter submodule (use newest release with native SF4 support)

### Deprecated
### Removed
### Fixed
### Security

## v3.1.1-1

### Added
- Add sorting of Data search results (API v3.1)

### Changed
- Update Solr submodule to v5.5.5
- Update Drifter to latest release (added support for Symfony4)
- Updated `CopyrightOwner`: set `name` as required, renamed `contact` to `address` (**breaking change**).
