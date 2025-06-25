FROM node:20.10 as node
FROM php:8.2-apache

RUN apt update && \
    apt install -y \
        build-essential \
        git \
        curl \
        gnupg \
        wget \
        curl \
        zip \
        unzip \
        lsb-release \
        libaio1 \
        libmecab2 \
        libnuma1 \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libicu-dev \
        neovim\
        libzip-dev \
        libpng-dev \
        libpq-dev && \
        docker-php-ext-install -j$(nproc) gd bcmath intl pdo pdo_pgsql pgsql zip

    
EXPOSE 80

COPY . /var/www/html

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN pecl install mongodb && \
    echo "extension=mongodb.so" >> /usr/local/etc/php/php.ini

# Composer
RUN wget -qO composer-setup.php https://getcomposer.org/installer
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"
RUN chmod +x /usr/bin/composer

CMD ["apache2-foreground"]
