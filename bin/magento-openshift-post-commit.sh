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
