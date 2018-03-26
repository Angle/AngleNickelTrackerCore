#!/bin/sh

cd /var/www

echo --- Pull the most recent configuration files ---
sudo chmod 777 -R .
git stash
git pull
if [ $? -gt 0 ]; then
    chmod +x update.sh
    echo "Update stopped. Could not pull from remote repository."
else
    chmod +x update.sh
    chmod +x install.sh

    sudo chmod 777 -R .

    echo "- Arrange bundles"
    cp -R bundles/Angle/NickelTracker/CoreBundle/. symfony/admin/src/Angle/NickelTracker/CoreBundle/
    cp -R bundles/Angle/NickelTracker/AdminBundle/. symfony/admin/src/Angle/NickelTracker/AdminBundle/

    cp -R bundles/Angle/NickelTracker/CoreBundle/. symfony/api/src/Angle/NickelTracker/CoreBundle/
    cp -R bundles/Angle/NickelTracker/ApiBundle/. symfony/api/src/Angle/NickelTracker/ApiBundle/

    cp -R bundles/Angle/NickelTracker/CoreBundle/. symfony/app/src/Angle/NickelTracker/CoreBundle/
    cp -R bundles/Angle/NickelTracker/AppBundle/. symfony/app/src/Angle/NickelTracker/AppBundle/

    cd symfony

    for i in "admin" "api" "app"
    do
        cd $i
        echo "- Updating: $i -"
        echo -> Update packages with composer
        php composer.phar self-update
        sudo php composer.phar update # bug

        echo -> Fix permissions
        sudo chmod 777 -R app/logs

        echo -> Clear cache
        sudo chmod 777 -R app/cache
        sudo php app/console cache:clear --env=prod # bug
        sudo chmod 777 -R app/cache
        cd ..
    done

    cd /var/www

    echo - Update database -
    php symfony/admin/app/console doctrine:schema:update --force

    echo - Update crontab -
    crontab config/crontab
fi
