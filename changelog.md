# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/0.3.0/).

**Versioning schema**

The code version follows [Semantic Versioning](http://semver.org/), while release version includes also a package number to track changes to the packaging: `{Major}.{Minor}.{Patch}-{package version}`.

The `{package version}` is a number that will start from `1` and increment with every changes made to the Docker image or repository structure. 
`{package version}` will always return to `1` when a new `{Major}.{Minor}.{Patch}` change is performed, e.g. `3.0.1-1`, `3.0.1-2`, `3.0.2-1`, `3.1.0-1`. 


## 3.1.1

### Added

- Add sorting of Data search results

### Changed
- Update Solr submodule to v5.5.5
- Update Drifter to latest release (added support for Symfony4)
- Updated `CopyrightOwner`: set `name` as required, renamed `contact` to `address` (**breaking change**).
