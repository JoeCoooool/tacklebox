#!/bin/bash
set -e

echo "🎣 Starte TackleBox Installation..."

# 1. ID finden
CT_ID=$(pvesh get /cluster/nextid | tr -d '\r' | tr -d ' ')

# 2. Container erstellen
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp --rootfs local-lvm:4 \
  --memory 512 --unprivileged 1

pct start $CT_ID
echo "Warte auf Netzwerk..."
sleep 10

# 3. Software installieren
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml php-zip libapache2-mod-php

# 4. Datei pushen (Sie liegt unter /var/www/html/index.php auf dem Host)
echo "📤 Kopiere index.php..."
pct push $CT_ID /var/www/html/index.php /var/www/html/index.php

# 5. Rechte setzen
pct exec $CT_ID -- rm -f /var/www/html/index.html
pct exec $CT_ID -- mkdir -p /var/www/html/uploads
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 775 /var/www/html/
pct exec $CT_ID -- systemctl restart apache2

IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}' | tr -d '\r' | tr -d ' ')
echo "✅ Erledigt! URL: http://$IP"
