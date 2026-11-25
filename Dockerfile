FROM php:8.2-apache

RUN docker-php-ext-install mysqli

# Dejar un único MPM activo (prefork, el que usa mod_php)
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork

COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/

# Railway indica el puerto en la variable PORT
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground
