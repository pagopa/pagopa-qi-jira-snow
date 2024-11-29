FROM php:8.3-fpm

# update repo e installazione dipendenze php
RUN apt update -y && \
    apt -y upgrade && \
    apt -y install git libzip-dev zip curl && \
    docker-php-ext-install zip && \
    mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    mkdir -p download

# installazione composer
RUN cd /tmp && \
    curl -k https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php && \
    mv composer.phar /usr/local/bin/composer && \
    rm -f composer-setup.php