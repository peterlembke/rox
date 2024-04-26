FROM ubuntu:24.04

MAINTAINER Peter Lembke <rox@charzam.com>

ARG HOST_UID=1000
ARG HOST_GID=1000

ENV HOST_UID $HOST_UID
ENV HOST_GID $HOST_GID
ENV APACHE_USER dockerhost
ENV APACHE_GROUP dockerhost

ENV TZ=Europe/Stockholm
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt update && apt install -y \
  apt-utils \
  apache2 \
  && apt clean \
  && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN a2enmod proxy
RUN a2enmod proxy_fcgi

COPY default.conf /etc/apache2/sites-available/000-default.conf

COPY default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN mkdir /etc/apache2/ssl
COPY certificates/*.crt /etc/apache2/ssl/
COPY certificates/*.key /etc/apache2/ssl/
RUN a2enmod ssl
RUN a2ensite default-ssl.conf

COPY entrypoint.sh /
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
EXPOSE 80
EXPOSE 443
