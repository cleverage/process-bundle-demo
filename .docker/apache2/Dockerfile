FROM ubuntu/apache2:2.4-22.04_beta

ARG UID
ARG GID

ENV TZ=Europe/Paris
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Enable required modules
RUN a2enmod proxy_fcgi

RUN usermod -u $UID www-data \
    && groupmod -g $GID www-data
