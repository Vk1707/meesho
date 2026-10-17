-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 19, 2025 at 04:08 PM
-- Server version: 5.7.23-23
-- PHP Version: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paytmzym_imc_wpservice`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bulk_uploads`
--

CREATE TABLE `tbl_bulk_uploads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `admin_id` int(11) NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `msg_body` text COLLATE utf8mb4_unicode_ci,
  `media_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Processed','Failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores bulk upload details';

--
-- Dumping data for table `tbl_bulk_uploads`
--

INSERT INTO `tbl_bulk_uploads` (`id`, `admin_id`, `file_name`, `msg_body`, `media_file`, `status`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 1, '1758217598_imc_employee.xlsx', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 'Processed', NULL, '2025-09-18 23:16:38', '2025-09-18 23:17:01'),
(2, 2, '1758270293_imc_employee.xlsx', 'New Test Welcome msg', '1758270293_ai-tshirt-designer-splash.png', 'Processed', NULL, '2025-09-19 13:54:53', '2025-09-19 13:56:02'),
(3, 2, '1758275213_imc_employee.xlsx', 'HI {user_name} ,\r\nWelcome To Our Comminuty {user_name}.\r\nLinks : https://grtrading.in/\r\n\r\n', '1758275213_shopify.jpg', 'Processed', NULL, '2025-09-19 15:16:53', '2025-09-19 15:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bulk_upload_records`
--

CREATE TABLE `tbl_bulk_upload_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `upload_id` bigint(20) UNSIGNED NOT NULL,
  `employee_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Processed','Failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores individual record status for bulk uploads';

--
-- Dumping data for table `tbl_bulk_upload_records`
--

INSERT INTO `tbl_bulk_upload_records` (`id`, `upload_id`, `employee_id`, `user_name`, `user_phone`, `status`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 1, '702187', 'Vivekanand', '8802036217', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(2, 1, '702190', 'AKHTAR KHANWALA', '9868703516', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(3, 1, '740213', 'MEMON AFREEN', '8588889466', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(4, 1, '740420', 'Anil Kumar', '8826019166', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(5, 1, '787225', 'Ashok Kumar Jha', '8285500750', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(6, 1, '1157141', 'Kumar Ranjan', '7428667993', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(7, 1, '700925', 'POONAM PRANAV ASSOCIATES', '9810973046', 'Processed', NULL, '2025-09-18 23:17:01', NULL),
(8, 2, 'TEST007', 'Vivek', '7210608154', 'Processed', NULL, '2025-09-19 13:56:02', NULL),
(9, 3, 'TEST008', 'Manish Test', '9968053670', 'Processed', NULL, '2025-09-19 15:18:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_events`
--

CREATE TABLE `tbl_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` enum('Birthday','Anniversary','Manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` date DEFAULT NULL,
  `temp_with_img` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temp_without_img` text COLLATE utf8mb4_unicode_ci,
  `selected_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '\r\n0 = all,\r\n\r\ncomma separated grade_id',
  `description` text COLLATE utf8mb4_unicode_ci,
  `text_caption` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores event details for notifications';

--
-- Dumping data for table `tbl_events`
--

INSERT INTO `tbl_events` (`id`, `event_name`, `event_type`, `event_date`, `event_time`, `temp_with_img`, `temp_without_img`, `selected_user`, `description`, `text_caption`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Birthday Event for Grade 1', 'Birthday', NULL, NULL, '68cadc4e45590.jpg', '68cadc6e04939.jpg', '1', 'Birthday Event for Grade 1 for employee', NULL, 1, '2025-09-13 21:02:34', '2025-09-17 21:36:06'),
(3, 'Anniversary Event', 'Anniversary', NULL, NULL, '68cadc60353f9.jpg', '68cadc6035645.jpg', '1', 'Anniversary Event Message', NULL, 1, '2025-09-16 16:23:52', '2025-09-17 21:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_grades`
--

CREATE TABLE `tbl_grades` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `grade_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1: Active, 0: Inactive',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_grades`
--

INSERT INTO `tbl_grades` (`id`, `grade_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Grade 1 Level', 'This range is grade 1 level', 1, '2025-09-12 22:57:33', '2025-09-17 21:43:38');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_msg_queue`
--

CREATE TABLE `tbl_msg_queue` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `user_phone` varchar(20) NOT NULL,
  `message_type` varchar(50) NOT NULL COMMENT '1=Birthday, 2=Anniversary,3= Manual, 4=Send Now Notification',
  `msg_body` text,
  `media_file` varchar(255) DEFAULT NULL,
  `append_user_image` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('Pending','Sent','Failed') NOT NULL DEFAULT 'Pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_msg_queue`
--

INSERT INTO `tbl_msg_queue` (`id`, `user_id`, `event_id`, `user_phone`, `message_type`, `msg_body`, `media_file`, `append_user_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '8700967004', '1', NULL, NULL, 1, 'Sent', '2025-09-17 15:55:05', '2025-09-17 22:53:06'),
(2, 3, 1, '7210608154', '1', NULL, NULL, 0, 'Sent', '2025-09-17 15:55:08', '2025-09-18 17:12:05'),
(3, 2, 3, '8700967004', '2', NULL, NULL, 0, 'Sent', '2025-09-17 15:55:04', '2025-09-17 15:55:02'),
(4, 3, 3, '7210608154', '2', NULL, NULL, 1, 'Sent', '2025-09-17 15:55:06', '2025-09-17 15:55:09'),
(5, 2, 1, '8700967004', '1', NULL, NULL, 1, 'Sent', '2025-09-17 15:56:03', '2025-09-17 15:57:03'),
(6, 3, 1, '7210608154', '1', NULL, NULL, 0, 'Sent', '2025-09-17 15:56:05', '2025-09-17 15:57:09'),
(7, 2, 3, '8700967004', '2', NULL, NULL, 0, 'Sent', '2025-09-17 15:56:04', '2025-09-17 15:57:05'),
(8, 3, 3, '7210608154', '2', NULL, NULL, 1, 'Sent', '2025-09-17 15:56:07', '2025-09-17 15:57:12'),
(9, 2, NULL, '8700967004', '4', 'Test Diwali Msg', '1758111812_diwali_temp_img.jpg', 1, 'Sent', '2025-09-17 17:53:32', '2025-09-17 19:06:02'),
(10, 3, NULL, '7210608154', '4', 'Test Diwali Msg', '1758111812_diwali_temp_img.jpg', 1, 'Sent', '2025-09-17 17:53:32', '2025-09-17 19:06:11'),
(11, 4, NULL, '9873477232', '4', 'Test Diwali Msg', '1758111812_diwali_temp_img.jpg', 1, 'Sent', '2025-09-17 17:53:32', '2025-09-18 17:13:04'),
(12, 5, NULL, '8802036217', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:02'),
(13, 6, NULL, '9868703516', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:06'),
(14, 7, NULL, '8588889466', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:09'),
(15, 8, NULL, '8826019166', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:12'),
(16, 9, NULL, '8285500750', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:14'),
(17, 10, NULL, '7428667993', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:18'),
(18, 11, NULL, '9810973046', '6', 'HI test msg Welcome', '1758217598_imc_anniv_temp_img.jpg', 0, 'Sent', '2025-09-18 23:17:01', '2025-09-18 23:17:21'),
(21, 12, NULL, '8700967004', '4', 'Test Message', '1758268893_diwali_temp_img.jpg', 0, 'Sent', '2025-09-19 13:31:33', '2025-09-19 13:45:02'),
(22, 12, NULL, '8700967004', '4', 'Hii New Test Message Schedule 13:52', '1758270141_diwali_temp_img.jpg', 0, 'Sent', '2025-09-19 13:52:21', '2025-09-19 13:57:01'),
(23, 13, NULL, '7210608154', '6', 'New Test Welcome msg', '1758270293_ai-tshirt-designer-splash.png', 0, 'Sent', '2025-09-19 13:56:02', '2025-09-19 13:57:07'),
(24, 14, NULL, '9968053670', '6', 'HI {user_name} ,\r\nWelcome To Our Comminuty {user_name}.\r\nLinks : https://grtrading.in/\r\n\r\n', '1758275213_shopify.jpg', 0, 'Sent', '2025-09-19 15:18:02', '2025-09-19 15:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notification_setting`
--

CREATE TABLE `tbl_notification_setting` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `mail_type` tinyint(4) NOT NULL COMMENT '1 = Gmail, 2 = Outlook, 3 = Zoho, etc.',
  `sender_email` varchar(255) NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `mail_password` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(100) NOT NULL,
  `smtp_port` int(11) NOT NULL,
  `reply_to_email` varchar(255) DEFAULT NULL COMMENT 'Email address to be used in reply-to',
  `default_email` varchar(255) DEFAULT NULL COMMENT 'Email address to receive test emails',
  `admin_email` varchar(80) DEFAULT NULL,
  `cc_emails` varchar(255) DEFAULT NULL COMMENT 'CC Emails (comma-separated)',
  `bcc_emails` varchar(255) DEFAULT NULL COMMENT 'BCC Emails (comma-separated)',
  `authorization_key` text NOT NULL,
  `access_whatsapp` tinyint(1) NOT NULL DEFAULT '0' COMMENT '2 = WhatsApp Order Message Access',
  `default_no` varchar(15) DEFAULT NULL,
  `service_status` tinyint(1) DEFAULT '0' COMMENT '0 = Live, 1 = Testing',
  `wp_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1= Activated(Scan)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_notification_setting`
--

INSERT INTO `tbl_notification_setting` (`id`, `admin_id`, `mail_type`, `sender_email`, `sender_name`, `mail_password`, `smtp_host`, `smtp_port`, `reply_to_email`, `default_email`, `admin_email`, `cc_emails`, `bcc_emails`, `authorization_key`, `access_whatsapp`, `default_no`, `service_status`, `wp_status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'user4.grt@gmail.com', 'GRT BILLING', 'gtgy azkx tsgb pmue', 'smtp.gmail.com', 587, '', 'vk7210608102@gmail.com', 'vk7210608102@gmail.com', NULL, NULL, 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.HcrfYt4hhEVGu4H7iiznEXQtY1FWiX0nsqqDqq-qdfQ', 1, '918700967004', 0, 1, '2025-08-05 07:56:50', '2025-09-19 08:24:34'),
(2, 2, 1, 'user4.grt@gmail.com', 'GRT BILLING', 'gtgy azkx tsgb pmue', 'smtp.gmail.com', 587, 'user1.grt@gmail.com', 'user1.grt@gmail.com', 'user1.grt@gmail.com', 'user1.grt@gmail.com', 'user1.grt@gmail.com', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.HcrfYt4hhEVGu4H7iiznEXQtY1FWiX0nsqqDqq-qdfQ', 0, '919911661135', 1, 0, '2025-08-05 07:56:50', '2025-09-19 10:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_states`
--

CREATE TABLE `tbl_states` (
  `state_id` int(11) NOT NULL,
  `state_name` varchar(50) NOT NULL,
  `state_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_states`
--

INSERT INTO `tbl_states` (`state_id`, `state_name`, `state_code`) VALUES
(1, 'Andhra Pradesh', '01'),
(2, 'Assam', '02'),
(3, 'Arunachal Pradesh', '03'),
(4, 'Bihar', '04'),
(5, 'Gujrat', '24'),
(6, 'Haryana', '06'),
(7, 'Himachal Pradesh', '02'),
(8, 'Jammu & Kashmir', '01'),
(9, 'Karnataka', '29'),
(10, 'Kerala', '32'),
(11, 'Madhya Pradesh', '23'),
(12, 'Maharashtra', '27'),
(13, 'Manipur', '14'),
(14, 'Meghalaya', '17'),
(15, 'Mizoram', '15'),
(16, 'Nagaland', '13'),
(17, 'Orissa', '21'),
(18, 'Punjab', '31'),
(19, 'Rajasthan', '08'),
(20, 'Sikkim', '11'),
(21, 'Tamil Nadu', '33'),
(22, 'Tripura', '16'),
(23, 'Uttar Pradesh', '09'),
(24, 'West Bengal', '19'),
(25, 'Delhi', '07'),
(26, 'Goa', '30'),
(27, 'Pondichery', '34'),
(28, 'Lakshdweep', '31'),
(29, 'Daman & diu', '26'),
(30, 'Dadra & nagar', '25'),
(31, 'Chandigarh', '04'),
(32, 'Andaman & nicobar', '35'),
(33, 'Uttaranchal', '05'),
(34, 'Jharkhand', '20'),
(35, 'Chattisgarh', '22'),
(36, 'International', ''),
(37, 'Telangana', '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_subscription_log`
--

CREATE TABLE `tbl_subscription_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Sub User Id',
  `expiry_date` datetime NOT NULL,
  `expired_status` tinyint(4) NOT NULL COMMENT '1 means expired',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int(11) NOT NULL,
  `uniq_id` varchar(50) DEFAULT NULL,
  `user_type` int(11) NOT NULL COMMENT '1-Master Admin, 2-Admin, 3- Employee',
  `username` varchar(100) DEFAULT NULL,
  `user_email` varchar(250) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_address` varchar(250) DEFAULT NULL,
  `user_phone` varchar(50) DEFAULT NULL,
  `user_alt_phone` varchar(20) DEFAULT NULL COMMENT 'For Employee Use',
  `user_gender` enum('male','female') DEFAULT NULL,
  `user_doj` date DEFAULT NULL COMMENT 'Date Of Joining',
  `user_dob` date DEFAULT NULL,
  `user_doa` date DEFAULT NULL COMMENT 'For Employee Use',
  `personal_photo` text,
  `anniversary_photo` text,
  `grade_id` int(11) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `user_city` varchar(100) DEFAULT NULL,
  `user_state` varchar(70) DEFAULT NULL,
  `user_pincode` varchar(10) DEFAULT NULL,
  `user_aadhar` varchar(30) DEFAULT NULL COMMENT 'for employee use',
  `remarks` varchar(250) DEFAULT NULL,
  `user_pass` varchar(250) DEFAULT NULL,
  `user_salt` varchar(250) DEFAULT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `user_status` tinyint(4) NOT NULL DEFAULT '1',
  `create_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `uniq_id`, `user_type`, `username`, `user_email`, `user_name`, `user_address`, `user_phone`, `user_alt_phone`, `user_gender`, `user_doj`, `user_dob`, `user_doa`, `personal_photo`, `anniversary_photo`, `grade_id`, `religion`, `user_city`, `user_state`, `user_pincode`, `user_aadhar`, `remarks`, `user_pass`, `user_salt`, `company_name`, `website_url`, `user_status`, `create_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, NULL, 'user4.grt@gmail.com', 'GR Trading', '28/132, Ground Floor, West Patel Nagar', '9911661135', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'West Delhi', '25', '110008', NULL, '', '$2y$10$WvWoX3JfclViQjC8cEQ6NOm6bN/pzqP02/n1thJU6MmXq.D7PGzQC', '8ec12d129e49038d93cd2a7bd250356a', 'GR TRADING', '', 1, 0, '2024-11-24 04:00:27', '2025-09-17 11:52:06'),
(2, NULL, 1, NULL, 'demo1@grtrading.in', 'GR Trading', 'Address House No.', '9911661135', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'New Delhi', '25', '110086', NULL, 'done', '$2y$10$iYH/CTGwX4K.wlqWNFE3qeRGmjvHRyVr8HXI.AXg5SoSX0yqnP06m', 'd0122aea730215a4cc815fbbd5284e76', 'GR TRADING', 'https://grtrading.in/', 1, 0, '2025-09-12 17:37:02', '2025-09-19 09:54:01'),
(3, 'EMP003', 3, NULL, 'testvivek@gmail.com', 'Prithvi Raj', 'hhhh', '7210608154', '9878654361', 'male', '2025-01-01', '2025-09-17', '2025-09-17', NULL, '68c45edfa9be7.jpg', 1, 'Hindu', 'North Delhi', '25', '110086', NULL, 'Done 2', NULL, NULL, NULL, NULL, 1, 1, '2025-09-12 17:56:47', '2025-09-17 15:12:28'),
(4, 'EMP004', 3, NULL, 'gr.trading15@gmail.com', 'Hari Prasad', 'H.NO.', '9873477232', '', 'male', '2025-01-01', '2025-09-17', '2025-09-17', '68caa80b5020e.jpg', '68caa80b518fd.jpg', 1, 'Hindu', 'New Delhi', '25', '110086', NULL, 'Test User', NULL, NULL, NULL, NULL, 1, 1, '2025-09-17 12:22:35', '2025-09-17 12:22:50'),
(5, '702187', 3, NULL, NULL, 'Vivekanand', NULL, '8802036217', NULL, NULL, NULL, '2025-09-18', '2020-07-06', NULL, NULL, NULL, NULL, '', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(6, '702190', 3, NULL, NULL, 'AKHTAR KHANWALA', NULL, '9868703516', NULL, NULL, NULL, '2025-09-18', '2019-03-26', NULL, NULL, NULL, NULL, 'HYDERABAD', '37', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(7, '740213', 3, NULL, NULL, 'MEMON AFREEN', NULL, '8588889466', NULL, NULL, NULL, '2025-09-18', NULL, NULL, NULL, NULL, NULL, 'Mumbai', '12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(8, '740420', 3, NULL, NULL, 'Anil Kumar', NULL, '8826019166', NULL, NULL, NULL, '2025-09-18', '2019-12-04', NULL, NULL, NULL, NULL, 'Samastipur', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(9, '787225', 3, NULL, NULL, 'Ashok Kumar Jha', NULL, '8285500750', NULL, NULL, NULL, '2025-09-18', '2019-12-06', NULL, NULL, NULL, NULL, 'Central Delhi', '25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(10, '1157141', 3, NULL, NULL, 'Kumar Ranjan', NULL, '7428667993', NULL, NULL, NULL, '2025-09-18', '2019-06-02', NULL, NULL, NULL, NULL, 'Gorakhpur', '23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(11, '700925', 3, NULL, NULL, 'POONAM PRANAV ASSOCIATES', NULL, '9810973046', NULL, NULL, NULL, '2025-09-18', '2019-11-26', NULL, NULL, NULL, NULL, 'Gurgaon', '6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, '2025-09-18 17:47:01', '2025-09-18 17:47:01'),
(12, 'TEST01', 3, NULL, '', 'TEST USER', '', '8700967004', '', 'male', NULL, '2025-04-01', '2025-07-15', NULL, NULL, 1, '', '', '25', '', NULL, '', NULL, NULL, NULL, NULL, 1, 2, '2025-09-19 07:39:23', '2025-09-19 07:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_wp_msg_logs`
--

CREATE TABLE `tbl_wp_msg_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `message_status` enum('sent','failed') DEFAULT 'failed',
  `message_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '	1=Birthday, 2=Anniversary,3= Manual, 4=Send Now Notification, 5 = Send Simple Msg',
  `msg_count` int(11) NOT NULL DEFAULT '1' COMMENT 'Number of send attempts (initial + resends)',
  `response` mediumtext,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_wp_msg_logs`
--

INSERT INTO `tbl_wp_msg_logs` (`id`, `user_id`, `user_phone`, `message_status`, `message_type`, `msg_count`, `response`, `created_at`, `updated_at`) VALUES
(9, '2', '8700967004', 'failed', 4, 1, '{\"status\":false,\"error\":\"WhatsApp client is not ready\",\"clientStatus\":\"connecting\"}', '2025-09-17 12:24:02', '2025-09-17 12:24:02'),
(10, '3', '7210608154', 'failed', 4, 1, '{\"status\":false,\"error\":\"WhatsApp client is not ready\",\"clientStatus\":\"connecting\"}', '2025-09-17 12:24:05', '2025-09-17 12:24:05'),
(11, '4', '9873477232', 'failed', 4, 1, '{\"status\":false,\"error\":\"WhatsApp client is not ready\",\"clientStatus\":\"qr_ready\"}', '2025-09-17 12:24:08', '2025-09-17 12:24:08'),
(12, '2', '8700967004', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T13:36:02.545Z\"}', '2025-09-17 13:36:02', '2025-09-17 13:36:02'),
(13, '3', '7210608154', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"917210608154\",\"timestamp\":\"2025-09-17T13:36:11.163Z\"}', '2025-09-17 13:36:11', '2025-09-17 13:36:11'),
(14, '4', '9873477232', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"919873477232\",\"timestamp\":\"2025-09-17T13:36:22.440Z\"}', '2025-09-17 13:36:22', '2025-09-17 13:36:22'),
(15, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T16:23:06.348Z\"}', '2025-09-17 16:23:06', '2025-09-17 16:23:06'),
(16, '2', '9873477232', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"919873477232\",\"timestamp\":\"2025-09-17T16:25:03.951Z\"}', '2025-09-17 16:25:03', '2025-09-17 16:25:03'),
(17, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T16:31:06.098Z\"}', '2025-09-17 16:31:06', '2025-09-17 16:31:06'),
(18, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T17:15:06.466Z\"}', '2025-09-17 17:15:06', '2025-09-17 17:15:06'),
(19, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T17:18:06.346Z\"}', '2025-09-17 17:18:06', '2025-09-17 17:18:06'),
(20, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T17:19:05.931Z\"}', '2025-09-17 17:19:05', '2025-09-17 17:19:05'),
(21, '2', '8700967004', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-17T17:23:06.600Z\"}', '2025-09-17 17:23:06', '2025-09-17 17:23:06'),
(22, '3', '7210608154', 'sent', 1, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T11:42:05.056Z\"}', '2025-09-18 11:42:05', '2025-09-18 11:42:05'),
(23, '4', '9873477232', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T11:43:04.489Z\"}', '2025-09-18 11:43:04', '2025-09-18 11:43:04'),
(24, '5', '8802036217', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:02.676Z\"}', '2025-09-18 17:47:02', '2025-09-18 17:47:02'),
(25, '6', '9868703516', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:06.100Z\"}', '2025-09-18 17:47:06', '2025-09-18 17:47:06'),
(26, '7', '8588889466', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:09.886Z\"}', '2025-09-18 17:47:09', '2025-09-18 17:47:09'),
(27, '8', '8826019166', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:12.319Z\"}', '2025-09-18 17:47:12', '2025-09-18 17:47:12'),
(28, '9', '8285500750', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:14.952Z\"}', '2025-09-18 17:47:14', '2025-09-18 17:47:14'),
(29, '10', '7428667993', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:18.380Z\"}', '2025-09-18 17:47:18', '2025-09-18 17:47:18'),
(30, '11', '9810973046', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-18T17:47:21.092Z\"}', '2025-09-18 17:47:21', '2025-09-18 17:47:21'),
(31, '2', '8700967004', 'sent', 5, 1, '{\"status\":true,\"message\":\"Message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-19T07:52:56.621Z\"}', '2025-09-19 07:52:56', '2025-09-19 07:52:56'),
(32, '2', '7210608154', 'sent', 5, 1, '{\"status\":true,\"message\":\"Message sent successfully\",\"to\":\"917210608154\",\"timestamp\":\"2025-09-19T07:52:57.045Z\"}', '2025-09-19 07:52:57', '2025-09-19 07:52:57'),
(33, '12', '8700967004', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-19T08:15:02.005Z\"}', '2025-09-19 08:15:02', '2025-09-19 08:15:02'),
(34, '12', '8700967004', 'sent', 4, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-19T08:27:01.892Z\"}', '2025-09-19 08:27:01', '2025-09-19 08:27:01'),
(35, '13', '7210608154', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"917210608154\",\"timestamp\":\"2025-09-19T08:27:07.055Z\"}', '2025-09-19 08:27:07', '2025-09-19 08:27:07'),
(36, '2', '8700967004', 'sent', 5, 1, '{\"status\":true,\"message\":\"Message sent successfully\",\"to\":\"918700967004\",\"timestamp\":\"2025-09-19T09:50:44.849Z\"}', '2025-09-19 09:50:44', '2025-09-19 09:50:44'),
(37, '2', '7210608154', 'sent', 5, 1, '{\"status\":true,\"message\":\"Message sent successfully\",\"to\":\"917210608154\",\"timestamp\":\"2025-09-19T09:50:45.434Z\"}', '2025-09-19 09:50:45', '2025-09-19 09:50:45'),
(38, '14', '9968053670', 'sent', 6, 1, '{\"status\":true,\"message\":\"Media message sent successfully\",\"to\":\"919968053670\",\"timestamp\":\"2025-09-19T09:52:03.031Z\"}', '2025-09-19 09:52:03', '2025-09-19 09:52:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_bulk_uploads`
--
ALTER TABLE `tbl_bulk_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_bulk_upload_records`
--
ALTER TABLE `tbl_bulk_upload_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_upload_id` (`upload_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_events`
--
ALTER TABLE `tbl_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `tbl_grades`
--
ALTER TABLE `tbl_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_name` (`grade_name`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `tbl_msg_queue`
--
ALTER TABLE `tbl_msg_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_event_id` (`event_id`);

--
-- Indexes for table `tbl_notification_setting`
--
ALTER TABLE `tbl_notification_setting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`);

--
-- Indexes for table `tbl_states`
--
ALTER TABLE `tbl_states`
  ADD PRIMARY KEY (`state_id`);

--
-- Indexes for table `tbl_subscription_log`
--
ALTER TABLE `tbl_subscription_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_wp_msg_logs`
--
ALTER TABLE `tbl_wp_msg_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_bulk_uploads`
--
ALTER TABLE `tbl_bulk_uploads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_bulk_upload_records`
--
ALTER TABLE `tbl_bulk_upload_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_grades`
--
ALTER TABLE `tbl_grades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_msg_queue`
--
ALTER TABLE `tbl_msg_queue`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tbl_notification_setting`
--
ALTER TABLE `tbl_notification_setting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_states`
--
ALTER TABLE `tbl_states`
  MODIFY `state_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `tbl_subscription_log`
--
ALTER TABLE `tbl_subscription_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_wp_msg_logs`
--
ALTER TABLE `tbl_wp_msg_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
