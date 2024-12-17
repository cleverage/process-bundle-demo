.ONESHELL:
SHELL := /bin/bash

DOCKER_RUN_PHP = docker compose -f .docker/compose.yaml run --rm php "bash" "-c"
DOCKER_COMPOSE = docker compose -f .docker/compose.yaml

start: upd doctrine/migrations doctrine/fixtures assets/install #[Global] Start application

src/vendor: #[Composer] install dependencies
	$(DOCKER_RUN_PHP) "composer install --no-interaction"

upd: #[Docker] Start containers detached
	touch .docker/.env
	make src/vendor
	$(DOCKER_COMPOSE) up --remove-orphans --detach

up: #[Docker] Start containers
	touch .docker/.env
	make src/vendor
	$(DOCKER_COMPOSE) up --remove-orphans

stop: #[Docker] Down containers
	$(DOCKER_COMPOSE) stop

down: #[Docker] Down containers
	$(DOCKER_COMPOSE) down

build: #[Docker] Build containers
	$(DOCKER_COMPOSE) build

ps: # [Docker] Show running containers
	$(DOCKER_COMPOSE) ps

bash: #[Docker] Connect to php container with current host user
	$(DOCKER_COMPOSE) exec -u $$(id -u $${USER}):$$(id -g $${USER}) php bash

logs: #[Docker] Show logs
	$(DOCKER_COMPOSE) logs -f

doctrine/migrations: #[Symfony] Run database migration
	$(DOCKER_RUN_PHP) "bin/console doctrine:migrations:migrate --no-interaction"

doctrine/fixtures: #[Symfony] Run database fixtures load
	$(DOCKER_RUN_PHP) "bin/console doctrine:fixtures:load --no-interaction"

assets/install: #[Symfony] Install assets
	$(DOCKER_RUN_PHP) "bin/console assets:install"

xdebug/on: #[Docker] Enable xdebug
	$(DOCKER_COMPOSE) stop php
	XDEBUG_MODE=debug,develop $(DOCKER_COMPOSE) up --remove-orphans --detach

xdebug/off: #[Docker] Disable xdebug
	$(DOCKER_COMPOSE) stop php
	XDEBUG_MODE=off $(DOCKER_COMPOSE) up --remove-orphans --detach

quality: phpstan php-cs-fixer rector #[Quality] Run all quality checks

phpstan: #[Quality] Run PHPStan
	$(DOCKER_RUN_PHP) "vendor/bin/phpstan --no-progress --memory-limit=1G analyse"

php-cs-fixer: #[Quality] Run PHP-CS-Fixer
	$(DOCKER_RUN_PHP) "vendor/bin/php-cs-fixer fix --diff --verbose"

rector: #[Quality] Run Rector
	$(DOCKER_RUN_PHP) "vendor/bin/rector"

tests: phpunit #[Tests] Run all tests

phpunit: #[Tests] Run PHPUnit
	$(DOCKER_RUN_PHP) "vendor/bin/phpunit"
