# use a debian-based container that has apache and php already installed
FROM php:7-apache

# default of parent image: start apache2 and PHP on port 80.
EXPOSE 80

# Install neccessary tools that are still missing for composer.
RUN \
    apt-get update &&\
    apt-get install --no-install-recommends --yes \
        git \
        supervisor \
        unzip \
        # icu is required by php-intl
        libicu-dev \
        # gettext provides envsubst command
        gettext &&\
    # remove the cached packages and artifacts created during installation
    apt-get clean &&\
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* &&\
    # install the missing 'intl' extension
    docker-php-ext-install -j$(nproc) intl &&\
    # composer is still missing, we install it the way it says on the
    # website.
    curl -sS https://getcomposer.org/installer  \
        | php -- --install-dir=/usr/local/bin --filename=composer

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
        echo "</VirtualHost>" ; \
    } | tee "$APACHE_CONFDIR/sites-available/symfony.conf" \
    && a2dissite 000-default && a2enmod rewrite && a2enmod headers && a2ensite symfony

# copy over our entire directory into /var/www in the container
COPY . /var/www/k-search

# all further commands will be run relative to our main directory
WORKDIR /var/www/k-search

RUN \
    # prepare our environment file, variables defined inside will get
    # overwritten by environment variables.
    cp .env.dist .env &&\
    # install php dependencies with composer and fix file ownership (since
    # composer is being run as root user here)
    composer install --prefer-dist &&\
    chown www-data:www-data . --recursive

# run swagger to create documentation automatically
RUN \
    mkdir -p public/bundles/swagger-ui &&\
    cp \
        vendor/swagger-api/swagger-ui/dist/swagger-ui* \
        public/bundles/swagger-ui/ &&\
    vendor/bin/swagger --output public src/Model/ src/Controller/ &&\
    # prepare our environment file, variables defined inside will get
    # overwritten by environment variables.
    cp .env.dist .env &&\
    # fix file ownership, since some commands were being run with root
    chown www-data:www-data . --recursive

COPY docker/conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ENTRYPOINT ["supervisord"]
CMD []
