
# K-Search

The K-Search is the search-related component of the K-Link project, it consists of a set of APIs
that enable third party clients to (1) send data to be analyzed by the K-Search internal search
engine and later (2) perform a full-text search over that data.

The K-Search has been implemented for PHP (v5.6.x) by using the [Symfony](http://symfony.com/)
framework.
The project structure follows the latest Symfony (v3.2) recommendation, as available at
[Symfony Architecture page](http://symfony.com/doc/current/quick_tour/the_architecture.html).

## Development Setup

This project uses [Drifter](https://github.com/liip/drifter) to provide a replicable development
environment, where most of the needed configurations and external services are installed and
configured.
The only requirement is a working installation of [Vagrant](https://www.vagrantup.com/), Drifter
supports both VirtualBox and the fast LXC containers, choose the one that best suits your needs.
Refer to the Drifter documentation for further details about its requirements and configuration.

To create working instance of a VM for the K-Search development and testing run the following
commands:

1. Obtain the K-Search code: `git clone git@git.klink.asia:main/k-search.git --recursive`
2. Start the VM and let Drifter provision it: `vagrant up`

The K-Search VM will be available at `http://kcore.dev`.

### Dependencies

The K-Search uses a set of Open Source technologies to support the full-text search and
data handling/manipulation functionality:

- [Apache Solr](http://lucene.apache.org/solr/): Search engine for data indexing and searching
- [Apache Tika](https://tika.apache.org/): Text and metadata extraction from multiple file formats
- [Apache PDFBox](https://pdfbox.apache.org/): PDF reading library, for PDF preview
- [PhantomJs](http://phantomjs.org/): Headless webbrowser, for Web documents preview generation
- [Clavin](https://github.com/Berico-Technologies/CLAVIN): Location extraction and geo-tagging
    library (currently *disabled*)

## Further Readings:

#### Framework Architecture

See [Architecture](docs/architecture.md)

#### CLI Commands

See [Architecture](docs/architecture.md)

#### API Definition

The API documentation and definition is available [here](docs/api.md).
