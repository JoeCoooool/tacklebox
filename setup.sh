#!/bin/bash
# TACKLEBOX LITE-INSTALLER

set -e
echo "🎣 Starte TackleBox LXC Installation..."

# Container ID finden
CT_ID=$(pvesh get /cluster/nextid)

# LXC Container erstellen (Alles in einer Zeile!)
echo "Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst --hostname tacklebox --password tacklebox123 --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs local-lvm:4 --memory 512 --unprivileged 1

# Starten
pct start $CT_ID
echo "Warte auf Netzwerk..."
sleep 10

# Software installieren
echo "Installiere Apache & PHP..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml curl

# Dateien laden
echo "Lade index.php von GitHub..."
pct exec $CT_ID -- rm /var/www/html/index.html
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# Rechte
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 755 /var/www/html/

IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}')
echo "---------------------------------------------------"
echo "✅ FERTIG! URL: http://$IP"
echo "---------------------------------------------------"
