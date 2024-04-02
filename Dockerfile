FROM php:8.2.4RC1-apache

RUN apt update \
        && apt install -y \
            g++ \
            libicu-dev \
            libpq-dev \
            libzip-dev \
            zip \
            zlib1g-dev \
        && docker-php-ext-install \
            intl \
            opcache \
            pdo \
            mysqli \
            pdo_mysql \
            bcmath
RUN a2enmod rewrite
RUN service apache2 restart
RUN mkdir /var/www/logs
WORKDIR /var/www/workdir
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

###########################################
# apache conf
###########################################

COPY bin/apache/prod-api.budgetcontrol.cloud.conf /etc/apache2/sites-available/budgetcontrol.cloud.conf
COPY bin/apache/gateway.conf /etc/apache2/sites-available/budgetcontrol.gateway.conf
COPY bin/apache/authtentication.conf /etc/apache2/sites-available/budgetcontrol.authtentication.conf
COPY bin/apache/stats.conf /etc/apache2/sites-available/budgetcontrol.stats.conf
COPY bin/apache/workspace.conf /etc/apache2/sites-available/budgetcontrol.workspace.conf

RUN a2ensite budgetcontrol.cloud.conf
RUN a2ensite budgetcontrol.gateway.conf
RUN a2ensite budgetcontrol.authtentication.conf
RUN a2ensite budgetcontrol.stats.conf
RUN a2ensite budgetcontrol.workspace.conf
RUN a2enmod rewrite

###########################################

RUN mkdir /var/www/script
COPY bin/entrypoint.sh /var/www/script/entrypoint.sh
RUN chmod +x /var/www/script/entrypoint.sh

EXPOSE 3000

ENTRYPOINT [ "/var/www/script/entrypoint.sh" ] 