FROM composer:latest@sha256:e0c9ac329256c25b0dee572df37d986570fb26bb6baaa7d0abe69b84181701e1 as composer
RUN mkdir -p /tmp/repo
COPY . /tmp/repo/
WORKDIR /tmp/repo
RUN rm -rf LICENSE README.md .gitignore && composer install


FROM php:8.3-apache@sha256:71142afb203a28e9c8d68bfb1937fb6d5b50c4d33214a178c98bf91e7e3a4bf0
COPY --from=composer /tmp/repo /var/www/html
RUN apt -y update && \
   apt -y upgrade && \
   apt -y install curl && \
   mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
   mkdir -p download && \
    a2enmod rewrite