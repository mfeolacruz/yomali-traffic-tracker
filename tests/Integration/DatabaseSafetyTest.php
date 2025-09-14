<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration;

use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

/**
 * Critical safety test to ensure we never accidentally use production database in tests
 * 
 * @group integration
 * @group database
 * @group safety
 */
final class DatabaseSafetyTest extends IntegrationTestCase
{
    public function testWeAreUsingTestDatabaseAndNotProduction(): void
    {
        // Get the actual database name from the connection
        $pdo = $this->getPdo();
        $stmt = $pdo->query('SELECT DATABASE() as current_db');
        $result = $stmt->fetch();
        $actualDatabase = $result['current_db'];
        
        // CRITICAL: Must be test database
        $this->assertEquals('tracker_db_test', $actualDatabase, 
            "CRITICAL FAILURE: Tests are using database '{$actualDatabase}' instead of 'tracker_db_test'. " .
            "This could destroy production data!"
        );
        
        // Double check environment variables
        $envDatabase = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        $this->assertEquals('tracker_db_test', $envDatabase,
            "Environment variable DB_NAME is set to '{$envDatabase}' instead of 'tracker_db_test'"
        );
        
        // Verify APP_ENV is testing
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV');
        $this->assertEquals('testing', $appEnv,
            "APP_ENV should be 'testing' but is '{$appEnv}'"
        );
    }
    
    public function testTestDatabaseExistsAndIsAccessible(): void
    {
        // Verify we can access the test database and it has our tables
        $pdo = $this->getPdo();
        
        // Check if visits table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'visits'");
        $result = $stmt->fetch();
        
        $this->assertNotFalse($result, 
            "Test database 'tracker_db_test' should have 'visits' table. " .
            "Run database migrations for test environment."
        );
    }
    
    public function testWeCanSafelyTruncateTestTables(): void
    {
        // This should be safe since we're in test database
        $pdo = $this->getPdo();
        
        // Insert a test record
        $stmt = $pdo->prepare("INSERT INTO visits (ip_address, page_url, page_domain, page_path) VALUES (?, ?, ?, ?)");
        $stmt->execute(['127.0.0.1', 'https://test.com/safety', 'test.com', '/safety']);
        
        // Verify it exists
        $count = $pdo->query("SELECT COUNT(*) as count FROM visits")->fetch()['count'];
        $this->assertGreaterThan(0, $count, "Test record should be inserted");
        
        // Truncate table (this is the dangerous operation we're testing)
        $this->truncateTable('visits');
        
        // Verify it's empty
        $count = $pdo->query("SELECT COUNT(*) as count FROM visits")->fetch()['count'];
        $this->assertEquals(0, $count, "Table should be empty after truncate");
    }
}