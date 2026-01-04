ARG BUILD_FROM=ghcr.io/hassio-addons/base:16.2.1
FROM $BUILD_FROM

# PHP und Apache installieren
RUN apk add --no-cache \
    apache2 \
    php82 \
    php82-apache2 \
    php82-pdo_sqlite \
    php82-sqlite3 \
    php82-session \
    php82-json \
    php82-ctype \
    php82-fileinfo

# Verzeichnis für die Website erstellen
WORKDIR /var/www/localhost/htdocs

# Kopiere deine index.php (Muss im selben Ordner wie das Dockerfile liegen!)
COPY index.php .

# Rechte für Apache setzen
RUN mkdir -p /run/apache2 && \
    mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs /run/apache2

# Start-Skript kopieren und Windows-Zeilenenden (\r) entfernen
COPY run.sh /
RUN sed -i 's/\r$//' /run.sh && chmod a+x /run.sh

CMD [ "/run.sh" ]
