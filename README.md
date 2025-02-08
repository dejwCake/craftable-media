# Media

This packages adds an ability to assign a media to your eloquent models. It uses Spatie's `spatie/laravel-medialibrary`, but it goes a bit further:
- Collections definition - inspired by the conversions definition, we have created Media Collections definition via similar fluent API
- Auto-Process - saving the eloquent model automatically processes and attaches media collections from the request
- Authorization - controls, who has the permission to attach specific medium to specific model
- Private access - controls, who has the permission to view specific medium

You can find full documentation at https://docs.getcraftable.com/#/media.

## Run tests

To run tests use this docker environment.

```shell
  docker compose run -it --rm test vendor/bin/phpunit
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:

```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
```
