.PHONY: help up down build logs shell mysql seed clean test test-coverage test-unit test-integration composer install

# Colors
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m

help: ## Show this help
	@echo "${GREEN}Yomali Traffic Tracker - Commands${NC}"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-15s${NC} %s\n", $$1, $$2}'

up: ## Start all containers
	docker-compose up -d
	@echo "${GREEN}✓ All services started${NC}"
	@echo "  Web:        http://localhost:$${APP_PORT:-8888}"
	@echo "  PHPMyAdmin: http://localhost:$${PMA_PORT:-8081}"

down: ## Stop all containers
	docker-compose down
	@echo "${YELLOW}✓ All services stopped${NC}"

build: ## Build containers
	docker-compose build --no-cache
	@echo "${GREEN}✓ Containers built${NC}"

install: ## Install PHP dependencies
	docker-compose up -d
	docker exec yomali_php composer install
	@echo "${GREEN}✓ PHP dependencies installed${NC}"

logs: ## View logs
	docker-compose logs -f

shell: ## Access PHP container
	docker exec -it yomali_php bash

mysql: ## Access MySQL CLI
	docker exec -it yomali_mysql mysql -u$${DB_USER:-tracker_user} -p$${DB_PASSWORD:-tracker_pass} $${DB_NAME:-tracker_db}

seed: ## Re-seed database with test data
	docker exec -i yomali_mysql mysql -uroot -p$${DB_ROOT_PASSWORD:-root_password} < database/02-seeder.sql
	@echo "${GREEN}✓ Database seeded with test data${NC}"

composer: ## Run composer commands (e.g., make composer cmd="require package")
	docker exec yomali_php composer $(cmd)

clean: ## Clean everything (including data)
	docker-compose down -v
	rm -rf vendor/
	rm -rf public/coverage/
	@echo "${RED}✓ All containers, volumes and dependencies removed${NC}"

test: ## Run tests (without coverage, no warnings)
	@docker exec yomali_php vendor/bin/phpunit
	@echo "${GREEN}✓ Tests completed${NC}"

test-unit: ## Run only unit tests
	@docker exec yomali_php vendor/bin/phpunit --testsuite unit
	@echo "${GREEN}✓ Unit tests completed${NC}"

test-integration: ## Run only integration tests
	@docker exec yomali_php vendor/bin/phpunit --testsuite integration
	@echo "${GREEN}✓ Integration tests completed${NC}"

test-coverage: ## Run tests with coverage report (requires Xdebug)
	@docker exec -e XDEBUG_MODE=coverage yomali_php vendor/bin/phpunit --configuration phpunit-coverage.xml
	@echo "${GREEN}✓ Tests with coverage completed${NC}"
	@echo "${YELLOW}Coverage report available at: http://localhost:$${APP_PORT:-8888}/coverage/${NC}"

test-coverage-text: ## Run tests with coverage summary in terminal
	@docker exec -e XDEBUG_MODE=coverage yomali_php vendor/bin/phpunit --configuration phpunit-coverage.xml --coverage-text
	@echo "${GREEN}✓ Tests with coverage completed${NC}"

cs: ## Check code style (PSR-12)
	docker exec yomali_php vendor/bin/phpcs --standard=PSR12 src/

cs-fix: ## Fix code style automatically
	docker exec yomali_php vendor/bin/phpcbf --standard=PSR12 src/

stan: ## Run PHPStan static analysis
	docker exec yomali_php vendor/bin/phpstan analyse src --level=7

xdebug-on: ## Enable Xdebug for debugging
	@sed -i.bak 's/XDEBUG_MODE=.*/XDEBUG_MODE=develop,debug/' .env
	docker-compose up -d php
	@echo "${GREEN}✓ Xdebug enabled for debugging${NC}"

xdebug-coverage: ## Enable Xdebug for coverage
	@sed -i.bak 's/XDEBUG_MODE=.*/XDEBUG_MODE=coverage/' .env
	docker-compose up -d php
	@echo "${GREEN}✓ Xdebug enabled for coverage${NC}"

xdebug-off: ## Disable Xdebug (better performance)
	@sed -i.bak 's/XDEBUG_MODE=.*/XDEBUG_MODE=off/' .env
	docker-compose up -d php
	@echo "${YELLOW}✓ Xdebug disabled${NC}"

restart: down up ## Restart all services

setup: ## Initial project setup
	@cp -n .env.example .env || true
	@make build
	@make up
	@sleep 3
	@make install
	@make seed
	@echo "${GREEN}✓ Project setup complete!${NC}"

quality: ## Run all quality checks
	@echo "${YELLOW}Running code quality checks...${NC}"
	@make cs
	@make stan
	@make test
	@echo "${GREEN}✓ All quality checks passed${NC}"

clean-coverage: ## Remove coverage reports
	rm -rf public/coverage/
	@echo "${YELLOW}✓ Coverage reports removed${NC}"