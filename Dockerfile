FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html
COPY contrib/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY contrib/php.ini /usr/local/etc/php/conf.d/99-custom.ini
