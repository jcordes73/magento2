FROM registry.redhat.io/ubi8/php-73

# Add application sources
ADD app-src .

# Install the dependencies
RUN TEMPFILE=$(mktemp) && \
    curl -o "$TEMPFILE" "https://getcomposer.org/installer" && \
    php <"$TEMPFILE" && \
    ./composer.phar install --no-interaction --no-ansi --optimize-autoloader --version=1.10.16 --install-dir=bin --filename=composer && \
    composer install && \
    php -dmemory_limit=1G bin/magento setup:upgrade && \
    php -dmemory_limit=1G bin/magento setup:di:compile && \
    php bin/magento deploy:mode:set developer --skip-compilation && \
    php bin/magento setup:static-content:deploy -f && \
    php bin/magento cache:clean && \
    php bin/magento cache:flush && \
    php bin/magento setup:install --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1 --use-secure=1 && \
    php bin/magento admin:user:create --admin-user=$MAGENTO_ADMIN_USER --admin-password=$MAGENTO_ADMIN_PASSWORD --admin-email="$MAGENTO_ADMIN_EMAIL" --admin-firstname="$MAGENTO_ADMIN_FIRSTNAME" --admin-lastname="$MAGENTO_ADMIN_LASTNAME"

# Run script uses standard ways to configure the PHP application
# and execs httpd -D FOREGROUND at the end
# See more in <version>/s2i/bin/run in this repository.
# Shortly what the run script does: The httpd daemon and php needs to be
# configured, so this script prepares the configuration based on the container
# parameters (e.g. available memory) and puts the configuration files into
# the approriate places.
# This can obviously be done differently, and in that case, the final CMD
# should be set to "CMD httpd -D FOREGROUND" instead.
CMD /usr/libexec/s2i/run
