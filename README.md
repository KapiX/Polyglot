# Polyglot

## Installation

```
composer install
php artisan migrate
php artisan storage:link
```

It is assumed that the database has case-insensitive collation set.

## Docker

```
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link
```