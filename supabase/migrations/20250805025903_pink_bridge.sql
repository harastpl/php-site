/*
  # Add Product Color Features

  1. New Tables
    - Add enable_colors column to products table
    - Add product_colors junction table for many-to-many relationship
    - Add print_type column to order_items for FDM/SLA
    - Add shipping_total column to orders table
    - Add delivery_partner and tracking_id columns to orders table

  2. Security
    - No RLS changes needed as these are admin-managed features

  3. Changes
    - Products can now have color options enabled
    - Order items can specify print type (FDM/SLA)
    - Enhanced order tracking capabilities
*/

-- Add enable_colors column to products table
ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `enable_colors` tinyint(1) NOT NULL DEFAULT 0;

-- Create product_colors junction table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `product_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_color_unique` (`product_id`, `color_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add print_type column to order_items table
ALTER TABLE `order_items` ADD COLUMN IF NOT EXISTS `print_type` enum('FDM','SLA') NOT NULL DEFAULT 'FDM';

-- Add shipping_total column to orders table if not exists
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `shipping_total` decimal(10,2) DEFAULT 0;

-- Add delivery tracking columns to orders table if not exists
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delivery_partner` varchar(100) DEFAULT NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `tracking_id` varchar(100) DEFAULT NULL;

-- Add transaction_id column to orders table if not exists
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `transaction_id` varchar(255) DEFAULT NULL;

-- Add Resin material for SLA printing
INSERT IGNORE INTO `materials` (`name`, `description`) VALUES 
('Resin (SLA)', 'High-resolution resin for SLA 3D printing');