-- Create main database
CREATE DATABASE IF NOT EXISTS tracker_db
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

-- Create test database
CREATE DATABASE IF NOT EXISTS tracker_db_test
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

-- Grant permissions for test database
GRANT ALL PRIVILEGES ON tracker_db_test.* TO 'tracker_user'@'%';
FLUSH PRIVILEGES;

-- Use main database
USE tracker_db;

-- Create visits table
CREATE TABLE IF NOT EXISTS visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    page_url VARCHAR(2048) NOT NULL COMMENT 'Full page URL',
    page_domain VARCHAR(255) NOT NULL COMMENT 'Domain for faster filtering',
    page_path VARCHAR(1024) NOT NULL COMMENT 'URL path for grouping',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain_created_ip (page_domain, created_at, ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Create same structure in test database
USE tracker_db_test;

CREATE TABLE IF NOT EXISTS visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    page_url VARCHAR(2048) NOT NULL COMMENT 'Full page URL',
    page_domain VARCHAR(255) NOT NULL COMMENT 'Domain for faster filtering',
    page_path VARCHAR(1024) NOT NULL COMMENT 'URL path for grouping',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain_created_ip (page_domain, created_at, ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Switch back to main database
USE tracker_db;