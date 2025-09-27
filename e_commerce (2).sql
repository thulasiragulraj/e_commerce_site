-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2025 at 12:14 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_commerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password`, `address`, `created_at`, `status`) VALUES
(1, 'ragulraj', 'ragulraj@example.com', '8825920268', '$2y$10$dkZucbPJbewz7uDGRJqyIufc/kbAR6ze5a1WruPp84/ubHHbGxdiG', '456 Alagappapuram 18th Street', '2025-09-24 12:01:23', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `product_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `item_name`, `product_count`) VALUES
(1, 'Electronics', 6),
(2, 'Fashion', 5),
(3, 'Furniture', 4);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `payment_status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `product_id`, `quantity`, `price`, `total_price`, `order_date`, `status`, `payment_status`) VALUES
(1, 1, 'P0001', 2, 35000.00, 70000.00, '2025-09-24 12:12:44', 'pending', 'pending'),
(2, 1, 'P0014', 2, 200.00, 400.00, '2025-09-24 12:35:02', 'pending', 'pending'),
(3, 1, 'P0013', 1, 200.00, 200.00, '2025-09-25 11:01:33', 'pending', 'pending'),
(4, 1, 'P0012', 1, 2000.00, 2000.00, '2025-09-25 11:10:20', 'pending', 'pending'),
(5, 1, 'P0010', 1, 35000.00, 35000.00, '2025-09-25 11:47:58', 'pending', 'pending');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `before_orders_insert` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    -- If total_price is NULL or 0, calculate it
    IF NEW.total_price IS NULL OR NEW.total_price = 0 THEN
        SET NEW.total_price = NEW.price * NEW.quantity;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `payment_gateway_order_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'INR',
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method_id`, `payment_gateway_order_id`, `amount`, `currency`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 4, 3, NULL, 2000.00, 'INR', 'completed', '2025-09-25 12:00:04', '2025-09-25 12:00:04'),
(2, 2, 2, NULL, 400.00, 'INR', 'pending', '2025-09-25 12:06:00', '2025-09-25 12:06:00');

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `before_payment_insert` BEFORE INSERT ON `payments` FOR EACH ROW BEGIN
    DECLARE order_total DECIMAL(10,2);
    
    SELECT total_price INTO order_total
    FROM orders
    WHERE id = NEW.order_id;
    
    SET NEW.amount = order_total;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `type`, `status`, `details`, `created_at`, `updated_at`) VALUES
(1, 'PayPal', 'online', 'active', NULL, '2025-09-24 14:40:20', '2025-09-24 14:40:20'),
(2, 'UPI', 'online', 'active', NULL, '2025-09-24 14:40:20', '2025-09-24 14:40:20'),
(3, 'Cash on delivery', 'offline', 'active', NULL, '2025-09-24 14:40:20', '2025-09-24 14:40:20'),
(4, 'Credit Card', 'online', 'active', 'Visa / Master / Amex supported', '2025-09-25 10:40:07', '2025-09-25 10:40:07'),
(5, 'Debit Card', 'online', 'active', 'Supports all major debit cards', '2025-09-25 10:40:07', '2025-09-25 10:40:07'),
(6, 'Net Banking', 'online', 'active', 'Internet banking for all major banks', '2025-09-25 10:40:07', '2025-09-25 10:40:07'),
(7, 'Wallet', 'online', 'active', 'Paytm / PhonePe / Freecharge wallets', '2025-09-25 10:40:07', '2025-09-25 10:40:07');

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `description`, `price`, `duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Basic', 'Access to standard features', 199.00, 30, 1, '2025-09-25 04:43:17', '2025-09-25 04:43:17'),
(2, 'Pro', 'Includes premium support and extra features', 499.00, 30, 1, '2025-09-25 04:43:17', '2025-09-25 04:43:17'),
(3, 'Premium', 'All features + unlimited access', 4999.00, 365, 1, '2025-09-25 04:43:17', '2025-09-25 04:43:17'),
(4, 'Free Trial', '7-day free trial with limited access', 0.00, 7, 1, '2025-09-25 04:50:21', '2025-09-25 04:50:21');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_id` varchar(20) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_id`, `item_id`, `product_name`, `price`, `stock_quantity`) VALUES
(1, 'P0001', 1, 'Samsung TV', 35000.00, 2),
(2, 'P0002', 1, 'Dell Laptop', 55000.00, 1),
(3, 'P0003', 1, 'iPhone 15', 80000.00, 1),
(4, 'P0004', 2, 'Levis Jeans', 2500.00, 1),
(5, 'P0005', 2, 'Nike Shoes', 4500.00, 1),
(6, 'P0006', 2, 'RayBan Sunglasses', 7500.00, 1),
(7, 'P0007', 3, 'Wooden Sofa', 30000.00, 1),
(8, 'P0008', 3, 'Dining Table', 20000.00, 1),
(9, 'P0009', 3, 'Office Chair', 6000.00, 1),
(10, 'P0010', 1, 'Samsung phone', 35000.00, 0),
(11, 'P0011', 1, 'Sample phone', 2000.00, 1),
(12, 'P0012', 1, 'Sample mobile', 2000.00, 1),
(13, 'P0013', 2, 'Sample pant', 200.00, 0),
(14, 'P0014', 3, 'Sample chair', 200.00, 1),
(15, 'P0015', 2, 'tone jene', 3500.00, 2);

