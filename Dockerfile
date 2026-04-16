FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_pgsql pgsql

RUN echo "PidFile /tmp/apache2.pid" >> /etc/apache2/apache2.conf
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
