ARG BUILD_FROM=ghcr.io/hassio-addons/base:14.0.0
FROM $BUILD_FROM

# Installiere Apache und PHP
RUN apk add --no-cache \
    php82 \
    php82-apache2 \
    php82-pdo_sqlite \
    php82-session \
    php82-json \
    php82-zip \
    apache2

# Verzeichnis für die Webseite
WORKDIR /var/www/localhost/htdocs

# Kopiere deine App-Datei (index.php muss im selben Ordner liegen)
COPY index.php .

# Erstelle den Uploads-Ordner und setze Rechte für den Webserver
RUN mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs

# Start-Skript für Apache
COPY run.sh /
RUN chmod a+x /run.sh

CMD [ "/run.sh" ]
