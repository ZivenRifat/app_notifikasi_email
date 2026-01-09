FROM php:8.2-apache

# Enable apache rewrite
RUN a2enmod rewrite

# Install PHP extensions (mysqli optional)
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/storage /var/www/html/logs

WORKDIR /var/www/html

EXPOSE 80
