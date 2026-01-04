ARG BUILD_FROM
FROM $BUILD_FROM

# Pakete installieren (ohne die "82" Endung, damit Alpine sie findet)
RUN apk add --no-cache \
    apache2 \
    php83 \
    php83-apache2 \
    php83-pdo_sqlite \
    php83-sqlite3 \
    php83-session \
    php83-json \
    php83-ctype \
    php83-fileinfo \
    bash

# Verzeichnis für die Website vorbereiten
WORKDIR /var/www/localhost/htdocs

# Kopiere deine index.php
COPY index.php .

# Rechte setzen
RUN mkdir -p /run/apache2 && \
    mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs /run/apache2

# Start-Skript kopieren und reparieren
COPY run.sh /
RUN sed -i 's/\r$//' /run.sh && chmod a+x /run.sh

CMD [ "/run.sh" ]
