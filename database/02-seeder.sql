-- Use main database for seed data
USE tracker_db;

-- Clear existing data
TRUNCATE TABLE visits;

-- Stored procedure to generate realistic test data
DELIMITER $$

DROP PROCEDURE IF EXISTS GenerateTestData$$

CREATE PROCEDURE GenerateTestData()
BEGIN
    DECLARE i INT DEFAULT 0;
    DECLARE j INT DEFAULT 0;
    DECLARE k INT DEFAULT 0;
    DECLARE random_ip VARCHAR(45);
    DECLARE random_date DATETIME;
    DECLARE domain_name VARCHAR(255);
    DECLARE page_path VARCHAR(1024);
    DECLARE full_url VARCHAR(2048);

    -- Insert data for multiple domains over the last 6 months
    WHILE i < 5 DO
        -- Select domain based on index
        SET domain_name = CASE i
            WHEN 0 THEN 'example.com'
            WHEN 1 THEN 'testsite.org'
            WHEN 2 THEN 'demo.app'
            WHEN 3 THEN 'myblog.net'
            WHEN 4 THEN 'shop.store'
        END;

        -- Generate visits for each domain
        SET j = 0;
        WHILE j < 500 DO
            -- Generate random IP (pool of 50 IPs for repeat visitors)
            SET random_ip = CONCAT(
                FLOOR(RAND() * 50 + 150), '.',
                FLOOR(RAND() * 256), '.',
                FLOOR(RAND() * 256), '.',
                FLOOR(RAND() * 50 + 1)
            );

            -- Random date within last 6 months
            SET random_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY);
            SET random_date = DATE_ADD(random_date, INTERVAL FLOOR(RAND() * 24) HOUR);

            -- Generate page paths based on domain
            SET page_path = CASE
                WHEN domain_name = 'shop.store' THEN
                    CASE FLOOR(RAND() * 8)
                        WHEN 0 THEN '/'
                        WHEN 1 THEN '/products'
                        WHEN 2 THEN '/products/electronics'
                        WHEN 3 THEN '/products/clothing'
                        WHEN 4 THEN '/cart'
                        WHEN 5 THEN '/checkout'
                        WHEN 6 THEN '/about'
                        ELSE '/contact'
                    END
                WHEN domain_name = 'myblog.net' THEN
                    CASE FLOOR(RAND() * 6)
                        WHEN 0 THEN '/'
                        WHEN 1 THEN '/blog'
                        WHEN 2 THEN '/blog/post-1'
                        WHEN 3 THEN '/blog/post-2'
                        WHEN 4 THEN '/blog/tech'
                        ELSE '/blog/lifestyle'
                    END
                ELSE
                    CASE FLOOR(RAND() * 5)
                        WHEN 0 THEN '/'
                        WHEN 1 THEN '/about'
                        WHEN 2 THEN '/services'
                        WHEN 3 THEN '/contact'
                        ELSE '/portfolio'
                    END
            END;

            SET full_url = CONCAT('https://', domain_name, page_path);

            INSERT INTO visits (ip_address, page_url, page_domain, page_path, created_at)
            VALUES (random_ip, full_url, domain_name, page_path, random_date);

            SET j = j + 1;
        END WHILE;

        SET i = i + 1;
    END WHILE;

    -- Heavy traffic on example.com homepage (last week)
    SET k = 0;
    WHILE k < 300 DO
        SET random_ip = CONCAT('192.168.1.', FLOOR(RAND() * 20 + 1));
        SET random_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY);

        INSERT INTO visits (ip_address, page_url, page_domain, page_path, created_at)
        VALUES (random_ip, 'https://example.com/', 'example.com', '/', random_date);

        SET k = k + 1;
    END WHILE;

    -- Repeat visitors scenario (same IPs visiting multiple pages)
    SET k = 0;
    WHILE k < 50 DO
        SET random_ip = CONCAT('10.0.0.', FLOOR(RAND() * 10 + 1));
        SET random_date = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY);

        INSERT INTO visits (ip_address, page_url, page_domain, page_path, created_at)
        VALUES
            (random_ip, 'https://shop.store/', 'shop.store', '/', random_date),
            (random_ip, 'https://shop.store/products', 'shop.store', '/products', DATE_ADD(random_date, INTERVAL 5 MINUTE)),
            (random_ip, 'https://shop.store/cart', 'shop.store', '/cart', DATE_ADD(random_date, INTERVAL 10 MINUTE));

        SET k = k + 1;
    END WHILE;

END$$

DELIMITER ;

-- Execute the procedure
CALL GenerateTestData();

-- Show summary
SELECT
    page_domain as Domain,
    COUNT(*) as Total_Visits,
    COUNT(DISTINCT ip_address) as Unique_Visitors,
    MIN(created_at) as First_Visit,
    MAX(created_at) as Last_Visit
FROM visits
GROUP BY page_domain
ORDER BY Total_Visits DESC;