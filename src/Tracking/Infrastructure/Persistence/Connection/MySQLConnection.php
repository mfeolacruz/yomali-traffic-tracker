<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tracking\Infrastructure\Persistence\Connection;

use Dotenv\Dotenv;

final class MySQLConnection
{
    private static ?self $instance = null;
    private static bool $envLoaded = false;
    private \PDO $pdo;

    private function __construct()
    {
        $this->loadEnvironment();

        // Get configuration - will throw if not found
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
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
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
        // Try $_ENV first (set by Dotenv or tests), then getenv() (Docker/system)
        $value = $_ENV[$key] ?? getenv($key);

        // getenv() returns false when variable doesn't exist
        // We also treat empty string as invalid
        if ($value === false || $value === '') {
            throw new \RuntimeException(
                "Required environment variable '{$key}' is not set. " .
                "Please check your .env file or Docker configuration."
            );
        }

        return $value;
    }

    private function loadEnvironment(): void
    {
        if (self::$envLoaded) {
            return;
        }

        // IMPORTANT: Only load .env if we don't have the required variables
        // This respects variables set by tests
        $requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
        $allVarsPresent = true;

        foreach ($requiredVars as $var) {
            if (!isset($_ENV[$var]) && !getenv($var)) {
                $allVarsPresent = false;
                break;
            }
        }

        // If we already have all variables, don't load .env
        if ($allVarsPresent) {
            self::$envLoaded = true;
            return;
        }

        // Find and load .env file
        $envPath = '/var/www';
        $envFile = $envPath . '/.env';

        if (!file_exists($envFile)) {
            self::$envLoaded = true;
            return;
        }

        try {
            // Use safeLoad() to NOT overwrite existing variables
            $dotenv = Dotenv::createMutable($envPath);
            $dotenv->safeLoad(); // safeLoad doesn't overwrite existing variables
            $dotenv->required($requiredVars);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to load environment configuration: ' . $e->getMessage()
            );
        }

        self::$envLoaded = true;
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
        self::$envLoaded = false;
    }

    public function isConnected(): bool
    {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // Prevent cloning of the instance
    private function __clone()
    {
    }

    // Prevent deserialization of the instance
    public function __wakeup()
    {
        throw new \Exception("Cannot deserialize singleton");
    }
}
