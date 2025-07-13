-- Add missing columns to existing tables

-- Add is_featured column to products table
ALTER TABLE `products` ADD COLUMN `is_featured` tinyint(1) NOT NULL DEFAULT 0;

-- Add admin_price column to orders table
ALTER TABLE `orders` ADD COLUMN `admin_price` decimal(10,2) DEFAULT NULL;

-- Add address column to users table if not exists
ALTER TABLE `users` ADD COLUMN `address` text DEFAULT NULL;

-- Update colors table to ensure hex codes don't have # prefix
UPDATE `colors` SET `hex_code` = CONCAT('#', `hex_code`) WHERE `hex_code` NOT LIKE '#%';

-- Insert default admin user if not exists (password: admin123)
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `is_admin`) VALUES 
('admin', 'admin@volt3dge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insert default colors with # prefix
INSERT IGNORE INTO `colors` (`name`, `hex_code`) VALUES 
('White', '#FFFFFF'),
('Black', '#000000'),
('Red', '#FF0000'),
('Blue', '#0000FF'),
('Green', '#00FF00'),
('Yellow', '#FFFF00'),
('Orange', '#FFA500'),
('Purple', '#800080'),
('Pink', '#FFC0CB'),
('Gray', '#808080');

-- Insert default materials
INSERT IGNORE INTO `materials` (`name`, `description`) VALUES 
('PLA (Recommended)', 'Biodegradable thermoplastic, easy to print, beginner-friendly'),
('ABS', 'Strong and durable plastic, higher temperature resistance'),
('PETG', 'Chemical resistant, food safe, crystal clear'),
('TPU', 'Flexible rubber-like material'),
('Wood Fill', 'PLA with wood fibers, can be sanded and stained'),
('Metal Fill', 'PLA with metal particles for weight and appearance');

-- Insert default bulk discounts
INSERT IGNORE INTO `bulk_discounts` (`min_quantity`, `discount_percentage`) VALUES 
(5, 5.00),
(10, 10.00),
(20, 15.00),
(50, 20.00);