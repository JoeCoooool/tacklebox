#!/bin/bash
# TACKLEBOX INSTALLER (SAFE-PUSH VERSION)
set -e

echo "🎣 Starte TackleBox Installation..."

# 1. ID und Storage
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')
STORAGE="local-lvm" 

# 2. Container erstellen
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs ${STORAGE}:4 \
  --memory 512 --unprivileged 1

pct start $CT_ID
sleep 10

# 3. Software installieren
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml php-zip libapache2-mod-php

# 4. PHP-DATEI PUSHEN (Das verhindert alle Kopierfehler!)
# WICHTIG: Die Datei 'index.php' muss im selben Ordner liegen wie dieses Skript!
if [ -f "index.php" ]; then
    echo "📤 Kopiere index.php in den Container..."
    pct push $CT_ID index.php /var/www/html/index.php
else
    echo "❌ FEHLER: index.php nicht im aktuellen Ordner gefunden!"
    exit 1
fi

# 5. Rechte & Aufräumen
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 775 /var/www/html/
pct exec $CT_ID -- systemctl restart apache2

IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')
echo "---------------------------------------------------"
echo "✅ FERTIG! Deine TackleBox läuft unter: http://$IP"
echo "---------------------------------------------------"
