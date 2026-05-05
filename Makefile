PHP_FPM_SERVICE ?= $(shell systemctl list-units --type=service --no-legend 2>/dev/null | awk '/php[0-9.]*-fpm/ {print $$1; exit}')
WEB_USER       ?= www-data
CONSOLE         = php bin/console
ENV             = prod

.PHONY: deploy pull install build migrate cache permissions reload-fpm logs

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
