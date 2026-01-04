#!/bin/bash
# TACKLEBOX ULTIMATE INSTALLER (FULLY AUTOMATED)
# Optimiert für Version ohne Scan-Funktion

set -e

echo "🎣 Starte TackleBox LXC Voll-Installation..."

# 1. Container ID finden
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')

# 2. Container erstellen
echo "Erstelle Container $CT_ID..."
# Erstellt einen Debian 12 LXC mit 512MB RAM und 4GB Speicher
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst --hostname tacklebox --password tacklebox123 --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs local-lvm:4 --memory 512 --unprivileged 1

# 3. Starten
pct start $CT_ID
echo "Warte auf Netzwerk (15s)..."
sleep 15

# 4. Software installieren
echo "Installiere Webserver und PHP Module..."
pct exec $CT_ID -- apt-get update
# php-zip ist nötig für den Backup-Export, php-sqlite3 für die Datenbank
pct exec $CT_ID -- apt-get install -y apache2 php libapache2-mod-php php-sqlite3 php-gd php-curl php-xml php-zip curl

# 5. AUTOMATISIERUNG: Apache Konfiguration
echo "Konfiguriere Apache..."
# PHP Priorität setzen
pct exec $CT_ID -- sed -i 's/DirectoryIndex index.html index.cgi index.pl index.php index.xhtml index.htm/DirectoryIndex index.php index.html/' /etc/apache2/mods-enabled/dir.conf

# Standard Apache-Seiten löschen
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- rm -f /var/www/html/index.nginx-debian.html

# 6. PHP-Datei von GitHub laden
echo "Lade TackleBox Code von GitHub..."
# HINWEIS: Stelle sicher, dass der Link zu deinem Repo korrekt ist!
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 7. Rechte & Verzeichnisse (WICHTIG für SQLite & Uploads)
echo "Setze Berechtigungen..."
# Erstellt den Upload-Ordner manuell
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
# Setzt den Webserver (www-data) als Besitzer für den gesamten Ordner
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
# Setzt Schreibrechte (775), damit SQLite die DB-Datei im Ordner erstellen darf
pct exec $CT_ID -- chmod -R 775 /var/www/html/

# Apache neu starten um alles zu übernehmen
pct exec $CT_ID -- systemctl restart apache2

# 8. IP Adresse holen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ ERFOLGREICH INSTALLIERT!"
echo "Deine TackleBox ist jetzt erreichbar unter:"
echo "http://$IP"
echo ""
echo "Standard-Passwort: Muss beim ersten Aufruf gesetzt werden."
echo "---------------------------------------------------"
