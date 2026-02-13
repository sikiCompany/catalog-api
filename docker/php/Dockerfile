FROM php:8.2-fpm

# Argumentos
ARG USER=laravel
ARG UID=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    libzip-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP necessárias
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar extensão Redis
RUN pecl install redis && docker-php-ext-enable redis

# Instalar extensão para Elasticsearch (PHP 8.2)
RUN pecl install elasticsearch && docker-php-ext-enable elasticsearch || true

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário do sistema
RUN useradd -G www-data,root -u $UID -d /home/$USER $USER
RUN mkdir -p /home/$USER/.composer && \
    chown -R $USER:$USER /home/$USER

# Configurar PHP
COPY php.ini /usr/local/etc/php/conf.d/local.ini

# Definir diretório de trabalho
WORKDIR /var/www

# Expor porta do PHP-FPM
EXPOSE 9000

# Mudar para usuário não-root
USER $USER

CMD ["php-fpm"]
