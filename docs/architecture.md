## Project Architecture

Due to historical reasons (the K-Search component was initially implemented on Symfony 2.x)
the K-Search code has been structured in multiple bundles, each one responsible of a part
of the whole set of APIs.
There is an additional bundle where the common entities and services are defined.

__[To be done after APIv3 refactoring]__


## Testing

The K-Search comes with a set of unit tests and integration tests, the latter mostly related to the
Thumbnail generation and the SOLR integration.
Tests are usually annotated by their requirements:
 - `@group pdfbox` for tests requiring PDFBox to be available
 - `@group phantomjs` for tests requiring PhantomJS to be available
 - `@group solr` for tests requiring a running instance of SOLR

Tests are run using `phpunit`, filters can be applied to limit the tests only for the tagged ones.

## Coding Standards

This project follows the [Symfony coding stadards](http://symfony.com/doc/current/contributing/code/standards.html)
definition.
It includes the [PSR-0](http://www.php-fig.org/psr/psr-0/), [PSR-1](http://www.php-fig.org/psr/psr-1/),
[PSR-2](http://www.php-fig.org/psr/psr-2/) and [PSR-4](http://www.php-fig.org/psr/psr-4/) standards.

The [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) tool is used to ensure the code
respects such standards, run it as `vendor/bin/php-cs-fixer fix --dry-run`.
The current `PHP-CS-Fixer` configuration is available in the `/.php_cs.dist` file.
