ARG BUILD_FROM
FROM $BUILD_FROM

# Pakete installieren (Allgemeine Namen für bessere Kompatibilität)
RUN apk add --no-cache \
    apache2 \
    php \
    php-apache2 \
    php-pdo_sqlite \
    php-sqlite3 \
    php-session \
    php-json \
    php-ctype \
    php-fileinfo \
    php-curl \
    php-zip \
    bash

# Verzeichnis vorbereiten
WORKDIR /var/www/localhost/htdocs
COPY index.php .

# Rechte setzen
RUN mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs

# Start-Skript
COPY run.sh /
RUN chmod a+x /run.sh

CMD [ "/run.sh" ]
