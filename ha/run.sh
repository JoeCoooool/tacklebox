#!/usr/bin/with-contenv bashio
echo "Starte TackleBox..."
mkdir -p /run/apache2
exec /usr/sbin/httpd -D FOREGROUND
