-- phpMyAdmin SQL Dump
-- version 4.0.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 18, 2026 at 11:59 AM
-- Server version: 5.6.12-log
-- PHP Version: 5.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `techpulse369`
--
CREATE DATABASE IF NOT EXISTS `techpulse369` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `techpulse369`;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(250) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=56 ;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `user_id`, `action`, `table_affected`, `record_id`, `created_at`) VALUES
(1, 2, 'User signed in', 'users', 2, '2026-07-10 16:11:13'),
(2, 7, 'User signed in', 'users', 7, '2026-07-10 16:14:18'),
(3, 1, 'User signed in', 'users', 1, '2026-07-10 16:16:50'),
(4, 1, 'Added spare stock: zc (Qty: 5)', 'stock', 13, '2026-07-10 16:18:31'),
(5, 1, 'Updated gadget details for ID: 7', 'gadgets', 7, '2026-07-10 16:22:14'),
(6, 7, 'User signed in', 'users', 7, '2026-07-10 16:23:16'),
(7, 1, 'User signed in', 'users', 1, '2026-07-10 16:26:22'),
(8, 7, 'User signed in', 'users', 7, '2026-07-10 16:38:39'),
(9, 1, 'User signed in', 'users', 1, '2026-07-10 16:39:39'),
(10, 1, 'User signed in', 'users', 1, '2026-07-10 18:09:01'),
(11, 7, 'User signed in', 'users', 7, '2026-07-10 18:10:30'),
(12, 7, 'User signed in', 'users', 7, '2026-07-10 18:13:30'),
(13, 1, 'User signed in', 'users', 1, '2026-07-10 18:21:18'),
(14, 1, 'Deleted inventory stock item ID: 13', 'stock', 13, '2026-07-10 18:33:12'),
(15, 1, 'Registered vendor: Apple Inn Pvt. Ltd.', 'vendors', 6, '2026-07-10 18:37:14'),
(16, 1, 'Updated stock item ID: 6', 'stock', 6, '2026-07-10 18:38:24'),
(17, 1, 'User signed in', 'users', 1, '2026-07-10 20:46:08'),
(18, 7, 'User signed in', 'users', 7, '2026-07-10 20:46:23'),
(19, 7, 'User signed in', 'users', 7, '2026-07-11 16:20:22'),
(20, 1, 'User signed in', 'users', 1, '2026-07-11 16:24:24'),
(21, 7, 'User signed in', 'users', 7, '2026-07-11 16:31:21'),
(22, 1, 'User signed in', 'users', 1, '2026-07-11 16:31:49'),
(23, 1, 'User signed in', 'users', 1, '2026-07-11 16:32:56'),
(24, 7, 'User signed in', 'users', 7, '2026-07-11 16:51:06'),
(25, 1, 'Updated ticket ID 1 status: in-progress -> testing', 'issues', 1, '2026-07-13 18:11:09'),
(26, 1, 'Updated ticket ID 4 status: testing -> resolved', 'issues', 4, '2026-07-13 18:15:28'),
(27, 3, 'Updated ticket ID 3 status: assigned -> testing', 'issues', 3, '2026-07-13 18:17:55'),
(28, 1, 'Updated gadget details for ID: 19', 'gadgets', 19, '2026-07-13 18:30:40'),
(29, 1, 'Updated gadget details for ID: 16', 'gadgets', 16, '2026-07-14 14:11:47'),
(30, 10, 'Logged support ticket ID: 7', 'issues', 7, '2026-07-14 14:13:07'),
(31, 1, 'Assigned ticket ID 7 to tech ID: 3', 'issues', 7, '2026-07-14 14:13:23'),
(32, 1, 'Updated ticket ID 7 status: assigned -> in-progress', 'issues', 7, '2026-07-14 14:14:21'),
(33, 1, 'Updated gadget details for ID: 9', 'gadgets', 9, '2026-07-14 18:03:22'),
(34, 1, 'Updated gadget details for ID: 19', 'gadgets', 19, '2026-07-24 19:50:22'),
(35, 1, 'Updated gadget details for ID: 18', 'gadgets', 18, '2026-07-24 20:52:25'),
(36, 1, 'Updated gadget details for ID: 17', 'gadgets', 17, '2026-07-24 21:13:40'),
(37, 7, 'Logged support ticket ID: 8', 'issues', 8, '2026-07-31 18:56:05'),
(38, 1, 'Assigned ticket ID 2 to tech ID: 6', 'issues', 2, '2026-07-31 23:38:19'),
(39, 1, 'Assigned ticket ID 8 to tech ID: 3', 'issues', 8, '2026-07-31 23:38:46'),
(40, 1, 'Updated ticket ID 1 status: testing -> resolved', 'issues', 1, '2026-08-01 00:19:16'),
(41, 1, 'Assigned ticket ID 3 to tech ID: 6', 'issues', 3, '2026-08-01 00:19:42'),
(42, 1, 'Updated stock item ID: 2', 'stock', 2, '2026-08-02 22:15:06'),
(43, 1, 'Updated stock item ID: 3', 'stock', 3, '2026-08-02 22:15:22'),
(44, 1, 'Added new gadget: Apple MacBook Air M4 (TP-2026-0001)', 'gadgets', 21, '2026-08-02 22:24:20'),
(45, 1, 'Updated gadget details for ID: 21', 'gadgets', 21, '2026-08-02 22:27:23'),
(46, 1, 'Updated ticket ID 3 status: testing -> resolved', 'issues', 3, '2026-08-06 18:58:25'),
(47, 1, 'Assigned ticket ID 7 to tech ID: 1', 'issues', 7, '2026-08-06 18:59:00'),
(48, 1, 'Assigned ticket ID 7 to tech ID: 6', 'issues', 7, '2026-08-06 18:59:06'),
(49, 1, 'Assigned ticket ID 7 to tech ID: 3', 'issues', 7, '2026-08-06 18:59:17'),
(50, 1, 'Assigned ticket ID 7 to tech ID: 6', 'issues', 7, '2026-08-06 18:59:25'),
(51, 1, 'Updated ticket ID 2 status: assigned -> testing', 'issues', 2, '2026-08-11 20:32:10'),
(52, 1, 'Updated gadget details for ID: 15', 'gadgets', 15, '2026-09-28 21:32:51'),
(53, 1, 'Updated gadget details for ID: 14', 'gadgets', 14, '2026-09-28 21:35:43'),
(54, 1, 'Updated gadget details for ID: 21', 'gadgets', 21, '2026-09-28 21:37:18'),
(55, 1, 'Updated gadget details for ID: 21', 'gadgets', 21, '2026-09-28 21:40:00');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `created_at`) VALUES
(1, 'Meet AJ', 'meetaj1708@yahoo.com', 'Bug Report', 'Website looks coolest', '2026-07-10 18:21:04');

