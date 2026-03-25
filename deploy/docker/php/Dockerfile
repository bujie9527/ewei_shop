FROM php:7.4-fpm

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libzip-dev unzip git \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli gd zip \
  && rm -rf /var/lib/apt/lists/*

RUN { \
      echo "memory_limit=${PHP_MEMORY_LIMIT:-512M}"; \
      echo "upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE:-50M}"; \
      echo "post_max_size=${PHP_POST_MAX_SIZE:-50M}"; \
      echo "max_execution_time=300"; \
      echo "max_input_time=300"; \
      echo "date.timezone=${TZ:-Asia/Shanghai}"; \
    } > /usr/local/etc/php/conf.d/99-ewei.ini

WORKDIR /var/www/html

