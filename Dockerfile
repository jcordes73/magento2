FROM registry.redhat.io/ubi8/php-73

ENV COMPOSER_VERSION=1.10.16

# Add application sources
ADD . .

# Install the dependencies
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --version=$COMPOSER_VERSION --install-dir=bin --filename=composer && \
    php --ini && \
    php bin/composer install && \
    php -dmemory_limit=2G bin/magento setup:upgrade && \
    php -dmemory_limit=2G bin/magento setup:di:compile && \
    php bin/magento deploy:mode:set developer --skip-compilation && \
    php bin/magento setup:static-content:deploy -f && \
    php bin/magento cache:clean && \
    php bin/magento cache:flush && \
    php bin/magento setup:install --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1 --use-secure=1 && \
    php bin/magento admin:user:create --admin-user=$MAGENTO_ADMIN_USER --admin-password=$MAGENTO_ADMIN_PASSWORD --admin-email="$MAGENTO_ADMIN_EMAIL" --admin-firstname="$MAGENTO_ADMIN_FIRSTNAME" --admin-lastname="$MAGENTO_ADMIN_LASTNAME"

# Run script uses standard ways to configure the PHP application
CMD /usr/libexec/s2i/run
