#!/bin/bash
# version 3.0 2014.12.0

echo Starting installation procedure..

echo -e "\e[1m--- Installing LAMP Stack ---\e[0m"
echo - Update Repositories -
sudo add-apt-repository -y ppa:ondrej/php5-oldstable > /dev/null
sudo apt-get update > /dev/null

echo -e "\e[1m--- Install Apache2 ---\e[0m"
sudo apt-get install -y apache2
sudo a2enmod rewrite
sudo service apache2 restart

echo -e "\e[1m--- Install PHP5.5 ---\e[0m"
sudo apt-get install -y php5
sudo apt-get install -y libapache2-mod-php5
sudo service apache2 restart

echo - Install PHP5 MySQL Drivers -
sudo apt-get install -y php5-mysql

echo - Install PHP5 cURL -
sudo apt-get install -y php5-curl
sudo service apache2 restart

echo - Install PHP5 MCrypt -
# Reference: http://php.net/manual/en/mcrypt.installation.php#114609
sudo apt-get install -y php5-mcrypt
sudo mv -i /etc/php5/conf.d/mcrypt.ini /etc/php5/mods-available/
sudo php5enmod mcrypt
sudo service apache2 restart

echo -e "\e[1m--- User permisssions ---\e[0m"
sudo adduser ubuntu www-data
sudo chown -R www-data:www-data /var/www
sudo chmod -R g+rw /var/www

echo -e "\e[1m--- Configure Apache & Virtual Hosts ---\e[0m"
echo - Prepare log files -
sudo mkdir /etc/apache2/logs
sudo chmod 777 -R /etc/apache2/logs

echo - Disable Default Virtual Hosts -
sudo a2dissite 000-default

echo - Install Custom Virtual Hosts -
for i in "admin" "api" "app" "zzz-catch-all"
do
    sudo cp _config/vhost/${i}.conf /etc/apache2/sites-available/${i}.conf
    sudo a2ensite ${i}
done


echo - Install Composer -
cd symfony
for i in "admin" "api" "app"
do
    cd $i
    curl -s http://getcomposer.org/installer | php
    cd ..
done
cd ..

sudo service apache2 restart

echo "[All Done]"