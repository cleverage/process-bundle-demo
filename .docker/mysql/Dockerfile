ARG MYSQL_VERSION=9.1.0
FROM mysql:${MYSQL_VERSION}

ARG UID
ARG GID

ENV TZ=Europe/Paris

RUN usermod -u $UID mysql \
    && groupmod -g $GID mysql

USER mysql:mysql
