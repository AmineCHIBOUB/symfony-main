FROM php:8.3-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf &&  apt-get update \
  &&  apt-get install -y --no-install-recommends \
  locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev unzip librust-glib-sys-dev \
  &&  echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
  &&  locale-gen

RUN apt-get install -y --no-install-recommends libnss3-dev libxcb1-dev

RUN echo 'alias sf="php bin/console"' >> ~/.bashrc
RUN echo 'alias test="php bin/phpunit"' >> ~/.bashrc

RUN curl -sS https://getcomposer.org/installer | php -- &&  mv composer.phar /usr/local/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update && apt install -y yarn

RUN curl -sS https://get.symfony.com/cli/installer | bash - &&  mv /root/.symfony5/bin/symfony /usr/local/bin

RUN docker-php-ext-configure intl

RUN docker-php-ext-install pdo pdo_mysql opcache intl zip exif calendar dom mbstring gd xsl &&  pecl install apcu && docker-php-ext-enable apcu

WORKDIR /var/www/

RUN git config --global user.email "ldandoy@overconsulting.net" && git config --global user.name "Loïc DANDOY"