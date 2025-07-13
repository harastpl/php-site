-- Add cart table for persistent cart storage (optional)
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add admin_price column to orders table if not exists
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `admin_price` decimal(10,2) DEFAULT NULL;

-- Update address field to support JSON format
ALTER TABLE `users` MODIFY COLUMN `address` text DEFAULT NULL;

-- Add is_featured column to products table if not exists
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `is_featured` tinyint(1) NOT NULL DEFAULT 0;