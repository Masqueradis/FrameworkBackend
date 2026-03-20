DOCKER_COMPOSE=docker compose -f docker/compose.yaml --env-file docker/.env

.PHONY: up down ps restart bash build

up:
	${DOCKER_COMPOSE} up -d

down:
	${DOCKER_COMPOSE} down

ps:
	${DOCKER_COMPOSE} ps

restart:
	${DOCKER_COMPOSE} down
	${DOCKER_COMPOSE} up -d

bash:
	${DOCKER_COMPOSE} exec app bash

build:
	${DOCKER_COMPOSE} build

