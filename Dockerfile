ARG PHP_VERSION=8.1

FROM php:${PHP_VERSION}-cli-alpine as base

RUN set -eux; \
	apk add --no-cache --virtual .build-deps \
		$PHPIZE_DEPS \
	; \
	\
	docker-php-ext-configure pcntl --enable-pcntl; \
	docker-php-ext-install -j$(nproc) \
    	pcntl\
    	; \
	pecl install \
        redis \
	; \
	pecl clear-cache; \
	docker-php-ext-enable \
        redis \
	; \
    rm -r /tmp/pear; \
	\
	runDeps="$( \
		scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
			| tr ',' '\n' \
			| sort -u \
			| awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
	)"; \
	apk add --no-cache --virtual .phpexts-rundeps $runDeps; \
	\
	apk del .build-deps

RUN rm /usr/local/bin/php-cgi  \
    && rm /usr/local/bin/phpdbg \
    && rm /usr/src -Rf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=jrottenberg/ffmpeg:3-scratch / /
WORKDIR /app
COPY composer.json composer.lock ./
COPY src/ ./src/
COPY worker.php worker.php

FROM base as prod
RUN set -eux; \
    if [ -f composer.json ]; then \
		composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
		composer clear-cache; \
        composer dump-autoload --classmap-authoritative --no-dev; \
    fi
ENTRYPOINT ["php", "/app/worker.php"]

FROM base as dev
RUN apk add --update \
    rsync \
    && rm  -rf /tmp/* /var/cache/apk/*

RUN mkdir /opt/cache/vendor -p

RUN set -eux; \
    if [ -f composer.json ]; then \
		composer install --prefer-dist --no-autoloader --no-scripts --no-progress; \
		composer clear-cache; \
        composer dump-autoload; \
    fi
COPY ./dev-entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]

FROM ossrs/srs:6 as stream-server
COPY ./docker/srs/rtsp.conf /usr/local/srs/conf/rtsp.conf
ENTRYPOINT ["./objs/srs", "-c", "conf/rtsp.conf"]
