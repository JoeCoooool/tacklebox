#!/bin/bash
# TACKLEBOX ULTIMATE AUTOMATION
set -e

echo "🎣 Starte TackleBox Voll-Automatisierung..."

# 1. Container ID & Storage Setup
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')
STORAGE="local-lvm" # Falls dein Storage anders heißt (z.B. 'local'), hier ändern

# 2. LXC Container erstellen
echo "📦 Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox \
  --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --rootfs ${STORAGE}:4 \
  --memory 512 \
  --unprivileged 1

# 3. Starten und auf Netzwerk warten
pct start $CT_ID
echo "🌐 Warte auf Netzwerk-Verbindung..."
sleep 12

# 4. PHP-Umgebung installieren
echo "⚙️ Installiere Webserver und PHP-Module..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml php-zip libapache2-mod-php curl

# 5. AUTOMATISCHER CODE-DOWNLOAD
# Hier wird dein echter PHP-Code von GitHub geladen
echo "📥 Lade echten TackleBox Code von GitHub..."
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 6. Berechtigungen & Verzeichnisse (WICHTIG!)
echo "🔐 Setze Schreibrechte..."
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 775 /var/www/html/

# 7. Webserver Neustart
pct exec $CT_ID -- systemctl restart apache2

# 8. IP-Adresse für den Benutzer anzeigen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ ALLES FERTIG!"
echo "Deine TackleBox ist nun bereit."
echo "URL: http://$IP"
echo "---------------------------------------------------"
