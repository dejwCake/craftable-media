# Media

This package adds media management capabilities to your Eloquent models. It builds on Spatie's `spatie/laravel-medialibrary` and adds:
- Collections definition – a fluent API for defining media collections, inspired by conversion definitions
- Auto-processing – saving an Eloquent model can automatically process and attach media collections from the request
- Authorization – control who has permission to attach specific media to specific models
- Private access – control who has permission to view specific media

## Documentation
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
docker compose run -it --rm php-qa phpmd ./config,./lang,./routes,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```

### Run tests

Run tests against mariadb:
```shell
docker compose run -it --rm -e DB_CONNECTION=mysql test ./vendor/bin/phpunit
```

Run tests against postgresql:
```shell
docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```

Run tests with coverage:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit --coverage-text
```

### Run the whole PHP suite

Run every PHP check and the test suite against both databases in sequence (stops at the first failure):
```shell
docker compose run -it --rm test composer update \
  && docker compose run -it --rm php-qa composer normalize \
  && docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache \
  && docker compose run -it --rm php-qa phpcs -s --colors --extensions=php \
  && docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon \
  && docker compose run -it --rm php-qa phpmd ./config,./lang,./routes,./src,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml \
  && docker compose run -it --rm -e DB_CONNECTION=mysql test ./vendor/bin/phpunit \
  && docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```
