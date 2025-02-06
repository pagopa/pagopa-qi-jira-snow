FROM composer:latest as composer
RUN mkdir -p /tmp/repo
COPY . /tmp/repo/
WORKDIR /tmp/repo
RUN rm -rf LICENSE README.md .gitignore && composer install


FROM php:8.3-apache
COPY --from=composer /tmp/repo /var/www/html
RUN apt -y update && \
   apt -y upgrade && \
   apt -y install curl && \
   mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
   mkdir -p download && \
    a2enmod rewrite

### Apache (proxies to MapProxy).
EXPOSE 8080
