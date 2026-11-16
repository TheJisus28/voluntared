#!/bin/sh
# Arranque del contenedor de VoluntaRed:
#  1. espera a que la base de datos responda;
#  2. si está vacía, la instala (con datos de demostración si VR_DATOS_DEMO=1);
#  3. ajusta permisos de las carpetas de escritura y arranca Apache.
set -e

cd /var/www/html

php docker/bd.php esperar

if php docker/bd.php instalada; then
    echo "[voluntared] La base de datos ya está instalada."
else
    echo "[voluntared] Base de datos vacía: instalando..."
    if [ "${VR_DATOS_DEMO:-1}" = "1" ]; then
        php database/instalar.php
    else
        php database/instalar.php --sin-demo
    fi
fi

mkdir -p storage/documentos storage/correos storage/logs storage/respaldos public/uploads/fotos
chown -R www-data:www-data storage public/uploads

echo "[voluntared] Listo: ${VR_URL:-http://localhost:8080}"
exec "$@"
