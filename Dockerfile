## Build the K-Search docker image
## using a multi-stage approach

FROM edbizarro/gitlab-ci-pipeline-php:7.2 AS builder

ENV APP_ENV prod
ENV APP_DEBUG 0

COPY --chown=php:php . /var/www/html
RUN \
    composer install --no-dev --prefer-dist --no-ansi --no-interaction --no-progress &&\
    make &&\
    rm -rf docker &&\
    rm -rf tests &&\
    rm -rf var/cache &&\
    rm -f Makefile

## Assembling the K-Search image
FROM php:7.2-apache AS php

ENV APP_ENV prod
ENV APP_DEBUG 0

# default of parent image: expose apache2 on port 80.
EXPOSE 80

# Install neccessary dependencies
RUN \
    apt-get update \
    && apt-get install --no-install-recommends --no-install-suggests --yes \
        supervisor \
        # icu is required by php-intl
        libicu-dev \
        # gettext provides envsubst command
        # gettext &&\
    && docker-php-ext-install -j$(nproc) intl \
    && pecl install apcu && docker-php-ext-enable apcu \ 
    && rm -rf /tmp/pear \
    && docker-php-source delete \
    && apt-get clean \
    && rm -r /var/lib/apt/lists/*

# The next command is to prepare apache for serving symfony projects.
# since they have a different layout to other PHP apps, the default
# configuration will not work.
RUN { \
        echo "<VirtualHost *:80>"; \
        echo "\tDocumentRoot /var/www/k-search/public"; \
        echo "\t<Directory /var/www/k-search/public>"; \
        echo "\t\tAllowOverride None"; \
        echo "\t\tOrder Allow,Deny"; \
        echo "\t\tAllow from All"; \
        echo "\t\tHeader always set Access-Control-Allow-Origin \"*\""; \
        echo "\t\tHeader always set Access-Control-Allow-Methods \"GET, POST, OPTIONS\""; \
        echo "\t\tHeader always set Access-Control-Max-Age \"1\""; \
        echo "\t\tHeader always set Access-Control-Allow-Headers \"x-requested-with, Content-Type, origin, authorization, accept\""; \
        echo "\t\t<IfModule mod_rewrite.c>"; \
        echo "\t\t\tOptions -MultiViews"; \
        echo "\t\t\tRewriteEngine On"; \
        echo "\t\t\t# Added a rewrite to respond with a 200 SUCCESS on every OPTIONS request."; \
        echo "\t\t\tRewriteCond %{REQUEST_METHOD} OPTIONS"; \
        echo "\t\t\tRewriteRule ^(.*)$ \" \" [R=200,QSA,L]"; \
        echo "\t\t\tRewriteCond %{REQUEST_FILENAME} !-f"; \
        echo "\t\t\tRewriteCond %{HTTP:Authorization} ^(.+)$"; \
        echo "\t\t\tRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]"; \
        echo "\t\t\tRewriteCond %{REQUEST_FILENAME} !-f"; \
        echo "\t\t\tRewriteRule ^(.*)$ index.php [QSA,L]"; \
        echo "\t\t</IfModule>"; \
        echo "\t</Directory>"; \
        echo "\t<Directory /var/www/k-search>"; \
        echo "\t\tOptions FollowSymlinks"; \
        echo "\t</Directory>"; \
        echo "\t<Directory /var/www/k-search/public/bundles>"; \
        echo "\t\t<IfModule mod_rewrite.c>"; \
        echo "\t\t\tRewriteEngine Off"; \
        echo "\t\t</IfModule>"; \
        echo "\t</Directory>"; \
        echo "\tPassEnv APP_ENV APP_DEBUG"; \
        echo "</VirtualHost>" ; \
    } | tee "$APACHE_CONFDIR/sites-available/symfony.conf" \
    && a2dissite 000-default && a2enmod rewrite && a2enmod headers && a2ensite symfony

COPY docker/conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

WORKDIR /var/www/k-search

# copy over our entire directory into /var/www in the container
COPY \
    --from=builder \
    --chown=www-data:www-data \
    /var/www/html/ \
    /var/www/k-search

ENTRYPOINT ["/usr/local/bin/start.sh"]
CMD []
