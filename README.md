# Yomali Traffic Tracker

A professional website traffic tracking system built with PHP 8.4 and clean architecture principles.

## 🏗 Architecture

This project follows Domain-Driven Design (DDD) with a clean architecture approach:

- **Domain Layer** (`src/Domain/`): Business entities and logic
- **Application Layer** (`src/Application/`): Use cases and application services
- **Infrastructure Layer** (`src/Infrastructure/`): External services, database, HTTP

## 🛠 Technology Stack

- **PHP** 8.4
- **MySQL** 8.0
- **Nginx** Alpine
- **Docker** & Docker Compose
- **PHPUnit** for testing
- **Xdebug** for debugging

## 🚀 Quick Start

### Prerequisites

- Docker and Docker Compose installed
- Make command available
- Git

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/yomali-traffic-tracker.git
cd yomali-traffic-tracker
```

2. Setup the project:
```bash
make setup
```

This will:
- Copy `.env.example` to `.env`
- Build Docker containers
- Install PHP dependencies
- Seed database with test data

3. Access the services:
- **Application**: http://localhost:8888
- **PHPMyAdmin**: http://localhost:8081
    - Username: `root`
    - Password: `root_password`

## 📝 Available Commands

### Container Management
```bash
make help         # Show all available commands
make up           # Start all containers
make down         # Stop all containers
make restart      # Restart all services
make build        # Build containers
make logs         # View logs
make shell        # Access PHP container shell
make mysql        # Access MySQL CLI
make clean        # Clean everything (including data)
```

### Development
```bash
make seed         # Re-seed database with test data
make install      # Install PHP dependencies
make composer     # Run composer commands (e.g., make composer cmd="require package")
```

### Testing
```bash
make test              # Run all tests
make test-unit         # Run only unit tests  
make test-integration  # Run only integration tests
make test-acceptance   # Run only acceptance tests
make test-coverage     # Run tests with coverage report (requires Xdebug)
make test-coverage-text # Run tests with coverage summary in terminal
```

### Code Quality
```bash
make cs           # Check code style (PSR-12)
make cs-fix       # Fix code style automatically
make stan         # Run PHPStan static analysis
make quality      # Run all quality checks (cs + stan + test)
```

### Debugging
```bash
make xdebug-on       # Enable Xdebug for debugging
make xdebug-coverage # Enable Xdebug for coverage
make xdebug-off      # Disable Xdebug (better performance)
```

## 🧪 Testing

Run all tests:
```bash
make test
```

Run with coverage:
```bash
make test-coverage
```

## 🔍 Debugging with Xdebug

Enable Xdebug:
```bash
make xdebug-on
```

Disable Xdebug (better performance):
```bash
make xdebug-off
```

### PHPStorm Xdebug Configuration

1. **Configure PHP Interpreter**
    - File → Settings → PHP
    - PHP Language Level: 8.4
    - CLI Interpreter: Add from Docker Compose
    - Service: php

2. **Configure Debug**
    - File → Settings → PHP → Debug
    - Debug port: 9003
    - Can accept external connections: ✓

3. **Create Debug Configuration**
    - Run → Edit Configurations
    - Add PHP Remote Debug
    - IDE key: PHPSTORM
    - Server: Create new (localhost:8888)
    - Use path mappings: project → /var/www

4. **Start Debugging**
    - Click phone icon (Start Listening)
    - Set breakpoints in your code
    - Access http://localhost:8888

## 🌐 API Endpoints

The system provides RESTful API endpoints for tracking and monitoring website visits.

### Base URL
```
http://localhost:8888/api/v1/
```

### Endpoints

#### 📍 **POST /track.php** - Track Page Visit

**Purpose:** Record a page visit from a website

**Method:** `POST`  
**URL:** `http://localhost:8888/api/v1/track.php`  
**Content-Type:** `application/json`

**Request Body:**
```json
{
  "url": "https://example.com/page"
}
```

**Responses:**
- **204 No Content** - Visit tracked successfully
- **400 Bad Request** - Invalid JSON, missing URL, or invalid URL format
  ```json
  { "error": "URL is required" }
  ```
- **405 Method Not Allowed** - Only POST and OPTIONS methods are allowed
  ```json
  { "error": "Method not allowed" }
  ```
- **500 Internal Server Error** - Server error
  ```json
  { "error": "Internal server error" }
  ```

