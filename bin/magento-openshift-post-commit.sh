#!/bin/bash
php composer.phar install $INSTALLER_ARGS
php -dmemory_limit=1G bin/magento setup:upgrade
php -dmemory_limit=1G bin/magento setup:di:compile
php bin/magento deploy:mode:set developer --skip-compilation
php bin/magento setup:static-content:deploy -f
php bin/magento cache:clean
php bin/magento cache:flush
