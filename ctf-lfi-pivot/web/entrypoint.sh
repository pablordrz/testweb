#!/bin/bash
set -e

# La imagen base enlaza access.log -> /dev/stdout (para que docker logs
# capture las peticiones). Eso rompe el LFI: no es un fichero real que se
# pueda incluir con include(). Lo sustituimos por un fichero normal.
rm -f /var/log/apache2/access.log
touch /var/log/apache2/access.log
chmod 644 /var/log/apache2/access.log

exec apache2-foreground
