-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 07:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evolve1`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `duration` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','in_progress','completed') NOT NULL DEFAULT 'pending',
  `booking_code` varchar(20) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `station_id`, `booking_date`, `booking_time`, `duration`, `amount`, `payment_status`, `razorpay_order_id`, `razorpay_payment_id`, `status`, `booking_code`, `qr_code`, `created_at`, `updated_at`, `payment_amount`, `payment_date`, `payment_method`) VALUES
(18, 2, 20, '2025-03-14', '11:53:00', 30, 150.00, 'completed', 'order_Q6xt3SMsYJiNNu', 'pay_Q6xtRBFjV9uGrv', 'cancelled', NULL, NULL, '2025-03-15 06:19:52', '2025-03-15 06:56:21', NULL, NULL, NULL),
(23, 2, 20, '2025-03-15', '11:59:00', 30, 39.00, 'completed', 'order_Q6y0GCXGbQWopS', 'pay_Q6y3IeyBFjCgRZ', 'confirmed', NULL, NULL, '2025-03-15 06:26:42', '2025-03-15 06:29:55', NULL, NULL, NULL),
(24, 2, 20, '2025-03-15', '09:12:00', 30, 39.00, 'completed', 'order_Q7Ji4Q6DOosVYW', 'pay_Q7JjNtwpRtOxMG', 'confirmed', NULL, NULL, '2025-03-16 03:40:43', '2025-03-16 03:42:18', NULL, NULL, NULL),
(25, 2, 20, '2025-03-17', '21:50:00', 30, 39.00, 'completed', 'order_Q7WcnIRz8QrRyU', 'pay_Q7WdHtJJlw2UcG', 'confirmed', NULL, NULL, '2025-03-16 16:18:46', '2025-03-16 16:19:34', NULL, NULL, NULL),
(26, 2, 20, '2025-03-18', '13:53:00', 30, 39.00, 'completed', 'order_Q7WhT7VnttQMTl', 'pay_Q7WhlAAhwt5cqE', 'confirmed', NULL, NULL, '2025-03-16 16:23:11', '2025-03-16 16:23:48', NULL, NULL, NULL),
(27, 2, 20, '2025-03-18', '12:14:00', 30, 39.00, 'completed', 'order_Q7X4BbTHSjUCm9', 'pay_Q7X4tABkGkXC99', 'confirmed', NULL, NULL, '2025-03-16 16:44:42', '2025-03-16 16:45:41', NULL, NULL, NULL),
(28, 99, 20, '2025-03-17', '14:47:00', 30, 39.00, 'completed', 'order_Q7XccTiMLWNnO8', 'pay_Q7Xd2kNIHDGz2w', 'confirmed', NULL, NULL, '2025-03-16 17:17:17', '2025-03-16 17:18:00', NULL, NULL, NULL),
(29, 99, 20, '2025-03-19', '03:09:00', 30, 39.00, 'completed', 'order_Q7Z1W8QDUtwZX1', 'pay_Q7Z2W2NnFyZsue', 'confirmed', NULL, NULL, '2025-03-16 18:39:33', '2025-03-16 18:40:49', NULL, NULL, NULL),
(30, 99, 20, '2025-03-18', '00:16:00', 30, 39.00, 'completed', 'order_Q7Z9GTq22VMoaK', 'pay_Q7Z9ZtwyL53hz8', 'confirmed', NULL, NULL, '2025-03-16 18:46:53', '2025-03-16 18:47:33', NULL, NULL, NULL),
(31, 99, 14, '2025-03-20', '03:18:00', 30, 30.00, 'completed', 'order_Q7ZBNvOJPH4Rh2', 'pay_Q7ZBnKJZekXk6b', 'confirmed', NULL, NULL, '2025-03-16 18:48:54', '2025-03-16 18:49:35', NULL, NULL, NULL),
(32, 99, 14, '2025-03-24', '03:31:00', 30, 30.00, 'pending', NULL, NULL, 'cancelled', 'EV3E0062', NULL, '2025-03-16 19:01:23', '2025-03-16 19:40:20', NULL, NULL, NULL),
(33, 99, 14, '2025-03-24', '03:31:00', 30, 30.00, 'completed', 'order_Q7ZR3Aj76Wajhr', 'pay_Q7ZRPsEnrK5S2t', 'confirmed', NULL, NULL, '2025-03-16 19:03:44', '2025-03-16 19:04:23', NULL, NULL, NULL),
(34, 99, 14, '2025-03-19', '03:44:00', 90, 90.00, 'pending', 'order_Q7ZawjiZHRb4jJ', NULL, 'pending', NULL, NULL, '2025-03-16 19:13:06', '2025-03-16 19:13:06', NULL, NULL, NULL),
(35, 99, 20, '2025-03-13', '03:53:00', 30, 3000.00, 'pending', 'order_Q7Zna0eE5y9b4v', NULL, 'pending', NULL, NULL, '2025-03-16 19:25:03', '2025-03-16 19:25:03', NULL, NULL, NULL),
(36, 99, 20, '2025-03-20', '03:55:00', 30, 39.00, 'completed', 'order_Q7ZoTDO0VGdCwk', 'pay_Q7Zot4ehF8g7cK', 'confirmed', NULL, NULL, '2025-03-16 19:25:54', '2025-03-16 19:26:36', NULL, NULL, NULL),
(37, 99, 20, '2025-03-17', '01:02:00', 30, 39.00, 'completed', 'order_Q7ZtocnfxnDJR1', 'pay_Q7ZuHxt4tOtJnB', 'confirmed', NULL, NULL, '2025-03-16 19:30:57', '2025-03-16 19:36:18', NULL, NULL, NULL),
(38, 99, 20, '2025-03-12', '01:09:00', 30, 39.00, 'pending', 'order_Q7ZzwyIET80MCm', NULL, 'pending', NULL, NULL, '2025-03-16 19:36:46', '2025-03-16 19:36:46', NULL, NULL, NULL),
(39, 99, 20, '2025-03-24', '01:10:00', 30, 39.00, 'completed', 'order_Q7a1B251EVQrHw', 'pay_Q7a1ZSzEaKCTsI', 'confirmed', NULL, NULL, '2025-03-16 19:37:56', '2025-03-16 19:38:36', NULL, NULL, NULL),
(40, 99, 20, '2025-03-17', '01:13:00', 30, 39.00, 'completed', 'order_Q7a3zasl6PXDdf', 'pay_Q7a4HHD7VeICoj', 'confirmed', NULL, NULL, '2025-03-16 19:40:35', '2025-03-16 19:41:10', NULL, NULL, NULL),
(41, 99, 20, '2025-03-18', '05:12:00', 30, 39.00, 'completed', 'order_Q7a6R3uBzNocbv', 'pay_Q7a6hCdDNLgSQ4', 'confirmed', NULL, NULL, '2025-03-16 19:42:54', '2025-03-16 19:43:28', NULL, NULL, NULL),
(42, 99, 14, '2025-03-18', '05:15:00', 30, 30.00, 'completed', 'order_Q7a9LlYotBov1O', 'pay_Q7a9b0gNmxZagW', 'completed', NULL, NULL, '2025-03-16 19:45:40', '2025-03-18 04:40:36', NULL, NULL, NULL),
(43, 99, 20, '2025-03-26', '05:19:00', 30, 39.00, 'completed', 'order_Q7aDxKQLa698YO', 'pay_Q7aEL6QqUHpnYl', 'confirmed', NULL, NULL, '2025-03-16 19:50:01', '2025-03-16 19:51:15', NULL, NULL, NULL),
(44, 2, 20, '2025-03-18', '19:56:00', 30, 39.00, 'completed', 'order_Q7p9WxoWNs0Mb9', 'pay_Q7p9sjOqaIW0YN', 'confirmed', NULL, NULL, '2025-03-17 10:26:13', '2025-03-17 10:26:53', NULL, NULL, NULL),
(45, 2, 20, '2025-03-19', '19:58:00', 30, 39.00, 'completed', 'order_Q7pBVRyEhxjHeh', 'pay_Q7pBkNwsDk6gvO', 'confirmed', NULL, NULL, '2025-03-17 10:28:06', '2025-03-17 10:28:38', NULL, NULL, NULL),
(46, 99, 14, '2025-03-18', '21:06:00', 30, 30.00, 'completed', 'order_Q7pKP6BXpGxLsn', 'pay_Q7pKkO2scbKwPP', 'confirmed', NULL, NULL, '2025-03-17 10:36:31', '2025-03-17 10:37:10', NULL, NULL, NULL),
(47, 2, 14, '2025-03-18', '12:53:00', 90, 90.00, 'completed', 'order_Q7uDwLxC6vO2K1', 'pay_Q7uEeiJ965tZu0', 'completed', NULL, NULL, '2025-03-17 15:23:51', '2025-03-18 04:40:38', NULL, NULL, NULL),
(48, 2, 20, '2025-03-18', '12:59:00', 30, 39.00, 'completed', 'order_Q7uJtDxkAVwPH6', 'pay_Q7uKeCutEFPP5f', 'confirmed', NULL, NULL, '2025-03-17 15:29:29', '2025-03-17 15:30:34', NULL, NULL, NULL),
(49, 2, 20, '2025-03-18', '12:04:00', 30, 39.00, 'completed', 'order_Q7uPJMqkswPEVK', 'pay_Q7uPcWAeWAq5W5', 'confirmed', NULL, NULL, '2025-03-17 15:34:37', '2025-03-17 15:36:07', NULL, NULL, NULL),
(50, 2, 20, '2025-03-18', '12:18:00', 30, 39.00, 'completed', 'order_Q7udkDq65ZdEye', 'pay_Q7ue3ww0q0cwjI', 'confirmed', NULL, NULL, '2025-03-17 15:48:17', '2025-03-17 15:48:55', NULL, NULL, NULL),
(51, 2, 20, '2025-03-18', '23:23:00', 30, 39.00, 'completed', 'order_Q7ujMMrcpO7aFh', 'pay_Q7ujfH6YntH4nm', 'confirmed', NULL, NULL, '2025-03-17 15:53:36', '2025-03-17 15:54:25', NULL, NULL, NULL),
(52, 2, 20, '2025-03-18', '23:30:00', 30, 39.00, 'completed', 'order_Q7uqFmHhpOO06p', 'pay_Q7uqjmNFZib6rc', 'confirmed', NULL, NULL, '2025-03-17 16:00:08', '2025-03-17 16:00:54', NULL, NULL, NULL),
(53, 2, 20, '2025-03-18', '12:34:00', 30, 39.00, 'completed', 'order_Q7uuafIqmBiP0N', 'pay_Q7uv20vsKufEzJ', 'confirmed', NULL, NULL, '2025-03-17 16:04:14', '2025-03-17 16:04:59', NULL, NULL, NULL),
(54, 2, 20, '2025-03-18', '12:37:00', 30, 39.00, 'completed', 'order_Q7uyHDI0BgfZOg', 'pay_Q7uydKm25fJAHP', 'confirmed', NULL, NULL, '2025-03-17 16:07:43', '2025-03-17 16:08:22', NULL, NULL, NULL),
(55, 2, 20, '2025-03-19', '12:47:00', 30, 39.00, 'completed', 'order_Q7v8dVycu4eWzN', 'pay_Q7v9CIvYRAcnaG', 'confirmed', NULL, NULL, '2025-03-17 16:17:32', '2025-03-17 16:18:23', NULL, NULL, NULL),
(56, 2, 20, '2025-03-18', '12:51:00', 30, 39.00, 'completed', 'order_Q7vCrvUYk3scVu', 'pay_Q7vD5Ya7eKH7Wr', 'confirmed', NULL, NULL, '2025-03-17 16:21:32', '2025-03-17 16:22:04', NULL, NULL, NULL),
(57, 2, 20, '2025-03-18', '12:59:00', 30, 39.00, 'completed', 'order_Q7vKpABOL2IpcH', 'pay_Q7vL4U1MKSDMag', '', NULL, NULL, '2025-03-17 16:29:04', '2025-03-17 16:33:21', NULL, NULL, NULL),
(58, 2, 20, '2025-03-17', '13:10:00', 30, 39.00, 'completed', 'order_Q7vXPDfjD8uxTx', 'pay_Q7vXimZGpOm3sN', '', NULL, NULL, '2025-03-17 16:40:59', '2025-03-17 16:42:38', NULL, NULL, NULL),
(59, 2, 20, '2025-03-24', '13:17:00', 30, 39.00, 'completed', 'order_Q7vemhoVkdYJMJ', 'pay_Q7vf4reQx8MZkK', 'completed', NULL, NULL, '2025-03-17 16:47:58', '2025-03-17 16:50:32', NULL, NULL, NULL),
(60, 2, 20, '2025-03-18', '14:31:00', 30, 39.00, 'completed', 'order_Q7vt4XJgb4WKlQ', 'pay_Q7vtKOOFSRCMGr', 'completed', NULL, NULL, '2025-03-17 17:01:29', '2025-03-17 17:03:42', NULL, NULL, NULL),
(61, 2, 19, '2025-03-18', '22:51:00', 30, 3000.00, 'pending', 'order_Q7wC7YzkJYVyhL', NULL, 'pending', NULL, NULL, '2025-03-17 17:19:31', '2025-03-17 17:19:31', NULL, NULL, NULL),
(62, 2, 19, '2025-03-18', '22:51:00', 120, 12000.00, 'pending', 'order_Q7wCXCTz65RjGd', NULL, 'pending', NULL, NULL, '2025-03-17 17:19:55', '2025-03-17 17:19:55', NULL, NULL, NULL),
(63, 2, 19, '2025-03-18', '22:51:00', 30, 3000.00, 'completed', 'order_Q7wDDKozraMyca', 'pay_Q7wDRKJ0YfNc66', 'completed', NULL, NULL, '2025-03-17 17:20:33', '2025-03-17 17:24:06', NULL, NULL, NULL),
(64, 2, 20, '2025-03-18', '23:51:00', 30, 39.00, 'completed', 'order_Q7xDUMoPj33xHb', 'pay_Q7xDrktq2iuDOp', 'confirmed', NULL, NULL, '2025-03-17 18:19:30', '2025-03-17 18:20:12', NULL, NULL, NULL),
(65, 2, 20, '2025-03-18', '01:05:00', 30, 39.00, 'completed', 'order_Q7xU2TWKXe3ggp', 'pay_Q7xUUSm8T80bhK', 'completed', NULL, NULL, '2025-03-17 18:35:11', '2025-03-17 18:54:37', NULL, NULL, NULL),
(66, 2, 20, '2025-03-19', '00:53:00', 30, 39.00, 'completed', 'order_Q87VoTaT5ycaq6', 'pay_Q87WarAt12ZkF5', 'completed', NULL, NULL, '2025-03-18 04:23:48', '2025-03-18 04:27:29', NULL, NULL, NULL),
(67, 2, 15, '2025-03-19', '02:23:00', 30, 30.00, 'completed', 'order_Q880r3ISk9YzZ9', 'pay_Q8817bBJWp2Gbp', 'completed', NULL, NULL, '2025-03-18 04:53:11', '2025-03-18 04:55:51', NULL, NULL, NULL),
(68, 2, 15, '2025-03-13', '01:26:00', 30, 30.00, 'completed', 'order_Q884HfAS9vn5sO', 'pay_Q884fI893ZdtfA', 'completed', NULL, NULL, '2025-03-18 04:56:26', '2025-03-18 04:59:09', NULL, NULL, NULL),
(69, 99, 20, '2025-03-19', '23:25:00', 120, 156.00, 'completed', 'order_Q8IHBDF0Scx2VX', 'pay_Q8IHetm0cXTi8N', 'completed', NULL, NULL, '2025-03-18 14:55:33', '2025-03-18 15:25:21', NULL, NULL, NULL),
(70, 99, 20, '2025-03-19', '12:40:00', 120, 156.00, 'completed', 'order_Q8IXMcz6kxBDj6', 'pay_Q8IXeec1W29MIu', 'confirmed', NULL, NULL, '2025-03-18 15:10:52', '2025-03-18 15:11:27', NULL, NULL, NULL),
(71, 99, 20, '2025-03-19', '12:44:00', 30, 39.00, 'completed', 'order_Q8IbThwdA9xeof', 'pay_Q8Ibj32XxU5Azo', 'completed', NULL, NULL, '2025-03-18 15:14:46', '2025-03-18 15:24:37', NULL, NULL, NULL),
(72, 2, 15, '2025-03-19', '12:57:00', 120, 120.00, 'completed', 'order_Q8Ioy5aU53Cg16', 'pay_Q8IpO0GQ0u1y8c', 'completed', NULL, NULL, '2025-03-18 15:27:32', '2025-03-18 15:40:19', NULL, NULL, NULL),
(73, 99, 20, '2025-03-19', '13:15:00', 120, 156.00, 'completed', 'order_Q8J7or8gMjMwrr', 'pay_Q8J8C3Bh0ng1X1', 'completed', NULL, NULL, '2025-03-18 15:45:23', '2025-03-18 16:09:08', NULL, NULL, NULL),
(74, 99, 20, '2025-03-18', '12:42:00', 120, 156.00, 'completed', 'order_Q8JaXOQIdYBY05', 'pay_Q8Jalyl7eh6JZv', 'completed', NULL, NULL, '2025-03-18 16:12:34', '2025-03-18 17:14:02', NULL, NULL, NULL),
(75, 2, 20, '2025-03-20', '10:42:00', 30, 39.00, 'completed', 'order_Q8TnwBiPjsKPBh', 'pay_Q8ToUcD9e7Dgjd', 'in_progress', NULL, NULL, '2025-03-19 02:12:11', '2025-03-19 02:13:49', NULL, NULL, NULL),
(76, 2, 15, '2025-03-18', '07:51:00', 30, 30.00, 'completed', 'order_Q8TvTE6pKGjq7i', 'pay_Q8TwQeTKpG9Tt2', 'in_progress', NULL, NULL, '2025-03-19 02:19:19', '2025-03-19 02:21:24', NULL, NULL, NULL),
(77, 2, 15, '2025-03-18', '10:55:00', 30, 30.00, 'completed', 'order_Q8U2Ciw8jj1BzY', 'pay_Q8U2SIWKo6ule8', 'completed', NULL, NULL, '2025-03-19 02:25:42', '2025-03-19 02:52:26', NULL, NULL, NULL),
(78, 2, 15, '2025-03-20', '11:04:00', 30, 30.00, 'completed', 'order_Q8UBGnOvyTbSDS', 'pay_Q8UBb65vdBHMtm', 'completed', NULL, NULL, '2025-03-19 02:34:17', '2025-03-19 02:43:11', NULL, NULL, NULL),
(79, 2, 15, '2025-03-20', '16:40:00', 120, 120.00, 'completed', 'order_Q8ZusBMUa4aFw5', 'pay_Q8ZvDtGCK3eaw6', 'in_progress', NULL, NULL, '2025-03-19 08:10:58', '2025-03-19 08:13:34', NULL, NULL, NULL),
(80, 2, 20, '2025-03-20', '18:03:00', 30, 39.00, 'completed', 'order_Q8aJCCMLeaAYHh', 'pay_Q8aJUkYDihZuoH', 'confirmed', NULL, NULL, '2025-03-19 08:34:00', '2025-03-19 08:34:34', NULL, NULL, NULL),
(81, 99, 21, '2025-03-20', '19:34:00', 120, 4000.00, 'completed', 'order_Q8aq0esP04Dc0j', 'pay_Q8aqQl842oE0e0', 'confirmed', NULL, NULL, '2025-03-19 09:05:03', '2025-03-19 09:05:46', NULL, NULL, NULL),
(82, 99, 21, '2025-03-18', '18:14:00', 30, 1000.00, 'failed', 'order_Q8bVr3XFWiQ4V4', NULL, 'cancelled', NULL, NULL, '2025-03-19 09:44:40', '2025-03-19 09:45:17', NULL, NULL, NULL),
(83, 2, 21, '2025-03-21', '19:16:00', 30, 1000.00, 'completed', 'order_Q8bXsTK6W1BeBS', 'pay_Q8bYCjkqUgCsFQ', 'confirmed', NULL, NULL, '2025-03-19 09:46:35', '2025-03-19 09:47:13', NULL, NULL, NULL),
(84, 99, 21, '2025-03-20', '21:15:00', 30, 1000.00, 'completed', 'order_Q8fcjKdeyXnzDj', 'pay_Q8fd1pNWIsbCjy', 'confirmed', NULL, NULL, '2025-03-19 13:45:57', '2025-03-19 13:46:31', NULL, NULL, NULL),
(85, 99, 21, '2025-03-20', '19:30:00', 30, 1000.00, 'completed', 'order_Q8fpR0tw9JLgPV', 'pay_Q8fppPBZ99IIp9', 'confirmed', NULL, NULL, '2025-03-19 13:57:58', '2025-03-19 13:58:44', NULL, NULL, NULL),
(86, 99, 21, '2025-03-19', '23:10:00', 30, 1000.00, 'completed', 'order_Q8gYt8vJrmE3fa', 'pay_Q8gZM5kS04BRmo', 'completed', NULL, NULL, '2025-03-19 14:41:00', '2025-03-19 16:27:57', NULL, NULL, NULL),
(87, 99, 21, '2025-03-20', '12:29:00', 30, 1000.00, 'completed', 'order_Q8gsF1ub2EbDoi', 'pay_Q8gsZUKQGQD8Ub', 'confirmed', NULL, NULL, '2025-03-19 14:59:19', '2025-03-19 14:59:56', NULL, NULL, NULL),
(88, 99, 21, '2025-03-19', '23:50:00', 30, 1000.00, 'completed', 'order_Q8hE9UpXYqqIAN', 'pay_Q8hENkUTDnOdNQ', 'confirmed', NULL, NULL, '2025-03-19 15:20:04', '2025-03-19 15:20:34', NULL, NULL, NULL),
(89, 99, 21, '2025-03-20', '14:58:00', 30, 1000.00, 'completed', 'order_Q8hNYQh7wYNBBB', 'pay_Q8hNwKn1f6ZdB2', 'completed', NULL, NULL, '2025-03-19 15:28:58', '2025-03-19 15:35:34', NULL, NULL, NULL),
(90, 99, 21, '2025-03-19', '12:09:00', 30, 1000.00, 'completed', 'order_Q8hZCTrAPdd7jz', 'pay_Q8hZWIp4DOoTcx', 'completed', NULL, NULL, '2025-03-19 15:39:59', '2025-03-19 15:44:44', NULL, NULL, NULL),
(91, 99, 21, '2025-03-19', '12:24:00', 30, 1000.00, 'completed', 'order_Q8honcAeGUYyEK', 'pay_Q8hp82YYzxalDk', 'completed', NULL, NULL, '2025-03-19 15:54:45', '2025-03-19 16:25:37', NULL, NULL, NULL),
(92, 99, 21, '2025-03-20', '12:29:00', 30, 1000.00, 'completed', 'order_Q8hu2lFHqmldJD', 'pay_Q8huGAcyDQ5R2J', 'completed', NULL, NULL, '2025-03-19 15:59:43', '2025-03-19 16:24:46', NULL, NULL, NULL),
(93, 2, 21, '2025-03-20', '11:00:00', 30, 1000.00, 'completed', 'order_Q8jnnjygtaFvu1', 'pay_Q8jo5HfwvEbkro', 'confirmed', NULL, NULL, '2025-03-19 17:51:12', '2025-03-19 17:51:46', NULL, NULL, NULL),
(94, 99, 21, '2025-03-20', '08:00:00', 30, 1000.00, 'completed', 'order_Q8jpI6OfvyCdEN', 'pay_Q8jpY1lytYoHE4', 'completed', NULL, NULL, '2025-03-19 17:52:36', '2025-03-19 17:56:16', NULL, NULL, NULL),
(95, 99, 21, '2025-03-19', '11:30:00', 30, 1000.00, 'completed', 'order_Q8jw6c000T2G8N', 'pay_Q8jwMBRoeI7H79', 'completed', NULL, NULL, '2025-03-19 17:59:03', '2025-03-20 01:48:30', NULL, NULL, NULL),
(96, 2, 21, '2025-03-20', '09:00:00', 30, 1000.00, 'pending', 'order_Q8s2gWPnC6B34U', NULL, 'pending', NULL, NULL, '2025-03-20 01:54:49', '2025-03-20 01:54:49', NULL, NULL, NULL),
(97, 2, 21, '2025-03-20', '13:00:00', 30, 1000.00, 'completed', 'order_Q8scPIHPd74rHP', 'pay_Q8scwOHlMWLMa4', 'confirmed', NULL, NULL, '2025-03-20 02:28:38', '2025-03-20 02:30:43', NULL, NULL, NULL),
(98, 2, 21, '2025-03-20', '14:00:00', 30, 1000.00, 'completed', 'order_Q8sihSUrudc8dF', 'pay_Q8sizOzag9gufS', 'confirmed', NULL, NULL, '2025-03-20 02:34:36', '2025-03-20 02:35:10', NULL, NULL, NULL),
(99, 2, 21, '2025-03-20', '10:00:00', 30, 1000.00, 'completed', 'order_Q8sqf5bt06rNsJ', 'pay_Q8srdaaDQor3j2', 'confirmed', NULL, NULL, '2025-03-20 02:42:08', '2025-03-20 02:43:21', NULL, NULL, NULL),
(100, 2, 21, '2025-03-21', '09:00:00', 90, 3000.00, 'completed', 'order_Q8yEuoyPSBNQ8O', 'pay_Q8yFMFZDPZHvVh', 'confirmed', NULL, NULL, '2025-03-20 07:58:34', '2025-03-20 08:06:28', NULL, NULL, NULL),
(101, 2, 21, '2025-03-22', '08:00:00', 30, 1000.00, 'completed', 'order_Q8zD47OOtVf3Dy', 'pay_Q8zDMvQ46aZ3Bu', 'completed', NULL, NULL, '2025-03-20 08:55:30', '2025-03-20 09:01:31', NULL, NULL, NULL),
(102, 99, 21, '2025-03-21', '18:00:00', 30, 1000.00, 'completed', 'order_Q907XVYq4ct3o5', 'pay_Q907tMcQQHzKKm', 'completed', NULL, NULL, '2025-03-20 09:48:58', '2025-03-20 10:01:35', NULL, NULL, NULL),
(103, 2, 21, '2025-03-23', '08:00:00', 30, 1000.00, 'completed', 'order_Q9hnREPOkt7G6P', 'pay_Q9hnovOj3Sx4mZ', 'confirmed', NULL, NULL, '2025-03-22 04:32:27', '2025-03-22 04:33:09', NULL, NULL, NULL),
(104, 2, 22, '2025-03-22', '08:00:00', 30, 100.00, 'failed', 'order_Q9jJgFGzDb5UYb', NULL, 'cancelled', NULL, NULL, '2025-03-22 06:01:40', '2025-03-22 06:05:04', NULL, NULL, NULL),
(105, 2, 22, '2025-03-22', '09:00:00', 30, 100.00, 'completed', 'order_Q9jQkeJFOJwQ8v', 'pay_Q9jR0fWEbqhfon', 'confirmed', NULL, NULL, '2025-03-22 06:08:22', '2025-03-22 06:08:54', NULL, NULL, NULL),
(106, 2, 22, '2025-03-23', '08:00:00', 30, 100.00, 'completed', 'order_Q9jUjaxmNYVMVj', 'pay_Q9jUziZpzuqUe6', 'confirmed', NULL, NULL, '2025-03-22 06:12:08', '2025-03-22 06:12:40', NULL, NULL, NULL),
(107, 2, 22, '2025-03-23', '09:00:00', 30, 100.00, 'failed', 'order_Q9jYlxBXcUoo6E', NULL, 'cancelled', NULL, NULL, '2025-03-22 06:15:58', '2025-03-22 06:16:29', NULL, NULL, NULL),
(108, 2, 22, '2025-03-28', '08:00:00', 30, 100.00, 'completed', 'order_Q9jaA2N4Xm1fhN', 'pay_Q9jabiKcbeZoqD', 'confirmed', NULL, NULL, '2025-03-22 06:17:16', '2025-03-22 06:19:13', NULL, NULL, NULL),
(109, 2, 22, '2025-03-23', '21:00:00', 30, 100.00, 'completed', 'order_Q9jevXYwuxEj3i', 'pay_Q9jfCC2F8RvliC', 'confirmed', NULL, NULL, '2025-03-22 06:21:47', '2025-03-22 06:22:24', NULL, NULL, NULL),
(110, 2, 22, '2025-03-22', '10:30:00', 30, 100.00, 'completed', 'order_Q9jibNseIB1JoR', 'pay_Q9jizK2dhC580m', 'confirmed', NULL, NULL, '2025-03-22 06:25:16', '2025-03-22 06:29:16', NULL, NULL, NULL),
(111, 2, 22, '2025-03-23', '20:00:00', 30, 100.00, 'completed', 'order_Q9joh9Egx6xL4B', 'pay_Q9jovUpErqri7s', 'confirmed', NULL, NULL, '2025-03-22 06:31:02', '2025-03-22 06:31:32', NULL, NULL, NULL),
(112, 2, 22, '2025-03-22', '11:30:00', 30, 100.00, 'completed', 'order_Q9jrX11vFiEtxe', 'pay_Q9jrsIc6m1jvXl', 'confirmed', NULL, NULL, '2025-03-22 06:33:43', '2025-03-22 06:34:20', NULL, NULL, NULL),
(113, 2, 22, '2025-03-23', '17:30:00', 30, 100.00, 'completed', 'order_Q9juQpwlY0bwNC', 'pay_Q9juma1li7PXet', 'confirmed', NULL, NULL, '2025-03-22 06:36:28', '2025-03-22 06:37:04', NULL, NULL, NULL),
(114, 2, 22, '2025-03-23', '18:30:00', 30, 100.00, 'completed', 'order_Q9jxZpzMxCewq3', 'pay_Q9jxpsgyDczjmO', 'confirmed', NULL, NULL, '2025-03-22 06:39:26', '2025-03-22 06:39:59', NULL, NULL, NULL),
(115, 2, 22, '2025-03-23', '16:30:00', 30, 100.00, 'completed', 'order_Q9k1B9NnIbNiOU', 'pay_Q9k1T1LjgvKEou', 'confirmed', NULL, NULL, '2025-03-22 06:42:51', '2025-03-22 06:43:25', NULL, NULL, NULL),
(116, 2, 22, '2025-03-22', '15:30:00', 30, 100.00, 'completed', 'order_Q9k4qMdbqQkDIW', 'pay_Q9k5BHkiTGixtB', 'confirmed', NULL, NULL, '2025-03-22 06:46:19', '2025-03-22 06:46:57', NULL, NULL, NULL),
(117, 2, 22, '2025-03-28', '11:00:00', 30, 100.00, 'completed', 'order_Q9k8yj7vFNv9he', 'pay_Q9k9BkohNs8kBS', 'confirmed', NULL, NULL, '2025-03-22 06:50:14', '2025-03-22 06:50:44', NULL, NULL, NULL),
(118, 2, 22, '2025-03-30', '08:00:00', 30, 100.00, 'completed', 'order_Q9kC2PbLfAyKno', 'pay_Q9kCMo3bIH7LVO', 'confirmed', NULL, NULL, '2025-03-22 06:53:08', '2025-03-22 06:53:44', NULL, NULL, NULL),
(119, 2, 22, '2025-03-22', '16:30:00', 30, 100.00, 'completed', 'order_Q9kEnoAvUcHwdE', 'pay_Q9kFRLAnVEyb0K', 'confirmed', NULL, NULL, '2025-03-22 06:55:45', '2025-03-22 06:56:39', NULL, NULL, NULL),
(120, 111, 22, '2025-03-26', '10:30:00', 30, 100.00, 'completed', 'order_QAHwNGDD6YQzwK', 'pay_QAHxOIXg6qrAGp', 'completed', NULL, NULL, '2025-03-23 15:53:54', '2025-03-23 15:59:16', NULL, NULL, NULL),
(121, 113, 22, '2025-03-27', '10:00:00', 90, 300.00, 'completed', 'order_QAtoYJPPSBrzCm', 'pay_QAtpWk30ebaDpT', 'confirmed', NULL, NULL, '2025-03-25 04:56:51', '2025-03-25 04:58:07', NULL, NULL, NULL),
(122, 2, 22, '2025-03-27', '12:00:00', 30, 100.00, 'completed', 'order_QBIzOoWROjAcZQ', 'pay_QBIzeAftiF72Oh', 'confirmed', NULL, NULL, '2025-03-26 05:34:26', '2025-03-26 05:34:58', NULL, NULL, NULL),
(123, 2, 22, '2025-03-27', '13:00:00', 30, 100.00, 'completed', 'order_QBJ8uqRG5cByt4', 'pay_QBJ989ggSrQ9Be', 'completed', NULL, NULL, '2025-03-26 05:43:26', '2025-03-26 06:07:03', NULL, NULL, NULL),
(124, 2, 22, '2025-03-27', '19:30:00', 30, 100.00, 'completed', 'order_QBJN8YVWxioY2f', 'pay_QBJNOPfamp5Lc5', 'confirmed', NULL, NULL, '2025-03-26 05:56:54', '2025-03-26 05:57:26', NULL, NULL, NULL),
(125, 2, 22, '2025-03-27', '20:30:00', 30, 100.00, 'completed', 'order_QBJQundijqqz31', 'pay_QBJR9ys2woWInh', 'confirmed', NULL, NULL, '2025-03-26 06:00:29', '2025-03-26 06:01:00', NULL, NULL, NULL),
(126, 2, 22, '2025-03-27', '17:30:00', 30, 100.00, 'completed', 'order_QBJTn1UVuxELLc', 'pay_QBJU55HloUplej', 'confirmed', NULL, NULL, '2025-03-26 06:03:12', '2025-03-26 06:03:46', NULL, NULL, NULL),
(127, 2, 22, '2025-03-29', '08:00:00', 30, 100.00, 'completed', 'order_QBJZk1PADx2KlV', 'pay_QBJZy8UnA6rH10', 'completed', NULL, NULL, '2025-03-26 06:08:50', '2025-03-26 06:14:18', NULL, NULL, NULL),
(128, 2, 22, '2025-03-27', '08:00:00', 30, 100.00, 'completed', 'order_QBK7toOnkWauNy', 'pay_QBK82DI4oVeMpv', 'confirmed', NULL, NULL, '2025-03-26 06:41:10', '2025-03-26 06:41:34', NULL, NULL, NULL),
(129, 2, 22, '2025-03-29', '11:30:00', 30, 100.00, 'completed', 'order_QBKClAY0BbzmRU', 'pay_QBKD0MWoLhFhTt', 'confirmed', NULL, NULL, '2025-03-26 06:45:46', '2025-03-26 06:46:16', NULL, NULL, NULL),
(130, 2, 22, '2025-03-27', '18:30:00', 30, 100.00, 'completed', 'order_QBKHvPQhM3Qztb', 'pay_QBKI6F31XnlaWE', 'confirmed', NULL, NULL, '2025-03-26 06:50:40', '2025-03-26 06:51:05', NULL, NULL, NULL),
(131, 2, 22, '2025-03-26', '08:00:00', 30, 100.00, 'completed', 'order_QBKORg69yyT1F2', 'pay_QBKOb3P68iK3LU', 'confirmed', NULL, NULL, '2025-03-26 06:56:50', '2025-03-26 06:57:15', NULL, NULL, NULL),
(132, 2, 22, '2025-03-31', '08:00:00', 30, 100.00, 'completed', 'order_QBLnBTNeAJQluv', 'pay_QBLnLCzp4zLh6h', 'confirmed', NULL, NULL, '2025-03-26 08:18:57', '2025-03-26 08:19:26', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_logs`
--

