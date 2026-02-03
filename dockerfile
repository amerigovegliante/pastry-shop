FROM php:8.2-apache

# Abilita mod_rewrite
RUN a2enmod rewrite

# Installa mysqli
RUN docker-php-ext-install mysqli

# Copia tutto il progetto
COPY . /var/www/html/

# Permessi
RUN chown -R www-data:www-data /var/www/html

# Apache punta già a /var/www/html (dove sta index.php)

EXPOSE 80
