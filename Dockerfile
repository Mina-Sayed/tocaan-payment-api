FROM php:8.5-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        git \
        curl \
        sqlite \
        zip \
        unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
