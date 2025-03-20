include .env

init:
	docker compose up --build -d
	docker exec ${CONTAINER_PREFIX}-app composer install
	docker exec ${CONTAINER_PREFIX}-app bin/console doctrine:migrations:migrate --no-interaction

start:
	docker compose start

stop:
	docker compose stop
