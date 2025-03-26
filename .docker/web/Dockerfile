FROM dunglas/frankenphp:1.1-php8.3

ENV SERVER_NAME=:80
ENV XDG_CONFIG_HOME=/app

ADD  ./.docker/web/Caddyfile /etc/caddy/Caddyfile

# Caddy requires an additional capability to bind to port 80 and 443
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

RUN apt update
RUN apt-get update

RUN apt install -y vim git default-mysql-client nano cron supervisor libreoffice
RUN apt-get install zlib1g-dev

# add additional extensions here:
RUN install-php-extensions \
    @composer \
	pdo_mysql \
    mysqli \
	gd \
	intl \
	zip \
	opcache

ADD ./.docker/web/supervisor/conf.d/laravel-worker.conf /etc/supervisor/conf.d/

# NODE
ARG INSTALL_NODE=false
RUN if [ ${INSTALL_NODE} = true ]; then \
    curl -fsSL https://deb.nodesource.com/setup_21.x | bash - &&\
    apt install -y nodejs \
;fi

# SPX
ARG INSTALL_SPX=false

COPY .docker/web/spx.ini /usr/local/etc/php/conf.d/spx.ini

RUN if [ ${INSTALL_SPX} = true ]; then \
    apt install -y git && \
    mkdir -p /spx-code && \
    cd /spx-code && \
    git clone https://github.com/NoiseByNorthwest/php-spx.git . && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    echo 'spx.enabled=1' >> "$PHP_INI_DIR/php.ini" && \
    echo 'spx.debug=1' >> "$PHP_INI_DIR/php.ini" && \
    echo 'spx.http_key="dev"' >> "$PHP_INI_DIR/php.ini" && \
    echo 'spx.http_enabled=1' >> "$PHP_INI_DIR/php.ini" && \
    echo 'spx.http_ip_whitelist="*"' >> "$PHP_INI_DIR/php.ini" \
;else \
    rm /usr/local/etc/php/conf.d/spx.ini \
;fi

COPY . /app

WORKDIR /app

# configure PHP
RUN sed -i -e "s/;\?max_input_vars = .*/max_input_vars = 5000/g"  /usr/local/etc/php/php.ini-production
RUN sed -i -e "s/post_max_size = 8M/post_max_size = 800M/g" /usr/local/etc/php/php.ini-production
RUN sed -i -e "s/upload_max_filesize = 2M/upload_max_filesize = 2000M/g" /usr/local/etc/php/php.ini-production
RUN sed -i -e "s/max_file_uploads = 20/max_file_uploads = 2000/g" /usr/local/etc/php/php.ini-production

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ARG USERNAME=root
ARG UID=0
ARG GROUP=root
ARG ROOTPASS=l3tm31n

RUN usermod --password $(echo ${ROOTPASS} | openssl passwd -1 -stdin) root

RUN if [ ${USERNAME} != "root" ]; then \
    useradd -ms /bin/bash ${USERNAME} -u ${UID} \
;fi

# Caddy requires write access to /data/caddy and /config/caddy
RUN chown -R ${USERNAME}:${GROUP} /data/caddy && chown -R ${USERNAME}:${GROUP} /config/caddy

USER ${USERNAME}:${GROUP}

# crontab
RUN (crontab -l 2>/dev/null; echo "* * * * * su -l www-data -s /bin/bash -c 'cd /app/ && /usr/local/bin/php artisan schedule:run >> /app/storage/logs/cron-log 2>&1'") | crontab -
