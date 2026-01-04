#!/bin/bash
# TACKLEBOX COMPLETE INSTALLER
set -e

echo "🎣 Starte TackleBox LXC Voll-Installation..."

# 1. Container ID finden und säubern
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')

# 2. Container erstellen
echo "Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst --hostname tacklebox --password tacklebox123 --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs local-lvm:4 --memory 512 --unprivileged 1

# 3. Starten
pct start $CT_ID
echo "Warte auf Netzwerk (15 Sek)..."
sleep 15

# 4. Software installieren & PHP Modul sicherstellen
echo "Installiere Apache, PHP und Module..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php libapache2-mod-php php-sqlite3 php-gd php-curl php-xml curl

# 5. Aufräumen & PHP-Datei laden
echo "Konfiguriere Webserver..."
# Lösche ALLES im Web-Verzeichnis, damit es keine Konflikte gibt
pct exec $CT_ID -- rm -rf /var/www/html/*

# Lade deine index.php direkt von GitHub
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 6. Rechte & Apache Neustart
echo "Setze Berechtigungen..."
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 755 /var/www/html/
# Starte Apache neu, um PHP sicher zu laden
pct exec $CT_ID -- systemctl restart apache2

# 7. IP Adresse holen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ ALLES ERLEDIGT! Du musst nichts weiter tun."
echo "URL: http://$IP"
echo "---------------------------------------------------"
