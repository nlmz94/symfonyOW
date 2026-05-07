PHP_FPM_SERVICE ?= $(shell systemctl list-units --type=service --no-legend 2>/dev/null | awk '/php[0-9.]*-fpm/ {print $$1; exit}')
WEB_USER       ?= www-data
CONSOLE         = php bin/console
ENV             = prod

.PHONY: deploy pull install build migrate cache permissions reload-fpm logs stan wipe-anime scrape-anime reset-anime reset-images-cache install-cron uninstall-cron

deploy: pull install build migrate cache permissions reload-fpm
	@echo ""
	@echo "Deploy complete."

pull:
	git pull --ff-only

install:
	APP_ENV=$(ENV) composer install --no-dev --optimize-autoloader --no-interaction
	yarn install --frozen-lockfile

build:
	yarn build

migrate:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --env=$(ENV)

cache:
	$(CONSOLE) cache:clear --env=$(ENV) --no-debug
	$(CONSOLE) cache:warmup --env=$(ENV) --no-debug

permissions:
	sudo chown -R $(WEB_USER):$(WEB_USER) var/
	sudo chmod -R u+rwX,g+rwX var/

reload-fpm:
	@if [ -n "$(PHP_FPM_SERVICE)" ]; then \
		echo "Reloading $(PHP_FPM_SERVICE)"; \
		sudo systemctl reload $(PHP_FPM_SERVICE); \
	else \
		echo "No php-fpm service detected; skipping reload"; \
	fi

logs:
	tail -n 100 -f var/log/$(ENV)-$$(date +%Y-%m-%d).log

stan:
	vendor/bin/phpstan analyse --memory-limit=1G

wipe-anime:
	$(CONSOLE) app:anime:wipe --force

scrape-anime:
	$(CONSOLE) app:anime:scrape --all-strategies --no-debug
	$(CONSOLE) app:liip:warmup --no-debug

reset-anime: wipe-anime scrape-anime

reset-images-cache:
	$(CONSOLE) liip:imagine:cache:remove --no-debug
	$(CONSOLE) app:liip:warmup --no-debug

# Adds (or refreshes) the weekly scrape cron line for the current user.
# Idempotent: any existing line referencing scrape-weekly.sh is replaced.
CRON_SCHEDULE = 0 2 * * 0
CRON_SCRIPT   = $(shell pwd)/bin/scrape-weekly.sh
CRON_LOG      = $(shell pwd)/var/log/scrape-cron.log

install-cron:
	chmod +x bin/scrape-weekly.sh
	@( crontab -l 2>/dev/null | grep -v "scrape-weekly.sh" ; \
	   echo "$(CRON_SCHEDULE) $(CRON_SCRIPT) >> $(CRON_LOG) 2>&1" ) | crontab -
	@echo "Installed:"
	@crontab -l | grep "scrape-weekly.sh"

uninstall-cron:
	@crontab -l 2>/dev/null | grep -v "scrape-weekly.sh" | crontab - || true
	@echo "Removed scrape-weekly.sh entries from crontab."
