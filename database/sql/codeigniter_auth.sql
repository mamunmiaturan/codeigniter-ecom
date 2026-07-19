-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2026 at 05:00 AM
-- Server version: 9.6.0
-- PHP Version: 8.4.21
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */
;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */
;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */
;
/*!40101 SET NAMES utf8mb4 */
;
--
-- Database: `codeigniter_auth`
--

-- --------------------------------------------------------
--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int DEFAULT NULL,
  `module_name` varchar(100) DEFAULT NULL COMMENT 'e.g. Meal Booking, Finance',
  `table_name` varchar(150) NOT NULL,
  `row_id` int NOT NULL,
  `action` enum(
    'create',
    'update',
    'delete',
    'soft_delete',
    'restore',
    'login',
    'logout',
    'other'
  ) NOT NULL,
  `description` text COMMENT 'Human readable summary',
  `old_data` longtext COMMENT 'JSON string of previous state',
  `new_data` longtext COMMENT 'JSON string of new state',
  `route_path` varchar(255) DEFAULT NULL COMMENT 'URL that triggered the action',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (
    `id`,
    `user_id`,
    `module_name`,
    `table_name`,
    `row_id`,
    `action`,
    `description`,
    `old_data`,
    `new_data`,
    `route_path`,
    `ip_address`,
    `user_agent`,
    `created_at`
  )
VALUES (
    1,
    1,
    'Meals',
    'meals',
    1,
    'update',
    'Kacchi price changed from 250 to 280',
    NULL,
    NULL,
    NULL,
    '103.1.2.3',
    NULL,
    '2026-05-13 17:50:53'
  ),
  (
    2,
    2,
    'Meal Booking',
    'meal_booking',
    10,
    'delete',
    'Meal cancelled for user ID 10',
    NULL,
    NULL,
    NULL,
    '103.1.2.4',
    NULL,
    '2026-05-13 17:50:53'
  );
-- --------------------------------------------------------
--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int UNSIGNED NOT NULL DEFAULT '0',
  `data` blob NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `ci_sessions`
--

INSERT INTO `ci_sessions` (`id`, `ip_address`, `timestamp`, `data`)
VALUES (
    '68376039f733de2cc6293d4a99ea2491968668c4',
    '127.0.0.1',
    1778699301,
    0x5f5f63695f6c6173745f726567656e65726174657c693a313737383639393330313b5f5f63695f766172737c613a323a7b733a31393a22616c6572742d6d6573736167652d6572726f72223b733a333a226f6c64223b733a32313a22616c6572742d6d6573736167652d73756363657373223b733a333a226f6c64223b7d6e616d657c733a31353a224d616d756e204d696120547572616e223b756e6971756569647c733a373a225355502d303031223b6c6f676765725f70686f746f7c4e3b6c6f67676564696e5f69647c733a313a2231223b6c6f67676564696e5f726f6c655f69647c733a313a2231223b6c6f67676564696e5f7573657269647c733a313a2231223b646174655f666f726d61747c733a383a2225592d256d2d2564223b7365745f6c616e677c733a373a22456e676c697368223b6c6f67676564696e7c623a313b
  ),
  (
    'e1b1bb0b7d6d209748f9696c2afa4cbd64e2b524',
    '127.0.0.1',
    1778734761,
    0x5f5f63695f6c6173745f726567656e65726174657c693a313737383733343638333b72656469726563745f75726c7c733a32363a2268747470733a2f2f617574682e746573742f73657474696e6773223b6e616d657c733a31353a224d616d756e204d696120547572616e223b756e6971756569647c733a373a225355502d303031223b6c6f676765725f70686f746f7c4e3b6c6f67676564696e5f69647c733a313a2231223b6c6f67676564696e5f726f6c655f69647c733a313a2231223b6c6f67676564696e5f7573657269647c733a313a2231223b646174655f666f726d61747c733a383a2225592d256d2d2564223b7365745f6c616e677c733a373a22456e676c697368223b6c6f67676564696e7c623a313b5f5f63695f766172737c613a313a7b733a32313a22616c6572742d6d6573736167652d73756363657373223b733a333a226f6c64223b7d
  );
-- --------------------------------------------------------
--
-- Table structure for table `email_config`
--

