[![Build Status](https://travis-ci.org/k-box/k-search.svg?branch=develop)](https://travis-ci.org/k-box/k-search)

# K-Search

The K-Search is the search-related component of the K-Link project, it consists of a set of APIs
that enable third party clients to (1) send data to be analyzed by the K-Search internal search
engine and (2) perform a full-text search over such data.

The K-Search has been implemented for PHP (v7.1.x) by using the [Symfony](http://symfony.com/)
framework.
The project structure follows the latest Symfony (v4.x) recommendation, as available at
[Symfony Architecture page](http://symfony.com/doc/current/quick_tour/the_architecture.html).

## Getting started

### Development Setup

This project uses [Drifter](https://github.com/liip/drifter) to provide a replicable development
environment, where most of the needed configurations and external services are installed and
configured.

The only requirement is a working installation of [Vagrant](https://www.vagrantup.com/).
Drifter supports both VirtualBox and the LXC containers, choose the one that best suits your needs.
Refer to the [Drifter documentation](https://liip-drifter.readthedocs.io/en/stable/requirements.html) 
for further details about its requirements and configuration.

To create working instance of a VM for the K-Search development and testing run the following
commands:

1. Obtain the K-Search code: `git clone git@git.klink.asia:main/k-search.git --recursive`
2. Start the VM and let Drifter provision it: `vagrant up`

The K-Search VM will be available at `http://ksearch.test`.

### Dependencies

The K-Search uses a set of Open Source technologies to support the full-text search and
data handling/manipulation functionality:

- [Apache Solr](http://lucene.apache.org/solr/): Search engine for data indexing and searching;
- [Apache Tika](https://tika.apache.org/): Text and metadata extraction from multiple file formats, bundled with Solr.

## API

The K-Search API documentation is available in [API section](./docs/api.md).

## Further Readings

The general documentation is located under the [`docs` folder](./docs). 
Here is a brief table of contents for the most important parts:

- [API definition](./docs/api.md).
- [Framework Architecture](./docs/framework-architecture.md)
- [CLI Commands](./docs/commands.md)

## Contributing

Contributions to the K-Search API are accepted by opening a Pull Request on the project page on GitHub.

1. the code must conform to the [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html);
2. the code must be covered by tests (`PHPUnit`, ...);
3. a changelog is provided with a summary of the changes.

The K-Search API code is regularly checked with the following tools:

- `php-cs-fixer`: to comply to the Symfony Coding Standards and additional PSRs (see: http://cs.sensiolabs.org)
- `phpstan`: Static analysis checks for PHP with a check-level of `7` (see: https://github.com/phpstan/phpstan)  
- `phpunit`: PHP testing framework (see: https://phpunit.de/)

## License

This project is licensed under the AGPL v3 license, see [LICENSE.txt](./LICENSE.txt).
