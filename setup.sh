#!/bin/bash
# TACKLEBOX PRO INSTALLER (FULLY AUTOMATED)

set -e

echo "🎣 Starte TackleBox LXC Voll-Installation..."

# 1. Container ID finden
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')

# 2. Container erstellen
echo "Erstelle Container $CT_ID..."
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox \
  --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --rootfs local-lvm:4 \
  --memory 512 \
  --unprivileged 1

# 3. Starten
pct start $CT_ID
echo "Warte auf Netzwerk..."
sleep 10

# 4. Software & PHP Module installieren (inkl. SQLite & Zip)
echo "Installiere Webserver und alle benötigten PHP-Module..."
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php libapache2-mod-php php-sqlite3 php-gd php-curl php-xml php-zip curl

# 5. Konfiguration & Aufräumen
echo "Konfiguriere Apache..."
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- a2enmod rewrite

# 6. PHP-Code direkt in den Container schreiben
# (Verhindert Link-Fehler von GitHub)
echo "Schreibe TackleBox Code..."
# Hier nutzen wir 'cat', um den Code direkt einzufügen
# Ich setze hier einen Platzhalter ein - du kannst hier deinen Code einfügen 
# oder weiterhin curl nutzen, wenn der Link sicher funktioniert:
pct exec $CT_ID -- curl -sL -o /var/www/html/index.php https://raw.githubusercontent.com/JoeCoooool/tacklebox/main/index.php

# 7. RECHTE-FIX (Das verhindert den Fehler 500)
echo "Setze Schreibrechte für Datenbank und Uploads..."
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
# Webserver-User zum Besitzer machen
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
# Rechte so setzen, dass PHP Dateien erstellen darf (für SQLite nötig!)
pct exec $CT_ID -- chmod -R 775 /var/www/html/

# 8. Neustart der Dienste
echo "Starte Apache neu..."
pct exec $CT_ID -- systemctl restart apache2

# 9. IP Adresse holen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')

echo "---------------------------------------------------"
echo "✅ INSTALLATION ABGESCHLOSSEN!"
echo "Deine TackleBox ist jetzt erreichbar unter:"
echo "http://$IP"
echo ""
echo "HINWEIS: Falls du immer noch einen Fehler 500 siehst,"
echo "prüfe den Link in Schritt 6 oder die Rechte in Schritt 7."
echo "---------------------------------------------------"
