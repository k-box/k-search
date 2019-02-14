[![Build Status](https://travis-ci.com/k-box/k-search.svg?branch=develop)](https://travis-ci.com/k-box/k-search)

# K-Search

The K-Search is the interface of the search component for the K-Link project. It consists of a set of APIs
that enable third party clients to:

1. send data to be analyzed by the K-Search internal engine and 
2. perform a full-text search over such data.


## Installation

K-Search can be installed on most operating systems. The setup is heavily based on [Docker](https://www.docker.com/).

### Prerequisites

- Check the [system requirements](./docs/requirements.md).
- Use an operating system [supported by Docker](https://docs.docker.com/install/#server) (we use [Debian](https://debian.org) in production)
- Make sure you have installed the latest version of [Docker](https://docs.docker.com/install/linux/docker-ce/debian/) and [Docker Compose](https://docs.docker.com/compose/install/).

### Simplest installation

These few commands allow you to quickly install a K-Search **locally** on your computer for testing purposes.

* Create a directory: `mkdir k-search && cd k-search`
* Download configuration file: `curl -o docker-compose.yml https://raw.githubusercontent.com/k-box/k-search/develop/docker-compose.yml.dist`
* Start up services: `docker-compose up -d` (when running this for the first time, it will download a lot of data and take a while)
* Visit your K-Search: [http://localhost:8080/docs](http://localhost:8080/docs) (no login is required in the default setup).

## Usage

The general documentation is located under the [`docs` folder](./docs). 

Here is a brief table of contents for the most important parts:

- [HTTP API definition](./docs/api.md).
- [Framework Architecture](./docs/framework-architecture.md)
- [CLI Commands](./docs/commands.md)

## Development

The K-Search is implemented in PHP (v7.1.x) by using the [Symfony](http://symfony.com/)
framework.
The project structure follows the latest Symfony (v4.x) recommendation, as available at
[Symfony Architecture page](http://symfony.com/doc/current/quick_tour/the_architecture.html).

### Setup using Vagrant

This project uses [Drifter](https://github.com/liip/drifter) to provide a replicable development
environment, where most of the needed configurations and external services are installed and
configured.

The only requirement is a working installation of [Vagrant](https://www.vagrantup.com/).
Drifter supports both VirtualBox and the LXC containers, choose the one that best suits your needs.
Refer to the [Drifter documentation](https://liip-drifter.readthedocs.io/en/stable/requirements.html) 
for further details about its requirements and configuration.

To create working instance of a VM for the K-Search development and testing run the following
commands:

1. Obtain the K-Search code: `git clone https://github.com/k-box/k-search.git --recursive`
2. Start the VM and let Drifter provision it: `vagrant up`

The K-Search VM will be available at `http://ksearch.test`.

### Dependencies

The K-Search uses a set of Open Source technologies to support the full-text search and
data handling/manipulation functionality:

- [K-Search Engine](https://github.com/k-box/k-search-engine), based on [Apache Solr](http://lucene.apache.org/solr/): Search engine for data indexing and searching;
- [Apache Tika](https://tika.apache.org/): Text and metadata extraction from multiple file formats, bundled with Solr.



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
