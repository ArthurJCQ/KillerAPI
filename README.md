# Killer API

This code runs the API of https://killerparty.app

# Installation

## Requirements

Make sure that you have [composer](https://getcomposer.org/download/) and PHP 8.2 minimum installed on your system.

[Docker compose](https://docs.docker.com/compose/) is recommended for database, although you can use PostgreSQL without container if you rather.

You will have to install [Mercure](https://mercure.rocks/) binary as well.

## Install dependencies

```bash
$ composer install
```

## Set up database

Run database with docker-compose file :
```bash
$ docker-compose up -d
```

... Or with any other way you choose.

Then, create your database using this command :
```bash
$ bin/console doctrine:database:create
```

And run migrations (you can use quick notation as follow) :
```bash
$ bin/console do:mi:mi
```

## Generate Keypair for JWT Authentication

This API is using JWT tokens to authenticate clients.
A keypair must be generated in order to sign these JWTs. To do so, run :
```bash
$ bin/console lexik:jwt:generate-keypair --overwrite
```

## Mercure

To use SSE events with Mercure, start with downloading Mercure binary on the [github release page](https://github.com/dunglas/mercure/releases), according to your dev environment.

Unzip the archive anywhere your want in your PC (not in this project).
Then, open `Caddyfile.dev` and add this line above the `route {` directive :
```Caddyfile
header Access-Control-Allow-Origin http://YOU-FRONT-END-APP-DOMAIN:PORT
```

To launch mercure, just launch this command :
```bash
$ MERCURE_PUBLISHER_JWT_KEY='dev-@1123581321-killer-mercure-secret' \
MERCURE_SUBSCRIBER_JWT_KEY='dev-@1123581321-killer-mercure-secret' \
./mercure run --config Caddyfile.dev
```

`MERCURE_PUBLISHER_JWT_KEY` & `MERCURE_SUBSCRIBER_JWT_KEY` must have the same value as the `MERCURE_JWT_SECRET` in your `.env`.

Check Mercure on this url : https://localhost/.well-known/mercure/ui/

You may have to authorize `unsecure https` on this domain.

## Running the Symfony API

If you have the Symfony CLI, just run :
```bash
$ symfony serve
```

Otherwise, use just php and run :
```bash
$ php -S 127.0.0.1:8000 -t public
```

# Tests & Static analysis

A Makefile is provided to ease tests & static analysis commands.

## PHPcs

```bash
$ make phpcs
```

## PHPstan

```bash
$ make phpstan
```

## PHPstan

```bash
$ make phpstan
```

## Unit tests

```bash
$ make unit-tests
```

## Functional tests

```bash
$ make functional-tests
```
