#!/bin/bash

########################
##### VIRTUAL HOST #####
########################

# Virtual host needed variables
APPS=( "webservice" "api" "marketing" "content" "config" "executive" "customer" )
SUBDOMAINS=( "ws" "api" "mkt" "cms" "config" "exec" "customer" )
appsLength=${#APPS[@]}
needleApp="#APP#"
needleUrl="#URL#"
needleDomain="#DOMAIN#"
destDir="config/vhost/#APP#.conf"


# Virtual Host Boilerplate
read -r -d '' VHOST_BOILERPLATE << EOM
<VirtualHost *:80>
    ServerName      #URL#
    ServerAdmin     admin@#DOMAIN#
    ServerSignature email
    DocumentRoot    /var/www/symfony/#APP#/web
    RewriteEngine   On
    <Directory /var/www/symfony/#APP#/web>
        AllowOverride   All
    </Directory>
</VirtualHost>
EOM

read -r -d '' VHOST_PORTAL << EOM
<VirtualHost *:80>
    ServerName      www.#DOMAIN#
    ServerAlias     #DOMAIN#
    ServerAlias     *.#DOMAIN#
    ServerAdmin     admin@#DOMAIN#
    ServerSignature email
    DocumentRoot    /var/www/symfony/#APP#/web
    RewriteEngine   On
    <Directory /var/www/symfony/#APP#/web>
        AllowOverride   All
    </Directory>
</VirtualHost>
EOM

## TODO: Create a 'zzz-catch-all' config file

## Ask for base domain
echo "Please input the domain that will be used for this installation (e.g. 'domain.com'): "
read baseDomain

## Generate VHOST for all except Portal
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

## Generate VHOST for Portal
# Replace variables
vhost=${VHOST_PORTAL//${needleDomain}/${baseDomain}}
vhost=${vhost//${needleApp}/"portal"}
dest=${destDir//${needleApp}/"portal"}

# Write file
echo "${vhost}" > "${dest}"


########################
#### PARAMETERS YAML ###
########################

## TODO: Remove Amazon S3 Support

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
    # Amazon S3
    amazon_s3_key: #S3KEY#
    amazon_s3_secret: #S3SECRET#
    amazon_s3_bucket: #S3BUCKET#
    cdn_prefix_url: #S3CDN#
    # Rhino SSM
    rhino_base_url: #RHINOURL#
EOM

needleDBHost="#DBHOST#"
needleDBName="#DBNAME#"
needleDBUser="#DBUSER#"
needleDBPass="#DBPASSWORD#"
needleSecret="#SECRET#"
needleS3Key="#S3KEY#"
needleS3Secret="#S3SECRET#"
needleS3Bucket="#S3BUCKET#"
needleS3CDN="#S3CDN#"
needleRhinoUrl="#RHINOURL#"

APPS=( "webservice" "api" "marketing" "content" "config" "executive" "customer" "portal" )
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

## Ask for Amazon Key
echo "Please input the Access Key ID for the Amazon S3 bucket: "
read s3Key

## Ask for Amazon Secret
echo "Please input the Secret Key for the Amazon S3 bucket: "
read s3Secret

## Ask for Amazon Bucket
echo "Please input the Bucket Name for the Amazon S3 bucket: "
read s3Bucket

## Ask for Amazon CDN
echo "Please input the complete CDN URL for the Amazon S3 bucket: "
read s3CDN

## Ask for Rhino SSM URL
echo "Please input the complete base URL for the service: "
read rhinoUrl

destDir="symfony/#APP#/app/config/parameters.yml"

## Generate parameters.yml for all
for (( i=1; i < ${appsLength}+1; i++ ));
do
    # TODO: Only HEX characters (not alphanumeric)
    # bash generate random 32 character alphanumeric string (lowercase only)
    secret=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)

    # Replace YAML
    yaml=${YAML_BOILERPLATE//${needleDBHost}/${dbHost}}
    yaml=${yaml//${needleDBName}/${dbName}}
    yaml=${yaml//${needleDBUser}/${dbUser}}
    yaml=${yaml//${needleDBPass}/${dbPassword}}
    yaml=${yaml//${needleDBPass}/${dbPassword}}
    yaml=${yaml//${needleSecret}/${secret}}
    yaml=${yaml//${needleS3Key}/${s3Key}}
    yaml=${yaml//${needleS3Secret}/${s3Secret}}
    yaml=${yaml//${needleS3Bucket}/${s3Bucket}}
    yaml=${yaml//${needleS3CDN}/${s3CDN}}
    yaml=${yaml//${needleRhinoUrl}/${rhinoUrl}}
    # Strings to replace with
    replaceApp=${APPS[$i-1]}
    # Replace strings
    dest=${destDir//${needleApp}/${replaceApp}}

    # Write file
    echo "Creating parameters.yml: " ${dest} "..."
    touch ${dest}
    echo "${yaml}" > "${dest}"
done
