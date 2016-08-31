# NickelTrackerCore
Envelope budget

## Documentation
TODO: See `/doc/`

## Reqs
Ubuntu 14.04 LTS

## Setup

1. Update your packages and install Git
```
sudo apt-get update
sudo apt-get install -y git
```

2. Run the following snippet
``` 
sudo mkdir /var/www
sudo chmod -R 777 /var/www
cd /var/www
git clone https://github.com/Angle/AngleNickelTrackerCore .
```

3. Run the `setup.sh` script to generate your configuration files
```
sudo chmod +x setup.sh
./setup.sh
```

4. Run the `install.sh` script to initialize the server configuration and install the required packages
```
sudo chmod +x install.sh
./install.sh
```

5. Run the `update.sh` script to install the Symfony Apps and to generate the database structure
```
sudo chmod +x update.sh
./update.sh
```



## Symfony Apps
1. **api**
    * bundles: `core`,  `api`
    * subdomain: `api`
    * security: public (no security)
2. **admin**
    * bundles: `core`,  `admin`
    * subdomain: `admin`
    * security: firewalled, having `ROLE_SUPER_ADMIN`
3. **app**
    * bundles: `core`,  `app`
    * subdomain: `app`
    * security: firewalled, having `ROLE_USER`


## Created by
- [Alejandro Hernández G](https://github.com/alexhg11)
- [Edmundo Fuentes](https://github.com/edmundofuentes)
