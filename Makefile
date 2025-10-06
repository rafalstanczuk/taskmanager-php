.PHONY: help build up down restart logs test test-unit test-integration migrate seed clean install shell

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
RED := \033[0;31m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)Available commands:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'

build: ## Build Docker images
	@echo "$(BLUE)Building Docker images...$(NC)"
	docker compose build

up: ## Start all services
	@echo "$(BLUE)Starting services...$(NC)"
	docker compose up -d
	@echo "$(GREEN)Services started$(NC)"

down: ## Stop all services
	@echo "$(BLUE)Stopping services...$(NC)"
	docker compose down
	@echo "$(GREEN)Services stopped$(NC)"

restart: down up ## Restart all services

logs: ## Show service logs
	docker compose logs -f

test: ## Run all tests
	@echo "$(BLUE)Running test suite...$(NC)"
	./scripts/run-tests.sh --skip-integration

test-unit: ## Run unit tests only
	@echo "$(BLUE)Running unit tests...$(NC)"
	docker compose run --rm php vendor/bin/phpunit --testsuite Unit --testdox

test-integration: ## Run integration tests (requires server)
	@echo "$(BLUE)Running integration tests...$(NC)"
	@echo "$(RED)Note: Start server first with 'make serve'$(NC)"
	docker compose run --rm php vendor/bin/phpunit --testsuite Integration --testdox

test-coverage: ## Run tests with coverage report
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	docker compose run --rm php vendor/bin/phpunit --testsuite Unit --coverage-text

migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	docker compose run --rm php php scripts/migrate.php
	@echo "$(GREEN)Migrations completed$(NC)"

seed: ## Seed database with example data
	@echo "$(BLUE)Seeding database...$(NC)"
	docker compose run --rm php php scripts/seed.php
	@echo "$(GREEN)Database seeded$(NC)"

clean: ## Clean up containers and volumes
	@echo "$(BLUE)Cleaning up...$(NC)"
	docker compose down -v
	docker system prune -f
	@echo "$(GREEN)Cleanup completed$(NC)"

install: ## Install dependencies
	@echo "$(BLUE)Installing dependencies...$(NC)"
	docker compose run --rm php composer install --no-interaction
	@echo "$(GREEN)Dependencies installed$(NC)"

shell: ## Open PHP container shell
	@echo "$(BLUE)Opening shell...$(NC)"
	docker compose run --rm php bash

serve: ## Start development server
	@echo "$(BLUE)Starting development server on http://localhost:8000$(NC)"
	@echo "$(GREEN)Press Ctrl+C to stop$(NC)"
	docker compose run --rm -p 8000:8000 php php -S 0.0.0.0:8000 -t public

setup: build install migrate seed ## Complete project setup
	@echo "$(GREEN)Setup completed! Run 'make serve' to start the server$(NC)"

ci: ## Run CI/CD pipeline locally
	@echo "$(BLUE)Running CI/CD pipeline...$(NC)"
	./scripts/run-tests.sh --skip-integration --verbose

