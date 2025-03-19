FROM php:8.4-fpm

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo pdo_pgsql redis pcntl intl bcmath zip

RUN pecl install xdebug-3.4.0

COPY php.ini /usr/local/etc/php/php.ini
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ARG XDEBUG_CLIENT_HOST=host.docker.internal
ARG XDEBUG_IDE_KEY=PHPSTORM
RUN echo "xdebug.client_host=${XDEBUG_CLIENT_HOST}" >> /usr/local/etc/php/conf.d/xdebug.ini && \
    echo "xdebug.ide_key=${XDEBUG_IDE_KEY}" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN curl -s https://getcomposer.org/installer | php \
  && mv composer.phar /usr/bin/composer

WORKDIR /var/www
RUN rm -rf /var/www/html
USER 1000

EXPOSE 9000
