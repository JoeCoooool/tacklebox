#!/usr/bin/env bash
# Title: Proxmox Helper: TackleBox Pro Installer
# Author: JoeCool <your.email@example.com>
# License: MIT
# Description: Creates an LXC container and automatically installs TackleBox Pro
# Tested on: Proxmox VE 7/8
# URL: https://github.com/JoeCoooool/tacklebox

set -e

# 1. Variablen (ID wird automatisch die nächste freie)
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')
STORAGE="local-lvm" 

echo "🎣 Starte Komplett-Installation für TackleBox (ID: $CT_ID)..."

# 2. LXC Container erstellen
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox \
  --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --rootfs ${STORAGE}:4 \
  --memory 512 \
  --unprivileged 1

# 3. Starten und warten
pct start $CT_ID
echo "🌐 Warte auf Netzwerk (15 Sek)..."
sleep 15

# 4. Alles installieren (Apache, PHP + ALLE Module)
echo "⚙️ Installiere Webserver & PHP Komponenten..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php libapache2-mod-php php-sqlite3 php-gd php-curl php-xml php-zip php-mbstring curl

# 5. DEN ECHTEN CODE LADEN
echo "📥 Lade App-Code von GitHub..."
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 6. RECHTE-FIX (Vermeidet Fehler 500)
echo "🔐 Konfiguriere Schreibrechte..."
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 775 /var/www/html/

# 7. Webserver scharf schalten
pct exec $CT_ID -- systemctl restart apache2

# 8. IP ermitteln
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ TACKLEBOX INSTALLATION ERFOLGREICH!"
echo "---------------------------------------------------"
echo "Öffne jetzt direkt: http://$IP"
echo "---------------------------------------------------"

