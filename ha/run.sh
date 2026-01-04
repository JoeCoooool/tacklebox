#!/usr/bin/env bash
set -e

echo "Starte TackleBox Pro..."

# Apache PID File aufräumen, falls vorhanden
rm -f /run/apache2/httpd.pid

# Apache im Vordergrund starten
exec /usr/sbin/httpd -D FOREGROUND
