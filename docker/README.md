# Dev environment

Everything the app needs runs in containers. The only host requirement is
Docker Desktop (or Docker Engine + Compose v2).

```bash
docker compose up -d --build     # or: make up
```

First boot takes a few minutes (composer install). After that:

| What      | Where                                        |
|-----------|----------------------------------------------|
| API       | http://localhost:8000/api                    |
| Profiler  | http://localhost:8000/_profiler              |
| Database  | `127.0.0.1:3307` — user `app`, pass `app`, db `onlyweebs` |

Port **3307**, not 3306, so this does not collide with the MariaDB you already
run locally.

## Services

| Service    | Image                  | Role                                          |
|------------|------------------------|-----------------------------------------------|
| `php`      | built, PHP 8.5-FPM     | app + CLI; runs composer install and schema setup on boot |
| `nginx`    | `nginx:1.29-alpine`    | front controller on port 8000                 |
| `database` | `mariadb:11.4`         | MySQL-protocol DB, seeded with `onlyweebs_test` too |

PHP extensions installed: `pdo_mysql`, `intl`, `gd` (with WebP — required by the
`*_webp` Liip filter sets), `exif`, `zip`, `opcache`, `xdebug`.

## Everyday commands

```bash
docker compose logs -f php nginx         # tail app + web server
docker compose exec -u www-data php bash # shell in the app container
docker compose exec -u www-data php php bin/console cache:clear
docker compose exec database mariadb -uapp -papp onlyweebs
docker compose down -v && docker compose up -d   # wipe DB and start over
```

`make up`, `make sh`, `make dbsh`, `make dc-test`, `make fresh` wrap these if you
have `make` available (it is not installed on Windows by default).

## Config notes

`DATABASE_URL` and `TRUSTED_PROXIES` are set in `compose.yaml`, not in `.env`.
Symfony's Dotenv never overrides a real environment variable, so the committed
`.env` keeps pointing at your host database and nothing needs to change when you
switch between the two.

`upload_max_filesize` is raised to 8M in `docker/php/conf.d/app.ini`; PHP's 2M
default would reject the 5M profile pictures `UserController` accepts before
validation ever ran.

`var/` is a named volume rather than a bind mount — it is
write-heavy and painfully slow over a Windows bind mount. `vendor/` *is* bind
mounted so PhpStorm can index it.

## Xdebug

Installed but inert. To enable step debugging on port 9003:

```bash
XDEBUG_MODE=debug docker compose up -d php     # or: make xdebug
```

## Schema bootstrap

`migrations/` cannot build the database from nothing — the oldest migration
(`Version20250824171142`) runs `ALTER TABLE anime`, but no migration ever creates
`anime`, `genre`, `studio`, `producer` or the join tables. They were made with
`doctrine:schema:update` and never captured.

So the entrypoint branches: on an empty database it runs
`doctrine:schema:create` from entity metadata and then marks all migrations as
applied; on an existing database it just runs `doctrine:migrations:migrate`.
Both paths are non-fatal — a database problem leaves you with a running
container to debug rather than a restart loop.

This is worth fixing properly at some point (a baseline migration that creates
those tables), because the same gap affects any fresh prod or CI database.
