-- Update database to match current structure and fix issues

-- First, let's add missing columns to existing tables
ALTER TABLE `colors` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();
ALTER TABLE `materials` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp();

-- Add missing columns to orders table
ALTER TABLE `orders` ADD COLUMN `discount_amount` decimal(10,2) DEFAULT 0;
ALTER TABLE `orders` ADD COLUMN `final_total` decimal(10,2) NOT NULL DEFAULT 0;
ALTER TABLE `orders` ADD COLUMN `payment_status` enum('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending';
ALTER TABLE `orders` ADD COLUMN `payment_id` varchar(255) DEFAULT NULL;

-- Add missing columns to order_items table
ALTER TABLE `order_items` ADD COLUMN `infill_percentage` int(11) DEFAULT 20;
ALTER TABLE `order_items` ADD COLUMN `layer_height` decimal(3,2) DEFAULT 0.20;
ALTER TABLE `order_items` ADD COLUMN `support_needed` tinyint(1) DEFAULT 0;
ALTER TABLE `order_items` ADD COLUMN `color_id` int(11) DEFAULT NULL;
ALTER TABLE `order_items` ADD COLUMN `material_id` int(11) DEFAULT NULL;

-- Add missing columns to users table
ALTER TABLE `users` ADD COLUMN `is_admin` tinyint(1) NOT NULL DEFAULT 0;

-- Add missing columns to products table
ALTER TABLE `products` ADD COLUMN `stock` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `products` ADD COLUMN `low_stock_threshold` int(11) NOT NULL DEFAULT 10;

-- Create bulk_discounts table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bulk_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_quantity` int(11) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default bulk discounts
INSERT IGNORE INTO `bulk_discounts` (`min_quantity`, `discount_percentage`) VALUES 
(5, 5.00),
(10, 10.00),
(20, 15.00),
(50, 20.00);

-- Insert default colors with proper hex codes
INSERT IGNORE INTO `colors` (`name`, `hex_code`) VALUES 
('White', 'FFFFFF'),
('Black', '000000'),
('Red', 'FF0000'),
('Blue', '0000FF'),
('Green', '00FF00'),
('Yellow', 'FFFF00'),
('Orange', 'FFA500'),
('Purple', '800080');

-- Insert default materials
INSERT IGNORE INTO `materials` (`name`, `description`) VALUES 
('PLA (Recommended)', 'Biodegradable thermoplastic, easy to print, beginner-friendly'),
('ABS', 'Strong and durable plastic, higher temperature resistance'),
('PETG', 'Chemical resistant, food safe, crystal clear'),
('TPU', 'Flexible rubber-like material'),
('Wood Fill', 'PLA with wood fibers, can be sanded and stained'),
('Metal Fill', 'PLA with metal particles for weight and appearance');

-- Create admin user if not exists
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `is_admin`) VALUES 
('admin', 'admin@volt3dge.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Update existing orders to have proper final_total
UPDATE `orders` SET `final_total` = `total` WHERE `final_total` = 0;