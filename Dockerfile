FROM composer:latest@sha256:e0c9ac329256c25b0dee572df37d986570fb26bb6baaa7d0abe69b84181701e1 as composer
RUN mkdir -p /tmp/repo
COPY . /tmp/repo/
WORKDIR /tmp/repo
RUN rm -rf LICENSE README.md .gitignore && composer install


FROM php:8.3-apache@sha256:0cf609bab6581684ed08145132a88ec2a47fb1ddfd14148945076862492ffe8b
COPY --from=composer /tmp/repo /var/www/html
RUN apt -y update && \
   apt -y upgrade && \
   apt -y install curl && \
   sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
   sed -i 's/VirtualHost \*:80/VirtualHost \*:8080/' /etc/apache2/sites-available/000-default.conf && \
   sed -i 's/VirtualHost \*:80/VirtualHost \*:8080/' /etc/apache2/sites-enabled/000-default.conf && \
   mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
   mkdir -p download && \
   a2enmod rewrite

# Change the Apache listening port from 80 to 8080
# RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
#     && sed -i 's/VirtualHost \*:80/VirtualHost \*:8080/' /etc/apache2/sites-available/000-default.conf

### Apache (proxies to MapProxy).
EXPOSE 8080
