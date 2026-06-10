-- ================================================================
-- TECHZONE - DATABASE SCHEMA
-- MySQL 5.7+ / MariaDB 10.3+
-- ================================================================

CREATE DATABASE IF NOT EXISTS techzone_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE techzone_db;

-- ---------------------------------------------------------------
-- BẢNG NGƯỜI DÙNG
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname  VARCHAR(100) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  role      ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  phone     VARCHAR(20)  DEFAULT NULL,
  address   TEXT         DEFAULT NULL,
  avatar    VARCHAR(255) DEFAULT NULL,
  created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BẢNG DANH MỤC SẢN PHẨM
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  icon VARCHAR(10)  DEFAULT '📦'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BẢNG SẢN PHẨM
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name        VARCHAR(200) NOT NULL,
  brand       VARCHAR(100) NOT NULL,
  description TEXT         DEFAULT NULL,
  price       DECIMAL(15,0) NOT NULL DEFAULT 0,
  stock       INT          NOT NULL DEFAULT 0,
  image_emoji VARCHAR(10)  DEFAULT '📦',
  image_file  VARCHAR(255) DEFAULT NULL,
  rating      DECIMAL(2,1) NOT NULL DEFAULT 4.5,
  featured    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BẢNG ĐƠN HÀNG
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code       VARCHAR(20) NOT NULL UNIQUE,
  user_id          INT UNSIGNED DEFAULT NULL,
  customer_name    VARCHAR(100) NOT NULL,
  customer_phone   VARCHAR(20)  NOT NULL,
  customer_address TEXT         NOT NULL,
  note             TEXT         DEFAULT NULL,
  subtotal         DECIMAL(15,0) NOT NULL DEFAULT 0,
  shipping_fee     DECIMAL(15,0) NOT NULL DEFAULT 0,
  total_price      DECIMAL(15,0) NOT NULL DEFAULT 0,
  payment_method   ENUM('qr','cod','momo') NOT NULL DEFAULT 'cod',
  status           ENUM('Chờ xử lý','Đang giao','Đã hoàn thành','Đã hủy') NOT NULL DEFAULT 'Chờ xử lý',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BẢNG CHI TIẾT ĐƠN HÀNG
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  product_id  INT UNSIGNED DEFAULT NULL,
  name        VARCHAR(200) NOT NULL,
  price       DECIMAL(15,0) NOT NULL,
  quantity    INT          NOT NULL DEFAULT 1,
  image_emoji VARCHAR(10)  DEFAULT '📦',
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- BẢNG GIỎ HÀNG (server-side cart)
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(128) NOT NULL,
  user_id    INT UNSIGNED DEFAULT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT          NOT NULL DEFAULT 1,
  promo_price DECIMAL(15,0) DEFAULT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cart (session_id, product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ================================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- ================================================================

-- Tài khoản admin mặc định: admin@techzone.com / 123456
-- Tài khoản khách: user@techzone.com / 123456
INSERT INTO users (fullname, email, password, role) VALUES
  ('Admin Quản Trị',     'admin@techzone.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
  ('Nguyễn Khách Hàng',  'user@techzone.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');
-- NOTE: password hash trên = bcrypt("password") từ Laravel factory.
-- Để dùng "123456" thực sự, chạy lệnh: password_hash('123456', PASSWORD_BCRYPT)
-- Hoặc xem file includes/seed_passwords.php

INSERT INTO categories (name, slug, icon) VALUES
  ('Laptop & Máy tính',    'laptop',    '💻'),
  ('Điện thoại & Tablet',  'phone',     '📱'),
  ('Âm thanh & Loa',       'audio',     '🎧'),
  ('Phụ kiện công nghệ',   'accessory', '⌨️');

INSERT INTO products (category_id, name, brand, description, price, stock, image_emoji, image_file, rating, featured) VALUES
-- CATEGORY 1: Laptop & Máy tính
(1, 'MacBook Air M2 13.6 inch', 'Apple', 'Sản phẩm được trang bị chip Apple M2 mạnh mẽ, thiết kế vỏ nhôm nguyên khối siêu mỏng nhẹ, màn hình Retina sắc nét cùng thời lượng pin lên đến 18 giờ liên tục.', 27490000, 15, '💻', 'macbookairm22022.webp', 4.8, 1),
(1, 'Asus ROG Strix G16 Gaming', 'Asus', 'Laptop chuyên dụng cho game thủ với bộ vi xử lý Intel Core thế hệ mới kết hợp cùng card đồ họa rời RTX 4060, mang lại hiệu năng xử lý đồ họa vượt trội.', 32990000, 8, '🎮', 'asusROGstrixg16gaming.webp', 4.7, 0),
(1, 'Laptop Dell XPS 15', 'Dell', 'Dòng máy tính xách tay cao cấp với màn hình OLED độ phân giải cao, viền siêu mỏng tạo không gian hiển thị rộng rãi và sang trọng.', 45000000, 10, '💻', 'laptopdellxps15.webp', 4.9, 1),
(1, 'Laptop HP Envy 13', 'HP', 'Máy tính xách tay với thiết kế thời trang, vỏ kim loại chắc chắn cùng hiệu năng ổn định, phù hợp cho nhu cầu văn phòng và học tập.', 22000000, 15, '💻', 'laptop-hp-envy-13-ba1535tu-4u6m4pa.webp', 4.6, 0),
(1, 'Laptop Lenovo ThinkPad X1 Carbon', 'Lenovo', 'Máy tính xách tay dành cho doanh nhân với bàn phím có độ nảy tốt, trọng lượng siêu nhẹ và khả năng bảo mật thông tin tối ưu.', 43000000, 5, '💻', 'thinkpadx1.webp', 4.8, 1),

-- CATEGORY 2: Điện thoại & Tablet
(2, 'iPhone 15 Pro Max 256GB', 'Apple', 'Điện thoại cao cấp sở hữu khung viền bằng chất liệu Titanium bền bỉ, camera zoom quang học chất lượng cao và cổng kết nối USB-C tốc độ truyền tải dữ liệu nhanh.', 28990000, 12, '📱', 'iphone-15-pro-max_3.webp', 4.9, 1),
(2, 'Samsung Galaxy S24 Ultra AI', 'Samsung', 'Điện thoại thông minh tích hợp trí tuệ nhân tạo Galaxy AI, camera độ phân giải hai trăm Megapixel và bút S-Pen đa năng đi kèm.', 26490000, 20, '✏️', 'samsunggalaxys24ultraAI.webp', 4.6, 0),
(2, 'Samsung Galaxy Z Fold 5', 'Samsung', 'Điện thoại màn hình gập thế hệ mới với khả năng mở rộng không gian làm việc như một chiếc máy tính bảng thu nhỏ, thiết kế bản lề linh hoạt.', 40000000, 8, '📱', 'samsung-galaxy-fold5-den-1.webp', 4.7, 1),
(2, 'Google Pixel 8 Pro', 'Google', 'Điện thoại thông minh của Google nổi bật với khả năng xử lý ảnh chụp bằng trí tuệ nhân tạo, cho ra bức ảnh hoàn hảo trong mọi điều kiện ánh sáng.', 24000000, 12, '📱', 'google-pixel-8-pro_7_.webp', 4.8, 0),
(2, 'Xiaomi 14 Ultra', 'Xiaomi', 'Sản phẩm hợp tác cùng Leica với hệ thống camera chuyên nghiệp, cảm biến kích thước lớn giúp thu sáng ấn tượng cho từng khung hình.', 25000000, 15, '📱', 'xiaomi-14-ultra_1_1_1.webp', 4.5, 0),

-- CATEGORY 3: Âm thanh & Loa
(3, 'Tai nghe Sony WH-1000XM5', 'Sony', 'Tai nghe chụp tai có khả năng chống ồn chủ động đỉnh cao, chất lượng âm thanh sắc nét chuẩn Hi-Res Audio.', 6490000, 25, '🎧', 'tainghesony.webp', 4.8, 1),
(3, 'Loa Bluetooth JBL Charge 5', 'JBL', 'Loa di động kết nối không dây, khả năng chống bụi và chống nước chuẩn IP67, âm thanh mạnh mẽ với dải trầm sâu.', 3950000, 0, '🔊', 'loa_bluetooth_jbl_charge_5_den_02_e5f1d467f6.webp', 4.4, 0),
(3, 'Loa Marshall Stanmore III', 'Marshall', 'Loa để bàn với thiết kế cổ điển sang trọng, chất lượng âm thanh chi tiết, phù hợp làm vật dụng trang trí cao cấp.', 10509000, 10, '🔊', 'loa_bluetooth_marshall_stanmore_iii_den_5b04ca8236.webp', 4.9, 1),
(3, 'Tai nghe AirPods Pro 2', 'Apple', 'Tai nghe không dây chống ồn chủ động với khả năng lọc tạp âm thông minh, mang lại không gian âm nhạc riêng tư và sống động.', 4690000, 30, '🎧', 'tai-nghe-airpods-pro-2022-trang-3.webp', 4.8, 1),
(3, 'Tai nghe Bose QuietComfort 45', 'Bose', 'Dòng tai nghe chống ồn huyền thoại với đệm tai êm ái, mang lại cảm giác thoải mái khi đeo trong thời gian dài cùng chất âm trung thực.', 8990000, 12, '🎧', 'tai-nghe-bose-quietcomfort-45-trang.jpg', 4.7, 0),

-- CATEGORY 4: Phụ kiện công nghệ
(4, 'Bàn Phím Cơ Keychron K2V2', 'Keychron', 'Bàn phím cơ không dây layout bảy mươi lăm phần trăm tối giản, hỗ trợ đèn nền LED RGB và có thể thay thế switch dễ dàng.', 1890000, 4, '⌨️', 'banphimcokeychronk2v2.webp', 4.5, 0),
(4, 'Chuột Gaming Logitech G502 Hero', 'Logitech', 'Chuột chơi game trang bị cảm biến quang học HERO độ phân giải lên đến hai mươi lăm nghìn DPI, tích hợp nhiều phím chức năng có thể lập trình.', 1190000, 35, '🖱️', 'chuotgaminglogitechg502hero.webp', 4.7, 0),
(4, 'Chuột không dây Logitech MX Master 3S', 'Logitech', 'Chuột công thái học cao cấp với con lăn siêu nhanh, cảm biến chính xác trên mọi bề mặt, hỗ trợ tối đa cho công việc văn phòng.', 22350000, 20, '🖱️', 'Chuột Bluetooth Logitech MX Master 3S Đen-dd.webp', 4.9, 1),
(4, 'Bàn phím cơ Logitech G Pro', 'Logitech', 'Bàn phím cơ chuyên dụng cho game thủ với kích thước gọn gàng, độ phản hồi nhanh giúp thao tác chính xác trong mọi trận đấu.', 4390000, 15, '⌨️', 'banphimcokeychronk2v2.webp', 4.6, 0),
(4, 'USB SanDisk 128GB', 'SanDisk', 'Thiết bị lưu trữ dữ liệu cầm tay với dung lượng lớn, tốc độ truyền tải nhanh chóng và thiết kế nhỏ gọn tiện lợi.', 499000, 100, '💾', 'sandisk_ultra_curve_cz550_thumb_a8f996f2e1.webp', 4.5, 0),
(4, 'Chuột có dây Gaming Razer', 'Razer', 'Chuột chơi game có dây với độ bền cao, thiết kế cầm nắm thoải mái và tốc độ phản hồi nhanh, hỗ trợ tốt cho các trò chơi bắn súng góc nhìn thứ nhất.', 399000, 50, '🖱️', 'chuot_gaming_icore_gm03_2d87a36a81.webp', 4.4, 0);


INSERT INTO orders (order_code, customer_name, customer_phone, customer_address, subtotal, shipping_fee, total_price, payment_method, status, created_at) VALUES
  ('ORD-9842', 'Nguyễn Văn Hùng', '0912345678', 'Số 15 Tạ Quang Bửu, Hai Bà Trưng, Hà Nội',
   6490000, 0, 6490000, 'momo', 'Đang giao', '2026-06-05 08:12:00'),
  ('ORD-1294', 'Trần Thị Lan', '0388776655', 'Tháp B, chung cư Lexington, Quận 2, TP. Hồ Chí Minh',
   3780000, 0, 3780000, 'cod', 'Chờ xử lý', '2026-06-05 14:30:00');

INSERT INTO order_items (order_id, product_id, name, price, quantity, image_emoji) VALUES
  (1, 5, 'Tai nghe Sony WH-1000XM5', 6490000, 1, '🎧'),
  (2, 6, 'Bàn Phím Cơ Keychron K2V2', 1890000, 2, '⌨️');