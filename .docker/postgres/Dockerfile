ARG POSTGRES_VERSION=16
FROM postgres:${POSTGRES_VERSION}

ARG UNAME=postgres
ARG UID
ARG GID

ENV TZ=Europe/Paris

RUN usermod -u $UID $UNAME \
    && groupmod -g $GID $UNAME \
    && chown -R $UNAME:$UNAME /var/lib/postgresql /var/run/postgresql

USER $UNAME:$UNAME
