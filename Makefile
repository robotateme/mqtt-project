SHELL := /usr/bin/env bash

DC := docker compose -f laradock/docker-compose.yml --env-file laradock/.env
SERVICES := nginx php-fpm workspace postgres clickhouse zookeeper kafka mosquitto
CORE := /var/www/core
BUS := /var/www/bus

.DEFAULT_GOAL := help

.PHONY: help build up down restart status ps logs shell \
	core-install core-migrate core-clickhouse core-consume core-test core-phpstan core-psalm core-analyse core-health \
	bus-install bus-consume bus-phpstan bus-psalm bus-analyse bus-health bus-ready analyse check

help:
	@printf '%s\n' \
		'Usage: make <target>' \
		'' \
		'Infrastructure:' \
		'  build              Build Laradock services' \
		'  up                 Start project services' \
		'  down               Stop project services' \
		'  restart            Restart project services' \
		'  status | ps        Show service status' \
		'  logs service=nginx Follow logs for one service' \
		'  shell              Open workspace shell' \
		'' \
		'Core:' \
		'  core-install       Composer install for Laravel core' \
		'  core-migrate       Run PostgreSQL migrations' \
		'  core-clickhouse    Create ClickHouse schema' \
		'  core-consume       Consume Kafka packets into ClickHouse' \
		'  core-test          Run Laravel tests' \
		'  core-phpstan       Run PHPStan level 8 for core' \
		'  core-psalm         Run Psalm strict analysis for core' \
		'  core-analyse       Run core static analysis' \
		'  core-health        Check core HTTP health endpoints' \
		'' \
		'Bus:' \
		'  bus-install        Composer install for bus worker' \
		'  bus-consume        Run MQTT -> Kafka worker' \
		'  bus-phpstan        Run PHPStan level 8 for bus' \
		'  bus-psalm          Run Psalm strict analysis for bus' \
		'  bus-analyse        Run bus static analysis' \
		'  bus-health         Check bus liveness endpoint' \
		'  bus-ready          Check bus worker readiness endpoint' \
		'' \
		'Validation:' \
		'  analyse            Run static analysis for core and bus' \
		'  check              Run syntax/tests/health checks'

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
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && composer install'

core-migrate:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan migrate --force'

core-clickhouse:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan clickhouse:migrate'

core-consume:
	$(DC) exec workspace bash -lc 'cd $(CORE) && php artisan kafka:consume-packets'

core-test:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && php artisan test'

core-phpstan:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && composer phpstan'

core-psalm:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && composer psalm'

core-analyse:
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && composer analyse'

core-health:
	curl -fsS -H 'Host: core.localhost' http://localhost/health
	@printf '\n'
	curl -fsS -H 'Host: core.localhost' http://localhost/ready
	@printf '\n'

bus-install:
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && composer install'

bus-consume:
	$(DC) exec workspace bash -lc 'cd $(BUS) && php bin/mqtt-consume.php'

bus-phpstan:
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && composer phpstan'

bus-psalm:
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && composer psalm'

bus-analyse:
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && composer analyse'

bus-health:
	curl -fsS -H 'Host: bus.localhost' http://localhost/health
	@printf '\n'

bus-ready:
	curl -fsS -H 'Host: bus.localhost' http://localhost/ready
	@printf '\n'

analyse:
	$(MAKE) core-analyse
	$(MAKE) bus-analyse

check:
	$(DC) config --services >/dev/null
	$(DC) exec -T workspace bash -lc 'cd $(CORE) && find app src config database routes -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null'
	$(DC) exec -T workspace bash -lc 'cd $(BUS) && find app bin config public -type f -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null'
	$(MAKE) analyse
	$(MAKE) core-test
	$(MAKE) core-health
	$(MAKE) bus-health
