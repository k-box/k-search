
### K-Link Core API layer

#### Requirements




#### Core setup procedure so far

composer install will create a default `app/config/parameters.yml` is some parameters are omitted, please ensure that this file will be available and will be correct on the target system after extracting the Core archive.


#### Edits for Continuous Integration

- added `app/config/parameters.example.yml` with some defaults to enable the construction of a base build on the CI