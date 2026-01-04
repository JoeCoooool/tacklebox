ARG BUILD_FROM
FROM $BUILD_FROM

# Pakete installieren
RUN apk add --no-cache \
    apache2 \
    php82 \
    php82-apache2 \
    php82-pdo_sqlite \
    php82-sqlite3 \
    php82-session \
    php82-json \
    php82-ctype \
    php82-fileinfo \
    bash

# Apache Konfiguration
RUN sed -i 's#^DocumentRoot ".*"#DocumentRoot "/var/www/localhost/htdocs"#' /etc/apache2/httpd.conf

WORKDIR /var/www/localhost/htdocs
COPY index.php .

# Rechte setzen
RUN mkdir -p uploads && \
    chown -R apache:apache /var/www/localhost/htdocs && \
    chmod -R 777 /var/www/localhost/htdocs

# Start-Skript kopieren und AUTOMATISCH REPARIEREN (CRLF zu LF)
COPY run.sh /
RUN sed -i 's/\r$//' /run.sh && chmod a+x /run.sh

CMD [ "/run.sh" ]
