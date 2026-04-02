-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th3 07, 2026 lúc 11:44 AM
-- Phiên bản máy phục vụ: 10.6.19-MariaDB-cll-lve-log
-- Phiên bản PHP: 8.2.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `cfabtwqjhosting_webnro`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tin tức', 'tin-tuc', 'Các tin tức mới nhất về game', 'active', '2025-12-29 10:46:00', NULL),
(2, 'Sự kiện', 'su-kien', 'Các sự kiện đặc biệt trong game', 'active', '2025-12-15 15:14:15', NULL),
(3, 'Hướng dẫn', 'huong-dan', 'Hướng dẫn chơi game', 'active', '2025-12-15 15:14:15', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `parent_comment_id` int(10) UNSIGNED DEFAULT NULL,
  `nro_account_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `parent_comment_id`, `nro_account_id`, `username`, `avatar_url`, `content`, `likes`, `created_at`) VALUES
(33, 1, NULL, 3630, 'xuandepzai', '/assets/frontend/home/v1/images/x4/519.png', 'oee', 0, '2026-02-10 20:44:54'),
(34, 1, NULL, 3574, 'vampire', '/assets/frontend/home/v1/images/x4/522.png', 'lụm', 0, '2026-02-10 20:58:26'),
(36, 1, NULL, 3961, 'Nvv3103', '/assets/frontend/home/v1/images/x4/734.png', 'Ngon', 0, '2026-02-14 21:44:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `comment_id` int(10) UNSIGNED NOT NULL,
  `nro_account_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `posts`
--

CREATE TABLE `posts` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL COMMENT 'Tóm tắt bài viết',
  `featured_image` varchar(500) DEFAULT NULL,
  `category_id` int(11) UNSIGNED NOT NULL,
  `nro_account_id` int(11) UNSIGNED NOT NULL,
  `author_username` varchar(50) NOT NULL,
  `author_avatar` varchar(255) DEFAULT NULL,
  `status` enum('published','draft','trash') NOT NULL DEFAULT 'draft',
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `likes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `category_id`, `nro_account_id`, `author_username`, `author_avatar`, `status`, `views`, `likes`, `created_at`, `updated_at`, `published_at`) VALUES
(1, 'Khai mở máy chủ mới', 'khai-mo-may-chu-moi', '<div style=\"max-width:820px;margin:0 auto;\r\nfont-family:Tahoma,Arial,sans-serif;\r\nfont-size:16px;line-height:1.9;\r\nletter-spacing:0.02em;color:#0f172a;\">\r\n\r\n  <!-- TITLE -->\r\n  <h2 style=\"\r\n    text-align:center;\r\n    font-family:\'Bangers\',\'Impact\',\'Arial Black\',sans-serif;\r\n    font-size:28px;\r\n    letter-spacing:0.12em;\r\n    color:#1d4ed8;\r\n    margin-bottom:8px;\">\r\n    19H 14/2 – CHÍNH THỨC OPEN!!!\r\n  </h2>\r\n\r\n  <p style=\"\r\n    text-align:center;\r\n    font-family:\'Bangers\',\'Impact\',sans-serif;\r\n    font-size:20px;\r\n    color:#dc2626;\r\n    letter-spacing:0.08em;\r\n    margin-bottom:14px;\">\r\n    SERVER FREE VÀNG NGỌC – MỞ THÀNH VIÊN!!!\r\n  </p>\r\n\r\n  <h3 style=\"\r\n    text-align:center;\r\n    font-family:\'Bangers\',\'Impact\',sans-serif;\r\n    font-size:24px;\r\n    color:#f59e0b;\r\n    letter-spacing:0.14em;\r\n    margin-bottom:18px;\">\r\n    NGỌC RỒNG HDPE\r\n  </h3>\r\n\r\n  <p style=\"text-align:center;margin-bottom:24px;\">\r\n    Toàn bộ vật phẩm đều có thể cày được<br>\r\n    <em>Tuổi thơ sống lại – chiến đấu không giới hạn!</em>\r\n  </p>\r\n\r\n  <!-- INTRO -->\r\n  <p>🛡 <strong>Server Dame Chuẩn Gốc</strong> – Chuẩn cày cuốc mua bán trao đổi!</p>\r\n  <p>🎁 <strong>Quà tặng cực khủng</strong> – Săn boss nhận quà!</p>\r\n  <p>🛠️ <strong>Admin hỗ trợ nhiệt tình 24/24</strong></p>\r\n  <p>🎮 <strong>Đầy đủ nền tảng:</strong> PC – APK – iOS (có TF)</p>\r\n  <p>👉 Full map, skill, server mượt mà, mod đủ chức năng!</p>\r\n  <p>📈 Cân bằng hợp lý, không quá cách biệt!</p>\r\n\r\n  <!-- FEATURES -->\r\n  <h3 style=\"\r\n    font-family:\'Bangers\',\'Impact\',sans-serif;\r\n    font-size:22px;\r\n    color:#1d4ed8;\r\n    letter-spacing:0.1em;\r\n    margin:18px 0 14px;\">\r\n    🍀 SIÊU TÍNH NĂNG 🍀\r\n  </h3>\r\n\r\n  <p><strong>🔹 Phúc lợi:</strong> Ngọc xanh không giới hạn, vàng, xu, cải trang,...</p>\r\n\r\n  <p><strong>🔹 Chế độ tông môn sư</strong> tại NPC Tông Môn Sư: Cửa hàng đệ tử + nâng cấp đệ</p>\r\n\r\n  <p><strong>🔹 Đơn vị tiền tệ mới (Xu Elite)</strong> tại NPC Cumber Lọ Vương:</p>\r\n\r\n  <div style=\"margin-left:20px;margin-bottom:12px;\">\r\n\r\n    <p>▪ Cửa hàng tiện lợi: đá nâng cấp, đá may mắn, công thức VIP, đá mài, bùa giám định, pha lê, items,...</p>\r\n\r\n    <p>▪ Cửa hàng cao cấp: cải trang mới, cải trang anime, pet, đeo lưng, ván bay độc quyền</p>\r\n\r\n    <p>▪ Cửa hàng điểm boss: đổi xu Elite, thỏi vàng, bùa x2 tn/sm đệ tử, đá kích hoạt,...</p>\r\n\r\n    <p>▪ Cửa hàng đệ tử: nhiều loại đệ từ trung đến siêu</p>\r\n\r\n    <p>▪ Cửa hàng thỏi vàng: giáp tập luyện cấp 4, máy dò Capsule, đá bảo vệ,...</p>\r\n\r\n  </div>\r\n\r\n  <p><strong>🔹 Sổ Sứ Mệnh</strong> tại NPC Nakroth Vệ Thần: Cày level sổ nhận thưởng</p>\r\n  <p><strong>🔹 Rèn thỏi vàng</strong> tại NPC Thợ Kim Hoàn</p>\r\n  <p><strong>🔹 Điểm danh</strong> tại NPC Santa: Nhận quà miễn phí mỗi ngày</p>\r\n  <p><strong>🔹 Chức năng bang hội</strong></p>\r\n  <p><strong>🔹 Set kích hoạt:</strong> SKH thường – VIP</p>\r\n  <p><strong>🔹 Mini game</strong> tại NPC Lý Tiểu Nương</p>\r\n  <p><strong>🔹 Tiệm phở anh Hai</strong> siêu hot đã có mặt</p>\r\n\r\n  <p style=\"margin-top:24px;font-weight:bold;color:#dc2626;text-align:center;\">\r\n    CÒN VÔ VÀN TÍNH NĂNG KHÁC ĐANG CHỜ ĐÓN BẠN! THAM GIA NGAY NGỌC RỒNG HDPE ĐỂ TRẢI NGHIỆM!\r\n  </p>\r\n\r\n  <hr style=\"border:none;border-top:2px solid #1d4ed8;margin:28px 0;\">\r\n\r\n  <p style=\"text-align:center;\"><strong>HỖ TRỢ:</strong> Android – iOS – PC (IPA-TF)</p>\r\n\r\n  <p style=\"text-align:center;\">\r\n    <strong>Group Zalo:</strong>\r\n    <a href=\"https://zalo.me/g/tkdeeb069\" style=\"color:#1d4ed8;text-decoration:none;\">\r\n      https://zalo.me/g/tkdeeb069\r\n    </a>\r\n  </p>\r\n\r\n  <p style=\"text-align:center;\">\r\n    <strong>Fanpage:</strong>\r\n    <a href=\"https://www.facebook.com/ngocronghdpe\" style=\"color:#1877f2;text-decoration:none;\">\r\n      https://www.facebook.com/ngocronghdpe\r\n    </a>\r\n  </p>\r\n\r\n  <p style=\"text-align:center;\">\r\n    <strong>Group:</strong>\r\n    <a href=\"https://www.facebook.com/groups/1444219976744071/\" style=\"color:#1877f2;text-decoration:none;\">\r\n      https://www.facebook.com/groups/1444219976744071/\r\n    </a>\r\n  </p>\r\n\r\n  <p style=\"text-align:center;font-style:italic;color:#f59e0b;letter-spacing:0.05em;\">\r\n    Tuổi thơ sống lại – chiến đấu không giới hạn!\r\n  </p>\r\n\r\n</div>\r\n', 'Thông báo khai mở máy chủ mới', '\\assets\\frontend\\home\\v1\\images\\khai-mo-may-chu.jpg', 1, 1947, 'admin', NULL, 'published', 861, 0, '2025-12-29 10:48:26', '2026-03-07 10:33:24', NULL),
(124, 'Hướng Dẫn Cơ Chế Đệ Tử', 'huong-dan-co-che-de-tu', '<div style=\"max-width: 820px; margin: 0 auto; font-family: Tahoma, Arial, sans-serif; font-size: 16px; line-height: 1.9; letter-spacing: 0.02em; color: #0f172a\">\r\n  <!-- TITLE -->\r\n  <h2 style=\"text-align: center; font-family: &quot;Bangers&quot;, &quot;Impact&quot;, &quot;Arial Black&quot;, sans-serif; font-size: 28px; letter-spacing: 0.12em; color: #1d4ed8; margin-bottom: 12px\">HƯỚNG DẪN CƠ CHẾ ĐỆ TỬ NGỌC RỒNG HDPE</h2>\r\n\r\n  <p style=\"text-align: center; margin-bottom: 20px\">Tổng hợp chi tiết <strong>cách kiếm đệ tử</strong> và <strong>điều kiện nâng cấp đệ</strong> mới nhất tại Ngọc Rồng HDPE. Càng nâng cấp cao, đệ tử sẽ tăng phần trăm chỉ số càng mạnh.</p>\r\n\r\n  <hr style=\"border: none; border-top: 2px solid #1d4ed8; margin: 24px 0\" />\r\n\r\n  <!-- INTRO -->\r\n  <h3 style=\"font-family: &quot;Bangers&quot;, &quot;Impact&quot;, sans-serif; font-size: 22px; color: #f59e0b; letter-spacing: 0.08em; margin-bottom: 12px\">CƠ CHẾ HOẠT ĐỘNG</h3>\r\n\r\n  <p>Hệ thống <strong>Đệ Tử</strong> trong Ngọc Rồng HDPE được chia thành nhiều cấp bậc khác nhau. Người chơi cần săn boss, thu thập trứng đệ và đạt đủ điều kiện sức mạnh để mở khóa đệ cao hơn.</p>\r\n\r\n  <p>Mỗi loại đệ sẽ tăng % chỉ số khác nhau và yêu cầu người chơi phải đạt đủ điều kiện trước khi sử dụng, kể cả khi mua đệ VIP.</p>\r\n\r\n  <hr style=\"border: none; border-top: 2px solid #1d4ed8; margin: 24px 0\" />\r\n\r\n  <!-- TABLE TITLE -->\r\n  <h3 style=\"text-align: center; font-family: &quot;Bangers&quot;, &quot;Impact&quot;, sans-serif; font-size: 22px; color: #1d4ed8; letter-spacing: 0.1em; margin-bottom: 18px\">TÓM TẮT CÁCH KIẾM & ĐIỀU KIỆN ĐỆ</h3>\r\n\r\n  <!-- TABLE (ÉP ĐEN CHỈ RIÊNG PHẦN NÀY) -->\r\n  <div style=\"overflow-x: auto; margin-bottom: 20px\">\r\n    <table style=\"width: 100%; border-collapse: collapse; text-align: center; font-size: 15px; color: #000\">\r\n      <tr style=\"background: #e5e7eb; color: #000\">\r\n        <th style=\"padding: 12px; border: 1px solid #000; color: #000\">Tên Đệ</th>\r\n        <th style=\"padding: 12px; border: 1px solid #000; color: #000\">Cách Kiếm</th>\r\n        <th style=\"padding: 12px; border: 1px solid #000; color: #000\">Điều Kiện</th>\r\n      </tr>\r\n\r\n      <tr>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\"><strong style=\"color: #000\">Mabu (+5%)</strong></td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Săn Boss 22h tại TP Vegeta nhận trứng bư</td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Cần đệ thường</td>\r\n      </tr>\r\n\r\n      <tr style=\"background: #f3f4f6\">\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\"><strong style=\"color: #000\">Bình Hút (+10%)</strong></td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Săn Boss 22h rơi bình hút → Gặp osin tại đại hội võ thuật để vào map úp kills</td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Mabu 40 tỷ + 3000 kills</td>\r\n      </tr>\r\n\r\n      <tr>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\"><strong style=\"color: #000\">Goku Daima (+20%)</strong></td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Tiêu diệt boss tại BDKB map 110 rơi trứng → Săn Cumber để nhận kill</td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Bình Hút 80 tỷ + 500 kill</td>\r\n      </tr>\r\n\r\n      <tr style=\"background: #f3f4f6\">\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\"><strong style=\"color: #000\">Android 21 (+35%)</strong></td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Săn xên thị trấn sẽ rơi trứng → Farm quái tại map rừng cây</td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Goku Daima 120 tỷ + 15999 kill quái</td>\r\n      </tr>\r\n\r\n      <tr>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\"><strong style=\"color: #000\">Broly SSJ3 (+50%)</strong></td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Săn Super Broly SSJ3 sẽ rơi trứng → Tìm mảnh boss và mảnh quái</td>\r\n        <td style=\"padding: 12px; border: 1px solid #000; color: #000\">Android 21 180 tỷ + 999 mảnh mỗi loại</td>\r\n      </tr>\r\n    </table>\r\n  </div>\r\n\r\n  <!-- NOTE -->\r\n  <p style=\"margin-top: 10px; padding: 14px; background: #fee2e2; border-left: 5px solid #dc2626; font-weight: bold\">Lưu ý: Khi mua đệ VIP bạn vẫn phải đạt đủ điều kiện của đệ đó mới có thể sử dụng.</p>\r\n\r\n  <hr style=\"border: none; border-top: 2px solid #1d4ed8; margin: 28px 0\" />\r\n\r\n  <p style=\"text-align: center; font-style: italic; color: #f59e0b\">Ngọc Rồng HDPE</p>\r\n<p style=\"text-align: center; font-style: italic; color: #f59e0b\">Chiến đấu càng mạnh – trải nghiệm càng đỉnh!</p>\r\n</div>\r\n', 'Hướng dẫn chi tiết cơ chế đệ tử và điều kiện mở khóa từng cấp', '\\assets\\frontend\\home\\v1\\images\\img-huong-dan.jpg', 3, 1, 'admin', NULL, 'published', 281, 0, '2026-02-11 14:53:01', '2026-03-07 10:33:25', '2026-02-11 14:53:01');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `post_likes`
--

CREATE TABLE `post_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `post_id` int(10) UNSIGNED NOT NULL,
  `nro_account_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `nro_account_id`, `created_at`) VALUES
(3, 1, 1968, '2025-12-31 14:54:52'),
(4, 1, 1947, '2025-12-31 15:16:56'),
(6, 1, 3472, '2026-01-12 17:19:31'),
(7, 1, 3443, '2026-01-12 17:24:44'),
(11, 1, 3630, '2026-02-10 20:44:50'),
(13, 1, 3574, '2026-02-10 20:58:22'),
(15, 1, 3961, '2026-02-14 21:44:44'),
(16, 1, 3994, '2026-02-14 21:47:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int(11) UNSIGNED NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Ngọc Rồng HDPE', 'Tên website', '2025-12-17 15:54:09'),
(2, 'site_description', 'Website chính thức của Ngọc Rồng HDPE', 'Mô tả website', '2025-12-17 15:54:13'),
(3, 'site_keywords', 'game, nro, dragon ball', 'Từ khóa SEO', NULL),
(4, 'facebook_url', 'https://facebook.com', 'Link Facebook fanpage', NULL),
(5, 'facebook_group_url', 'https://facebook.com/groups', 'Link Facebook group', NULL),
(6, 'ios_download_url', 'https://drive.google.com/file/d/1GDbytRqr8Fo74fi7WxpM8_tpBVu0L9bX/view?usp=sharing', 'Link tải iOS', '2025-12-29 10:26:25'),
(7, 'android_download_url', 'https://drive.google.com/file/d/1H3N-Xd_4o6iZpb5e_hsvtSEsPVDVQ71J/view?usp=sharing', 'Link tải Android', '2025-12-29 10:26:06'),
(8, 'apk_download_url', 'https://drive.google.com/file/d/1AvHSHHhiBiVLw0ok7ivbbVeYvHpuBY4c/view?usp=sharing', 'Link tải APK', '2025-12-29 10:25:39'),
(9, 'payment_url', '#', 'Link nạp tiền', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `slides`
--

CREATE TABLE `slides` (
  `id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(500) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `slides`
--

INSERT INTO `slides` (`id`, `title`, `image`, `link`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(2, 'Sự kiện Trung Thu', '\\assets\\frontend\\home\\v1\\images\\img-su-kien.jpg', 'http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=web_nro&table=slides', 'active', 2, '2025-12-16 00:07:23', '2025-12-16 12:34:56'),
(3, 'Hướng dẫn tân thủ', '\\assets\\frontend\\home\\v1\\images\\img-huong-dan.jpg', '#', 'active', 3, '2025-12-16 00:07:23', '2025-12-29 10:32:14'),
(4, 'Hướng dẫn tân thủ', '\\assets\\frontend\\home\\v1\\images\\img-tin-tuc.jpg', 'http://localhost/phpmyadmin/index.php?route=/sql&pos=0&db=web_nro&table=slides', 'active', 3, '2025-12-29 10:49:09', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `task_main_template`
--

CREATE TABLE `task_main_template` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `trans_id_map`
--

CREATE TABLE `trans_id_map` (
  `id` int(11) NOT NULL,
  `trans_id` varchar(100) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `trans_id_map`
--

INSERT INTO `trans_id_map` (`id`, `trans_id`, `username`, `created_at`) VALUES
(24, 'trans_695795ffe814b8.17391755', 'admin', '2026-01-02 09:55:11'),
(23, 'trans_6955c95d354797.71371002', 'hungdzvn4', '2026-01-01 01:09:49'),
(22, 'trans_695203b2ab9b33.21591114', 'admin', '2025-12-29 04:29:38'),
(21, 'trans_695203ae225a38.59744016', 'admin', '2025-12-29 04:29:34'),
(20, 'trans_695203a8153b63.24713559', 'admin', '2025-12-29 04:29:28'),
(19, 'trans_69511159e59606.02032787', 'admin', '2025-12-28 11:15:37'),
(18, 'trans_695110cd6f5996.24506602', 'admin', '2025-12-28 11:13:17'),
(17, 'trans_69510fc83156c4.42524223', 'admin', '2025-12-28 11:08:56'),
(25, 'trans_6988acaf1ab6b2.92980868', 'vkien', '2026-02-08 15:33:03'),
(26, 'trans_6989b9cf6c8316.75234514', 'vkien', '2026-02-09 10:41:19'),
(27, 'trans_6989d469cc3a88.77461301', 'vkien', '2026-02-09 12:34:49'),
(28, 'trans_69906505336a13.01865588', 'tranquocbao', '2026-02-14 12:05:25'),
(29, 'trans_6990bf96b689a5.47297513', 'ulatroi', '2026-02-14 18:31:50'),
(30, 'trans_6992768bbd7302.89898689', 'tranquocbao2', '2026-02-16 01:44:43'),
(31, 'trans_699a903fee5997.91810494', 'k1ng111', '2026-02-22 05:12:31'),
(32, 'trans_699f0103549953.43419804', 'k1ng333', '2026-02-25 14:02:43');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Chỉ mục cho bảng `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_user_id` (`nro_account_id`);

--
-- Chỉ mục cho bảng `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`comment_id`,`nro_account_id`);

--
-- Chỉ mục cho bảng `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_author_id` (`nro_account_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_published_at` (`published_at`);

--
-- Chỉ mục cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`nro_account_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Chỉ mục cho bảng `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- Chỉ mục cho bảng `task_main_template`
--
ALTER TABLE `task_main_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Chỉ mục cho bảng `trans_id_map`
--
ALTER TABLE `trans_id_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `trans_id` (`trans_id`),
  ADD KEY `trans_id_2` (`trans_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT cho bảng `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `task_main_template`
--
ALTER TABLE `task_main_template`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `trans_id_map`
--
ALTER TABLE `trans_id_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Các ràng buộc cho bảng `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