CREATE TABLE `email_config` (
  `id` int NOT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `protocol` enum('smtp', 'mail', 'sendmail') NOT NULL DEFAULT 'smtp',
  `smtp_host` varchar(255) NOT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` text,
  `smtp_port` int NOT NULL DEFAULT '587',
  `encryption` enum('none', 'ssl', 'tls') DEFAULT 'tls',
  `status` enum('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `email_config`
--

INSERT INTO `email_config` (
    `id`,
    `from_email`,
    `protocol`,
    `smtp_host`,
    `smtp_user`,
    `smtp_pass`,
    `smtp_port`,
    `encryption`,
    `status`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    NULL,
    'smtp',
    'smtp.gmail.com',
    'noreply@auth.com.bd',
    'encrypted_password',
    587,
    'tls',
    'Active',
    '2026-05-13 23:50:53',
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `email_templates`
--

CREATE TABLE `email_templates` (
  `id` int NOT NULL,
  `template_key` varchar(150) NOT NULL COMMENT 'Unique identifier like invoice_mail, reset_password',
  `email_type` enum('System', 'Marketing', 'Notification') NOT NULL DEFAULT 'System',
  `subject` varchar(255) NOT NULL,
  `template_body` longtext NOT NULL,
  `available_tags` longtext COMMENT 'JSON or comma separated tags',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `email_templates`
--

INSERT INTO `email_templates` (
    `id`,
    `template_key`,
    `email_type`,
    `subject`,
    `template_body`,
    `available_tags`,
    `is_active`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    'order_confirmation',
    'System',
    'Auth Order Confirmed!',
    'Dear User, your order has been received. Thanks for staying with us.',
    NULL,
    1,
    '2026-05-13 23:50:53',
    NULL
  ),
  (
    2,
    'password_reset',
    'System',
    'Password Reset Request',
    'Please click the link below to reset your password.',
    NULL,
    1,
    '2026-05-13 23:50:53',
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `global_settings`
--

CREATE TABLE `global_settings` (
  `id` int NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `site_email` varchar(100) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `currency_symbol` varchar(5) DEFAULT '৳',
  `default_language` varchar(10) DEFAULT 'english',
  `timezone` varchar(50) DEFAULT 'Asia/Dhaka',
  `date_format` varchar(50) DEFAULT 'Y-m-d',
  `logo` varchar(255) DEFAULT NULL,
  `footer_text` text,
  `address` text,
  `mobile_no` varchar(60) DEFAULT NULL,
  `translation` varchar(50) DEFAULT 'english',
  `facebook_url` varchar(255) DEFAULT NULL,
  `twitter_url` varchar(255) DEFAULT NULL,
  `youtube_url` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `global_settings`
--

INSERT INTO `global_settings` (
    `id`,
    `site_name`,
    `site_email`,
    `currency`,
    `currency_symbol`,
    `default_language`,
    `timezone`,
    `date_format`,
    `logo`,
    `footer_text`,
    `address`,
    `mobile_no`,
    `translation`,
    `facebook_url`,
    `twitter_url`,
    `youtube_url`,
    `linkedin_url`,
    `instagram_url`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    'Auth1',
    'contact@auth.com.bd',
    'BDT',
    '৳',
    'english',
    'Asia/Dhaka',
    '%Y-%m-%d',
    NULL,
    '© 2026 Auth Bangladesh Ltd. All Rights Reserved.',
    'House 45, Road 12, Sector 7, Uttara, Dhaka-1230',
    '+8801700000000',
    'English',
    'https://facebook.com',
    'https://twitter.com',
    'https://youtube.com',
    'https://linkedin.com/company',
    'https://instagram.com',
    '2026-05-13 17:50:53',
    '2026-05-14 00:59:18'
  );
-- --------------------------------------------------------
--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL DEFAULT 'default',
  `payload` longtext NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `reserved_at` datetime DEFAULT NULL,
  `available_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;
--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (
    `id`,
    `queue`,
    `payload`,
    `attempts`,
    `reserved_at`,
    `available_at`,
    `created_at`
  )
VALUES (
    1,
    'default',
    '{\"job\":\"SendEmail\"}',
    0,
    NULL,
    NULL,
    '2026-05-13 17:50:53'
  );
-- --------------------------------------------------------
--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int NOT NULL,
  `word_key` varchar(255) NOT NULL,
  `english` text,
  `bengali` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (
    `id`,
    `word_key`,
    `english`,
    `bengali`,
    `created_at`
  )
VALUES (
    3,
    'login',
    'Login',
    'লগইন',
    '2026-05-13 23:50:53'
  ),
  (
    4,
    'email',
    'Email',
    'ইমেল',
    '2026-05-13 23:50:53'
  ),
  (
    5,
    'password',
    'Password',
    'পাসওয়ার্ড',
    '2026-05-13 23:50:53'
  ),
  (
    6,
    'site_name',
    'Site Name',
    'সাইটের নাম',
    '2026-05-13 23:50:53'
  ),
  (
    7,
    'dashboard',
    'Dashboard',
    '',
    '0000-00-00 00:00:00'
  ),
  (8, 'logout', 'Logout', '', '0000-00-00 00:00:00'),
  (
    9,
    'profile',
    'Profile',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    10,
    'reset_password',
    'Reset Password',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    11,
    'global',
    'Global',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    12,
    'settings',
    'Settings',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    13,
    'Database_Backup',
    'Database Backup',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    14,
    'Global_Setting',
    'Global Setting',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    15,
    'Module',
    'Module',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    16,
    'Role_&_Permission',
    'Role & Permission',
    '',
    '0000-00-00 00:00:00'
  ),
  (17, 'Users', 'Users', '', '0000-00-00 00:00:00'),
  (
    18,
    'user_list',
    'User List',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    19,
    'user_create',
    'User Create',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    20,
    'this_value_is_required',
    'This Value Is Required',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    21,
    'enter_valid_email',
    'Enter Valid Email',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    22,
    'are_you_sure',
    'Are You Sure',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    23,
    'delete_this_information',
    'Delete This Information',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    24,
    'yes_continue',
    'Yes Continue',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    25,
    'cancel',
    'Cancel',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    26,
    'deleted_note',
    'Deleted Note',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    27,
    'deleted',
    'Deleted',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    28,
    'information_deleted',
    'Information Deleted',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    29,
    'verify_this_audio',
    'Verify This Audio',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    30,
    'verify_note',
    'Verify Note',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    31,
    'verified',
    'Verified',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    32,
    'information_verified',
    'Information Verified',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    33,
    'Inactive_this_audio',
    'Inactive This Audio',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    34,
    'inactive_note',
    'Inactive Note',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    35,
    'inactivated',
    'Inactivated',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    36,
    'information_updated',
    'Information Updated',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    37,
    'varify_this_tts',
    'Varify This Tts',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    38,
    'Inactive_this_tts',
    'Inactive This Tts',
    '',
    '0000-00-00 00:00:00'
  ),
  (39, 'user', 'User', '', '0000-00-00 00:00:00'),
  (
    40,
    'add_user',
    'Add User',
    '',
    '0000-00-00 00:00:00'
  ),
  (41, 'name', 'Name', '', '0000-00-00 00:00:00'),
  (
    42,
    'mobile_no',
    'Mobile No',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    43,
    'date_of_birth',
    'Date Of Birth',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    44,
    'gender',
    'Gender',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    45,
    'select',
    'Select',
    '',
    '0000-00-00 00:00:00'
  ),
  (46, 'male', 'Male', '', '0000-00-00 00:00:00'),
  (
    47,
    'female',
    'Female',
    '',
    '0000-00-00 00:00:00'
  ),
  (48, 'other', 'Other', '', '0000-00-00 00:00:00'),
  (
    49,
    'blood_group',
    'Blood Group',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    50,
    'religion',
    'Religion',
    '',
    '0000-00-00 00:00:00'
  ),
  (51, 'islam', 'Islam', '', '0000-00-00 00:00:00'),
  (
    52,
    'hinduism',
    'Hinduism',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    53,
    'christianity',
    'Christianity',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    54,
    'buddhism',
    'Buddhism',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    55,
    'marital_status',
    'Marital Status',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    56,
    'single',
    'Single',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    57,
    'married',
    'Married',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    58,
    'widowed',
    'Widowed',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    59,
    'divorced',
    'Divorced',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    60,
    'separated',
    'Separated',
    '',
    '0000-00-00 00:00:00'
  ),
  (61, 'age', 'Age', '', '0000-00-00 00:00:00'),
  (
    62,
    'educational_qualification',
    'Educational Qualification',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    63,
    'nationality',
    'Nationality',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    64,
    'nid_no',
    'Nid No',
    '',
    '0000-00-00 00:00:00'
  ),
  (65, 'role', 'Role', '', '0000-00-00 00:00:00'),
  (
    66,
    'retype_password',
    'Retype Password',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    67,
    'status',
    'Status',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    68,
    'Active',
    'Active',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    69,
    'Inactive',
    'Inactive',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    70,
    'Suspended',
    'Suspended',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    71,
    'Blocked',
    'Blocked',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    72,
    'profile_picture',
    'Profile Picture',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    73,
    'address',
    'Address',
    '',
    '0000-00-00 00:00:00'
  ),
  (74, 'save', 'Save', '', '0000-00-00 00:00:00'),
  (
    75,
    'email_has_already_been_used',
    'Email Has Already Been Used',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    76,
    'you_do_not_have_permission_to_assign_this_role',
    'You Do Not Have Permission To Assign This Role',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    77,
    'information_has_been_saved_successfully',
    'Information Has Been Saved Successfully',
    '',
    '0000-00-00 00:00:00'
  ),
  (78, 'list', 'List', '', '0000-00-00 00:00:00'),
  (79, 'sl', 'Sl', '', '0000-00-00 00:00:00'),
  (80, 'photo', 'Photo', '', '0000-00-00 00:00:00'),
  (
    81,
    'user_id',
    'User Id',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    82,
    'action',
    'Action',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    83,
    'login_as_user',
    'Login As User',
    '',
    '0000-00-00 00:00:00'
  ),
  (84, 'edit', 'Edit', '', '0000-00-00 00:00:00'),
  (
    85,
    'edit_user',
    'Edit User',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    86,
    'update',
    'Update',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    87,
    'birthday',
    'Birthday',
    '',
    '0000-00-00 00:00:00'
  ),
  (88, 'phone', 'Phone', '', '0000-00-00 00:00:00'),
  (
    89,
    'authentication',
    'Authentication',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    90,
    'basic_details',
    'Basic Details',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    91,
    'emergency_contact',
    'Emergency Contact',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    92,
    'login_details',
    'Login Details',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    93,
    'login_authentication_deactivate',
    'Login Authentication Deactivate',
    '',
    '0000-00-00 00:00:00'
  ),
  (94, 'close', 'Close', '', '0000-00-00 00:00:00'),
  (
    95,
    'user_profile',
    'User Profile',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    96,
    'database',
    'Database',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    97,
    'restore',
    'Restore',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    98,
    'create_backup',
    'Create Backup',
    '',
    '0000-00-00 00:00:00'
  ),
  (99, 'file', 'File', '', '0000-00-00 00:00:00'),
  (
    100,
    'backup_size',
    'Backup Size',
    '',
    '0000-00-00 00:00:00'
  ),
  (101, 'date', 'Date', '', '0000-00-00 00:00:00'),
  (
    102,
    'file_upload',
    'File Upload',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    103,
    'module_and_permission',
    'Module And Permission',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    104,
    'module_list',
    'Module List',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    105,
    'create_module',
    'Create Module',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    106,
    'module_details',
    'Module Details',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    107,
    'permissions',
    'Permissions',
    '',
    '0000-00-00 00:00:00'
  ),
  (108, 'view', 'View', '', '0000-00-00 00:00:00'),
  (109, 'add', 'Add', '', '0000-00-00 00:00:00'),
  (
    110,
    'delete',
    'Delete',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    111,
    'module_type',
    'Module Type',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    112,
    'select_module_type',
    'Select Module Type',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    113,
    'existing_module',
    'Existing Module',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    114,
    'select_module_name',
    'Select Module Name',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    115,
    'permission_name',
    'Permission Name',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    116,
    'module_name',
    'Module Name',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    117,
    'select_all',
    'Select All',
    '',
    '0000-00-00 00:00:00'
  ),
  (118, 'roles', 'Roles', '', '0000-00-00 00:00:00'),
  (
    119,
    'create',
    'Create',
    '',
    '0000-00-00 00:00:00'
  ),
  (120, 'id', 'Id', '', '0000-00-00 00:00:00'),
  (
    121,
    'system_role',
    'System Role',
    '',
    '0000-00-00 00:00:00'
  ),
  (122, 'yes', 'Yes', '', '0000-00-00 00:00:00'),
  (
    123,
    'permission',
    'Permission',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    124,
    'general',
    'General',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    125,
    'setting',
    'Setting',
    '',
    '0000-00-00 00:00:00'
  ),
  (126, 'theme', 'Theme', '', '0000-00-00 00:00:00'),
  (127, 'logo', 'Logo', '', '0000-00-00 00:00:00'),
  (
    128,
    'system_name',
    'System Name',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    129,
    'system_email',
    'System Email',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    130,
    'currency',
    'Currency',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    131,
    'currency_symbol',
    'Currency Symbol',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    132,
    'language',
    'Language',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    133,
    'timezone',
    'Timezone',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    134,
    'date_format',
    'Date Format',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    135,
    'footer_text',
    'Footer Text',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    136,
    'system_logo',
    'System Logo',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    137,
    'text_logo',
    'Text Logo',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    138,
    'upload',
    'Upload',
    '',
    '0000-00-00 00:00:00'
  ),
  (139, 'bulk', 'Bulk', '', '0000-00-00 00:00:00'),
  (
    140,
    'add_employees',
    'Add Employees',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    141,
    'add_products',
    'Add Products',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    142,
    'employee',
    'Employee',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    143,
    'add_employee',
    'Add Employee',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    144,
    'all_employee',
    'All Employee',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    145,
    'product',
    'Product',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    146,
    'add_product',
    'Add Product',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    147,
    'all_product',
    'All Product',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    148,
    'assigned ',
    'Assigned ',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    149,
    'asset_transfer',
    'Asset Transfer',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    150,
    'transfer_tracking',
    'Transfer Tracking',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    151,
    'all_category',
    'All Category',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    152,
    'all_department',
    'All Department',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    153,
    'all_designation',
    'All Designation',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    154,
    'all_location',
    'All Location',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    155,
    'all_sbu',
    'All Sbu',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    156,
    'role_permission',
    'Role Permission',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    157,
    'Administrator',
    'Administrator',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    158,
    'all_users',
    'All Users',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    159,
    'the_configuration_has_been_updated',
    'The Configuration Has Been Updated',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    160,
    'role_permission_for',
    'Role Permission For',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    161,
    'feature',
    'Feature',
    '',
    '0000-00-00 00:00:00'
  ),
  (
    162,
    'information_has_been_updated_successfully',
    'Information Has Been Updated Successfully',
    '',
    '0000-00-00 00:00:00'
  );
-- --------------------------------------------------------
--
-- Table structure for table `language_list`
--

CREATE TABLE `language_list` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL COMMENT 'en, bn, etc',
  `status` enum('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `language_list`
--

INSERT INTO `language_list` (
    `id`,
    `name`,
    `code`,
    `status`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    'English',
    'english',
    'Active',
    '2026-05-13 23:50:53',
    NULL
  ),
  (
    2,
    'Bengali',
    'bengali',
    'Active',
    '2026-05-13 23:50:53',
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `timestamp` int NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;
--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `email`, `timestamp`)
VALUES (
    2,
    '103.1.2.3',
    'turan.dev.bd@gmail.com',
    1778694653
  );
-- --------------------------------------------------------
--
-- Table structure for table `login_credential`
--

CREATE TABLE `login_credential` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` int NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `device_token` text COMMENT 'For mobile push notifications',
  `last_login` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL COMMENT 'For soft delete'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `login_credential`
--

INSERT INTO `login_credential` (
    `id`,
    `user_id`,
    `email`,
    `password`,
    `role`,
    `active`,
    `device_token`,
    `last_login`,
    `created_at`,
    `updated_at`,
    `deleted_at`
  )
VALUES (
    1,
    1,
    'turan.dev.bd@gmail.com',
    '$2y$12$jSFEUUG0IepKTtmd4fvGk.twlsy6vGLlkmAzWcO1VpCfR71QcrWHK',
    1,
    1,
    NULL,
    '2026-05-14 10:58:10',
    '2026-05-13 23:50:53',
    '2026-05-14 10:58:10',
    NULL
  ),
  (
    2,
    2,
    'admin@gmail.com',
    '$2y$12$jSFEUUG0IepKTtmd4fvGk.twlsy6vGLlkmAzWcO1VpCfR71QcrWHK',
    2,
    1,
    NULL,
    NULL,
    '2026-05-13 23:50:53',
    NULL,
    NULL
  ),
  (
    3,
    3,
    'kifyz@mailinator.com',
    '$2y$12$0URa5PEAtCeXIaIlnTjJ0et.jeYAK9IrhUdLoKz3Keb.VniqsKgnO',
    2,
    1,
    NULL,
    NULL,
    '2026-05-14 00:07:11',
    NULL,
    NULL
  ),
  (
    4,
    4,
    'vuti@mailinator.com',
    '$2y$12$IrWUDDyfyUzIh.P7hpcN0.W4L6fgZwxsX0cdoXozCHkXNqbK8qUqW',
    2,
    1,
    NULL,
    NULL,
    '2026-05-14 00:08:05',
    NULL,
    NULL
  ),
  (
    5,
    5,
    'xizararefe@mailinator.com',
    '$2y$12$ro/F/bLZJfox5E8TBsG90utP9KjedUGiVd/ClqF.QwcE3HVKpIKuS',
    2,
    1,
    NULL,
    NULL,
    '2026-05-14 00:08:43',
    NULL,
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (`version` bigint NOT NULL) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;
--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`version`)
VALUES (20260512000022);
-- --------------------------------------------------------
--
-- Table structure for table `otp_history`
--

CREATE TABLE `otp_history` (
  `id` int UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb3;
--
-- Dumping data for table `otp_history`
--

INSERT INTO `otp_history` (
    `id`,
    `phone`,
    `otp_code`,
    `is_verified`,
    `created_at`
  )
VALUES (
    1,
    '01711223344',
    '456123',
    1,
    '2026-05-13 17:50:53'
  ),
  (
    2,
    '01811223344',
    '789456',
    0,
    '2026-05-13 17:50:53'
  );
-- --------------------------------------------------------
--
-- Table structure for table `permission`
--

CREATE TABLE `permission` (
  `id` int NOT NULL,
  `module_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `prefix` varchar(100) NOT NULL,
  `show_view` tinyint DEFAULT '1',
  `show_add` tinyint DEFAULT '1',
  `show_edit` tinyint DEFAULT '1',
  `show_delete` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `permission`
--

INSERT INTO `permission` (
    `id`,
    `module_id`,
    `name`,
    `prefix`,
    `show_view`,
    `show_add`,
    `show_edit`,
    `show_delete`,
    `created_at`
  )
VALUES
  (1,  1,  'Users',                       'user',                        1, 1, 1, 1, NULL),
  (2,  1,  'User Disable Authentication', 'user_disable_authentication', 1, 0, 0, 0, NULL),
  (3,  2,  'Global Setting',              'global_setting',              1, 0, 1, 0, NULL),
  (4,  2,  'Database Backup',             'database_backup',             1, 1, 0, 1, NULL),
  (5,  2,  'Database Restore',            'database_restore',            0, 1, 0, 0, NULL),
  (6,  3,  'Email Setting',               'email_setting',               1, 0, 1, 0, NULL),
  (7,  3,  'Email Logs',                  'email_log',                   1, 0, 0, 1, NULL),
  (8,  4,  'SMS Setting',                 'sms_setting',                 1, 1, 1, 0, NULL),
  (9,  4,  'SMS Logs',                    'sms_logs',                    1, 0, 0, 0, NULL),
  (10, 4,  'Send SMS',                    'send_sms',                    1, 1, 0, 0, NULL),
  (11, 5,  'Language',                    'language',                    1, 1, 1, 1, NULL),
  (12, 6,  'Modules',                     'modules',                     1, 1, 1, 1, NULL),
  (13, 6,  'Role & Permission',           'role_permission',             1, 1, 1, 1, NULL),
  (14, 7,  'Imports',                     'imports',                     1, 1, 0, 0, NULL),
  (15, 8,  'Activity Log',               'activity_log',                1, 0, 0, 0, NULL),
  (16, 9,  'System Log',                  'system_log',                  1, 0, 0, 1, NULL),
  (17, 10, 'Notifications',               'notifications',               1, 0, 0, 0, NULL);
-- --------------------------------------------------------
--
-- Table structure for table `permission_modules`
--

CREATE TABLE `permission_modules` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `prefix` varchar(50) NOT NULL,
  `system` tinyint(1) NOT NULL,
  `sorted` tinyint NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `permission_modules`
--

INSERT INTO `permission_modules` (
    `id`,
    `name`,
    `prefix`,
    `system`,
    `sorted`,
    `created_at`,
    `updated_at`
  )
VALUES
  (1,  'Users',              'users',         0, 1,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (2,  'Settings',           'settings',      0, 2,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (3,  'Email',              'email',         0, 3,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (4,  'SMS',                'sms',           0, 4,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (5,  'Language',           'language',      0, 5,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (6,  'Module & Permission','module',        0, 6,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (7,  'Import',             'import',        0, 7,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (8,  'Activity Logs',      'activity_logs', 0, 8,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (9,  'System Logs',        'system_logs',   0, 9,  '0000-00-00 00:00:00', '2026-05-13 23:50:53'),
  (10, 'Notifications',      'notifications', 0, 10, '0000-00-00 00:00:00', '2026-05-13 23:50:53');
-- --------------------------------------------------------
--
-- Table structure for table `reset_password`
--

CREATE TABLE `reset_password` (
  `key` longtext NOT NULL,
  `username` varchar(100) NOT NULL,
  `login_credential_id` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `reset_password`
--

INSERT INTO `reset_password` (
    `key`,
    `username`,
    `login_credential_id`,
    `created_at`
  )
VALUES (
    'RESET-KEY-ABC-123',
    'superman',
    '1',
    '2026-05-13 17:50:53'
  );
-- --------------------------------------------------------
--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `is_system` varchar(10) NOT NULL,
  `short_form` varchar(20) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (
    `id`,
    `name`,
    `prefix`,
    `is_system`,
    `short_form`
  )
VALUES (1, 'Superman', NULL, '1', NULL),
  (2, 'Admin', NULL, '1', NULL);
-- --------------------------------------------------------
--
-- Table structure for table `sms_config`
--

CREATE TABLE `sms_config` (
  `id` int NOT NULL,
  `clickatell_username` varchar(255) NOT NULL,
  `clickatell_password` varchar(255) NOT NULL,
  `clickatell_api_key` varchar(255) NOT NULL,
  `clickatell_number` varchar(255) NOT NULL,
  `twilio_account_sid` varchar(255) NOT NULL,
  `twilio_auth_token` varchar(255) NOT NULL,
  `twilio_number` varchar(255) NOT NULL,
  `active_gateway` varchar(50) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `sms_config`
--

INSERT INTO `sms_config` (
    `id`,
    `clickatell_username`,
    `clickatell_password`,
    `clickatell_api_key`,
    `clickatell_number`,
    `twilio_account_sid`,
    `twilio_auth_token`,
    `twilio_number`,
    `active_gateway`
  )
VALUES (
    1,
    'test_user',
    'test_pass',
    'CLICK-API-123',
    '12345',
    'TW-SID-123',
    'TW-TOKEN-123',
    '67890',
    'clickatell'
  );
-- --------------------------------------------------------
--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int NOT NULL,
  `user_id` bigint NOT NULL,
  `sms_text` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` bigint DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `status` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` bigint DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (
    `id`,
    `user_id`,
    `sms_text`,
    `template_id`,
    `datetime`,
    `status`,
    `remarks`,
    `created_by`,
    `updated_by`,
    `created_at`,
    `updated_at`,
    `deleted_at`
  )
VALUES (
    1,
    1,
    'Your OTP is 456123',
    NULL,
    '2026-05-13 17:50:53',
    'Delivered',
    NULL,
    'System',
    NULL,
    '2026-05-13 17:50:53',
    NULL,
    NULL
  ),
  (
    2,
    1,
    'Booking Confirmed!',
    NULL,
    '2026-05-13 17:50:53',
    'Failed',
    NULL,
    'System',
    NULL,
    '2026-05-13 17:50:53',
    NULL,
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int NOT NULL,
  `sms_type` varchar(200) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `template_body` longtext NOT NULL,
  `tags` longtext NOT NULL,
  `notified` int NOT NULL DEFAULT '1'
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (
    `id`,
    `sms_type`,
    `subject`,
    `template_body`,
    `tags`,
    `notified`
  )
VALUES (
    1,
    'otp_login',
    'OTP Login',
    'Apnar Auth login OTP holo {otp}. Do not share.',
    '{otp}',
    1
  ),
  (
    2,
    'booking_success',
    'Booking Success',
    'Apnar {date} tarikh-er meal booking confirm hoyeche. Thanks!',
    '{date}',
    1
  );
-- --------------------------------------------------------
--
-- Table structure for table `theme_settings`
--

CREATE TABLE `theme_settings` (
  `id` int NOT NULL,
  `branch_id` int DEFAULT NULL COMMENT 'NULL for global default',
  `primary_color` varchar(20) DEFAULT '#007bff',
  `secondary_color` varchar(20) DEFAULT '#6c757d',
  `sidebar_color` varchar(20) DEFAULT '#343a40',
  `sidebar_text_color` varchar(20) DEFAULT '#ffffff',
  `navbar_color` varchar(20) DEFAULT '#ffffff',
  `navbar_text_color` varchar(20) DEFAULT '#343a40',
  `dark_mode` tinyint(1) NOT NULL DEFAULT '0',
  `border_mode` enum('Rounded', 'Square') DEFAULT 'Rounded',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `theme_settings`
--

INSERT INTO `theme_settings` (
    `id`,
    `branch_id`,
    `primary_color`,
    `secondary_color`,
    `sidebar_color`,
    `sidebar_text_color`,
    `navbar_color`,
    `navbar_text_color`,
    `dark_mode`,
    `border_mode`,
    `created_at`,
    `updated_at`
  )
VALUES (
    1,
    NULL,
    '#E63946',
    '#1D3557',
    '#343a40',
    '#ffffff',
    '#ffffff',
    '#343a40',
    0,
    'Rounded',
    '2026-05-13 23:50:53',
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `user_id` varchar(50) DEFAULT NULL COMMENT 'Human-readable ID',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `gender` enum('Male', 'Female', 'Other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `age` int DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` enum('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single',
  `nid_no` varchar(50) DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `educational_qualification` varchar(255) DEFAULT NULL,
  `address` text,
  `status` enum('Active', 'Inactive', 'Blocked', 'Suspended') NOT NULL DEFAULT 'Active',
  `created_by` bigint DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL COMMENT 'For soft delete'
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
--
-- Dumping data for table `users`
--

INSERT INTO `users` (
    `id`,
    `user_id`,
    `name`,
    `email`,
    `mobile_no`,
    `photo`,
    `gender`,
    `dob`,
    `age`,
    `blood_group`,
    `religion`,
    `marital_status`,
    `nid_no`,
    `nationality`,
    `educational_qualification`,
    `address`,
    `status`,
    `created_by`,
    `updated_by`,
    `created_at`,
    `updated_at`,
    `deleted_at`
  )
VALUES (
    1,
    'SUP-001',
    'Mamun Mia Turan',
    'turan.dev.bd@gmail.com',
    '01965572363',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'Single',
    NULL,
    NULL,
    NULL,
    NULL,
    'Active',
    1,
    NULL,
    '2026-05-13 23:50:53',
    NULL,
    NULL
  ),
  (
    2,
    'ADM-001',
    'System Admin',
    'admin@gmail.com',
    '01700000002',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    NULL,
    'Single',
    NULL,
    NULL,
    NULL,
    NULL,
    'Active',
    1,
    NULL,
    '2026-05-13 23:50:53',
    NULL,
    NULL
  ),
  (
    3,
    '4c30cac',
    'Rafael Foster',
    'kifyz@mailinator.com',
    'Nostrum vero dolore',
    'defualt.png',
    'Male',
    '1970-01-01',
    64,
    'O-',
    '1',
    'Single',
    'Aut exercitationem q',
    'Ea voluptates adipis',
    'Qui ea aut ut incidi',
    'Amet ex et duis des',
    'Blocked',
    NULL,
    NULL,
    '2026-05-14 00:07:11',
    NULL,
    NULL
  ),
  (
    4,
    'a964e92',
    'Scott Cross',
    'vuti@mailinator.com',
    'Dolorum et sed asper',
    'defualt.png',
    'Other',
    '1970-01-01',
    53,
    'AB-',
    '5',
    'Widowed',
    'Accusantium debitis',
    'Hic illum mollit el',
    'Perferendis cillum a',
    'Quibusdam qui conseq',
    'Suspended',
    NULL,
    NULL,
    '2026-05-14 00:08:04',
    NULL,
    NULL
  ),
  (
    5,
    '41c2b16',
    'Stella Guy',
    'xizararefe@mailinator.com',
    'Ut suscipit ullamco',
    'defualt.png',
    'Male',
    '1970-01-01',
    95,
    'B-',
    '5',
    'Single',
    'Dolore nisi aliquam',
    'Consequatur sint te',
    'Tempora eveniet ear',
    'Lorem aspernatur lab',
    'Blocked',
    NULL,
    NULL,
    '2026-05-14 00:08:42',
    NULL,
    NULL
  );
-- --------------------------------------------------------
--
-- Table structure for table `user_privileges`
--

CREATE TABLE `user_privileges` (
  `id` int NOT NULL,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  `is_add` tinyint(1) NOT NULL,
  `is_edit` tinyint(1) NOT NULL,
  `is_view` tinyint(1) NOT NULL,
  `is_delete` tinyint(1) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = latin2;
--
-- Dumping data for table `user_privileges`
--

INSERT INTO `user_privileges` (
    `id`,
    `role_id`,
    `permission_id`,
    `is_add`,
    `is_edit`,
    `is_view`,
    `is_delete`
  )
VALUES
  -- Superman (role_id=1): full access to everything
  (1,  1, 1,  1, 1, 1, 1),
  (2,  1, 2,  1, 1, 1, 1),
  (3,  1, 3,  1, 1, 1, 1),
  (4,  1, 4,  1, 1, 1, 1),
  (5,  1, 5,  1, 1, 1, 1),
  (6,  1, 6,  1, 1, 1, 1),
  (7,  1, 7,  1, 1, 1, 1),
  (8,  1, 8,  1, 1, 1, 1),
  (9,  1, 9,  1, 1, 1, 1),
  (10, 1, 10, 1, 1, 1, 1),
  (11, 1, 11, 1, 1, 1, 1),
  (12, 1, 12, 1, 1, 1, 1),
  (13, 1, 13, 1, 1, 1, 1),
  (14, 1, 14, 1, 1, 1, 1),
  (15, 1, 15, 1, 1, 1, 1),
  (16, 1, 16, 1, 1, 1, 1),
  (17, 1, 17, 1, 1, 1, 1),
  -- Admin (role_id=2): limited access
  (18, 2, 1,  1, 1, 1, 0),
  (19, 2, 2,  0, 0, 1, 0),
  (20, 2, 6,  0, 1, 1, 0),
  (21, 2, 7,  0, 0, 1, 0),
  (22, 2, 8,  1, 1, 1, 0),
  (23, 2, 9,  0, 0, 1, 0),
  (24, 2, 10, 1, 0, 1, 0),
  (25, 2, 11, 1, 1, 1, 1),
  (26, 2, 14, 1, 0, 1, 0),
  (27, 2, 15, 0, 0, 1, 0),
  (28, 2, 17, 0, 0, 1, 0);
--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `email_config`
--
ALTER TABLE `email_config`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `template_key` (`template_key`);
--
-- Indexes for table `global_settings`
--
ALTER TABLE `global_settings`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `word_key` (`word_key`);
--
-- Indexes for table `language_list`
--
ALTER TABLE `language_list`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);
--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `login_credential`
--
ALTER TABLE `login_credential`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);
--
-- Indexes for table `otp_history`
--
ALTER TABLE `otp_history`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `permission`
--
ALTER TABLE `permission`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `permission_modules`
--
ALTER TABLE `permission_modules`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `sms_config`
--
ALTER TABLE `sms_config`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `theme_settings`
--
ALTER TABLE `theme_settings`
ADD PRIMARY KEY (`id`);
--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);
--
-- Indexes for table `user_privileges`
--
ALTER TABLE `user_privileges`
ADD PRIMARY KEY (`id`);
--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `email_config`
--
ALTER TABLE `email_config`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `email_templates`
--
ALTER TABLE `email_templates`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `global_settings`
--
ALTER TABLE `global_settings`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 163;
--
-- AUTO_INCREMENT for table `language_list`
--
ALTER TABLE `language_list`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `login_credential`
--
ALTER TABLE `login_credential`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 6;
--
-- AUTO_INCREMENT for table `otp_history`
--
ALTER TABLE `otp_history`
MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `permission`
--
ALTER TABLE `permission`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 18;
--
-- AUTO_INCREMENT for table `permission_modules`
--
ALTER TABLE `permission_modules`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 11;
--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `sms_config`
--
ALTER TABLE `sms_config`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
--
-- AUTO_INCREMENT for table `theme_settings`
--
ALTER TABLE `theme_settings`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 6;
--
-- AUTO_INCREMENT for table `user_privileges`
--
ALTER TABLE `user_privileges`
MODIFY `id` int NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 29;
COMMIT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */
;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */
;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */
;