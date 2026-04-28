SHELL := /usr/bin/env bash

DC := docker compose -f laradock/docker-compose.yml --env-file laradock/.env
SERVICES := nginx php-fpm php-worker workspace postgres redis mercure clickhouse zookeeper kafka mosquitto
CORE := /var/www/core
BUS := /var/www/bus
FRONTEND := /var/www/frontend
SAFE_GIT := git config --global --add safe.directory /var/www >/dev/null 2>&1 || true

.DEFAULT_GOAL := help

.PHONY: help build up down restart status ps logs shell \
	core-install core-migrate core-seed core-fresh-seed core-clickhouse core-consume core-swagger \
	core-test core-phpstan core-psalm core-analyse core-health \
	core-horizon core-horizon-status core-telescope-prune \
	bus-install bus-consume bus-test bus-phpstan bus-psalm bus-analyse bus-health bus-ready \
	frontend-install frontend-build frontend-health analyse check

help:
	@printf '\033[1;36m%s\033[0m\n\n' 'MQTT Project'
	@printf '  \033[1m%s\033[0m \033[33m%s\033[0m\n\n' 'Usage:' 'make <target>'
	@printf '\033[1;35m%s\033[0m\n' 'Infrastructure'
	@printf '  \033[32m%-22s\033[0m %s\n' 'build' 'Build Laradock services'
	@printf '  \033[32m%-22s\033[0m %s\n' 'up' 'Start project services'
	@printf '  \033[32m%-22s\033[0m %s\n' 'down' 'Stop project services'
	@printf '  \033[32m%-22s\033[0m %s\n' 'restart' 'Restart project services'
	@printf '  \033[32m%-22s\033[0m %s\n' 'status | ps' 'Show service status'
	@printf '  \033[32m%-22s\033[0m %s\n' 'logs service=nginx' 'Follow logs for one service'
	@printf '  \033[32m%-22s\033[0m %s\n\n' 'shell' 'Open workspace shell'
	@printf '\033[1;35m%s\033[0m\n' 'Core'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-install' 'Composer install for Laravel core'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-migrate' 'Run PostgreSQL migrations'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-seed' 'Seed demo users and devices'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-fresh-seed' 'Rebuild PostgreSQL schema with demo data'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-clickhouse' 'Create ClickHouse schema'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-consume' 'Consume Kafka packets into ClickHouse'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-swagger' 'Generate OpenAPI documentation'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-horizon' 'Run Horizon queue worker'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-horizon-status' 'Show Horizon status'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-telescope-prune' 'Prune Telescope entries'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-test' 'Run Laravel tests'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-phpstan' 'Run PHPStan level 8 for core'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-psalm' 'Run Psalm strict analysis for core'
	@printf '  \033[32m%-22s\033[0m %s\n' 'core-analyse' 'Run core static analysis'
	@printf '  \033[32m%-22s\033[0m %s\n\n' 'core-health' 'Check core HTTP health endpoints'
	@printf '\033[1;35m%s\033[0m\n' 'Bus'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-install' 'Composer install for bus worker'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-consume' 'Run MQTT -> Kafka worker'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-test' 'Run bus PHPUnit tests'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-phpstan' 'Run PHPStan level 8 for bus'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-psalm' 'Run Psalm strict analysis for bus'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-analyse' 'Run bus static analysis'
	@printf '  \033[32m%-22s\033[0m %s\n' 'bus-health' 'Check bus liveness endpoint'
	@printf '  \033[32m%-22s\033[0m %s\n\n' 'bus-ready' 'Check bus worker readiness endpoint'
	@printf '\033[1;35m%s\033[0m\n' 'Frontend'
	@printf '  \033[32m%-22s\033[0m %s\n' 'frontend-install' 'NPM clean install for Vue frontend'
	@printf '  \033[32m%-22s\033[0m %s\n' 'frontend-build' 'Build Vue frontend assets'
	@printf '  \033[32m%-22s\033[0m %s\n\n' 'frontend-health' 'Check frontend HTTP entrypoint'
	@printf '\033[1;35m%s\033[0m\n' 'Validation'
	@printf '  \033[32m%-22s\033[0m %s\n' 'analyse' 'Run static analysis for core and bus'
	@printf '  \033[32m%-22s\033[0m %s\n' 'check' 'Run syntax, tests and health checks'

build:
	$(DC) build $(SERVICES)

up:
	$(DC) up -d $(SERVICES)

down:
	$(DC) down

restart: down up

status ps:
	$(DC) ps

logs:
	$(DC) logs -f --tail=200 $(service)

shell:
	$(DC) exec workspace bash

core-install:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(CORE) && composer install'

core-migrate:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan migrate --force'

core-seed:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan db:seed --force'

core-fresh-seed:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan migrate:fresh --seed --force'

core-clickhouse:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan clickhouse:migrate'

core-consume:
	$(DC) exec workspace bash -lc 'cd $(CORE) && php artisan kafka:consume-packets'

core-swagger:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && TELESCOPE_ENABLED=false php artisan l5-swagger:generate'

core-horizon:
	$(DC) exec workspace bash -lc 'cd $(CORE) && php artisan horizon'

core-horizon-status:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan horizon:status'

core-telescope-prune:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan telescope:prune --hours=48'

core-test:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(CORE) && TELESCOPE_ENABLED=false php artisan test'

core-phpstan:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(CORE) && composer phpstan'

core-psalm:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(CORE) && composer psalm'

core-analyse:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(CORE) && composer analyse'

core-health:
	curl -fsS -H 'Host: core.localhost' http://localhost/health
	@printf '\n'
	curl -fsS -H 'Host: core.localhost' http://localhost/ready
	@printf '\n'

bus-install:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(BUS) && composer install'

bus-consume:
	$(DC) exec workspace bash -lc 'cd $(BUS) && php bin/mqtt-consume.php'

bus-test:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(BUS) && composer test'

bus-phpstan:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(BUS) && composer phpstan'

bus-psalm:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(BUS) && composer psalm'

bus-analyse:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(BUS) && composer analyse'

bus-health:
	curl -fsS -H 'Host: bus.localhost' http://localhost/health
	@printf '\n'

bus-ready:
	curl -fsS -H 'Host: bus.localhost' http://localhost/ready
	@printf '\n'

frontend-install:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(FRONTEND) && npm ci'

frontend-build:
	$(DC) exec -T workspace bash -lc '$(SAFE_GIT); cd $(FRONTEND) && npm run build'

frontend-health:
	curl -fsS -H 'Host: frontend.localhost' http://localhost/ >/dev/null
	@printf 'frontend ok\n'

analyse:
	$(MAKE) core-analyse
	$(MAKE) bus-analyse

check:
	$(DC) config --services >/dev/null
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && find app src config database routes -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null'
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && find app bin config public -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null'
	$(MAKE) analyse
	$(MAKE) core-test
	$(MAKE) bus-test
	$(MAKE) core-health
	$(MAKE) bus-health
	$(MAKE) frontend-health
