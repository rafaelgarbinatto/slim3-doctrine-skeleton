# Skeleton
## Installation

Clone the repository and access the directory:
```sh
$ git clone git@github.com:siworksit/slim3-siworks-skeleton.git && cd slim3-siworks-skeleton
```
If you don't have the container `docker-hosts-updater` running, you can execute that: 
```sh
$ docker run -d --restart=always --name docker-hosts-updater -v /var/run/docker.sock:/var/run/docker.sock -v /etc/hosts:/opt/hosts grachev/docker-hosts-updater
```
It will automatically map the hostnames of the containers to the corresponding IPs. This mapping can be done manually through the hosts of the machine.

Execute the composer:
```sh
$ docker-compose run --rm composer
```

Run the web service with docker-compose:
```sh
$ docker-compose up -d web
```

Create the schema to mysql:
```sh
$ docker exec -t dev-skeleton-web vendor/bin/doctrine orm:schema-tool:create
```
