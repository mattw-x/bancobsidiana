# Usamos PHP 8.3 con Apache
FROM php:8.3-apache

# 1. Instalar dependencias del sistema, extensiones PHP (incluyendo SQLite) y Node.js 20
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Configuración de Apache (Optimizada)
# Habilitar módulos necesarios y cambiar el MPM para mayor estabilidad
RUN a2dismod mpm_event && a2dismod mpm_worker && a2enmod mpm_prefork rewrite headers

# Configurar Document Root a /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf | sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# 3. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Establecer directorio de trabajo
WORKDIR /var/www/html

# -----------------------------------------------------------------------------
# FASE DE DEPENDENCIAS
# -----------------------------------------------------------------------------

# 5. Instalar dependencias de Composer PRIMERO
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts

# 6. Instalar dependencias de Node.js
COPY package*.json ./
RUN npm install @tailwindcss/postcss postcss autoprefixer
RUN npm install

# -----------------------------------------------------------------------------
# FASE DE CONSTRUCCIÓN
# -----------------------------------------------------------------------------

# 7. Copiar TODO el código fuente
COPY . .

# 8. Compilar Assets (Vite)
RUN npm run build

# 9. Permisos y Estructura de carpetas
RUN mkdir -p database storage/framework/sessions storage/framework/views storage/framework/cache \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

# 10. Exponer puerto 80 (Railway mapeará este puerto automáticamente)
EXPOSE 80

# 11. Ejecutar Apache en primer plano (El Start Command de Railway tomará el control de todos modos)
CMD ["apache2-foreground"]
