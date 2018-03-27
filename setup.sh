#!/bin/bash

########################
##### VIRTUAL HOST #####
########################

# Virtual host needed variables
APPS=( "admin" "api" "app" )
SUBDOMAINS=( "admin" "api" "app" )
appsLength=${#APPS[@]}
needleApp="#APP#"
needleUrl="#URL#"
needleDomain="#DOMAIN#"
destDir="config/vhost/#APP#.conf"


# Virtual Host Boilerplate
read -r -d '' VHOST_BOILERPLATE << EOM
<VirtualHost *:80>
    ServerName      #URL#
    Redirect permanent / https://#URL#/
</VirtualHost>

<VirtualHost *:443>
    ServerName      #URL#
    DocumentRoot    /var/www/symfony/#APP#/web
    RewriteEngine   On
    <Directory /var/www/symfony/#APP#/web>
        AllowOverride   All
    </Directory>

    SSLEngine On
    #SSLCertificateFile /etc/letsencrypt/live/#URL#/fullchain.pem # Apache >= 2.4.8
    SSLCertificateKeyFile /etc/letsencrypt/live/#URL#/privkey.pem
    SSLCertificateFile /etc/letsencrypt/live/#URL#/cert.pem
    SSLCertificateChainFile /etc/letsencrypt/live/#URL#/chain.pem
</VirtualHost>
EOM

read -r -d '' VHOST_CATCH_ALL << EOM
<VirtualHost *:*>
    ServerName      #DOMAIN#
    ServerAlias     *.#DOMAIN#
    ServerAlias     *
    Redirect permanent / https://app.#DOMAIN#/
</VirtualHost>
EOM

## Ask for base domain
echo "Please input the domain that will be used for this installation (e.g. 'domain.com'): "
read baseDomain

## write out the base domain
echo "${baseDomain}" > "config/domain"

## Generate VHOST for all except App
for (( i=1; i < ${appsLength}+1; i++ ));
do
    # Strings to replace with
    replaceUrl=${SUBDOMAINS[$i-1]}"."${baseDomain}
    replaceApp=${APPS[$i-1]}
    # Replace variables
    vhost=${VHOST_BOILERPLATE//${needleUrl}/${replaceUrl}}
    vhost=${vhost//${needleApp}/${replaceApp}}
    vhost=${vhost//${needleDomain}/${baseDomain}}
    dest=${destDir//${needleApp}/${replaceApp}}

    # Write file
    echo "Creating vhost: " ${dest} "..."
    echo "${vhost}" > "${dest}"
done

## Generate VHOST for catch all
# Replace variables
vhost=${VHOST_CATCH_ALL//${needleDomain}/${baseDomain}}
vhost=${vhost//${needleApp}/"zzz-catch-all"}
dest=${destDir//${needleApp}/"zzz-catch-all"}

# Write Catch All file
echo "${vhost}" > "${dest}"


########################
#### PARAMETERS YAML ###
########################

# YAML Boilerplate
read -r -d '' YAML_BOILERPLATE << EOM
# This file is auto-generated during the composer install
parameters:
    # MySQL
    database_driver: pdo_mysql
    database_host: #DBHOST#
    database_port: 3306
    database_name: #DBNAME#
    database_user: #DBUSER#
    database_password: #DBPASSWORD#
    # Swiftmailer
    mailer_transport: gmail
    mailer_host: ~
    mailer_user: example@mydomain.com
    mailer_password: ~
    # Symfony
    secret: #SECRET#
    locale: en
    # Nickel Tracker
    nickel_tracker_base_domain: #BASEDOMAIN#
EOM

needleDBHost="#DBHOST#"
needleDBName="#DBNAME#"
needleDBUser="#DBUSER#"
needleDBPass="#DBPASSWORD#"
needleSecret="#SECRET#"
needleBaseDomain="#BASEDOMAIN#"

APPS=( "admin" "api" "app" )
appsLength=${#APPS[@]}

## Ask for MySQL host
echo "Please input the host for the database: "
read dbHost

## Ask for MySQL Name
echo "Please input the name for the database: "
read dbName

## Ask for MySQL User
echo "Please input the user for the database: "
read dbUser

## Ask for MySQL Password
echo "Please input the password for the database: "
read dbPassword

destDir="symfony/#APP#/app/config/parameters.yml"

## Generate parameters.yml for all
for (( i=1; i < ${appsLength}+1; i++ ));
do
    # bash generate random 32 character hex string (lowercase only)
    secret=$(cat /dev/urandom | tr -dc 'a-f0-9' | fold -w 32 | head -n 1)

    # Replace YAML
    yaml=${YAML_BOILERPLATE//${needleDBHost}/${dbHost}}
    yaml=${yaml//${needleDBName}/${dbName}}
    yaml=${yaml//${needleDBUser}/${dbUser}}
    yaml=${yaml//${needleDBPass}/${dbPassword}}
    yaml=${yaml//${needleDBPass}/${dbPassword}}
    yaml=${yaml//${needleSecret}/${secret}}
    yaml=${yaml//${needleBaseDomain}/${baseDomain}}
    # Strings to replace with
    replaceApp=${APPS[$i-1]}
    # Replace strings
    dest=${destDir//${needleApp}/${replaceApp}}

    # Write file
    echo "Creating parameters.yml: " ${dest} "..."
    touch ${dest}
    echo "${yaml}" > "${dest}"
done
