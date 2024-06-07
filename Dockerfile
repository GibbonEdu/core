FROM php:8.2-apache-buster



ENV VERSION=26.0.00
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

#WORKDIR /var/www/html already set by base image

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
  apt-transport-https


# Install apache, PHP, and supplimentary programs. openssh-server, curl, and lynx-cur are for debugging the container.
RUN apt-get update && apt-get -y upgrade && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    git \
    wget \
    curl \
    lynx \    
    ca-certificates \    
    libzip-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    && a2enmod rewrite && \
#    sed -i 's/short_open_tag = Off/short_open_tag = On/' $PHP_INI_DIR/php.ini && \
#    sed -i 's/magic_quotes_gpc = On/magic_quotes_gpc = Off/g' $PHP_INI_DIR/php.ini && \
#    sed -i "s/^allow_url_fopen.*$/allow_url_fopen = On/" $PHP_INI_DIR/php.ini && \
#    sed -i 's/error_reporting = .*$/error_reporting = E_ERROR | E_WARNING | E_PARSE/' $PHP_INI_DIR/php.ini && \
    apt-get clean autoclean && \
    apt-get autoremove -y && \
    rm -rfv /var/lib/{apt,dpkg,cache,log}/
RUN docker-php-ext-install bcmath \
  && docker-php-ext-configure gd --with-jpeg \
  && docker-php-ext-install gd \
  && docker-php-ext-install xml \  
  && docker-php-ext-install curl \
  && docker-php-ext-install mbstring \
  && docker-php-ext-install mysqli \
  && docker-php-ext-install opcache \
  && docker-php-ext-install pdo_mysql \  
  && docker-php-ext-install pdo \
  && docker-php-ext-install gettext \
  && docker-php-ext-install zip \
  && docker-php-ext-install intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN git clone --single-branch https://github.com/GibbonEdu/core.git -b v${VERSION} . && \
    rm -f docker-gibbon-entrypoint Dockerfile docker-compose.yml && \
    git clone --single-branch https://github.com/GibbonEdu/i18n.git ./i18n && \ 
    chmod -R 755 . && chown -R www-data:www-data .
#Tool to generate config.php
RUN wget https://github.com/okdana/twigc/releases/download/v0.4.0/twigc.phar -O /tmp/twigc.phar && \
    chmod +x /tmp/twigc.phar && mv /tmp/twigc.phar /usr/local/bin/twigc

ADD .htaccess .

# composer
RUN composer install && \
    chown 33:33 -R . && \
    chmod -Rv 755 .

# basic recommendations https://docs.gibbonedu.org/administrators/getting-started/installing-gibbon/#post-install-server-config
RUN echo "\nphp_flag register_globals off\n" >> .htaccess && \   
    sed "s/Options Indexes FollowSymLinks/Options FollowSymLinks/g" -i /etc/apache2/apache2.conf

COPY ./docker-gibbon-entrypoint /usr/local/bin

RUN chmod u+x /usr/local/bin/docker-gibbon-entrypoint

EXPOSE 80
VOLUME /var/www/html/uploads


ENTRYPOINT [ "docker-gibbon-entrypoint" ]

CMD ["apache2-foreground"]

