FROM php:8.3-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev

# Устанавливаем расширения PHP
RUN docker-php-ext-install pdo pdo_pgsql mysqli mbstring exif pcntl bcmath gd

# Добавляем корневые сертификаты Минцифры — они нужны для HTTPS-запросов
# к API MAX (https://platform-api2.max.ru), чей сертификат выпущен
# «Russian Trusted Sub CA» → «Russian Trusted Root CA».
COPY docker/php/certs/ /usr/local/share/ca-certificates/
RUN update-ca-certificates

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем рабочую директорию
WORKDIR /var/www/html

# Устанавливаем права для Symfony
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