--
-- Triggers `products`
--
DELIMITER $$
CREATE TRIGGER `after_products_delete` AFTER DELETE ON `products` FOR EACH ROW BEGIN
    UPDATE items
    SET product_count = (
        SELECT COUNT(*)
        FROM products
        WHERE item_id = OLD.item_id
    )
    WHERE id = OLD.item_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_products_insert` AFTER INSERT ON `products` FOR EACH ROW BEGIN
    UPDATE items
    SET product_count = (
        SELECT COUNT(*)
        FROM products
        WHERE item_id = NEW.item_id
    )
    WHERE id = NEW.item_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_products_update` AFTER UPDATE ON `products` FOR EACH ROW BEGIN
    -- Recalculate for old item_id
    UPDATE items
    SET product_count = (
        SELECT COUNT(*)
        FROM products
        WHERE item_id = OLD.item_id
    )
    WHERE id = OLD.item_id;

    -- Recalculate for new item_id
    UPDATE items
    SET product_count = (
        SELECT COUNT(*)
        FROM products
        WHERE item_id = NEW.item_id
    )
    WHERE id = NEW.item_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_insert_products` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    DECLARE next_id INT;

    -- Find max numeric part of product_id
    SELECT IFNULL(MAX(CAST(SUBSTRING(product_id, 2) AS UNSIGNED)), 0) + 1
    INTO next_id
    FROM products;

    -- Generate new product_id in format P0001, P0002...
    SET NEW.product_id = CONCAT('P', LPAD(next_id, 4, '0'));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_products_insert` BEFORE INSERT ON `products` FOR EACH ROW BEGIN
    DECLARE max_id INT;
    DECLARE new_id VARCHAR(10);

    SELECT IFNULL(MAX(CAST(SUBSTRING(product_id,2) AS UNSIGNED)),0)
    INTO max_id
    FROM products;

    SET new_id = CONCAT('P', LPAD(max_id + 1, 4, '0'));

    SET NEW.product_id = new_id;
    -- If you had something like SET NEW.quantity, change it to stock_quantity
    IF NEW.stock_quantity IS NULL THEN
        SET NEW.stock_quantity = 0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `customers_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('active','expired','cancelled','pending') DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `auto_renew` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `customers_id`, `plan_id`, `start_date`, `end_date`, `status`, `transaction_id`, `amount`, `auto_renew`, `created_at`, `updated_at`, `payment_method_id`) VALUES
(1, 1, 1, '2025-09-25 10:18:49', '2025-10-25 10:18:49', 'active', 'TXN123456789', 199.00, 1, '2025-09-25 04:48:49', '2025-09-25 05:27:21', 3),
(2, 1, 2, '2025-09-25 10:28:12', '2025-10-25 10:28:12', 'active', 'UPI987654321', 499.00, 0, '2025-09-25 04:58:12', '2025-09-25 04:58:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users1`
--

CREATE TABLE `users1` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users1`
--

INSERT INTO `users1` (`id`, `name`, `phone`, `email`, `password`, `role`, `qr_code`, `created_at`, `updated_at`) VALUES
(1, 'kabil', '9876543210', 'kabil@example.com', '$2y$10$08cf0DKNv.1qMXg27SrQu.VeP4V9XgePfg6CoY5FP1aq0boQ8oGXC', 'admin', NULL, '2025-09-24 05:17:41', '2025-09-24 05:17:41'),
(3, 'sriram', '9866543210', 'sriram@example.com', '$2y$10$Kn9O/nxS8LIryyEczRX4FuDhwtg.xCHwwyHfbvxgzWwqf7qyv46iW', 'admin', NULL, '2025-09-24 05:21:12', '2025-09-24 05:21:12'),
(4, 'siva', '9666543210', 'siva@example.com', '$2y$10$zSDQQshU6aAFYNLOM0QDeebucyZ/MJfIPVtZRUBkdvaN8Qzu2.E7.', 'admin', 'siva-4.png', '2025-09-24 05:22:56', '2025-09-24 05:22:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer` (`customer_id`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `payment_method_id` (`payment_method_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customers_id` (`customers_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `users1`
--
ALTER TABLE `users1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users1`
--
ALTER TABLE `users1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
