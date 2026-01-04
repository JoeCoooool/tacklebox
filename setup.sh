#!/bin/bash
# TACKLEBOX ULTIMATE INSTALLER (FULLY AUTOMATED)
set -e

echo "🎣 Starte TackleBox LXC Voll-Installation..."

# 1. Container ID finden
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')

# 2. Container erstellen
echo "Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst --hostname tacklebox --password tacklebox123 --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs local-lvm:4 --memory 512 --unprivileged 1

# 3. Starten
pct start $CT_ID
echo "Warte auf Netzwerk..."
sleep 15

# 4. Software installieren
echo "Installiere Webserver und PHP..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php libapache2-mod-php php-sqlite3 php-gd php-curl php-xml curl

# 5. AUTOMATISIERUNG: Apache Priorität auf PHP setzen & Default Page löschen
echo "Konfiguriere Apache Prioritäten..."
# Dieser Befehl sagt Apache: Suche ZUERST nach index.php
pct exec $CT_ID -- sed -i 's/DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf

# Radikales Löschen der Default-Dateien
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- rm -f /var/www/html/index.nginx-debian.html

# 6. PHP-Datei von GitHub laden
echo "Lade TackleBox Code..."
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 7. Rechte & Neustart
echo "Setze Berechtigungen und starte Dienste neu..."
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 755 /var/www/html/
pct exec $CT_ID -- systemctl restart apache2

# 8. IP Adresse holen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ ERFOLGREICH INSTALLIERT!"
echo "Deine TackleBox ist jetzt erreichbar unter:"
echo "http://$IP"
echo "---------------------------------------------------"
