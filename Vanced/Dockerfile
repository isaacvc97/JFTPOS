FROM php:8.2-cli

# Instala herramientas necesarias
RUN apt-get update && apt-get install -y \
    cron git curl zip unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    gnupg2 ca-certificates procps python3 python3-venv python3-dev \
    libxml2-dev libxslt1-dev gcc libffi-dev libssl-dev build-essential \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd soap


# Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar configuración personalizada de OpenSSL
COPY docker/openssl.cnf /etc/ssl/openssl.cnf
ENV OPENSSL_CONF=/etc/ssl/openssl.cnf

# Carpeta de trabajo
WORKDIR /var/www

# Copia archivos del proyecto
COPY . .

# Composer y NPM
RUN composer install
RUN npm install

# === Entorno Python ===

# Crear entorno virtual
RUN python3 -m venv /opt/venv

# Instalar lxml dentro del entorno virtual
RUN /opt/venv/bin/pip install --upgrade pip && \
    /opt/venv/bin/pip install lxml

# Añadir entorno virtual al PATH por defecto
ENV PATH="/opt/venv/bin:$PATH"

# Configurar cron para Laravel scheduler
RUN echo "* * * * * cd /var/www && php artisan schedule:run >> /var/www/storage/logs/cron.log 2>&1" >> /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler \
    && crontab /etc/cron.d/laravel-scheduler

# Iniciar cron en background + php artisan serve
CMD cron && php artisan serve --host=0.0.0.0 --port=8000

# Exponer puertos
EXPOSE 8000 5173

# Nota: usa docker-compose para definir el comando o entrada

