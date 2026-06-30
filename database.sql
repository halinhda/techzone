-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 24, 2026 lúc 10:54 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `techzone_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `promo_price` decimal(15,0) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart_items`
--

INSERT INTO `cart_items` (`id`, `session_id`, `user_id`, `product_id`, `quantity`, `promo_price`, `created_at`) VALUES
(1, '1919ecce2ba8ad26ef14312b8febd36e', NULL, 1, 2, NULL, '2026-06-09 23:22:56'),
(6, 'c6a6d4404db1c3a92be48724c43d3e37', NULL, 5, 1, NULL, '2026-06-20 01:31:32'),
(7, '2341b4b4b363ba08cf112e1552751a1b', 1, 1, 1, NULL, '2026-06-23 23:16:21'),
(8, '2341b4b4b363ba08cf112e1552751a1b', 1, 3, 1, NULL, '2026-06-23 23:28:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(10) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Laptop & Máy tính', 'laptop', '💻'),
(2, 'Điện thoại & Tablet', 'phone', '📱'),
(3, 'Âm thanh & Loa', 'audio', '🎧'),
(4, 'Phụ kiện công nghệ', 'accessory', '⌨️');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `note` text DEFAULT NULL,
  `subtotal` decimal(15,0) NOT NULL DEFAULT 0,
  `shipping_fee` decimal(15,0) NOT NULL DEFAULT 0,
  `total_price` decimal(15,0) NOT NULL DEFAULT 0,
  `payment_method` enum('qr','cod','momo') NOT NULL DEFAULT 'cod',
  `status` enum('Chờ xử lý','Đang giao','Đã hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ xử lý',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_address`, `note`, `subtotal`, `shipping_fee`, `total_price`, `payment_method`, `status`, `created_at`, `updated_at`) VALUES
(1, 'ORD-9842', NULL, 'Nguyễn Văn Hùng', '0912345678', 'Số 15 Tạ Quang Bửu, Hai Bà Trưng, Hà Nội', NULL, 6490000, 0, 120000, 'momo', 'Đã hoàn thành', '2026-06-17 23:01:41', '2026-06-23 23:01:41'),
(2, 'ORD-1294', NULL, 'Trần Thị Lan', '0388776655', 'Tháp B, chung cư Lexington, Quận 2, TP. Hồ Chí Minh', NULL, 3780000, 0, 560000, 'cod', 'Đã hoàn thành', '2026-06-18 23:01:41', '2026-06-23 23:01:41'),
(6, 'DH20260609210738546', 2, 'Nguyễn Kinh Quân', '0123485674', 'Thanh Xuân, Hà Nội', NULL, 45000000, 0, 45000000, 'cod', 'Đã hủy', '2026-06-22 23:01:41', '2026-06-23 23:01:41'),
(7, 'DH20260610100522310', 2, 'Nguyễn Khách Hàng', '0231465753', 'Thanh Xuân, Hà Nội', NULL, 45000000, 0, 45000000, 'cod', 'Đã hoàn thành', '2026-06-23 23:01:41', '2026-06-23 23:01:41'),
(8, 'DH20260610100630314', 2, 'Nguyễn Khách Hàng', '0907123456', 'Ba Đình, HN', NULL, 45000000, 0, 45000000, 'cod', 'Đã hủy', '2026-06-10 15:06:30', '2026-06-23 22:21:24'),
(9, 'DH20260623191636350', 2, 'Nguyễn Duy Khôi', '0123654789', 'Linh Xuân, HCM', NULL, 28990000, 0, 28990000, 'momo', 'Chờ xử lý', '2026-06-24 00:16:36', '2026-06-24 00:16:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `price` decimal(15,0) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `image_emoji` varchar(10) DEFAULT '?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `price`, `quantity`, `image_emoji`) VALUES
(1, 1, 5, 'Tai nghe Sony WH-1000XM5', 6490000, 1, '🎧'),
(2, 2, 6, 'Bàn Phím Cơ Keychron K2V2', 1890000, 2, '⌨️'),
(3, 6, 3, 'Laptop Dell XPS 15', 45000000, 1, '💻'),
(4, 7, 3, 'Laptop Dell XPS 15', 45000000, 1, '💻'),
(5, 8, 3, 'Laptop Dell XPS 15', 45000000, 1, '💻'),
(6, 9, 6, 'iPhone 15 Pro Max 256GB', 28990000, 1, '📱');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,0) NOT NULL DEFAULT 0,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_emoji` varchar(10) DEFAULT '?',
  `image_file` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 4.5,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `brand`, `description`, `price`, `stock`, `image_emoji`, `image_file`, `rating`, `featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'MacBook Air M2 13.6 inch', 'Apple', 'Sản phẩm được trang bị chip Apple M2 mạnh mẽ, thiết kế vỏ nhôm nguyên khối siêu mỏng nhẹ, màn hình Retina sắc nét cùng thời lượng pin lên đến 18 giờ liên tục.', 27490000, 14, '💻', 'macbookairm22022.webp', 4.8, 1, '2026-06-09 22:27:08', '2026-06-24 00:13:42'),
(2, 1, 'Asus ROG Strix G16 Gaming', 'Asus', 'Laptop chuyên dụng cho game thủ với bộ vi xử lý Intel Core thế hệ mới kết hợp cùng card đồ họa rời RTX 4060, mang lại hiệu năng xử lý đồ họa vượt trội.', 32990000, 8, '🎮', 'asusROGstrixg16gaming.webp', 4.7, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(3, 1, 'Laptop Dell XPS 15', 'Dell', 'Dòng máy tính xách tay cao cấp với màn hình OLED độ phân giải cao, viền siêu mỏng tạo không gian hiển thị rộng rãi và sang trọng.', 45000000, 9, '💻', 'laptopdellxps15.webp', 4.9, 1, '2026-06-09 22:27:08', '2026-06-23 23:28:08'),
(4, 1, 'Laptop HP Envy 13', 'HP', 'Máy tính xách tay với thiết kế thời trang, vỏ kim loại chắc chắn cùng hiệu năng ổn định, phù hợp cho nhu cầu văn phòng và học tập.', 22000000, 15, '💻', 'laptop-hp-envy-13-ba1535tu-4u6m4pa.webp', 4.6, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(5, 1, 'Laptop Lenovo ThinkPad X1 Carbon', 'Lenovo', 'Máy tính xách tay dành cho doanh nhân với bàn phím có độ nảy tốt, trọng lượng siêu nhẹ và khả năng bảo mật thông tin tối ưu.', 43000000, 4, '💻', 'thinkpadx1.webp', 4.8, 1, '2026-06-09 22:27:08', '2026-06-20 01:31:32'),
(6, 2, 'iPhone 15 Pro Max 256GB', 'Apple', 'Điện thoại cao cấp sở hữu khung viền bằng chất liệu Titanium bền bỉ, camera zoom quang học chất lượng cao và cổng kết nối USB-C tốc độ truyền tải dữ liệu nhanh.', 28990000, 10, '📱', 'iphone-15-pro-max_3.webp', 4.9, 1, '2026-06-09 22:27:08', '2026-06-24 00:16:36'),
(7, 2, 'Samsung Galaxy S24 Ultra AI', 'Samsung', 'Điện thoại thông minh tích hợp trí tuệ nhân tạo Galaxy AI, camera độ phân giải hai trăm Megapixel và bút S-Pen đa năng đi kèm.', 26490000, 20, '✏️', 'samsunggalaxys24ultraAI.webp', 4.6, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(8, 2, 'Samsung Galaxy Z Fold 5', 'Samsung', 'Điện thoại màn hình gập thế hệ mới với khả năng mở rộng không gian làm việc như một chiếc máy tính bảng thu nhỏ, thiết kế bản lề linh hoạt.', 40000000, 8, '📱', 'samsung-galaxy-fold5-den-1.webp', 4.7, 1, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(9, 2, 'Google Pixel 8 Pro', 'Google', 'Điện thoại thông minh của Google nổi bật với khả năng xử lý ảnh chụp bằng trí tuệ nhân tạo, cho ra bức ảnh hoàn hảo trong mọi điều kiện ánh sáng.', 24000000, 12, '📱', 'google-pixel-8-pro_7_.webp', 4.8, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(10, 2, 'Xiaomi 14 Ultra', 'Xiaomi', 'Sản phẩm hợp tác cùng Leica với hệ thống camera chuyên nghiệp, cảm biến kích thước lớn giúp thu sáng ấn tượng cho từng khung hình.', 25000000, 15, '📱', 'xiaomi-14-ultra_1_1_1.webp', 4.5, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(11, 3, 'Tai nghe Sony WH-1000XM5', 'Sony', 'Tai nghe chụp tai có khả năng chống ồn chủ động đỉnh cao, chất lượng âm thanh sắc nét chuẩn Hi-Res Audio.', 6490000, 25, '🎧', 'tainghesony.webp', 4.8, 1, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(12, 3, 'Loa Bluetooth JBL Charge 5', 'JBL', 'Loa di động kết nối không dây, khả năng chống bụi và chống nước chuẩn IP67, âm thanh mạnh mẽ với dải trầm sâu.', 3950000, 0, '🔊', 'loa_bluetooth_jbl_charge_5_den_02_e5f1d467f6.webp', 4.4, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(13, 3, 'Loa Marshall Stanmore III', 'Marshall', 'Loa để bàn với thiết kế cổ điển sang trọng, chất lượng âm thanh chi tiết, phù hợp làm vật dụng trang trí cao cấp.', 10509000, 10, '🔊', 'loa_bluetooth_marshall_stanmore_iii_den_5b04ca8236.webp', 4.9, 1, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(14, 3, 'Tai nghe AirPods Pro 2', 'Apple', 'Tai nghe không dây chống ồn chủ động với khả năng lọc tạp âm thông minh, mang lại không gian âm nhạc riêng tư và sống động.', 4690000, 30, '🎧', 'tai-nghe-airpods-pro-2022-trang-3.webp', 4.8, 1, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(15, 3, 'Tai nghe Bose QuietComfort 45', 'Bose', 'Dòng tai nghe chống ồn huyền thoại với đệm tai êm ái, mang lại cảm giác thoải mái khi đeo trong thời gian dài cùng chất âm trung thực.', 8990000, 12, '🎧', 'tai-nghe-bose-quietcomfort-45-trang.jpg', 4.7, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(16, 4, 'Bàn Phím Cơ Keychron K2V2', 'Keychron', 'Bàn phím cơ không dây layout bảy mươi lăm phần trăm tối giản, hỗ trợ đèn nền LED RGB và có thể thay thế switch dễ dàng.', 1890000, 4, '⌨️', 'banphimcokeychronk2v2.webp', 4.5, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(17, 4, 'Chuột Gaming Logitech G502 Hero', 'Logitech', 'Chuột chơi game trang bị cảm biến quang học HERO độ phân giải lên đến hai mươi lăm nghìn DPI, tích hợp nhiều phím chức năng có thể lập trình.', 1190000, 35, '🖱️', 'chuotgaminglogitechg502hero.webp', 4.7, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(18, 4, 'Chuột không dây Logitech MX Master 3S', 'Logitech', 'Chuột công thái học cao cấp với con lăn siêu nhanh, cảm biến chính xác trên mọi bề mặt, hỗ trợ tối đa cho công việc văn phòng.', 22350000, 20, '🖱️', 'Chuột Bluetooth Logitech MX Master 3S Đen-dd.webp', 4.9, 1, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(19, 4, 'Bàn phím cơ Logitech G Pro', 'Logitech', 'Bàn phím cơ chuyên dụng cho game thủ với kích thước gọn gàng, độ phản hồi nhanh giúp thao tác chính xác trong mọi trận đấu.', 4390000, 15, '⌨️', 'banphimcokeychronk2v2.webp', 4.6, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(20, 4, 'USB SanDisk 128GB', 'SanDisk', 'Thiết bị lưu trữ dữ liệu cầm tay với dung lượng lớn, tốc độ truyền tải nhanh chóng và thiết kế nhỏ gọn tiện lợi.', 499000, 100, '💾', 'sandisk_ultra_curve_cz550_thumb_a8f996f2e1.webp', 4.5, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08'),
(21, 4, 'Chuột có dây Gaming Razer', 'Razer', 'Chuột chơi game có dây với độ bền cao, thiết kế cầm nắm thoải mái và tốc độ phản hồi nhanh, hỗ trợ tốt cho các trò chơi bắn súng góc nhìn thứ nhất.', 399000, 50, '🖱️', 'chuot_gaming_icore_gm03_2d87a36a81.webp', 4.4, 0, '2026-06-09 22:27:08', '2026-06-09 22:27:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `rating` int(11) DEFAULT 5,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `role`, `phone`, `address`, `avatar`, `created_at`, `updated_at`, `status`) VALUES
(1, 'Admin Quản Trị', 'admin@techzone.com', '$2y$10$T6AcpYv83ifxIAP.QrW5tOvw3WFGAmDybS1QzeUhdMXfNpwjV/0E.', 'admin', NULL, NULL, NULL, '2026-06-09 22:27:08', '2026-06-10 15:53:21', 1),
(2, 'Nguyễn Khách Hàng', 'user@techzone.com', '$2y$10$.5rFVrCcEqBmjVFyMH2T5ueraWTfYPupNy5IyWXergmq3sSOGbcXW', 'customer', NULL, NULL, NULL, '2026-06-09 22:27:08', '2026-06-23 23:59:37', 1),
(7, 'Tống Á Hiên', 'tongahien@email.com', '$2y$10$djMDoprzcq/W0w0k8QXj.erqJtvz09KqU37qen8wUNJf/BPx2Tx3u', '', NULL, NULL, NULL, '2026-06-24 01:07:53', '2026-06-24 01:07:53', 1),
(8, 'Mã Gia Kỳ', 'magiaky@email.com', '$2y$10$tKo07kTxXaNOQFUP7pCpG.Yfwf7Jga4b2wZZ6uB9n455Swp2xsMPO', '', NULL, NULL, NULL, '2026-06-24 01:12:50', '2026-06-24 01:12:50', 1);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cart` (`session_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
--
-- Cấu trúc bảng cho bảng `support_tickets`
--

CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `reply` TEXT DEFAULT NULL,
  `status` ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