**CORS Support:** Fully configured for cross-origin requests

---

#### 🔍 **GET /health.php** - Health Check

**Purpose:** Check API service health and status

**Method:** `GET`  
**URL:** `http://localhost:8888/api/v1/health.php`

**Response:**
```json
{
  "status": "healthy",
  "timestamp": 1694678400,
  "service": "yomali-tracker-api",
  "version": "1.0.0"
}
```

### 📋 API Documentation

- **Interactive Documentation:** http://localhost:8888/api/docs.php

The interactive documentation provides:
- **Try it out** functionality for all endpoints
- **Complete request/response examples**
- **Schema validation** and error examples
- **CORS preflight** testing support

### 🧪 Testing the API

**Quick Test Commands:**
```bash
# Test tracking endpoint
curl -X POST http://localhost:8888/api/v1/track.php \
  -H "Content-Type: application/json" \
  -d '{"url": "https://example.com/test"}'

# Test health endpoint  
curl http://localhost:8888/api/v1/health.php
```

## 📊 Database

The system uses an optimized MySQL schema with composite indexes for fast analytics queries.

Test data includes:
- 5 different domains
- 3000+ visits over 6 months
- Various traffic patterns

### Database Access

```bash
# Access MySQL CLI
make mysql

# Common queries
SHOW TABLES;
SELECT COUNT(*) FROM visits;
SELECT page_domain, COUNT(DISTINCT ip_address) as unique_visitors 
FROM visits 
GROUP BY page_domain;
```

## 🔧 Development

### Code Quality

The project uses:
- **PSR-12** coding standard
- **PHPStan** level 7 for static analysis
- **PHPUnit** for testing

### Pre-commit Hooks

Pre-commit hooks run automatically before each commit to ensure code quality.

To install hooks:
```bash
.githooks/install.sh
```

Hooks include:
- PHP syntax check
- PSR-12 code style validation
- PHPStan static analysis
- Debug statements detection
- Conventional commits validation

### Conventional Commits

This project follows the Conventional Commits specification:

```
<type>(<scope>): <subject>

Types:
- feat:     New feature
- fix:      Bug fix
- docs:     Documentation
- style:    Code style (formatting)
- refactor: Code refactoring
- perf:     Performance improvement
- test:     Tests
- chore:    Maintenance tasks
```

Examples:
```bash
git commit -m "feat: add visitor tracking endpoint"
git commit -m "fix(api): resolve CORS issue"
git commit -m "docs: update installation guide"
```

## 📁 Project Structure

```
yomali-traffic-tracker/
├── .docker/              # Docker configuration files
│   ├── nginx/           # Nginx config
│   ├── php/             # PHP Dockerfile
│   └── mysql/           # MySQL init scripts
├── .githooks/           # Git hooks for code quality
├── database/            # SQL schema and seeders
│   ├── 01-schema.sql   # Database structure
│   └── 02-seeder.sql   # Test data
├── public/              # Public web root
│   ├── api/            # API endpoints
│   │   └── v1/        # API version 1 endpoints
│   ├── assets/         # Static assets
│   │   ├── css/       # Stylesheets
│   │   └── js/        # JavaScript files
│   └── index.php      # Entry point
├── src/                 # Application source code
│   ├── Domain/         # Business logic and entities
│   ├── Application/    # Use cases and services
│   └── Infrastructure/ # External services, DB, HTTP
├── tests/              # Test suites
│   ├── Unit/          # Unit tests
│   ├── Integration/   # Integration tests
│   └── Acceptance/    # Acceptance tests
├── vendor/             # Composer dependencies (git-ignored)
├── .env                # Environment variables (git-ignored)
├── .env.example        # Environment template
├── .gitignore         # Git ignore rules
├── composer.json      # PHP dependencies
├── docker-compose.yml # Docker services definition
├── Makefile          # Command shortcuts
├── phpunit.xml       # PHPUnit configuration
└── README.md         # This file
```

## 🔐 Security

- SQL injection prevention via prepared statements
- XSS protection headers in Nginx
- CORS properly configured for API endpoints
- Input validation at domain layer
- Environment variables for sensitive data

## 👤 Author

Melissa Feo La Cruz - melissa.feolacruz@gmail.com

## 🙏 Acknowledgments

- Yomali Team for the opportunity