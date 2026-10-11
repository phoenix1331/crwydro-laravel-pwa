.DEFAULT_GOAL := help

DC = docker compose
APP = $(DC) exec app

.PHONY: help up down build shell migrate seed test logs fresh artisan composer tinker lint lint-test dev npm assets

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers in the background
	@$(DC) up -d

down: ## Stop all containers
	@$(DC) down

build: ## Rebuild container images
	@$(DC) build

shell: ## Open a shell in the app container
	@$(APP) bash

migrate: ## Run database migrations
	@$(APP) php artisan migrate

seed: ## Seed the database
	@$(APP) php artisan db:seed

test: ## Run the test suite
	@$(APP) php artisan test

logs: ## Tail app container logs
	@$(DC) logs -f app

fresh: ## Drop all tables, migrate and seed
	@$(APP) php artisan migrate:fresh --seed

artisan: ## Run an artisan command, e.g. make artisan cmd="route:list"
	@$(APP) php artisan $(cmd)

composer: ## Run a composer command, e.g. make composer cmd="require foo/bar"
	@$(APP) composer $(cmd)

tinker: ## Open a tinker session
	@$(APP) php artisan tinker

lint: ## Fix code style with Pint
	@$(APP) ./vendor/bin/pint

lint-test: ## Check code style without fixing
	@$(APP) ./vendor/bin/pint --test

dev: ## Run artisan dev in the app container (queue, logs, Vite)
	@$(APP) php artisan dev

npm: ## Run an npm command in the app container, e.g. make npm cmd="install"
	@$(APP) npm $(cmd)

assets: ## Build front-end assets for production (stops the Vite dev server hot file)
	@$(APP) sh -c "rm -f public/hot && npm run build"
