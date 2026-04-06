ENV_FILE := $(if $(wildcard docker/.env),docker/.env,docker/.env.example)
DOCKER_COMPOSE=docker compose -f docker/compose.yaml --env-file ${ENV_FILE}

.PHONY: up down ps restart bash build init

init:
	@if [ ! -f "./src/.env" ]; then \
		cp ./src/.env.example ./src/.env; \
	fi
	@if [ ! -f "./docker/.env" ]; then \
		cp ./docker/.env.example ./docker/.env; \
	fi

up: init
	${DOCKER_COMPOSE} up -d

down: init
	${DOCKER_COMPOSE} down

ps: init
	${DOCKER_COMPOSE} ps

restart: init
	${DOCKER_COMPOSE} down
	${DOCKER_COMPOSE} up -d

bash: init
	${DOCKER_COMPOSE} exec app bash

build: init
	${DOCKER_COMPOSE} build

setup: init build up
	${DOCKER_COMPOSE} exec app composer install
	${DOCKER_COMPOSE} exec app php artisan key:generate
	${DOCKER_COMPOSE} exec app php artisan migrate --seed
	${DOCKER_COMPOSE} exec app php artisan storage:link
	cd src && npm install
	cd src && npm run build