-- --------------------------------------------------------

--
-- Table structure for table `depreciation_log`
--

CREATE TABLE IF NOT EXISTS `depreciation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `year_number` int(11) NOT NULL,
  `opening_value` decimal(10,2) NOT NULL,
  `depreciation_amount` decimal(10,2) NOT NULL,
  `closing_value` decimal(10,2) NOT NULL,
  `rate_percent` decimal(5,2) NOT NULL,
  `calculated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT '5',
  `title` varchar(120) DEFAULT NULL,
  `comment` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `rating`, `title`, `comment`, `created_at`) VALUES
(1, 9, 5, 'Great Workflow for Issue Tracking', 'The support ticket workflow is exactly what we needed. It enables seamless assignment of tickets to the appropriate technicians, provides clear visibility into ticket status throughout the resolution process, and automatically records timestamps for every action taken.', '2026-08-02 20:35:05');

-- --------------------------------------------------------

--
-- Table structure for table `gadgets`
--

CREATE TABLE IF NOT EXISTS `gadgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_tag` varchar(25) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Laptop',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT '0.00',
  `useful_life_yrs` int(11) DEFAULT '5',
  `depreciation_rate` decimal(5,2) DEFAULT '20.00',
  `vendor_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('active','in-repair','retired') NOT NULL DEFAULT 'active',
  `description` text,
  `image` varchar(200) DEFAULT NULL,
  `co2_manufacture` decimal(6,2) NOT NULL DEFAULT '220.00',
  `co2_repair` decimal(5,2) NOT NULL DEFAULT '12.50',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_tag` (`asset_tag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=22 ;

--
-- Dumping data for table `gadgets`
--

INSERT INTO `gadgets` (`id`, `asset_tag`, `name`, `category`, `brand`, `model`, `serial_number`, `purchase_date`, `purchase_price`, `useful_life_yrs`, `depreciation_rate`, `vendor_id`, `assigned_to`, `status`, `description`, `image`, `co2_manufacture`, `co2_repair`, `created_at`) VALUES
(1, 'TP-2023-0001', 'Dell Latitude 5520', 'Laptop', 'Dell', 'Latitude 5520', 'DLL5520XA001', '2023-01-10', '72000.00', 5, '20.00', 1, 2, 'active', '15.6 inch, Core i5, 8GB RAM, 256GB SSD', NULL, '320.00', '14.50', '2026-07-10 16:10:35'),
(2, 'TP-2023-0002', 'HP EliteBook 840', 'Laptop', 'HP', 'EliteBook 840', 'HPE840YB002', '2023-03-15', '85000.00', 5, '20.00', 2, 4, 'active', '14 inch, Core i7, 16GB RAM, 512GB SSD', NULL, '300.00', '14.50', '2026-07-10 16:10:35'),
(3, 'TP-2023-0003', 'Dell Inspiron 15', 'Laptop', 'Dell', 'Inspiron 3511', 'DLL3511XC003', '2023-02-20', '54000.00', 5, '20.00', 1, 5, 'active', '15.6 inch, Core i5, 8GB RAM, 512GB SSD', NULL, '280.00', '14.50', '2026-07-10 16:10:35'),
(4, 'TP-2022-0004', 'Lenovo ThinkPad E14', 'Laptop', 'Lenovo', 'ThinkPad E14', 'LNV14E4XD004', '2022-06-12', '68000.00', 5, '20.00', 3, 2, 'active', '14 inch, Ryzen 5, 8GB RAM, 512GB SSD', NULL, '290.00', '14.50', '2026-07-10 16:10:35'),
(5, 'TP-2022-0005', 'HP ProBook 450', 'Laptop', 'HP', 'ProBook 450 G9', 'HPP450GE005', '2022-09-08', '62000.00', 5, '20.00', 2, NULL, 'active', '15.6 inch, Core i5, 8GB RAM, 256GB SSD', NULL, '295.00', '14.50', '2026-07-10 16:10:35'),
(6, 'TP-2024-0006', 'Samsung S23 Ultra', 'Mobile', 'Samsung', 'Galaxy S23 Ultra', 'SAMS23UF006', '2024-01-22', '124999.00', 3, '33.33', 4, 4, 'active', '200MP, 5000mAh, Snapdragon 8 Gen 2', NULL, '75.00', '8.00', '2026-07-10 16:10:35'),
(7, 'TP-2024-0007', 'iPhone 15 Pro', 'Mobile', 'Apple', 'iPhone 15 Pro', 'APPL15PRG007', '2024-02-14', '134900.00', 3, '33.33', 4, 7, 'active', 'A17 Pro, 48MP, 3274mAh, Titanium', NULL, '68.00', '8.00', '2026-07-10 16:10:35'),
(8, 'TP-2023-0008', 'OnePlus 11', 'Mobile', 'OnePlus', '11 5G', 'OP115GH008', '2023-11-18', '56999.00', 3, '33.33', 4, 5, 'active', 'Snapdragon 8 Gen 2, 50MP, 5000mAh', NULL, '70.00', '8.00', '2026-07-10 16:10:35'),
(9, 'TP-2022-0009', 'Dell 24 Monitor', 'Monitor', 'Dell', 'P2422H', 'DLLP24I009', '2022-04-05', '18500.00', 5, '20.00', 1, 11, 'active', '24 inch FHD IPS, HDMI+VGA', NULL, '150.00', '10.00', '2026-07-10 16:10:35'),
(10, 'TP-2022-0010', 'HP 22 Monitor', 'Monitor', 'HP', 'V22v G5', 'HPV22VJ010', '2022-04-05', '14500.00', 5, '20.00', 2, NULL, 'active', '21.5 inch FHD, HDMI', NULL, '130.00', '10.00', '2026-07-10 16:10:35'),
(11, 'TP-2023-0011', 'HP LaserJet Pro', 'Printer', 'HP', 'M404dn', 'HPM404K011', '2023-05-20', '32000.00', 5, '20.00', 2, NULL, 'active', 'Monochrome, 38ppm, Duplex', NULL, '200.00', '15.00', '2026-07-10 16:10:35'),
(12, 'TP-2021-0012', 'Canon PIXMA G2010', 'Printer', 'Canon', 'PIXMA G2010', 'CNPXG2L012', '2021-07-12', '12000.00', 5, '20.00', 4, NULL, 'retired', 'Colour inkjet, 4800dpi', NULL, '180.00', '15.00', '2026-07-10 16:10:35'),
(13, 'TP-2024-0013', 'Dell Latitude 7420', 'Laptop', 'Dell', 'Latitude 7420', 'DLL7420M013', '2024-03-01', '92000.00', 5, '20.00', 1, 2, 'active', '14 inch, Core i7, 16GB RAM, 512GB SSD', NULL, '310.00', '14.50', '2026-07-10 16:10:35'),
(14, 'TP-2023-0014', 'Logitech MX Keys', 'Accessory', 'Logitech', 'MX Keys', 'LGTMXKN014', '2023-08-15', '8999.00', 3, '33.33', 4, 4, 'active', 'Wireless keyboard, multi-device', 'img_1790611543_6725.jpg', '15.00', '3.00', '2026-07-10 16:10:35'),
(15, 'TP-2023-0015', 'Logitech MX Master', 'Accessory', 'Logitech', 'MX Master 3S', 'LGTMXMO015', '2023-08-15', '7499.00', 3, '33.33', 4, 5, 'active', 'Wireless mouse, 8000 DPI', 'img_1790611371_5513.jpg', '12.00', '3.00', '2026-07-10 16:10:35'),
(16, 'TP-2022-0016', 'Cisco IP Phone', 'Accessory', 'Cisco', '7942G', 'CSC7942P016', '2022-11-10', '9800.00', 5, '20.00', 4, 10, 'active', 'VoIP phone, 2-line', NULL, '40.00', '5.00', '2026-07-10 16:10:35'),
(17, 'TP-2024-0017', 'Apple iPad 10th', 'Tablet', 'Apple', 'iPad 10th Gen', 'APPLIPDQ017', '2024-01-10', '44900.00', 4, '25.00', 4, 2, 'active', '10.9 inch, A14 Bionic, 64GB WiFi', 'img_1784907820_4131.jpg', '95.00', '9.00', '2026-07-10 16:10:35'),
(18, 'TP-2023-0018', 'Samsung Tab S9', 'Tablet', 'Samsung', 'Galaxy Tab S9', 'SAMTS9R018', '2023-12-05', '74999.00', 4, '25.00', 4, 5, 'active', '11 inch AMOLED, Snapdragon 8 Gen 2', 'img_1784906545_2819.jpg', '105.00', '9.00', '2026-07-10 16:10:35'),
(19, 'TP-2021-0019', 'Dell OptiPlex 3080', 'Desktop', 'Dell', 'OptiPlex 3080', 'DLLO3080S019', '2021-03-15', '45000.00', 5, '20.00', 1, 7, 'active', 'Core i5, 8GB RAM, 1TB HDD', 'img_1784902822_3500.jpg', '450.00', '20.00', '2026-07-10 16:10:35'),
(20, 'TP-2020-0020', 'HP EliteDesk 800', 'Desktop', 'HP', 'EliteDesk 800', 'HPE800DT020', '2020-08-20', '52000.00', 5, '20.00', 2, NULL, 'retired', 'Core i7, 16GB RAM, 256GB SSD — End of life', NULL, '430.00', '20.00', '2026-07-10 16:10:35'),
(21, 'TP-2026-0001', 'Apple MacBook Air M4', 'Laptop', 'Apple', '195949838064', 'MW123HN/A', '2025-08-17', '99369.00', 7, '15.36', 6, 7, 'active', 'Apple M4 chip with 10-core CPU and 8-core GPU', 'img_1790611800_5606.jpg', '250.00', '12.50', '2026-08-02 22:24:20');

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE IF NOT EXISTS `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `raised_by` int(11) NOT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('raised','assigned','in-progress','testing','resolved') NOT NULL DEFAULT 'raised',
  `raised_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `gadget_id`, `raised_by`, `assigned_to`, `title`, `description`, `priority`, `status`, `raised_at`, `resolved_at`) VALUES
(1, 3, 2, 3, 'Laptop not turning on', 'Dell Inspiron 3511 completely dead. Tried charging overnight — no response. Power LED also not lighting up.', 'critical', 'resolved', '2025-06-28 09:15:00', '2026-08-01 00:19:16'),
(2, 1, 2, 6, 'Keyboard keys sticky', 'Several keys on the keyboard are sticking and not responding properly. Spilled water last week.', 'medium', 'testing', '2025-07-01 11:30:00', NULL),
(3, 6, 4, 6, 'Phone overheating', 'Samsung S23 Ultra gets very hot while on calls longer than 10 minutes. Battery draining fast.', 'high', 'resolved', '2025-06-30 14:20:00', '2026-08-06 18:58:25'),
(4, 4, 2, 3, 'Slow performance after update', 'ThinkPad E14 became very slow after Windows update. Takes 10 mins to boot. Also fan noise has increased.', 'medium', 'resolved', '2025-06-20 10:00:00', '2026-07-13 18:15:28'),
(5, 11, 1, 3, 'Printer paper jam frequent', 'HP LaserJet Pro M404dn jamming on every 3rd print. Paper comes out wrinkled from back tray.', 'low', 'resolved', '2025-06-15 09:00:00', '2025-06-18 16:45:00'),
(6, 2, 4, 3, 'Screen flickering', 'HP EliteBook 840 screen flickers randomly, especially on battery mode. Happens 3-4 times per hour.', 'high', 'resolved', '2025-06-10 14:00:00', '2025-06-13 17:00:00'),
(7, 16, 10, 6, 'glitch', 'not working', 'low', 'in-progress', '2026-07-14 14:13:07', NULL),
(8, 7, 7, 3, 'Volume button not working', 'Not that much serious !', 'low', 'assigned', '2026-07-31 18:56:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `issue_updates`
--

CREATE TABLE IF NOT EXISTS `issue_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `note` text,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `issue_updates`
--

INSERT INTO `issue_updates` (`id`, `issue_id`, `updated_by`, `old_status`, `new_status`, `note`, `updated_at`) VALUES
(1, 3, 2, NULL, 'raised', 'Reported: laptop completely unresponsive.', '2025-06-28 09:15:00'),
(2, 3, 1, 'raised', 'assigned', 'Assigned to Suresh. Priority: Critical. Pick up today.', '2025-06-28 10:00:00'),
(3, 3, 3, 'assigned', 'in-progress', 'Collected laptop. Diagnosing power supply circuit.', '2025-06-28 14:30:00'),
(4, 4, 2, NULL, 'raised', 'Reported slow boot and fan noise after update.', '2025-06-20 10:00:00'),
(5, 4, 1, 'raised', 'assigned', 'Assigned to Suresh.', '2025-06-20 10:45:00'),
(6, 4, 3, 'assigned', 'in-progress', 'Rolling back Windows update, running disk cleanup.', '2025-06-21 09:00:00'),
(7, 4, 3, 'in-progress', 'testing', 'Rolled back update. Boot time now 90 seconds. Testing.', '2025-06-25 16:00:00'),
(8, 5, 1, NULL, 'raised', 'Frequent paper jams reported by office staff.', '2025-06-15 09:00:00'),
(9, 5, 1, 'raised', 'assigned', 'Assigned to Suresh.', '2025-06-15 09:30:00'),
(10, 5, 3, 'assigned', 'in-progress', 'Cleaned paper path and rollers. Replaced pickup roller.', '2025-06-17 11:00:00'),
(11, 5, 3, 'in-progress', 'resolved', 'Paper jams fixed. Test printed 50 pages — no issues.', '2025-06-18 16:45:00'),
(12, 6, 4, NULL, 'raised', 'Screen flickers randomly on battery. Very distracting.', '2025-06-10 14:00:00'),
(13, 6, 1, 'raised', 'assigned', 'Assigned to Suresh.', '2025-06-10 14:30:00'),
(14, 6, 3, 'assigned', 'in-progress', 'Updated display drivers. Checking power settings.', '2025-06-11 10:00:00'),
(15, 6, 3, 'in-progress', 'resolved', 'Updated BIOS and GPU driver. Flickering resolved.', '2025-06-13 17:00:00'),
(16, 1, 1, 'in-progress', 'testing', 'Now went for testing !', '2026-07-13 18:11:09'),
(17, 4, 1, 'testing', 'resolved', 'Working properly now.', '2026-07-13 18:15:28'),
(18, 3, 3, 'assigned', 'testing', 'Working on testing phase.', '2026-07-13 18:17:55'),
(19, 7, 10, NULL, 'raised', 'Issue logged by employee.', '2026-07-14 14:13:07'),
(20, 7, 1, 'raised', 'assigned', 'Ticket assigned to technician ID: 3.', '2026-07-14 14:13:23'),
(21, 7, 1, 'assigned', 'in-progress', 'working on it', '2026-07-14 14:14:21'),
(22, 8, 7, NULL, 'raised', 'Issue logged by employee.', '2026-07-31 18:56:05'),
(23, 2, 1, 'raised', 'assigned', 'Ticket assigned to technician ID: 6.', '2026-07-31 23:38:19'),
(24, 8, 1, 'raised', 'assigned', 'Ticket assigned to technician ID: 3.', '2026-07-31 23:38:46'),
(25, 1, 1, 'testing', 'resolved', 'Working all good now.', '2026-08-01 00:19:16'),
(26, 3, 1, 'testing', 'testing', 'Ticket assigned to technician ID: 6.', '2026-08-01 00:19:42'),
(27, 3, 1, 'testing', 'resolved', 'working now ! fixed.', '2026-08-06 18:58:25'),
(28, 7, 1, 'in-progress', 'in-progress', 'Ticket assigned to technician ID: 1.', '2026-08-06 18:59:00'),
(29, 7, 1, 'in-progress', 'in-progress', 'Ticket assigned to technician ID: 6.', '2026-08-06 18:59:06'),
(30, 7, 1, 'in-progress', 'in-progress', 'Ticket assigned to technician ID: 3.', '2026-08-06 18:59:17'),
(31, 7, 1, 'in-progress', 'in-progress', 'Ticket assigned to technician ID: 6.', '2026-08-06 18:59:25'),
(32, 2, 1, 'assigned', 'testing', 'currently working on it.', '2026-08-11 20:32:10');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `scheduled_date` date NOT NULL,
  `completed_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `technician_id` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `gadget_id`, `service_type`, `scheduled_date`, `completed_date`, `cost`, `technician_id`, `notes`, `created_at`) VALUES
(1, 1, 'Annual Cleaning & Check', '2025-07-15', NULL, '0.00', 3, 'Scheduled annual cleaning — due this month', '2026-07-10 16:10:35'),
(2, 4, 'RAM Upgrade to 16GB', '2025-06-10', '2025-06-10', '3200.00', 3, 'Replaced 8GB with 16GB DDR4 Kingston RAM', '2026-07-10 16:10:35'),
(3, 11, 'Cartridge & Drum Replace', '2025-05-20', '2025-05-20', '4500.00', 3, 'Replaced toner cartridge and drum unit', '2026-07-10 16:10:35'),
(4, 2, 'Battery Health Check', '2025-07-20', NULL, '0.00', 3, 'Battery holding at 72% — check needed', '2026-07-10 16:10:35'),
(5, 19, 'HDD to SSD Migration', '2025-06-25', '2025-06-25', '5500.00', 3, 'Replaced 1TB HDD with 512GB SSD for speed', '2026-07-10 16:10:35'),
(6, 3, 'Power Supply Diagnosis', '2025-06-28', '2025-07-01', '2800.00', 3, 'Replaced blown capacitor on power board', '2026-07-10 16:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `token_2` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(1, 1, '4e20c015fd9f9130a14a113e90a3e340974b54fd', '2026-08-01 13:44:57', 1, '2026-08-01 13:29:57'),
(2, 1, 'e7c520b269c35beca3fc7b048a4923aba5a54495', '2026-08-01 13:53:35', 0, '2026-08-01 13:38:35'),
(3, 2, '9e4b9567c03dd2d0bed0cb363827a15f296ac3af', '2026-08-01 13:54:25', 1, '2026-08-01 13:39:25'),
(4, 2, '4ff0845b3af0938e8c4855785eca10d1d978db9c', '2026-08-30 21:27:40', 1, '2026-08-30 21:12:40'),
(5, 2, '8a4bb10fbd487506f6fe2106a005225be02ad162', '2026-09-29 21:27:09', 0, '2026-09-29 21:12:09');

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE IF NOT EXISTS `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT 'Other',
  `quantity` int(11) NOT NULL DEFAULT '0',
  `min_quantity` int(11) NOT NULL DEFAULT '2',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `vendor_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`id`, `name`, `category`, `quantity`, `min_quantity`, `unit_cost`, `vendor_id`, `created_at`) VALUES
(1, '8GB DDR4 RAM Kingston', 'RAM', 4, 3, '1800.00', 4, '2026-07-10 16:10:35'),
(2, '16GB DDR4 RAM Kingston', 'RAM', 4, 3, '3200.00', 4, '2026-07-10 16:10:35'),
(3, '256GB SSD Samsung 870 EVO', 'Storage', 5, 3, '3500.00', 4, '2026-07-10 16:10:35'),
(4, '512GB SSD Samsung 870 EVO', 'Storage', 2, 3, '5800.00', 4, '2026-07-10 16:10:35'),
(5, 'HP LaserJet Toner CF258A', 'Consumable', 3, 3, '3800.00', 2, '2026-07-10 16:10:35'),
(6, 'HP LaserJet Drum Unit CF258X', 'Consumable', 3, 3, '4200.00', 2, '2026-07-10 16:10:35'),
(7, 'Dell Laptop Charger 65W', 'Power', 5, 3, '1200.00', 1, '2026-07-10 16:10:35'),
(8, 'HP Laptop Charger 65W', 'Power', 3, 3, '1100.00', 2, '2026-07-10 16:10:35'),
(9, 'Thermal Paste Arctic MX-4', 'Tool', 2, 3, '350.00', 4, '2026-07-10 16:10:35'),
(10, 'Laptop Pickup Roller Kit', 'Parts', 1, 3, '850.00', 4, '2026-07-10 16:10:35'),
(11, 'USB-C to USB-A Adapter', 'Accessory', 6, 3, '250.00', 4, '2026-07-10 16:10:35'),
(12, 'HDMI Cable 1.8m', 'Accessory', 4, 3, '180.00', 4, '2026-07-10 16:10:35');

-- --------------------------------------------------------

--
-- Table structure for table `tp_sessions`
--

CREATE TABLE IF NOT EXISTS `tp_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` char(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_role` varchar(20) NOT NULL DEFAULT 'user',
  `is_support` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `tp_sessions`
--

INSERT INTO `tp_sessions` (`id`, `token`, `user_id`, `user_name`, `user_role`, `is_support`, `created_at`, `last_activity`, `expires_at`, `ip_address`, `user_agent`) VALUES
(1, 'a4a4f1f1bee3da99c6ac2827d8609bb4a22b2620dd6ed2d1c227c30c39788bca', 1, 'Meet AJ', 'admin', 0, '2026-10-18 17:28:12', '2026-10-18 17:28:16', '2026-10-19 01:28:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(200) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `is_support` tinyint(1) NOT NULL DEFAULT '0',
  `department` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_support`, `department`, `phone`, `last_login`, `created_at`) VALUES
(1, 'Meet AJ', 'meetajadmin@techpulse.com', 'Diya@#3690', 'admin', 0, 'IT Administration', '9876543210', '2026-10-18 17:28:12', '2026-07-10 16:10:34'),
(2, 'Raj Patel', 'employee@techpulse.com', 'Das@3690A', 'user', 0, 'Finance', '9988776655', '2026-09-29 21:56:15', '2026-07-10 16:10:34'),
(3, 'Suresh Support', 'tech@techpulse.com', 'Tech@123', 'user', 1, 'IT Support', '9765432109', '2026-07-13 18:17:24', '2026-07-10 16:10:34'),
(4, 'Priya Shah', 'priya@techpulse.com', 'Priya@456', 'user', 0, 'HR', '9123456780', NULL, '2026-07-10 16:10:34'),
(5, 'Amit Verma', 'amit@techpulse.com', 'Amit@789', 'user', 0, 'Marketing', '9234567801', '2026-08-02 20:18:04', '2026-07-10 16:10:34'),
(6, 'Neha Support', 'neha@techpulse.com', 'Neha@321', 'user', 1, 'IT Support', '9345678012', '2026-07-13 18:16:28', '2026-07-10 16:10:34'),
(7, 'Meet AJ', 'meetaj@techpulse.com', 'Das369@A', 'user', 0, 'Administration', '9966587441', '2026-09-29 22:20:22', '2026-07-10 16:14:00'),
(8, 'Dilen Raval', 'dilen@hmail.com', 'Dilen"45', 'user', 0, 'IT', '', '2026-07-11 17:22:27', '2026-07-11 17:22:19'),
(9, 'Hit Parekh', 'hitsevak@techpulse.com', 'Das"3690', 'user', 0, 'IT', '', '2026-08-02 20:33:21', '2026-07-13 19:04:53'),
(10, 'chirag', 'chirag369@techpulse.com', 'Chirag@123', 'user', 0, 'HR', '', '2026-07-14 14:10:34', '2026-07-14 14:10:26'),
(11, 'Mitul Padhiyar', 'mitulp@techpulse.com', 'Das369@A', 'user', 0, 'IT', '', '2026-07-14 18:06:47', '2026-07-14 18:00:06');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Supplier',
  `address` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `contact_person`, `phone`, `email`, `category`, `address`, `created_at`) VALUES
(1, 'Dell Technologies India', 'Rahul Sharma', '1800-425-2355', 'support@dell.in', 'Supplier & Service', 'Bengaluru, Karnataka', '2026-07-10 16:10:34'),
(2, 'HP India Pvt. Ltd.', 'Sunita Mehta', '1800-425-3002', 'support@hp.in', 'Supplier & Service', 'Mumbai, Maharashtra', '2026-07-10 16:10:34'),
(3, 'Lenovo India Solutions', 'Vikram Singh', '1800-419-7555', 'service@lenovo.in', 'Supplier', 'Chennai, Tamil Nadu', '2026-07-10 16:10:34'),
(4, 'Redington India', 'Anil Kumar', '044-42243353', 'sales@redington.in', 'Distributor', 'Chennai, Tamil Nadu', '2026-07-10 16:10:34'),
(5, 'iCare Service Center', 'Mohan Das', '9876512345', 'icare@local.in', 'Service Center', 'Anand, Gujarat', '2026-07-10 16:10:34'),
(6, 'Apple Inn Pvt. Ltd.', 'Hit Parekh', '+91 94145 03690', 'apple.org@international.in', 'Supplier & Service', 'Delhi, India', '2026-07-10 18:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

CREATE TABLE IF NOT EXISTS `warranties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gadget_id` int(11) NOT NULL,
  `type` enum('warranty','amc') NOT NULL DEFAULT 'warranty',
  `provider` varchar(100) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `document` varchar(200) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

--
-- Dumping data for table `warranties`
--

INSERT INTO `warranties` (`id`, `gadget_id`, `type`, `provider`, `start_date`, `end_date`, `document`, `notes`, `created_at`) VALUES
(1, 1, 'warranty', 'Dell Technologies', '2023-01-10', '2026-01-10', NULL, '3-year ProSupport. Call 1800-425-2355', '2026-07-10 16:10:35'),
(2, 2, 'warranty', 'HP Inc.', '2023-03-15', '2026-03-15', NULL, '3-year onsite warranty', '2026-07-10 16:10:35'),
(3, 3, 'warranty', 'Dell Technologies', '2023-02-20', '2025-02-20', NULL, '2-year standard warranty — EXPIRED', '2026-07-10 16:10:35'),
(4, 4, 'warranty', 'Lenovo', '2022-06-12', '2025-06-12', NULL, '3-year warranty', '2026-07-10 16:10:35'),
(5, 4, 'amc', 'iCare Service', '2025-06-13', '2026-06-12', NULL, 'Annual maintenance contract post-warranty', '2026-07-10 16:10:35'),
(6, 5, 'warranty', 'HP Inc.', '2022-09-08', '2024-09-08', NULL, '2-year warranty — EXPIRED', '2026-07-10 16:10:35'),
(7, 6, 'warranty', 'Samsung', '2024-01-22', '2025-01-22', NULL, '1-year manufacturer warranty', '2026-07-10 16:10:35'),
(8, 7, 'warranty', 'Apple India', '2024-02-14', '2025-02-14', NULL, '1-year AppleCare', '2026-07-10 16:10:35'),
(9, 8, 'warranty', 'OnePlus India', '2023-11-18', '2025-11-18', NULL, '2-year warranty', '2026-07-10 16:10:35'),
(10, 13, 'warranty', 'Dell Technologies', '2024-03-01', '2027-03-01', NULL, '3-year ProSupport Plus', '2026-07-10 16:10:35');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
