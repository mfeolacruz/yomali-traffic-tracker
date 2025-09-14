<?php

declare(strict_types=1);

namespace Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection;

use Dotenv\Dotenv;

final class MySQLConnection
{
    private static ?self $instance = null;
    private \PDO $pdo;

    private function __construct()
    {
        $this->loadEnvironment();

        $host = $this->getRequiredEnv('DB_HOST');
        $port = $this->getRequiredEnv('DB_PORT');
        $database = $this->getRequiredEnv('DB_NAME');
        $username = $this->getRequiredEnv('DB_USER');
        $password = $this->getRequiredEnv('DB_PASSWORD');

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        try {
            $this->pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get required environment variable or throw exception
     *
     * @throws \RuntimeException if variable is not set or empty
     */
    private function getRequiredEnv(string $key): string
    {
        // In test environment, prioritize $_ENV (set by phpunit.xml) over getenv() (Docker/system)
        $isTestEnvironment = ($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing';

        if ($isTestEnvironment && isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } else {
            // Try $_ENV first (set by Dotenv or tests), then getenv() (Docker/system)
            $value = $_ENV[$key] ?? getenv($key);
        }

        if ($value === false || $value === '') {
            throw new \RuntimeException(
                "Required environment variable '{$key}' is not set"
            );
        }

        return $value;
    }

    private function loadEnvironment(): void
    {
        // Only load .env file if we're not in test environment
        // Tests should rely on phpunit.xml environment configuration
        $isTestEnvironment = ($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing';

        if (!$isTestEnvironment) {
            $envFile = '/var/www/.env';

            if (file_exists($envFile)) {
                $dotenv = Dotenv::createMutable('/var/www');
                $dotenv->safeLoad();
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    // Prevent cloning
    private function __clone()
    {
    }
}
