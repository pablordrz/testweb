FROM php:7.4-apache

# Copiamos la aplicación vulnerable
COPY src/ /var/www/html/

# Flag de la primera fase (visible tras lograr RCE)
RUN echo "CTF{lfi_log_poisoning_to_pivot}" > /var/www/flag1.txt \
    && chmod 644 /var/www/flag1.txt

# Script de arranque: prepara permisos del log ANTES de lanzar Apache
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
