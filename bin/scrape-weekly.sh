#!/usr/bin/env bash
#
# Weekly cron job: pulls the 100 newest anime, then a randomly chosen year's
# full catalog. Keeps the DB fresh against AniList edits without doing a full
# multi-hour rescrape.
#
# Install on the VPS:
#   chmod +x bin/scrape-weekly.sh
#   crontab -e
#   # add:
#   0 2 * * 0 /var/www/onlyweebs/bin/scrape-weekly.sh >> /var/www/onlyweebs/var/log/scrape-cron.log 2>&1
#
set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

CONSOLE="php bin/console"
COMMON_FLAGS="--no-debug --env=prod --update-existing"

YEAR_FROM=1980
YEAR_TO=$(date +%Y)
RANDOM_YEAR=$(( YEAR_FROM + RANDOM % (YEAR_TO - YEAR_FROM + 1) ))

echo "============================================================"
echo "Weekly scrape — $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "============================================================"

echo
echo "[1/3] Latest 100 anime (sort=START_DATE_DESC)"
$CONSOLE app:anime:scrape --sort=START_DATE_DESC --pages=2 --per-page=50 $COMMON_FLAGS

echo
echo "[2/3] Random year sweep — $RANDOM_YEAR"
$CONSOLE app:anime:scrape --year="$RANDOM_YEAR" --all --per-page=50 $COMMON_FLAGS

echo
echo "[3/3] Warm Liip cache for any newly-arrived images"
$CONSOLE app:liip:warmup --no-debug --env=prod

echo
echo "Done — $(date -u +%Y-%m-%dT%H:%M:%SZ)"