CREATE TABLE `booking_logs` (
  `log_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` enum('check_in','check_out') NOT NULL,
  `action_time` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_logs`
--

INSERT INTO `booking_logs` (`log_id`, `booking_id`, `user_id`, `action_type`, `action_time`, `status`, `created_at`) VALUES
(3, 89, 99, 'check_out', '2025-03-19 21:05:34', 'completed', '2025-03-19 15:35:34'),
(4, 90, 99, 'check_in', '2025-03-19 21:11:17', 'checked_in', '2025-03-19 15:41:17'),
(5, 90, 99, 'check_in', '2025-03-19 21:14:18', 'checked_in', '2025-03-19 15:44:18'),
(6, 90, 99, 'check_out', '2025-03-19 21:14:44', 'completed', '2025-03-19 15:44:44'),
(7, 91, 99, 'check_in', '2025-03-19 21:26:08', 'checked_in', '2025-03-19 15:56:08'),
(8, 91, 99, 'check_out', '2025-03-19 21:26:34', 'completed', '2025-03-19 15:56:34'),
(9, 92, 99, 'check_in', '2025-03-19 21:36:30', 'checked_in', '2025-03-19 16:06:30'),
(10, 92, 99, 'check_out', '2025-03-19 21:43:01', 'completed', '2025-03-19 16:13:01'),
(11, 92, 99, 'check_in', '2025-03-19 21:53:36', 'checked_in', '2025-03-19 16:23:36'),
(12, 92, 99, 'check_out', '2025-03-19 21:54:46', 'completed', '2025-03-19 16:24:46'),
(13, 91, 99, 'check_in', '2025-03-19 21:55:09', 'checked_in', '2025-03-19 16:25:09'),
(14, 91, 99, 'check_out', '2025-03-19 21:55:37', 'completed', '2025-03-19 16:25:37'),
(15, 86, 99, 'check_in', '2025-03-19 21:57:54', 'in_progress', '2025-03-19 16:27:54'),
(16, 86, 99, 'check_out', '2025-03-19 21:57:57', 'completed', '2025-03-19 16:27:57'),
(17, 94, 99, 'check_in', '2025-03-19 23:25:40', 'checked_in', '2025-03-19 17:55:40'),
(18, 94, 99, 'check_out', '2025-03-19 23:26:16', 'completed', '2025-03-19 17:56:16'),
(19, 95, 99, 'check_in', '2025-03-20 07:17:42', 'checked_in', '2025-03-20 01:47:42'),
(20, 95, 99, 'check_out', '2025-03-20 07:18:30', 'completed', '2025-03-20 01:48:30'),
(21, 101, 2, 'check_in', '2025-03-20 14:31:13', 'checked_in', '2025-03-20 09:01:13'),
(22, 101, 2, 'check_out', '2025-03-20 14:31:31', 'completed', '2025-03-20 09:01:31'),
(23, 102, 99, 'check_in', '2025-03-20 15:30:40', 'checked_in', '2025-03-20 10:00:40'),
(24, 102, 99, 'check_out', '2025-03-20 15:31:35', 'completed', '2025-03-20 10:01:35'),
(25, 120, 111, 'check_in', '2025-03-23 21:28:22', 'checked_in', '2025-03-23 15:58:22'),
(26, 120, 111, 'check_out', '2025-03-23 21:29:16', 'completed', '2025-03-23 15:59:16'),
(27, 123, 2, 'check_in', '2025-03-26 11:36:52', 'checked_in', '2025-03-26 06:06:52'),
(28, 123, 2, 'check_out', '2025-03-26 11:37:03', 'completed', '2025-03-26 06:07:03'),
(29, 127, 2, 'check_in', '2025-03-26 11:44:14', 'checked_in', '2025-03-26 06:14:14'),
(30, 127, 2, 'check_out', '2025-03-26 11:44:18', 'completed', '2025-03-26 06:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `charging_stations`
--

CREATE TABLE `charging_stations` (
  `station_id` int(11) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` point NOT NULL,
  `address` varchar(255) NOT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `charger_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`charger_types`)),
  `total_slots` int(11) NOT NULL,
  `available_slots` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price_per_hour` decimal(10,2) NOT NULL DEFAULT 100.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charging_stations`
--

INSERT INTO `charging_stations` (`station_id`, `owner_name`, `name`, `location`, `address`, `operator_id`, `price`, `charger_types`, `total_slots`, `available_slots`, `status`, `image`, `created_at`, `updated_at`, `price_per_hour`) VALUES
(2, 'Melbin Sabu ', 'Evolve', 0x000000000101000000f46fffa7fb335340170ee10e61202340, 'Podimattom, Kanjirappally, Kottayam, Kerala, 686507, India', NULL, 200.00, '{\"types\":[\"Type1\",\"CHAdeMO\"]}', 3, 3, 'active', NULL, '2025-02-19 17:41:19', '2025-02-24 16:42:42', 100.00),
(3, 'athul', 'EV', 0x00000000010100000043d3c89d88345340cb93b5977e0f2340, 'Amal Jyothi College of Engineering, Amal Jyothi College of Engineering Skywalk, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 150.00, '{\"types\":[\"Type1\",\"Type2\"]}', 3, 3, 'active', NULL, '2025-02-19 18:20:29', '2025-02-24 16:42:03', 100.00),
(4, 'Joel', 'New', 0x0000000001010000001cf77fcbe23053404dfa35a9991e2340, 'Ponkunnam - KVMS - Erumely Road, Chirakkadavu Center, Chirakkadavu, Kanjirappally, Kottayam, Kerala, 686519, India', NULL, 200.00, '{\"types\":[\"Type2\",\"CHAdeMO\"]}', 5, 5, 'active', NULL, '2025-02-20 10:12:35', '2025-02-20 10:12:35', 100.00),
(6, 'aa', 'aa', 0x000000000101000000172aff5a5e35534051830e5fcb182340, 'Edakkunnam, Kanjirappally, Kottayam, Kerala, 686512, India', NULL, 200.00, '{\"types\":[\"CHAdeMO\"]}', 1, 1, 'inactive', NULL, '2025-02-21 16:56:23', '2025-02-24 16:45:51', 100.00),
(10, 'Test Owner', 'Test Station', 0x000000000101000000cceec9c3420d24405227a089b0155340, '123 Test Street, Kochi', 1, 25.50, '{\"types\": [\"Type 2\", \"CCS\"]}', 4, 4, 'active', NULL, '2025-02-22 03:51:13', '2025-02-24 16:52:26', 100.00),
(11, 'Test Owner', 'Test Station', 0x0000000001010000006d8fde701f345340195932c7f20e2340, 'Panicheppalli - Vizhikkathodu Road, Vizhikkathodu, Kanjirappally, Kottayam, केरल, 686518, भारत', 1, 25.50, '{\"types\":[\"CHAdeMO\"]}', 2, 4, 'inactive', NULL, '2025-02-22 11:10:52', '2025-02-24 17:00:02', 100.00),
(12, 'jino', 'test', 0x000000000101000000e40fc24786345340cc1f3a234f0f2340, 'Amal Jyothi College of Engineering, Koovappalli - Vizhikkathodu Road, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 25.00, '{\"types\":[\"CCS\",\"CHAdeMO\"]}', 3, 3, 'active', NULL, '2025-02-26 08:59:26', '2025-02-26 08:59:26', 100.00),
(13, 'jino', 'mmmmm', 0x000000000101000000ecf82f108434534011397d3d5f0f2340, 'Amal Jyothi College of Engineering, Koovappalli - Vizhikkathodu Road, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 80.00, '{\"types\":[\"Type2\"]}', 2, 2, 'active', NULL, '2025-02-26 09:18:34', '2025-02-26 09:18:34', 100.00),
(14, 'vinod', 'gfd', 0x0000000001010000000be0fe4f5d33534087caced23bff2240, 'Chenappady, Kanjirappally, Kottayam, Kerala, 686544, India', NULL, 60.00, '{\"types\":[\"Type2\"]}', 2, 2, 'active', NULL, '2025-02-26 15:30:00', '2025-03-18 04:40:38', 100.00),
(15, 'vinod', 'evnew', 0x000000000101000000f0fff67f28f35240aa51cb8711732640, 'Kannanchery, Kozhikode, Kerala, 673029, India', NULL, 60.00, '{\"types\":[\"CCS\",\"CHAdeMO\"]}', 3, 1, 'inactive', NULL, '2025-02-26 15:41:30', '2025-04-02 05:37:27', 100.00),
(16, 'abin', 'sample', 0x0000000001010000001000b8ffe7015340c9e947fe4a302640, 'Poochirikkad Colony, Kondotty, Malappuram, Kerala, 676314, India', NULL, 20.00, '{\"types\":[\"CCS\",\"CHAdeMO\"]}', 3, 3, 'active', NULL, '2025-02-27 01:57:06', '2025-02-27 01:57:06', 100.00),
(17, 'abin', 'agfds`', 0x000000000101000000fb09c27089345340816a72e7780f2340, 'Amal Jyothi College of Engineering, Koovappalli - Vizhikkathodu Road, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 88.00, '{\"types\":[\"Type2\"]}', 5, 5, 'inactive', NULL, '2025-02-27 02:55:50', '2025-02-27 05:26:09', 100.00),
(18, 'abin', 'ytrds', 0x000000000101000000dd5fff8b363453403abe578692102340, 'Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 82.00, '{\"types\":[\"CHAdeMO\"]}', 2, 2, 'active', NULL, '2025-02-27 05:50:51', '2025-02-27 05:50:51', 100.00),
(19, 'alen', 'dfghjk', 0x00000000010100000001b8ffaf7c35534095f95fdda00e2340, 'Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 89.00, '{\"types\":[\"Type1\"]}', 2, 2, 'active', NULL, '2025-02-27 05:59:17', '2025-02-28 08:14:15', 100.00),
(20, 'vinod', 'ew', 0x0000000001010000001f9b898e8934534051d26817780f2340, 'Amal Jyothi College of Engineering, Mannarakkayam - Koovappally Road, Mannarakkayam, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 78.00, '{\"types\":[\"CHAdeMO\"]}', 7, 7, 'inactive', NULL, '2025-03-09 17:55:31', '2025-04-02 05:37:16', 100.00),
(21, 'tomshibu', 'FGH', 0x000000000101000000300a5216ab34534025e659492b0e2340, 'Amal Jyothi College of Engineering, Amal Jyothi College of Engineering Skywalk, Koovapally, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 2000.00, '{\"types\":[\"Type1\",\"Type2\"]}', 4, 4, 'inactive', NULL, '2025-03-19 09:00:36', '2025-04-02 05:37:12', 100.00),
(22, 'tomshibu', 'sdfg', 0x00000000010100000084f9cbbedf3253403facc05e940d2340, 'Karshaka Open Marker, Ponkunnam - KVMS - Erumely Road, Mannamplavu, Vizhikkathodu, Kanjirappally, Kottayam, Kerala, 686518, India', NULL, 200.00, '{\"types\":[\"CCS\"]}', 2, 2, 'inactive', 'uploads/station_images/1742621422_SINTRONES-Solution-EV-Charging-Station.jpg', '2025-03-22 05:30:22', '2025-04-02 05:37:09', 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `enquiry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `enquiry_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read','responded') DEFAULT 'unread',
  `response` text DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`enquiry_id`, `user_id`, `station_id`, `message`, `enquiry_date`, `status`, `response`, `response_date`, `created_at`, `updated_at`) VALUES
(1, 2, 22, 'Subject: dfghjkl\n\nrtyuiop', '2025-03-24 05:00:28', 'responded', 'erftgyhuj', '2025-03-24 05:00:44', '2025-03-24 05:00:28', '2025-03-24 05:00:44'),
(2, 2, 22, 'Subject: jhgf\n\nhgvcx\r\n', '2025-03-24 05:23:16', 'responded', 'sdfsd', '2025-03-24 05:23:53', '2025-03-24 05:23:16', '2025-03-24 05:23:53'),
(3, 2, 22, 'Subject: sds\n\nxfxvds', '2025-03-24 05:26:47', 'responded', 'asdasda', '2025-03-24 05:27:01', '2025-03-24 05:26:47', '2025-03-24 05:27:01'),
(4, 2, 22, 'Subject: sdfs\n\nsdsfsd', '2025-03-24 05:31:17', 'responded', 'sdaasda', '2025-03-24 05:31:30', '2025-03-24 05:31:17', '2025-03-24 05:31:30');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('booking','system','alert') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `type`, `created_at`, `is_read`) VALUES
(1, 99, 'Payment Successful', 'Your payment of ₹30.00 for booking at gfd has been processed successfully.', 'booking', '2025-03-16 19:47:22', 0),
(2, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 15:36:12', 1),
(3, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 15:48:59', 1),
(4, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 15:54:30', 1),
(5, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:00:58', 1),
(6, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:05:03', 1),
(7, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:08:27', 1),
(8, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:18:27', 1),
(9, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:22:08', 1),
(10, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:29:43', 1),
(11, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:41:39', 1),
(12, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-17 16:42:38', 1),
(13, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 16:48:37', 1),
(14, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-17 16:50:31', 1),
(15, 2, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-17 16:50:32', 1),
(16, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 17:02:12', 1),
(17, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-17 17:03:32', 1),
(18, 2, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-17 17:03:42', 1),
(19, 2, 'Booking Confirmed', 'Your booking at dfghjk has been confirmed. Amount paid: ₹3000', 'booking', '2025-03-17 17:21:09', 1),
(20, 2, 'Check-in Successful', 'You have checked in at dfghjk.', 'booking', '2025-03-17 17:23:18', 1),
(21, 2, 'Check-out Successful', 'You have checked out from dfghjk. Thank you for using our service!', 'booking', '2025-03-17 17:24:06', 1),
(22, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 18:20:17', 1),
(23, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-17 18:36:01', 1),
(24, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-17 18:37:51', 1),
(25, 2, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-17 18:54:37', 1),
(26, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-18 04:24:57', 1),
(27, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-18 04:26:24', 1),
(28, 2, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-18 04:27:29', 1),
(29, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹30', 'booking', '2025-03-18 04:53:50', 1),
(30, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-18 04:55:25', 1),
(31, 2, 'Check-out Successful', 'You have checked out from evnew. Thank you for using our service!', 'booking', '2025-03-18 04:55:51', 1),
(32, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹30', 'booking', '2025-03-18 04:57:14', 1),
(33, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-18 04:58:09', 1),
(34, 2, 'Check-out Successful', 'You have checked out from evnew. Thank you for using our service!', 'booking', '2025-03-18 04:59:09', 1),
(35, 99, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹156', 'booking', '2025-03-18 14:58:29', 0),
(36, 99, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-18 14:59:50', 0),
(126, 99, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹156', 'booking', '2025-03-18 15:11:31', 0),
(127, 99, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-18 15:15:23', 0),
(128, 99, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-18 15:16:22', 0),
(138, 99, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-18 15:24:37', 0),
(139, 99, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-18 15:25:21', 0),
(140, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹120', 'booking', '2025-03-18 15:28:21', 1),
(141, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-18 15:32:37', 1),
(151, 2, 'Check-out Successful', 'You have checked out from evnew. Thank you for using our service!', 'booking', '2025-03-18 15:40:19', 1),
(152, 99, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹156', 'booking', '2025-03-18 15:46:07', 0),
(153, 99, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-18 15:47:03', 0),
(199, 99, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-18 16:09:08', 0),
(200, 99, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹156', 'booking', '2025-03-18 16:13:10', 0),
(201, 99, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-18 17:14:02', 0),
(202, 99, 'Check-out Successful', 'You have checked out from ew. Thank you for using our service!', 'booking', '2025-03-18 17:14:02', 0),
(203, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-19 02:13:07', 1),
(204, 2, 'Check-in Successful', 'You have checked in at ew.', 'booking', '2025-03-19 02:13:49', 1),
(205, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹30', 'booking', '2025-03-19 02:20:37', 1),
(206, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-19 02:21:24', 1),
(207, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹30', 'booking', '2025-03-19 02:26:19', 1),
(208, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-19 02:27:16', 1),
(209, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹30', 'booking', '2025-03-19 02:34:56', 1),
(210, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-19 02:39:09', 1),
(211, 2, 'Check-out Successful', 'You have checked out from evnew. Thank you for using our service!', 'booking', '2025-03-19 02:43:11', 1),
(212, 2, 'Check-out Successful', 'You have checked out from evnew. Thank you for using our service!', 'booking', '2025-03-19 02:52:26', 1),
(213, 2, 'Booking Confirmed', 'Your booking at evnew has been confirmed. Amount paid: ₹120', 'booking', '2025-03-19 08:11:51', 1),
(214, 2, 'Check-in Successful', 'You have checked in at evnew.', 'booking', '2025-03-19 08:13:34', 1),
(215, 2, 'Booking Confirmed', 'Your booking at ew has been confirmed. Amount paid: ₹39', 'booking', '2025-03-19 08:34:38', 1),
(216, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹4000', 'booking', '2025-03-19 09:05:50', 0),
(217, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 09:47:23', 1),
(218, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 13:46:37', 0),
(219, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 13:58:48', 0),
(220, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 14:42:50', 0),
(221, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 15:00:00', 0),
(222, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 15:20:38', 0),
(223, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 15:29:41', 0),
(224, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 15:33:11', 0),
(225, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 15:35:34', 0),
(226, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 15:40:39', 0),
(227, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 15:41:17', 0),
(228, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 15:44:18', 0),
(229, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 15:44:44', 0),
(230, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 15:55:27', 0),
(231, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 15:56:08', 0),
(232, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 15:56:34', 0),
(233, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 16:00:18', 0),
(234, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 16:06:30', 0),
(235, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 16:13:01', 0),
(236, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 16:23:36', 0),
(237, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 16:24:46', 0),
(238, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 16:25:09', 0),
(239, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 16:25:37', 0),
(240, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 17:51:50', 1),
(241, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 17:53:16', 0),
(242, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-19 17:55:40', 0),
(243, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-19 17:56:16', 0),
(244, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-19 17:59:40', 0),
(245, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-20 01:47:42', 0),
(246, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-20 01:48:30', 0),
(247, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-20 02:30:48', 1),
(248, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-20 02:43:25', 1),
(249, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-20 08:56:09', 1),
(250, 2, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-20 09:01:13', 1),
(251, 2, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-20 09:01:31', 1),
(252, 99, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-20 09:49:41', 0),
(253, 99, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-20 10:00:40', 0),
(254, 99, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-20 10:01:35', 0),
(255, 2, 'Booking Confirmed', 'Your booking at FGH has been confirmed. Amount paid: ₹1000', 'booking', '2025-03-22 04:33:13', 1),
(256, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:08:58', 1),
(257, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:12:46', 1),
(258, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:19:18', 1),
(259, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:22:29', 1),
(260, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:29:21', 1),
(261, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:31:37', 1),
(262, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:34:25', 1),
(263, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:37:09', 1),
(264, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:40:05', 1),
(265, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:43:31', 1),
(266, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:47:02', 1),
(267, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:50:49', 1),
(268, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:53:49', 1),
(269, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-22 06:56:43', 1),
(270, 111, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-23 15:55:14', 1),
(271, 111, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-23 15:58:22', 1),
(272, 111, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-23 15:59:16', 1),
(273, 113, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹300', 'booking', '2025-03-25 04:58:14', 0),
(274, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 05:35:03', 1),
(275, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 05:44:04', 1),
(276, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 05:57:31', 1),
(277, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:01:05', 1),
(278, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:03:51', 1),
(279, 2, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-26 06:06:52', 1),
(280, 2, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-26 06:07:03', 1),
(281, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:09:26', 1),
(282, 2, 'Booking Update', 'You have successfully checked in at your charging station.', '', '2025-03-26 06:14:14', 1),
(283, 2, 'Booking Update', 'Your charging session has been completed. Thank you for using our service!', '', '2025-03-26 06:14:18', 1),
(284, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:41:39', 1),
(285, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:46:22', 1),
(286, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:51:10', 1),
(287, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 06:57:20', 1),
(288, 2, 'Booking Confirmed', 'Your booking at sdfg has been confirmed. Amount paid: ₹100', 'booking', '2025-03-26 08:19:30', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_details`
--

CREATE TABLE `payment_details` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('completed','failed','refunded') DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_details`
--

INSERT INTO `payment_details` (`payment_id`, `booking_id`, `user_id`, `station_id`, `amount`, `payment_date`, `payment_method`, `transaction_id`, `status`) VALUES
(1, 18, 2, 20, 150.00, '2025-03-15 06:20:35', 'card', 'pay_Q6xtRBFjV9uGrv', 'completed'),
(2, 23, 2, 20, 39.00, '2025-03-15 06:29:55', 'card', 'pay_Q6y3IeyBFjCgRZ', 'completed'),
(3, 24, 2, 20, 39.00, '2025-03-16 03:42:18', 'card', 'pay_Q7JjNtwpRtOxMG', 'completed'),
(4, 25, 2, 20, 39.00, '2025-03-16 16:19:34', 'card', 'pay_Q7WdHtJJlw2UcG', 'completed'),
(5, 26, 2, 20, 39.00, '2025-03-16 16:23:48', 'card', 'pay_Q7WhlAAhwt5cqE', 'completed'),
(6, 27, 2, 20, 39.00, '2025-03-16 16:45:41', 'card', 'pay_Q7X4tABkGkXC99', 'completed'),
(7, 28, 99, 20, 39.00, '2025-03-16 17:18:00', 'card', 'pay_Q7Xd2kNIHDGz2w', 'completed'),
(8, 29, 99, 20, 39.00, '2025-03-16 18:40:49', 'card', 'pay_Q7Z2W2NnFyZsue', 'completed'),
(9, 30, 99, 20, 39.00, '2025-03-16 18:47:33', 'card', 'pay_Q7Z9ZtwyL53hz8', 'completed'),
(10, 31, 99, 14, 30.00, '2025-03-16 18:49:35', 'card', 'pay_Q7ZBnKJZekXk6b', 'completed'),
(11, 33, 99, 14, 30.00, '2025-03-16 19:04:23', 'card', 'pay_Q7ZRPsEnrK5S2t', 'completed'),
(12, 36, 99, 20, 39.00, '2025-03-16 19:26:36', 'card', 'pay_Q7Zot4ehF8g7cK', 'completed'),
(13, 39, 99, 20, 39.00, '2025-03-16 19:38:36', 'card', 'pay_Q7a1ZSzEaKCTsI', 'completed'),
(14, 40, 99, 20, 39.00, '2025-03-16 19:41:10', 'card', 'pay_Q7a4HHD7VeICoj', 'completed'),
(15, 41, 99, 20, 39.00, '2025-03-16 19:43:28', 'card', 'pay_Q7a6hCdDNLgSQ4', 'completed'),
(16, 43, 99, 20, 39.00, '2025-03-16 19:51:16', 'card', 'pay_Q7aEL6QqUHpnYl', 'completed'),
(17, 44, 2, 20, 39.00, '2025-03-17 10:26:53', 'card', 'pay_Q7p9sjOqaIW0YN', 'completed'),
(18, 45, 2, 20, 39.00, '2025-03-17 10:28:38', 'card', 'pay_Q7pBkNwsDk6gvO', 'completed'),
(19, 46, 99, 14, 30.00, '2025-03-17 10:37:10', 'card', 'pay_Q7pKkO2scbKwPP', 'completed'),
(20, 47, 2, 14, 90.00, '2025-03-17 15:24:51', 'card', 'pay_Q7uEeiJ965tZu0', 'completed'),
(21, 48, 2, 20, 39.00, '2025-03-17 15:30:34', 'card', 'pay_Q7uKeCutEFPP5f', 'completed'),
(22, 49, 2, 20, 39.00, '2025-03-17 15:36:07', 'card', 'pay_Q7uPcWAeWAq5W5', 'completed'),
(23, 50, 2, 20, 39.00, '2025-03-17 15:48:55', 'card', 'pay_Q7ue3ww0q0cwjI', 'completed'),
(24, 51, 2, 20, 39.00, '2025-03-17 15:54:25', 'card', 'pay_Q7ujfH6YntH4nm', 'completed'),
(25, 52, 2, 20, 39.00, '2025-03-17 16:00:54', 'card', 'pay_Q7uqjmNFZib6rc', 'completed'),
(26, 53, 2, 20, 39.00, '2025-03-17 16:04:59', 'card', 'pay_Q7uv20vsKufEzJ', 'completed'),
(27, 54, 2, 20, 39.00, '2025-03-17 16:08:22', 'card', 'pay_Q7uydKm25fJAHP', 'completed'),
(28, 55, 2, 20, 39.00, '2025-03-17 16:18:23', 'card', 'pay_Q7v9CIvYRAcnaG', 'completed'),
(29, 56, 2, 20, 39.00, '2025-03-17 16:22:04', 'card', 'pay_Q7vD5Ya7eKH7Wr', 'completed'),
(30, 57, 2, 20, 39.00, '2025-03-17 16:29:39', 'card', 'pay_Q7vL4U1MKSDMag', 'completed'),
(31, 58, 2, 20, 39.00, '2025-03-17 16:41:35', 'card', 'pay_Q7vXimZGpOm3sN', 'completed'),
(32, 59, 2, 20, 39.00, '2025-03-17 16:48:33', 'card', 'pay_Q7vf4reQx8MZkK', 'completed'),
(33, 60, 2, 20, 39.00, '2025-03-17 17:02:07', 'card', 'pay_Q7vtKOOFSRCMGr', 'completed'),
(34, 63, 2, 19, 3000.00, '2025-03-17 17:21:05', 'card', 'pay_Q7wDRKJ0YfNc66', 'completed'),
(35, 64, 2, 20, 39.00, '2025-03-17 18:20:12', 'card', 'pay_Q7xDrktq2iuDOp', 'completed'),
(36, 65, 2, 20, 39.00, '2025-03-17 18:35:56', 'card', 'pay_Q7xUUSm8T80bhK', 'completed'),
(37, 66, 2, 20, 39.00, '2025-03-18 04:24:53', 'card', 'pay_Q87WarAt12ZkF5', 'completed'),
(38, 67, 2, 15, 30.00, '2025-03-18 04:53:45', 'card', 'pay_Q8817bBJWp2Gbp', 'completed'),
(39, 68, 2, 15, 30.00, '2025-03-18 04:57:08', 'card', 'pay_Q884fI893ZdtfA', 'completed'),
(42, 69, 99, 20, 156.00, '2025-03-18 14:58:23', 'card', 'pay_Q8IHetm0cXTi8N', 'completed'),
(43, 70, 99, 20, 156.00, '2025-03-18 15:11:27', 'card', 'pay_Q8IXeec1W29MIu', 'completed'),
(44, 71, 99, 20, 39.00, '2025-03-18 15:15:19', 'card', 'pay_Q8Ibj32XxU5Azo', 'completed'),
(45, 72, 2, 15, 120.00, '2025-03-18 15:28:16', 'card', 'pay_Q8IpO0GQ0u1y8c', 'completed'),
(46, 73, 99, 20, 156.00, '2025-03-18 15:46:03', 'card', 'pay_Q8J8C3Bh0ng1X1', 'completed'),
(47, 74, 99, 20, 156.00, '2025-03-18 16:13:06', 'card', 'pay_Q8Jalyl7eh6JZv', 'completed'),
(48, 75, 2, 20, 39.00, '2025-03-19 02:13:02', 'card', 'pay_Q8ToUcD9e7Dgjd', 'completed'),
(49, 76, 2, 15, 30.00, '2025-03-19 02:20:33', 'card', 'pay_Q8TwQeTKpG9Tt2', 'completed'),
(50, 77, 2, 15, 30.00, '2025-03-19 02:26:15', 'card', 'pay_Q8U2SIWKo6ule8', 'completed'),
(51, 78, 2, 15, 30.00, '2025-03-19 02:34:52', 'card', 'pay_Q8UBb65vdBHMtm', 'completed'),
(52, 79, 2, 15, 120.00, '2025-03-19 08:11:45', 'card', 'pay_Q8ZvDtGCK3eaw6', 'completed'),
(53, 80, 2, 20, 39.00, '2025-03-19 08:34:34', 'card', 'pay_Q8aJUkYDihZuoH', 'completed'),
(54, 81, 99, 21, 4000.00, '2025-03-19 09:05:46', 'card', 'pay_Q8aqQl842oE0e0', 'completed'),
(55, 83, 2, 21, 1000.00, '2025-03-19 09:47:13', 'card', 'pay_Q8bYCjkqUgCsFQ', 'completed'),
(56, 84, 99, 21, 1000.00, '2025-03-19 13:46:31', 'card', 'pay_Q8fd1pNWIsbCjy', 'completed'),
(57, 85, 99, 21, 1000.00, '2025-03-19 13:58:44', 'card', 'pay_Q8fppPBZ99IIp9', 'completed'),
(59, 86, 99, 21, 1000.00, '2025-03-19 14:42:46', 'card', 'pay_Q8gZM5kS04BRmo', 'completed'),
(60, 87, 99, 21, 1000.00, '2025-03-19 14:59:56', 'card', 'pay_Q8gsZUKQGQD8Ub', 'completed'),
(61, 88, 99, 21, 1000.00, '2025-03-19 15:20:34', 'card', 'pay_Q8hENkUTDnOdNQ', 'completed'),
(62, 89, 99, 21, 1000.00, '2025-03-19 15:29:38', 'card', 'pay_Q8hNwKn1f6ZdB2', 'completed'),
(63, 90, 99, 21, 1000.00, '2025-03-19 15:40:35', 'card', 'pay_Q8hZWIp4DOoTcx', 'completed'),
(64, 91, 99, 21, 1000.00, '2025-03-19 15:55:23', 'card', 'pay_Q8hp82YYzxalDk', 'completed'),
(65, 92, 99, 21, 1000.00, '2025-03-19 16:00:12', 'card', 'pay_Q8huGAcyDQ5R2J', 'completed'),
(66, 93, 2, 21, 1000.00, '2025-03-19 17:51:46', 'card', 'pay_Q8jo5HfwvEbkro', 'completed'),
(67, 94, 99, 21, 1000.00, '2025-03-19 17:53:12', 'card', 'pay_Q8jpY1lytYoHE4', 'completed'),
(68, 95, 99, 21, 1000.00, '2025-03-19 17:59:36', 'card', 'pay_Q8jwMBRoeI7H79', 'completed'),
(69, 97, 2, 21, 1000.00, '2025-03-20 02:30:43', 'card', 'pay_Q8scwOHlMWLMa4', 'completed'),
(70, 98, 2, 21, 1000.00, '2025-03-20 02:35:10', 'card', 'pay_Q8sizOzag9gufS', 'completed'),
(71, 99, 2, 21, 1000.00, '2025-03-20 02:43:21', 'card', 'pay_Q8srdaaDQor3j2', 'completed'),
(74, 100, 2, 21, 3000.00, '2025-03-20 08:06:28', 'card', 'pay_Q8yFMFZDPZHvVh', 'completed'),
(75, 101, 2, 21, 1000.00, '2025-03-20 08:56:05', 'card', 'pay_Q8zDMvQ46aZ3Bu', 'completed'),
(76, 102, 99, 21, 1000.00, '2025-03-20 09:49:37', 'card', 'pay_Q907tMcQQHzKKm', 'completed'),
(77, 103, 2, 21, 1000.00, '2025-03-22 04:33:09', 'card', 'pay_Q9hnovOj3Sx4mZ', 'completed'),
(80, 105, 2, 22, 100.00, '2025-03-22 06:08:54', 'card', 'pay_Q9jR0fWEbqhfon', 'completed'),
(81, 106, 2, 22, 100.00, '2025-03-22 06:12:40', 'card', 'pay_Q9jUziZpzuqUe6', 'completed'),
(84, 108, 2, 22, 100.00, '2025-03-22 06:19:13', 'card', 'pay_Q9jabiKcbeZoqD', 'completed'),
(85, 109, 2, 22, 100.00, '2025-03-22 06:22:24', 'card', 'pay_Q9jfCC2F8RvliC', 'completed'),
(88, 110, 2, 22, 100.00, '2025-03-22 06:29:16', 'card', 'pay_Q9jizK2dhC580m', 'completed'),
(89, 111, 2, 22, 100.00, '2025-03-22 06:31:32', 'card', 'pay_Q9jovUpErqri7s', 'completed'),
(90, 112, 2, 22, 100.00, '2025-03-22 06:34:20', 'card', 'pay_Q9jrsIc6m1jvXl', 'completed'),
(91, 113, 2, 22, 100.00, '2025-03-22 06:37:04', 'card', 'pay_Q9juma1li7PXet', 'completed'),
(92, 114, 2, 22, 100.00, '2025-03-22 06:39:59', 'card', 'pay_Q9jxpsgyDczjmO', 'completed'),
(93, 115, 2, 22, 100.00, '2025-03-22 06:43:25', 'card', 'pay_Q9k1T1LjgvKEou', 'completed'),
(94, 116, 2, 22, 100.00, '2025-03-22 06:46:57', 'card', 'pay_Q9k5BHkiTGixtB', 'completed'),
(95, 117, 2, 22, 100.00, '2025-03-22 06:50:44', 'card', 'pay_Q9k9BkohNs8kBS', 'completed'),
(96, 118, 2, 22, 100.00, '2025-03-22 06:53:44', 'card', 'pay_Q9kCMo3bIH7LVO', 'completed'),
(97, 119, 2, 22, 100.00, '2025-03-22 06:56:39', 'card', 'pay_Q9kFRLAnVEyb0K', 'completed'),
(98, 120, 111, 22, 100.00, '2025-03-23 15:55:10', 'netbanking', 'pay_QAHxOIXg6qrAGp', 'completed'),
(99, 121, 113, 22, 300.00, '2025-03-25 04:58:07', 'netbanking', 'pay_QAtpWk30ebaDpT', 'completed'),
(100, 122, 2, 22, 100.00, '2025-03-26 05:34:58', 'card', 'pay_QBIzeAftiF72Oh', 'completed'),
(101, 123, 2, 22, 100.00, '2025-03-26 05:43:59', 'card', 'pay_QBJ989ggSrQ9Be', 'completed'),
(102, 124, 2, 22, 100.00, '2025-03-26 05:57:26', 'card', 'pay_QBJNOPfamp5Lc5', 'completed'),
(103, 125, 2, 22, 100.00, '2025-03-26 06:01:00', 'card', 'pay_QBJR9ys2woWInh', 'completed'),
(104, 126, 2, 22, 100.00, '2025-03-26 06:03:46', 'card', 'pay_QBJU55HloUplej', 'completed'),
(105, 127, 2, 22, 100.00, '2025-03-26 06:09:21', 'card', 'pay_QBJZy8UnA6rH10', 'completed'),
(106, 128, 2, 22, 100.00, '2025-03-26 06:41:34', 'netbanking', 'pay_QBK82DI4oVeMpv', 'completed'),
(107, 129, 2, 22, 100.00, '2025-03-26 06:46:16', 'netbanking', 'pay_QBKD0MWoLhFhTt', 'completed'),
(108, 130, 2, 22, 100.00, '2025-03-26 06:51:05', 'netbanking', 'pay_QBKI6F31XnlaWE', 'completed'),
(109, 131, 2, 22, 100.00, '2025-03-26 06:57:15', 'netbanking', 'pay_QBKOb3P68iK3LU', 'completed'),
(110, 132, 2, 22, 100.00, '2025-03-26 08:19:26', 'netbanking', 'pay_QBLnLCzp4zLh6h', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remember_tokens`
--

INSERT INTO `remember_tokens` (`id`, `user_id`, `token`, `expires`, `created_at`) VALUES
(1, 39, 'e04c208453b8cad3133feff60ec00a1137287fe7f624a2abc00aa3e0e35fdfba', '2025-04-08 17:47:49', '2025-03-09 16:47:49'),
(2, 47, '5b61b50382e5345e04b195856b122c2c2f6e3207e1e1969610efaa2f40aee893', '2025-04-08 17:48:13', '2025-03-09 16:48:13');

-- --------------------------------------------------------

--
-- Table structure for table `station_owner_requests`
--

CREATE TABLE `station_owner_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `business_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) NOT NULL,
  `state` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `business_registration` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `station_owner_requests`
--

INSERT INTO `station_owner_requests` (`request_id`, `user_id`, `owner_name`, `business_name`, `email`, `phone`, `address`, `city`, `state`, `postal_code`, `business_registration`, `password_hash`, `status`, `created_at`, `updated_at`) VALUES
(1, 33, 'real', 'jkernaj', 'real@gmail.com', '7896541235', '7jvbvhjkjhk', 'hgvkgu vkg', 'bjb jh j', '789654', 'kjkjb hjh j', '$2y$10$8vY7YkmqFpiO1aacbxfn8.6dyGmrQFTpiS.bgACrelJU8BQZXAnt2', 'approved', '2025-02-26 04:29:57', '2025-02-26 04:31:18'),
(2, 34, 'achu', 'sample1', 'achu20@gmail.com', '789546321', 'jkdsfvnjfn', 'mkksd fwk', 'sddjv sj', '789654', '78965412', '$2y$10$/m6QiOTWPJGt0AJwZ/ng9uAhb0IAAneZr2ZReeiBwWkRXJM6V8rva', '', '2025-02-26 07:35:46', '2025-02-26 19:02:05'),
(3, 35, 'alex', 'asxjhgfd', 'alex23@gmail.com', '7418529635', 'xcvbnm sdfghjk dfghjkl', 'ghjklghjkl', 'wertyuio', '789654', '74108520', '$2y$10$08IL1G4opKaqnh/5rsoQke.cJAsZwbY97TXKQxKddAqdmwA2cDEQm', 'pending', '2025-02-26 07:46:28', '2025-02-26 07:46:28'),
(4, 36, 'ewq', 'ytfds', 'qew@gmail.com', '7418529635', ',klmiujnybhtgv', 'juhgfd', 'iuytre', '741852', '963852741', '$2y$10$3Xc87A2ycEfeCM1toRlXRuP/bD.4qMgWJ/iXFu0TntPMiTx0xl0d6', 'rejected', '2025-02-26 07:48:36', '2025-02-27 05:42:33'),
(5, 37, 'jino', 'treds', 'jino2@gmail.com', '7418529635', 'gfds rtefdx erfd', 'gfds', 'ertfdcx', '741852', '74185296', '$2y$10$Ovg5YTkDLvWf80jmGdKOheUin/FUvftjQjhnlORVjp.nFJrg5qxMe', 'approved', '2025-02-26 08:00:55', '2025-02-26 08:38:30'),
(6, 39, 'vinod', 'wer', 'vinod@gmail.com', '7894568527', 'jhgfd', 'hjgfdsa', 'jhmngbfvdcs', '741', 'hgbfvdcs', '$2y$10$IJVoXgfyaNJLbfwikC5q.e0xba.dKX2I3YEHVFSARzk8h2E5L1sfi', 'approved', '2025-02-26 15:00:35', '2025-02-26 19:03:11'),
(7, 40, 'abin', 'charging station', 'abin@gmail.com', '7896541235', 'dhjqbdqqbfjbdq lqdb qh', 'ajnfiweqndq', 'hjkl', '74185', '744444444', '$2y$10$4BmUqnLWOOLNrTXLYXFPQ.ddG3CZb9XXQiG9eH6auaNRwWAGjXj2G', '', '2025-02-27 01:54:07', '2025-02-27 05:54:31'),
(8, 43, 'abin', 'Electron', 'abin2311@gmail.com', '9645825745', 'aaaaaaaaaaa', 'aaaa', 'aaaaa', '99445', '789456', '$2y$10$KwanSzp15e4kHycyQQvCVOhMLLS2a344SEW3HsC7DjqkjkygnnD/i', 'approved', '2025-02-27 05:41:34', '2025-02-27 05:42:42'),
(9, 44, 'alen', '7s7dfgh8', 'alen@gmail.com', '7418529999', 'fdvdgdsbs agaba', 'ssbsn', 'mhhmm', '65454', 'hfhvvnv', '$2y$10$w/WKrhHYz.ylcDhPFlOgxuZUz4AmMRgrBIl6oAr97r1bnxDCkxIbu', 'approved', '2025-02-27 05:57:57', '2025-02-27 05:58:14'),
(10, 47, 'www', NULL, 'www@gmail.com', '741-852-9696', 'wertghjkl;', 'qwertyujk', 'qwertyui', '741852', '74185', '$2y$10$nOAC3PvuUvbcwkkQBP4XuOPt.FG0cW5ROBscWmW3oICG74O5jyOi.', 'approved', '2025-03-09 16:36:31', '2025-03-09 16:40:56'),
(11, 52, 'Tom Shibu', NULL, 'tomshibu52@gmail.com', '090-727-8498', 'Thiruvambady Alphonse college road', 'Thiruvambady', 'Kerala', '673603', '741852', '$2y$10$W.8bsf2FiAGqR0pBjZCHDu1GDQ4iBD32p.cxtMD8mVQJ/eWWF0s5i', 'approved', '2025-03-09 17:04:55', '2025-03-09 17:05:37'),
(12, 59, 'gfds', NULL, 'aaa@gmail.com', '789-456-1255', 'qwertyui', 'wertyuio', 'wertyuio', '741852', '741852', '', 'pending', '2025-03-11 03:48:03', '2025-03-11 03:48:03'),
(13, 60, 'hgfds', NULL, 'kkk@gmail.com', '741-852-9696', 'qwertyuio', 'wertyuio', 'ertyui', '741788', '174852', '', 'pending', '2025-03-11 03:53:05', '2025-03-11 03:53:05'),
(14, 61, 'hgfd', NULL, 'lll@gmail.com', '741-787-5999', 'qwertyui', 'qwertyui', 'wertyui', '741852', '741852', '', 'pending', '2025-03-11 03:58:47', '2025-03-11 03:58:47'),
(15, 62, 'qertyuio', NULL, 'werty@gmail.com', '949-578-4989', 'Kallookulangara(H) Thiruvambady(PO)Kozhikode\nKallookulangara(H) Thiruvambady(PO)Kozhikode', 'Kozhikode', 'KERALA', '673603', '444444', '$2y$10$LgmTucJd7CuVWBB/ZwCoyuN/BVN1GISY9.PxU09cDE5dNdaze0dqC', 'pending', '2025-03-11 17:17:41', '2025-03-11 17:17:41'),
(16, 68, 'Aibal', '', 'aibal@gmail.com', '907-278-4987', 'Thiruvambady Alphonse college road', 'Thiruvambady', 'Kerala', '673603', '7418585', '$2y$10$e7Kw5DVIncOOWuylMaM5FuSwSo6ZLKwkiMIZZBKyJ6x/ao1dEyTxS', 'approved', '2025-03-12 06:19:28', '2025-03-12 15:06:12'),
(17, 1, 'Tom Shibu', '', 'tomshibu666@gmail.com', '9072784982', 'ghfdtgf', 'tyredws', 'tyrew', '741785', '745185', '$2y$10$J9oKCpDjy3DJS2EX0jP22OK/.BE27WgkctnzvJWn6/6ET5XlltiVm', 'rejected', '2025-03-12 06:25:27', '2025-03-12 06:25:52'),
(18, 2, 'tomshibu', '', 'tomshibu49@gmail.com', '9072784982', 'Thiruvambady Alphonse college road', 'Thiruvambady', 'Kerala', '673603', '741852', '$2y$10$lenI7O8z/P18G.4tdpm0ye/5BNNmejg2AY6zz2CLqEJMngzuXru1i', 'approved', '2025-03-16 04:20:53', '2025-03-16 04:21:35');

-- --------------------------------------------------------

--
-- Table structure for table `station_reviews`
--

CREATE TABLE `station_reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review_text` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `owner_response` text DEFAULT NULL,
  `response_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `station_reviews`
--

INSERT INTO `station_reviews` (`id`, `user_id`, `station_id`, `rating`, `review_text`, `created_at`, `updated_at`, `owner_response`, `response_date`) VALUES
(1, 2, 20, 4, 'wwww', '2025-03-21 15:32:57', NULL, NULL, NULL),
(2, 111, 22, 3, 'etyuiop[', '2025-03-23 21:30:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `passwordhash` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiration` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `email`, `passwordhash`, `name`, `username`, `phone_number`, `profile_picture`, `is_admin`, `created_at`, `status`, `updated_at`, `verification_token`, `token_expiry`, `otp`, `otp_expiration`, `is_verified`) VALUES
(1, 'tomshibu666@gmail.com', '$2y$10$rJfogzS3eGSJDGj6nTiIUOHF1.UtACTiZv4LnlrCWyhNT4KpIq54.', 'Tom Tom Tom', 'tomshibu1829', '9072784982', 'uploads/images.jpeg', 1, '2025-02-19 17:38:39', 'active', '2025-04-02 05:38:40', NULL, NULL, NULL, NULL, 0),
(2, 'tomshibu49@gmail.com', '$2y$10$lenI7O8z/P18G.4tdpm0ye/5BNNmejg2AY6zz2CLqEJMngzuXru1i', 'tomshibu', 'tomshibu', '9072784982', 'uploads/WhatsApp Image 2024-10-02 at 08.31.34_3d8d0416 (1).jpg', 0, '2025-02-19 17:42:07', 'active', '2025-02-22 11:50:57', NULL, NULL, NULL, NULL, 0),
(10, 'joel@gmail.com', '$2y$10$/KSYPDhUWWx3mBMk1DNmA.5j8lNWIC9KbXlAYA11tWCCRiEV0LIaq', 'Joel Martin ', 'Joel', '8889996547', NULL, 0, '2025-02-23 13:39:41', 'active', '2025-02-23 13:40:52', NULL, NULL, NULL, NULL, 0),
(12, 'alex@gmail.com', '$2y$10$Nm9KYHe95sBW7U.sKCw5.uTEF7iWRHkuOX9EooUn26t/ndLTINDUC', '', 'Alex', NULL, NULL, 0, '2025-02-23 13:42:06', 'active', '2025-02-23 13:42:06', NULL, NULL, NULL, NULL, 0),
(14, 'new@gmail.com', '$2y$10$av/.Bp8LFdMSSvPPG6ic/OIE6cPTgNfHDw.qM31qwgP4f1NdIBWcC', '', 'new', NULL, NULL, 0, '2025-02-23 13:44:12', 'active', '2025-02-23 13:44:12', NULL, NULL, NULL, NULL, 0),
(16, 'newq@gmail.com', '$2y$10$rOgY3lHMHhQRh317pJGuIO6y5pLUlnE4/0.2JmZkGsAtfojcRQGZa', '', 'new', NULL, NULL, 0, '2025-02-23 13:45:50', 'active', '2025-02-23 13:45:50', NULL, NULL, NULL, NULL, 0),
(18, 'sample@gmail.com', '$2y$10$oGV0gdCzMt27sffaE50tCOQsSmNPGp8mRGPG./IIQ.7sQnBntcAtC', '', 'sample', NULL, NULL, 0, '2025-02-23 13:47:14', 'active', '2025-02-23 13:47:14', NULL, NULL, NULL, NULL, 0),
(20, 'milan@gmail.com', '$2y$10$141N0.IOeFob6jh/Jmw8cOfwiCjinOQFJfclRDGg80WA3/NxPL/uq', '', 'milan', NULL, NULL, 0, '2025-02-23 13:51:00', 'active', '2025-02-23 13:51:00', NULL, NULL, NULL, NULL, 0),
(22, 'hii@gmail.com', '$2y$10$ucKI/8cOXmx7WNTMl35nYe1ax/iXWdcbrwdJ6JiZfcFaMmMk5cWS6', '', 'hii', NULL, NULL, 0, '2025-02-25 02:15:53', 'active', '2025-02-25 02:15:53', NULL, NULL, NULL, NULL, 0),
(31, 'ewer2@gmail.com', '$2y$10$/tNvccXoGCIfj/BED6LKzeypQhHSJHOblr./DlFmXye9K3prJLM.q', 'rere', 'sjdjnfgsi', NULL, NULL, 0, '2025-02-26 04:20:12', 'active', '2025-02-26 04:20:12', NULL, NULL, NULL, NULL, 0),
(32, 'melbin@gmail.com', '$2y$10$cYF/d97B/rRwjM9qIFdkLu8BQteSbKCooluvvhwRjv/pW3aE82Ly.', 'Melbin', 'melbin', NULL, NULL, 0, '2025-02-26 04:22:35', 'active', '2025-02-26 04:22:35', NULL, NULL, NULL, NULL, 0),
(33, 'real@gmail.com', '$2y$10$8vY7YkmqFpiO1aacbxfn8.6dyGmrQFTpiS.bgACrelJU8BQZXAnt2', 'real', 'real', NULL, NULL, 0, '2025-02-26 04:29:57', 'active', '2025-02-26 04:29:57', NULL, NULL, NULL, NULL, 0),
(34, 'achu20@gmail.com', '$2y$10$/m6QiOTWPJGt0AJwZ/ng9uAhb0IAAneZr2ZReeiBwWkRXJM6V8rva', 'achu', 'achu20', NULL, NULL, 0, '2025-02-26 07:35:46', 'active', '2025-02-26 07:35:46', NULL, NULL, NULL, NULL, 0),
(35, 'alex23@gmail.com', '$2y$10$08IL1G4opKaqnh/5rsoQke.cJAsZwbY97TXKQxKddAqdmwA2cDEQm', 'alex', 'alex12', NULL, NULL, 0, '2025-02-26 07:46:28', 'active', '2025-02-26 07:46:28', NULL, NULL, NULL, NULL, 0),
(36, 'qew@gmail.com', '$2y$10$3Xc87A2ycEfeCM1toRlXRuP/bD.4qMgWJ/iXFu0TntPMiTx0xl0d6', 'ewq', 'rewq', NULL, NULL, 0, '2025-02-26 07:48:36', 'active', '2025-02-26 07:48:36', NULL, NULL, NULL, NULL, 0),
(37, 'jino2@gmail.com', '$2y$10$Ovg5YTkDLvWf80jmGdKOheUin/FUvftjQjhnlORVjp.nFJrg5qxMe', 'jino', 'jino', NULL, NULL, 0, '2025-02-26 08:00:55', 'active', '2025-02-26 08:00:55', NULL, NULL, NULL, NULL, 0),
(39, 'vinod@gmail.com', '$2y$10$IJVoXgfyaNJLbfwikC5q.e0xba.dKX2I3YEHVFSARzk8h2E5L1sfi', 'vinod', 'vinod', NULL, NULL, 0, '2025-02-26 15:00:35', 'active', '2025-02-26 18:49:29', NULL, NULL, NULL, NULL, 0),
(40, 'abin@gmail.com', '$2y$10$4BmUqnLWOOLNrTXLYXFPQ.ddG3CZb9XXQiG9eH6auaNRwWAGjXj2G', 'abin', 'abin', NULL, NULL, 0, '2025-02-27 01:54:07', 'active', '2025-02-27 01:54:07', NULL, NULL, NULL, NULL, 0),
(41, 'abin23@gmail.com', '$2y$10$0x/tEnP7tzP4P4fXvoEY/uIiXn7jfmx2UO3dxN6Dezalu3RFpbBcO', '', 'abin23', NULL, NULL, 0, '2025-02-27 05:34:17', 'active', '2025-02-27 05:34:17', NULL, NULL, NULL, NULL, 0),
(43, 'abin2311@gmail.com', '$2y$10$KwanSzp15e4kHycyQQvCVOhMLLS2a344SEW3HsC7DjqkjkygnnD/i', 'abin', 'abin2311', NULL, NULL, 0, '2025-02-27 05:41:34', 'active', '2025-02-27 05:41:34', NULL, NULL, NULL, NULL, 0),
(44, 'alen@gmail.com', '$2y$10$w/WKrhHYz.ylcDhPFlOgxuZUz4AmMRgrBIl6oAr97r1bnxDCkxIbu', 'alen', 'alen', NULL, NULL, 0, '2025-02-27 05:57:57', 'active', '2025-02-27 05:57:57', NULL, NULL, NULL, NULL, 0),
(47, 'www@gmail.com', '$2y$10$nOAC3PvuUvbcwkkQBP4XuOPt.FG0cW5ROBscWmW3oICG74O5jyOi.', 'www', 'wwww', '741-852-9696', NULL, 0, '2025-03-09 16:36:31', 'active', '2025-03-09 16:36:31', NULL, NULL, NULL, NULL, 0),
(52, 'tomshibu52@gmail.com', '$2y$10$W.8bsf2FiAGqR0pBjZCHDu1GDQ4iBD32p.cxtMD8mVQJ/eWWF0s5i', 'Tom Shibu', 'tomshibu522', '090-727-8498', NULL, 0, '2025-03-09 17:04:55', 'active', '2025-03-09 17:04:55', NULL, NULL, NULL, NULL, 0),
(55, 'mazin@gmail.com', '$2y$10$iYtnU2mwcEM/fxFYpiJ.kOgKjdOKGPonmUHGAU74s9xX/J0AwOkXC', '', 'mazin', NULL, NULL, 0, '2025-03-09 17:37:49', 'active', '2025-03-09 17:37:49', NULL, NULL, NULL, NULL, 0),
(56, 'akash@gmail.com', '$2y$10$IiY0ffvBVWTdOOfaB3.eD.oc0.5WpKyI1hgRQT3N7d1W/aconArwS', '', 'akash', NULL, NULL, 0, '2025-03-09 18:25:10', 'active', '2025-03-09 18:25:10', NULL, NULL, NULL, NULL, 0),
(59, 'aaa@gmail.com', '$2y$10$B4fW42SKpdGdaW3aw71aZee0x5GgIwPOHtLRelR.g1ZGSOfd7HVSG', 'gfds', 'qwertyu', '789-456-1255', NULL, 0, '2025-03-11 03:48:03', 'active', '2025-03-11 03:48:03', NULL, NULL, NULL, NULL, 0),
(60, 'kkk@gmail.com', '$2y$10$D098KgOB3vuRNX13kqSBCuXyZulbC/pfMygODD6aQiCwAC9xpdXCu', 'hgfds', 'ghsdfs', '741-852-9696', NULL, 0, '2025-03-11 03:53:05', 'active', '2025-03-11 03:53:05', NULL, NULL, NULL, NULL, 0),
(61, 'lll@gmail.com', '$2y$10$c/GRZksWOa4nTxyIlRkPguASoR3Ih1eWO3DoDYGuihEMAZICZDui6', 'hgfd', 'trew', '741-787-5999', NULL, 0, '2025-03-11 03:58:47', 'active', '2025-03-11 03:58:47', NULL, NULL, NULL, NULL, 0),
(62, 'werty@gmail.com', '$2y$10$nZd2xo3XtA5ljaEnLzWitOLqMs.IE992GfdsRI7v7eyH072cEydB.', 'qertyuio', 'dfghjk', '949-578-4989', NULL, 0, '2025-03-11 17:17:41', 'active', '2025-03-11 17:17:41', NULL, NULL, NULL, NULL, 0),
(63, 'amal@gmail.com', '$2y$10$s2ItP3FhnA2.V0svG1WXKuK/ISKTrdl/08JyBoPpcWn6JYLtCIM7K', 'Amal', 'amal', '963785428', NULL, 0, '2025-03-11 17:32:59', 'active', '2025-03-11 17:34:51', NULL, NULL, NULL, NULL, 0),
(64, 'sdfsn@gmail.com', '$2y$10$BTaehnrhXRkiwhxJn86xtu2JiGHsVoTa.xXTIk53QBr8v4DBbrgpK', '', 'dfss', NULL, NULL, 0, '2025-03-11 18:09:21', 'active', '2025-03-11 18:09:21', NULL, NULL, NULL, NULL, 0),
(67, 'ggg@gmail.com', '$2y$10$0AIi6g5xjsk1Q48daxMCruXgmSO5b3A1h0m75kkOVU4BybpYMxXzm', 'ggg', 'gggg', '9638527417', NULL, 0, '2025-03-11 18:27:42', 'active', '2025-03-11 18:28:23', NULL, NULL, NULL, NULL, 0),
(68, 'aibal@gmail.com', '$2y$10$e7Kw5DVIncOOWuylMaM5FuSwSo6ZLKwkiMIZZBKyJ6x/ao1dEyTxS', 'Aibal', 'aibal', '', 'uploads/WhatsApp Image 2025-03-08 at 22.19.01_1368a1d3.jpg', 0, '2025-03-12 06:17:46', 'active', '2025-03-12 06:38:27', NULL, NULL, NULL, NULL, 0),
(69, 'nnn@gmail.com', '$2y$10$e8qTKfsGVc1DfNurUbfsQe.czZ0IDsTEvy3HpBCNYPVCW.e7FSM5e', '', 'nnn', NULL, NULL, 0, '2025-03-12 06:49:22', 'active', '2025-03-12 06:49:22', NULL, NULL, NULL, NULL, 0),
(70, 'gfd@gmail.com', '$2y$10$vUlHSdS4mpf.p4kSI/dIb.nbjQQb.ovuDKt9LgDAWaIYePEGmDkjC', '', 'gfd', NULL, NULL, 0, '2025-03-12 15:23:36', 'active', '2025-03-12 15:23:36', NULL, NULL, NULL, NULL, 0),
(85, 'skillscout20@gmail.com', '$2y$10$2/5B/SOlridF435dMfhjE.ADnR2N.FJPue9wpZI0.x5HxEq5YFKnW', 'asdada', 'esfsaefd', '', NULL, 0, '2025-03-12 17:37:16', 'active', '2025-03-12 17:48:26', NULL, NULL, NULL, NULL, 1),
(86, 'tsdfs@gmail.com', '$2y$10$UvZu81BKyc.Wg0.hB6s8POVCSWEO5eMKaraemZsqu0.D.5EfxU0hC', '', 'vfvfv', NULL, NULL, 0, '2025-03-12 18:26:39', 'pending', '2025-03-12 18:26:39', NULL, NULL, '808548', '2025-03-12 19:36:38', 0),
(87, 'tsdfds@gmail.com', '$2y$10$6DzmOPi27FuFvzcaA1sFfO5UC9wUwp/4E1ECbADUB665EAgoD2INK', '', 'vfvfe', NULL, NULL, 0, '2025-03-12 18:27:44', 'pending', '2025-03-12 18:27:44', NULL, NULL, '542801', '2025-03-12 19:37:44', 0),
(88, 'sdfs@gmail.com', '$2y$10$qA073Zj6CdRh9SlEM0u/seADzVjH.1373X.Po3GFNluds/qoIazh6', '', 'sfsf', NULL, NULL, 0, '2025-03-12 18:31:19', 'pending', '2025-03-12 18:31:19', NULL, NULL, '339782', '2025-03-12 19:41:19', 0),
(90, 'dfgh@gmail.com', '$2y$10$xvYoUNlS/ch4mShJnQO.a.qvn4qAt4ATZVdHarSCMzuwqCKtem4Iq', '', 'dfghj', NULL, NULL, 0, '2025-03-13 09:02:58', 'pending', '2025-03-13 09:02:58', NULL, NULL, '825361', '2025-03-13 10:12:58', 0),
(91, 'aasas@gmail.com', '$2y$10$63l9VLYpsriofVjdDRA9t.u.9YLu31lbFb0XaImA/XCrluobEQrL.', '', 'asas', NULL, NULL, 0, '2025-03-13 09:15:37', 'pending', '2025-03-13 09:15:37', NULL, NULL, '358065', '2025-03-13 10:25:37', 0),
(92, 'assa@gmail.com', '$2y$10$FNKIp3WbthYEvKUXGLXnNeMTLo1T.WQirCaO0OE0D3eBOcw2W84zG', '', 'saasas', NULL, NULL, 0, '2025-03-13 09:16:22', 'pending', '2025-03-13 09:16:22', NULL, NULL, '262219', '2025-03-13 10:26:22', 0),
(93, 'sdds@gmail.com', '$2y$10$zeVCQ5j8sUCQPXCB0XPe2uoMN4Dh.ImBhjKigqLlh/.1BPPnB9o36', '', 'sdsd', NULL, NULL, 0, '2025-03-13 09:25:42', 'pending', '2025-03-13 09:25:42', NULL, NULL, '723611', '2025-03-13 10:35:42', 0),
(94, 'sdf@gmail.com', '$2y$10$sbf7.DpDPaEjPUr2BzKF.uliIzam/PEqaaMR7T9oZDyOOYt.yjW9G', '', 'dfg', NULL, NULL, 0, '2025-03-13 09:33:24', 'pending', '2025-03-13 09:33:24', NULL, NULL, '912026', '2025-03-13 10:43:24', 0),
(99, 'tomshibugenai2024@gmail.com', '$2y$10$.1DeC4CcVmLwuld8ADyxQuGgV6OXya9rSngUJNMXB.q1rvUJUcT8G', 'Tom Shibu', 'tomshibu18', '', NULL, 0, '2025-03-16 17:15:50', 'active', '2025-03-16 17:16:53', NULL, NULL, NULL, NULL, 1),
(100, 'mm@gmail.com', '$2y$10$GsyV7iFyaJwa7I6YaRCJr.2sDCpfBl7nwZaGV4Qrr1oZnpKo5zY.O', '', 'wert', NULL, NULL, 0, '2025-03-17 10:15:17', 'pending', '2025-03-17 10:15:17', NULL, NULL, '184947', '2025-03-17 11:25:17', 0),
(101, 'asd@gmail.com', '$2y$10$3Jw9pDCNfVXxWb/ea97fQeRr7SznTq9xAbPGyQrle81T1HeYYQCIK', '', 'sadas', NULL, NULL, 0, '2025-03-17 10:18:34', 'pending', '2025-03-17 10:18:34', NULL, NULL, '580805', '2025-03-17 11:28:33', 0),
(105, 'rehansnair@gmail.com', '$2y$10$XdEsOtvxeHhBJnPhiuIzrO9BhDAdoMTwqUfYRMRDYbzNpRw8jJMxC', '', 'rehan', NULL, NULL, 0, '2025-03-18 16:23:18', 'pending', '2025-03-18 16:23:18', NULL, NULL, '533761', '2025-03-18 17:33:18', 0),
(110, 'tomshibu2027@mca.ajce.in', '$2y$10$CQlI62kyuuMqe2wf4yTsj.K.sdTmQ3m0oOk/IM1Rr9yR7tCZcLod.', 'tom', 'tom', NULL, NULL, 0, '2025-03-22 04:15:51', 'active', '2025-03-22 04:15:51', NULL, NULL, NULL, NULL, 0),
(111, 'milanmathew6656@gmail.com', '$2y$10$5bvWiFkKYm2.pgkwibpGge0uhS7Uk5wJtHVLHnosmHYxVSdJmqMI2', 'milan07', 'milan07', NULL, NULL, 0, '2025-03-23 15:50:41', 'active', '2025-03-23 15:50:41', NULL, NULL, NULL, NULL, 0),
(113, 'sulu13198@gmail.com', '$2y$10$iueTfXNL5ju6CJDig9kISunn7PnmsL63D8YUHT9YVCmEvi3xZuuJq', 'Sulthana', 'Sulthana', NULL, NULL, 0, '2025-03-25 04:55:13', '', '2025-04-02 05:37:32', NULL, NULL, NULL, NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_user_booking` (`user_id`,`booking_date`),
  ADD KEY `idx_station_booking` (`station_id`,`booking_date`),
  ADD KEY `idx_razorpay_order` (`razorpay_order_id`);

--
-- Indexes for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `charging_stations`
--
ALTER TABLE `charging_stations`
  ADD PRIMARY KEY (`station_id`),
  ADD KEY `operator_id` (`operator_id`),
  ADD SPATIAL KEY `location` (`location`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`enquiry_id`),
  ADD KEY `idx_station_enquiry` (`station_id`),
  ADD KEY `idx_user_enquiry` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payment_details`
--
ALTER TABLE `payment_details`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_user_payment` (`user_id`),
  ADD KEY `idx_station_payment` (`station_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`),
  ADD KEY `expires` (`expires`);

--
-- Indexes for table `station_owner_requests`
--
ALTER TABLE `station_owner_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `station_reviews`
--
ALTER TABLE `station_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_station` (`user_id`,`station_id`),
  ADD KEY `station_id` (`station_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=133;

--
-- AUTO_INCREMENT for table `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `charging_stations`
--
ALTER TABLE `charging_stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `enquiry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT for table `payment_details`
--
ALTER TABLE `payment_details`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `station_owner_requests`
--
ALTER TABLE `station_owner_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `station_reviews`
--
ALTER TABLE `station_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`);

--
-- Constraints for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD CONSTRAINT `booking_logs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `booking_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `charging_stations`
--
ALTER TABLE `charging_stations`
  ADD CONSTRAINT `charging_stations_ibfk_1` FOREIGN KEY (`operator_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD CONSTRAINT `enquiries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`),
  ADD CONSTRAINT `enquiries_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `payment_details`
--
ALTER TABLE `payment_details`
  ADD CONSTRAINT `payment_details_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payment_details_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`),
  ADD CONSTRAINT `payment_details_ibfk_3` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `station_owner_requests`
--
ALTER TABLE `station_owner_requests`
  ADD CONSTRAINT `station_owner_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`);

--
-- Constraints for table `station_reviews`
--
ALTER TABLE `station_reviews`
  ADD CONSTRAINT `station_reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`),
  ADD CONSTRAINT `station_reviews_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
