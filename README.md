# Media

This packages adds an ability to assign a media to your eloquent models. It uses Spatie's `spatie/laravel-medialibrary`, but it goes a bit further:
- Collections definition - inspired by the conversions definition, we have created Media Collections definition via similar fluent API
- Auto-Process - saving the eloquent model automatically processes and attaches media collections from the request
- Authorization - controls, who has the permission to attach specific medium to specific model
- Private access - controls, who has the permission to view specific medium

You can find full documentation at https://docs.getcraftable.com/#/media.

## How to develop this project

### Composer

Update dependencies:
```shell
docker compose run -it --rm test composer update
```

Composer normalization:
```shell
docker compose run -it --rm php-qa composer normalize
```

### Run tests

Run tests with pcov:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:
```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
```

### Run code analysis tools (php-qa)

PHP compatibility:
```shell
docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
```

Code style:
```shell
docker compose run -it --rm php-qa phpcs -s --colors --extensions=php
```

Fix style issues:
```shell
docker compose run -it --rm php-qa phpcbf -s --colors --extensions=php
```

Static analysis (phpstan):
```shell
docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon
```

Mess detector (phpmd):
```shell
docker compose run -it --rm php-qa phpmd ./src,./config,./install-stubs,./resources,./routes,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```
