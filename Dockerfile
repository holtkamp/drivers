FROM php:7.2-cli

RUN set -xe \
	&& apt-get update \
	&& docker-php-ext-install -j$(nproc) bcmath \
	&& pecl install redis-3.1.6 \
    && pecl install xdebug-2.6.0 \
    && docker-php-ext-enable redis xdebug
