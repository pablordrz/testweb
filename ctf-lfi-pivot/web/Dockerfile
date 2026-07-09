FROM php:7.4-apache

# Copiamos la aplicación vulnerable
COPY src/ /var/www/html/

# La carpeta de subidas debe ser escribible por el proceso de Apache/PHP
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Flag de la primera fase (visible tras lograr RCE)
RUN echo "CTF{lfi_log_poisoning_to_pivot}" > /var/www/flag1.txt \
    && chmod 644 /var/www/flag1.txt

# Script de arranque: prepara permisos del log ANTES de lanzar Apache
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
