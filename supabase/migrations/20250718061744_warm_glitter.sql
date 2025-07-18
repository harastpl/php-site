-- Add STL file column to products table
ALTER TABLE `products` ADD COLUMN `stl_file` varchar(255) DEFAULT NULL;

-- Ensure admin_price column exists in orders table
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `admin_price` decimal(10,2) DEFAULT NULL;

-- Update existing custom orders to have null price initially
UPDATE `orders` SET `admin_price` = NULL, `total` = 0, `final_total` = 0 
WHERE `id` IN (
    SELECT DISTINCT `order_id` FROM `order_items` WHERE `custom_stl` IS NOT NULL
) AND `admin_price` IS NOT NULL;

-- Update order_items for custom orders to have 0 price initially
UPDATE `order_items` SET `price` = 0 
WHERE `custom_stl` IS NOT NULL AND `price` > 0;