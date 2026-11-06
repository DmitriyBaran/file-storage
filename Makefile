.PHONY: init env key up down logs test purge shell

init: up ## Первый запуск: то же, что up

env: ## Создать .env из примера и APP_KEY, если их ещё нет (существующий ключ не трогает)
	@test -f .env || cp .env.example .env
	@grep -qE '^APP_KEY=.+' .env || $(MAKE) key

key: ## Сгенерировать APP_KEY в .env
	@sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:$$(openssl rand -base64 32)|" .env && rm -f .env.bak
	@echo "APP_KEY generated"

up: env
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f app worker scheduler

test:
	docker compose exec app vendor/bin/phpunit

purge: ## Запустить автоудаление вручную
	docker compose exec app php artisan files:purge-expired

shell:
	docker compose exec app bash
