#!/bin/bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --version=1.10.16 --install-dir=bin --filename=composer
composer install
php -dmemory_limit=1G bin/magento setup:upgrade
php -dmemory_limit=1G bin/magento setup:di:compile
php bin/magento deploy:mode:set developer --skip-compilation
php bin/magento setup:static-content:deploy -f
php bin/magento cache:clean
php bin/magento cache:flush
php bin/magento setup:install --language=en_US --currency=USD --timezone=America/Chicago --use-rewrites=1 --use-secure=1
php bin/magento admin:user:create --admin-user=$MAGENTO_ADMIN_USER --admin-password=$MAGENTO_ADMIN_PASSWORD --admin-email="$MAGENTO_ADMIN_EMAIL" --admin-firstname="$MAGENTO_ADMIN_FIRSTNAME" --admin-lastname="$MAGENTO_ADMIN_LASTNAME"
