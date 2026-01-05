#!/usr/bin/env bash
# Title: LXC | TackleBox Pro
# Description: Creates an LXC container and installs TackleBox Pro (Fishing Inventory Web App)
# Author: JoeCoooool
# License: MIT
# Tested on: Proxmox VE 8.x
# Source: https://github.com/JoeCoooool/tacklebox

set -e

### Load Proxmox Helper Script Framework ###
source /dev/stdin <<<"$(wget -qLO - https://raw.githubusercontent.com/community-scripts/ProxmoxVE/main/misc/build.func)"

header_info "TackleBox Pro Installer"

### Default Variables ###
APP="TackleBox Pro"
CT_OS="debian"
CT_OS_VERSION="12"
CT_TYPE="unprivileged"
CT_MEMORY="512"
CT_DISK="4"
CT_CORES="1"
CT_NET="dhcp"
CT_STORAGE="${STORAGE:-local-lvm}"
CT_HOSTNAME="tacklebox"

# Generate secure random container password
CT_PASSWORD="$(openssl rand -base64 16)"

variables
color
catch_errors

### Create LXC ###
msg_info "Creating LXC Container"
CT_ID=$(pvesh get /cluster/nextid)

pct create "$CT_ID" local:vztmpl/debian-12-standard_12.0-1_amd64.tar.zst \
  --hostname "$CT_HOSTNAME" \
  --memory "$CT_MEMORY" \
  --cores "$CT_CORES" \
  --rootfs "${CT_STORAGE}:${CT_DISK}" \
  --net0 name=eth0,bridge=vmbr0,ip="$CT_NET" \
  --password "$CT_PASSWORD" \
  --unprivileged 1 \
  --features keyctl=1,nesting=1 >/dev/null

msg_ok "LXC Container $CT_ID created"

### Start Container ###
msg_info "Starting Container"
pct start "$CT_ID"
sleep 10
msg_ok "Container started"

### Install Dependencies ###
msg_info "Installing dependencies"
pct exec "$CT_ID" -- bash -c "
apt update &&
apt install -y apache2 php php-sqlite3 unzip curl openssl &&
systemctl enable apache2
"
msg_ok "Dependencies installed"

### Install TackleBox Pro ###
msg_info "Installing TackleBox Pro"
pct exec "$CT_ID" -- bash -c "
rm -rf /var/www/html/*
curl -fsSL https://github.com/JoeCoooool/tacklebox/archive/refs/heads/main.zip -o /tmp/tacklebox.zip
unzip /tmp/tacklebox.zip -d /tmp
cp -r /tmp/tacklebox-main/* /var/www/html/
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;
"
msg_ok "TackleBox Pro installed"

### Restart Webserver ###
pct exec "$CT_ID" -- systemctl restart apache2

### Get Container IP ###
IP=$(pct exec "$CT_ID" -- hostname -I | awk '{print $1}')

### Finish ###
msg_ok "Completed Successfully!"

echo -e "\n${APP} is available at:"
echo "http://${IP}"
echo
echo "Container credentials:"
echo "Username: root"
echo "Password: ${CT_PASSWORD}"
echo
