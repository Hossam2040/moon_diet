-- Connect to moon_diet database
USE moon_diet;

-- Drop table if exists
DROP TABLE IF EXISTS offers;

-- Create offers table
CREATE TABLE offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Verify table creation
SHOW TABLES LIKE 'offers';

-- Show table structure
DESCRIBE offers;

-- Insert test data
INSERT INTO offers (image_url, is_active) VALUES 
('http://127.0.0.1:8000/storage/offers/test1.jpg', 1),
('http://127.0.0.1:8000/storage/offers/test2.jpg', 1);

-- Verify data
SELECT * FROM offers;
