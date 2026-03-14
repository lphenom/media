.PHONY: up down install test lint lint-fix phpstan kphp-check phar-build help

PHP = docker compose run --rm php

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

up: ## Start the dev container
	docker compose up -d

down: ## Stop the dev container
	docker compose down

install: ## Install Composer dependencies
	$(PHP) composer install --no-progress --prefer-dist

test: ## Run PHPUnit tests
	$(PHP) composer test

lint: ## Check code style (dry-run)
	$(PHP) composer lint

lint-fix: ## Fix code style in place
	$(PHP) composer lint-fix

phpstan: ## Run PHPStan static analysis
	$(PHP) composer phpstan

kphp-check: ## Build KPHP binary + PHAR (Dockerfile.check)
	docker build -f Dockerfile.check -t lphenom-media-check .

phar-build: ## Build the PHAR archive locally (requires phar.readonly=0)
	$(PHP) php -d phar.readonly=0 build/build-phar.php

shell: ## Open a shell in the PHP container
	$(PHP) sh

