ARG BUILD_FROM
FROM $BUILD_FROM

# Pakete installieren
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

# Apache Konfiguration: index.php als Standard setzen
RUN sed -i 's/DirectoryIndex index.html/DirectoryIndex index.php index.html/' /etc/apache2/httpd.conf

# Arbeitsverzeichnis säubern und vorbereiten
WORKDIR /var/www/localhost/htdocs
RUN rm -rf *

# DEINE Datei kopieren
COPY index.php .

# Rechte setzen
RUN mkdir -p /run/apache2 && \
    mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs /run/apache2

# Start-Skript
COPY run.sh /
RUN sed -i 's/\r$//' /run.sh && chmod a+x /run.sh

CMD [ "/run.sh" ]
