FROM php:7.4-apache

ARG user
ARG uid
ARG APACHE_DOCUMENT_ROOT

RUN apt-get update && apt-get install -y git
RUN docker-php-ext-install pdo_mysql

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN a2enmod ssl

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

RUN openssl req -new -newkey rsa:4096 -days 3650 -nodes -x509 -subj \
    "/C=PL/ST=X/L=X/O=Polyglot/CN=*.localhost" \
    -keyout /home/$user/selfsigned.key -out /home/$user/selfsigned.crt
RUN chown -R $user:$user /home/$user/selfsigned.*

RUN sed -ri -e "s!/var/www/html!$APACHE_DOCUMENT_ROOT!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/etc/ssl/certs/ssl-cert-snakeoil.pem!/home/$user/selfsigned.crt!g" /etc/apache2/sites-available/default-ssl.conf
RUN sed -ri -e "s!/etc/ssl/private/ssl-cert-snakeoil.key!/home/$user/selfsigned.key!g" /etc/apache2/sites-available/default-ssl.conf

RUN a2ensite default-ssl

WORKDIR /var/www

USER $user
