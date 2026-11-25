FROM php:8.2-cli

RUN docker-php-ext-install mysqli

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
COPY . /app

# Varios procesos para atender envíos simultáneos
ENV PHP_CLI_SERVER_WORKERS=4

# Railway indica el puerto en la variable PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
