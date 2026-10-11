FROM dunglas/frankenphp:php8.4

ARG USER=crwydro
ARG UID=1000
ARG GID=1000

RUN install-php-extensions \
    pdo_mysql \
    redis \
    gd \
    exif \
    intl \
    zip \
    pcntl \
    bcmath \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd --gid ${GID} ${USER} \
    && useradd --uid ${UID} --gid ${GID} --create-home ${USER} \
    && chown -R ${USER}:${USER} /config /data

ENV SERVER_NAME=:8000

WORKDIR /app

USER ${USER}

EXPOSE 8000 5173
