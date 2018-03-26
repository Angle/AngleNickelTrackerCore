#!/bin/bash

if [ ! -f config/domain ]; then
    echo "ERROR: Must run the setup.sh before installing."
else

    echo - Generate CertBot SSL Certificates -
    sudo service apache2 stop
    baseDomain=`cat config/domain`
    echo "base domain: "${baseDomain}
    for i in "admin" "api" "app"
    do
        domain=${i}"."${baseDomain}
        sudo certbot certonly --standalone -d ${domain}
    done

    sudo chmod 755 -R /etc/letsencrypt

fi


