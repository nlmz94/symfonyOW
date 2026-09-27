#!/usr/bin/env bash
set -euo pipefail

as_app() { runuser -u www-data -- "$@"; }

# var/ and the upload targets live on volumes/bind mounts that start out empty.
mkdir -p var/cache var/log \
         public/media/cache \
         public/users/profilePics \
         public/images/animes
chown -R www-data:www-data var public/media public/users public/images 2>/dev/null || true

# Reinstall only when the lockfile moved ahead of the last install.
if [ ! -f vendor/autoload_runtime.php ] || [ composer.lock -nt vendor/.install-stamp ]; then
    echo "[entrypoint] composer install"
    rm -rf var/cache/*
    as_app composer install --no-interaction --prefer-dist
    as_app touch vendor/.install-stamp
fi

# Schema bootstrap never blocks php-fpm from starting: a broken database should
# leave you with a running container you can debug, not a restart loop.
bootstrap_schema() {
    echo "[entrypoint] waiting for database"
    local ready=0
    for _ in $(seq 1 60); do
        if as_app php bin/console dbal:run-sql 'SELECT 1' --quiet >/dev/null 2>&1; then
            ready=1
            break
        fi
        sleep 1
    done
    if [ "$ready" != "1" ]; then
        echo "[entrypoint] database never answered; skipping schema setup" >&2
        return 0
    fi

    # migrations/ does not build the anime-side tables from nothing (the oldest
    # migration ALTERs `anime`, which no migration ever creates). So on an empty
    # database, build the schema from entity metadata and record every migration
    # as applied; from then on `migrate` behaves normally.
    if as_app php bin/console dbal:run-sql 'SELECT 1 FROM anime LIMIT 1' --quiet >/dev/null 2>&1; then
        echo "[entrypoint] doctrine:migrations:migrate"
        as_app php bin/console doctrine:migrations:migrate \
            --no-interaction --allow-no-migration \
            || echo "[entrypoint] migrate failed - see above" >&2
    else
        echo "[entrypoint] empty database: doctrine:schema:create + baseline"
        as_app php bin/console doctrine:schema:create --no-interaction \
            || { echo "[entrypoint] schema:create failed - see above" >&2; return 0; }
        as_app php bin/console doctrine:migrations:sync-metadata-storage \
            --no-interaction >/dev/null \
            || echo "[entrypoint] could not create the migrations metadata table" >&2
        as_app php bin/console doctrine:migrations:version \
            --add --all --no-interaction >/dev/null \
            || echo "[entrypoint] could not baseline migrations" >&2
    fi
}

if [ "${AUTO_MIGRATE:-1}" = "1" ]; then
    bootstrap_schema
fi

exec "$@"
