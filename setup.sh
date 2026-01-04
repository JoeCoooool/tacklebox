#!/bin/bash
# TACKLEBOX PRO - LXC INSTALLER

set -e
GREEN='\033[0;32m'
NC='\033[0m'

echo -e "${GREEN}🎣 Starte TackleBox LXC Installation...${NC}"

# 1. Container ID finden
CT_ID=$(pvesh get /cluster/nextid)

# 2. LXC Container erstellen
# Wir nutzen Debian 12, 512MB RAM und 4GB Speicher
echo -e "${GREEN}Erstelle Container $CT_ID...${NC}"
pct create $CT_ID local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname tacklebox \
  --password tacklebox123 \
  --net0 name=eth0,bridge=vmbr0,ip=dhcp \
  --rootfs local-lvm:4 \
  --memory 512 \
  --unprivileged 1

# 3. Container starten
pct start $CT_ID
echo -e "${GREEN}Warte auf Netzwerk...${NC}"
sleep 10

# 4. Software installieren
echo -e "${GREEN}Installiere Apache & PHP...${NC}"
pct exec $CT_ID -- apt-get update
pct exec $CT_ID -- apt-get install -y apache2 php php-sqlite3 php-gd php-curl php-xml

# 5. Dateien übertragen
echo -e "${GREEN}Kopiere index.php in den Container...${NC}"
pct exec $CT_ID -- rm /var/www/html/index.html
pct push $CT_ID index.php /var/www/html/index.php

# 6. Rechte anpassen
pct exec $CT_ID -- chown -R www-data:www-data /var/www/html/
pct exec $CT_ID -- chmod -R 755 /var/www/html/

# 7. IP Adresse anzeigen
IP=$(pct exec $CT_ID -- hostname -I | awk '{print $1}')

echo -e "${GREEN}---------------------------------------------------${NC}"
echo -e "✅ FERTIG! TackleBox ist installiert."
echo -e "URL: http://$IP"
echo -e "---------------------------------------------------${NC}"