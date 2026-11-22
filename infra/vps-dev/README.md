# VPS dev containers

A throwaway PHP 8.4 and MariaDB 11.4 stack for running the project's checks on the VPS dev server, which has no PHP, Composer or MariaDB on the host. It is separate from the Hermes sandbox and from production.

## Layout on the VPS

```
~/workspace/petgroomdev/          the repository (clone of develop)
~/workspace/petgroomdev-infra/    a copy of this folder, plus a .env that is not committed
```

The compose file mounts `../petgroomdev` into the PHP container, so keep that sibling layout.

## Secret handling

`compose.yml` reads `PGDEV_DB_ROOT_PASSWORD` from a `.env` file next to it. That `.env` is **not committed**: create it on the VPS with a random value and mode `600`. The password never appears in the repository, in chat or in logs. No port is published to the host, so MariaDB is reachable only from the `pgdev` Docker network.

The repository's own `.env` is never created or edited. The application key is passed per command as `APP_KEY`.

## Use

```sh
cd ~/workspace/petgroomdev-infra
docker compose up -d --build
docker compose exec -T -u $(id -u):$(id -g) -e HOME=/tmp app composer install --no-interaction
```

Run the default suite (SQLite) and the lint and analysis checks as usual. For the real-MariaDB suite, including `tests/Concurrency`, pass the connection per command and read the password from the `.env` without printing it:

```sh
set -a; . ./.env; set +a
KEY=$(docker compose exec -T app php artisan key:generate --show | tail -1)
docker compose exec -T -u $(id -u):$(id -g) -e HOME=/tmp \
  -e APP_KEY="$KEY" -e DB_CONNECTION=mariadb -e DB_HOST=mariadb -e DB_PORT=3306 \
  -e DB_DATABASE=petgrooming_spike -e DB_USERNAME=root -e DB_PASSWORD="$PGDEV_DB_ROOT_PASSWORD" \
  app vendor/bin/phpunit tests/Concurrency
```

The suite refuses to run against a database whose name does not end in `_spike`.

## Limits

Memory is capped (1 GB for MariaDB, 2 GB for PHP). This stack serves no HTTP and has no domain. Dev and production must stay isolated: separate Linux user, Docker network and resource limits.
