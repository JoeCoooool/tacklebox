#!/bin/bash
# TACKLEBOX PROMOX-INSTALLER via GITHUB
set -e

echo "🎣 Starte TackleBox LXC Installation..."

# 1. Container ID & Storage (Automatisch nächste ID)
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')
STORAGE="local-lvm" # Falls dein Storage anders heißt, hier anpassen

# 2. Container erstellen (Debian 12)
echo "Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox \
  --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --rootfs ${STORAGE}:4 \
  --memory 512 \
  --unprivileged 1

# 3. Container starten & warten
pct start $CT_ID
echo "Warte auf Netzwerk..."
sleep 10

# 4. PHP & Module installieren
echo "Installiere Pakete..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml php-zip libapache2-mod-php

# 5. PHP-CODE DIREKT IN CONTAINER SCHREIBEN
# Wir nutzen 'EOF' in Anführungszeichen, damit Bash die PHP-Variablen ($) ignoriert!
echo "Schreibe index.php..."
pct exec $CT_ID -- bash -c "cat << 'EOF' > /var/www/html/index.php
<?php
// HIER DEIN KOMPLETTER PHP CODE
ini_set('display_errors', 1); 
error_reporting(E_ALL);
session_start();
\$dbFile = 'tackle_app_db_9f2a.sqlite';
echo '<h1>TackleBox ist bereit!</h1>';
echo '<p>PHP Version: ' . phpversion() . '</p>';
// ... hier den Rest deines Codes einfügen ...
EOF"

# 6. Rechte setzen (Das Wichtigste gegen Fehler 500)
echo "Setze Berechtigungen..."
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 775 /var/www/html/
pct exec $CT_ID -- systemctl restart apache2

# 7. Abschluss
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')
echo "---------------------------------------------------"
echo "✅ ERFOLGREICH INSTALLIERT!"
echo "URL: http://$IP"
echo "---------------------------------------------------"
