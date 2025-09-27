-- Create offers table
CREATE TABLE offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data for testing
INSERT INTO offers (image_url, is_active) VALUES 
('http://127.0.0.1:8000/storage/offers/sample1.jpg', 1),
('http://127.0.0.1:8000/storage/offers/sample2.jpg', 1),
('http://127.0.0.1:8000/storage/offers/sample3.jpg', 0);
