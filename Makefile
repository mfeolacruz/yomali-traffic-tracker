.PHONY: help up down build logs shell mysql seed clean test composer install

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
	@echo "  Web:        http://localhost:$${APP_PORT:-8080}"
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
	@echo "${RED}✓ All containers, volumes and dependencies removed${NC}"

test: ## Run PHPUnit tests
	docker exec yomali_php vendor/bin/phpunit

test-coverage: ## Run tests with coverage report
	docker exec yomali_php vendor/bin/phpunit --coverage-html coverage

cs: ## Check code style (PSR-12)
	docker exec yomali_php vendor/bin/phpcs --standard=PSR12 src/

cs-fix: ## Fix code style automatically
	docker exec yomali_php vendor/bin/phpcbf --standard=PSR12 src/

stan: ## Run PHPStan static analysis
	docker exec yomali_php vendor/bin/phpstan analyse src --level=7

xdebug-on: ## Enable Xdebug
	@sed -i.bak 's/XDEBUG_MODE=.*/XDEBUG_MODE=develop,debug/' .env
	docker-compose up -d php
	@echo "${GREEN}✓ Xdebug enabled${NC}"

xdebug-off: ## Disable Xdebug (better performance)
	@sed -i.bak 's/XDEBUG_MODE=.*/XDEBUG_MODE=off/' .env
	docker-compose up -d php
	@echo "${YELLOW}✓ Xdebug disabled${NC}"

restart: down up ## Restart all services

setup: ## Initial project setup
	@cp -n .env.example .env || true
	@make build
	@make install
	@make seed
	@echo "${GREEN}✓ Project setup complete!${NC}"
	@echo ""
	@echo "  Access the application at: http://localhost:$${APP_PORT:-8080}"
	@echo "  Access PHPMyAdmin at:     http://localhost:$${PMA_PORT:-8081}"
	@echo ""
	@echo "  Run 'make help' to see all available commands"