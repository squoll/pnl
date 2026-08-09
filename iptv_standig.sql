-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Янв 01 2026 г., 11:17
-- Версия сервера: 10.11.6-MariaDB-0+deb12u1
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `iptv_standig`
--

-- --------------------------------------------------------

--
-- Структура таблицы `blocked_ips`
--

CREATE TABLE `blocked_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `blocked_at` datetime NOT NULL,
  `blocked_until` datetime NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `attempts_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) DEFAULT 0,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `username`, `attempt_time`, `success`, `user_agent`) VALUES
(84, '212.142.127.128', 'Squoll', '2025-12-31 14:25:15', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36'),
(85, '109.73.109.78', 'Squoll', '2026-01-01 10:48:58', 1, 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36');

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `logs`
--

INSERT INTO `logs` (`id`, `action`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'login_success', 'User: Squoll, IP: 109.73.109.78', '109.73.109.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-13 13:35:59');

-- --------------------------------------------------------

--
-- Структура таблицы `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `security_logs`
--

INSERT INTO `security_logs` (`id`, `event_type`, `ip_address`, `username`, `description`, `created_at`, `severity`) VALUES
(79, 'logs_cleared', '109.73.109.78', 'Squoll', 'All logs cleared', '2025-12-25 13:09:34', 'high'),
(80, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-26 10:29:13', 'low'),
(81, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-26 12:25:41', 'low'),
(82, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-27 09:49:48', 'low'),
(83, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-27 10:18:09', 'low'),
(84, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-28 09:25:12', 'low'),
(85, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-28 09:25:14', 'low'),
(86, 'login_success', '104.28.222.35', 'Squoll', 'Login success', '2025-12-28 09:31:05', 'low'),
(87, 'login_success', '104.28.254.35', 'Squoll', 'Login success', '2025-12-28 11:24:42', 'low'),
(88, 'login_success', '104.28.254.35', 'Squoll', 'Login success', '2025-12-28 11:24:44', 'low'),
(89, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-29 07:13:29', 'low'),
(90, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-29 12:48:39', 'low'),
(91, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-29 12:48:41', 'low'),
(92, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-29 13:08:52', 'low'),
(93, 'login_success', '77.219.3.71', 'Squoll', 'Login success', '2025-12-29 13:13:02', 'low'),
(94, 'login_success', '77.219.6.191', 'Squoll', 'Login success', '2025-12-29 14:46:37', 'low'),
(95, 'login_success', '77.219.6.191', 'Squoll', 'Login success', '2025-12-29 14:46:40', 'low'),
(96, 'login_success', '77.219.6.191', 'Squoll', 'Login success', '2025-12-29 14:47:12', 'low'),
(97, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-29 18:11:30', 'low'),
(98, 'session_hijack_attempt', '109.73.109.78', 'Squoll', 'Попытка перехвата сессии - смена IP', '2025-12-29 20:00:11', 'critical'),
(99, 'session_hijack_attempt', '109.73.109.78', 'Squoll', 'Попытка перехвата сессии - смена IP', '2025-12-29 20:00:11', 'critical'),
(100, 'logout', '109.73.109.78', 'Squoll', 'Пользователь вышел из системы', '2025-12-29 20:00:11', 'low'),
(101, 'logout', '109.73.109.78', 'Squoll', 'Пользователь вышел из системы', '2025-12-29 20:00:11', 'low'),
(102, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-30 18:01:22', 'low'),
(103, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-31 08:22:58', 'low'),
(104, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-31 08:51:15', 'low'),
(105, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2025-12-31 10:13:51', 'low'),
(106, 'session_hijack_attempt', '212.142.127.128', 'Squoll', 'Попытка перехвата сессии - смена IP', '2025-12-31 13:51:53', 'critical'),
(107, 'logout', '212.142.127.128', 'Squoll', 'Пользователь вышел из системы', '2025-12-31 13:51:53', 'low'),
(108, 'login_success', '212.142.127.128', 'Squoll', 'Login success', '2025-12-31 14:25:15', 'low'),
(109, 'login_success', '109.73.109.78', 'Squoll', 'Login success', '2026-01-01 10:48:58', 'low');

-- --------------------------------------------------------

--
-- Структура таблицы `tv_clients`
--

CREATE TABLE `tv_clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `provider_id` int(11) NOT NULL,
  `subscription_date` date NOT NULL,
  `months` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `device_count` int(11) NOT NULL,
  `viewing_program` varchar(255) NOT NULL,
  `paid` decimal(10,2) DEFAULT 0.00,
  `provider_cost` decimal(10,2) DEFAULT 0.00,
  `earned` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_clients`
--

INSERT INTO `tv_clients` (`id`, `first_name`, `phone`, `address`, `latitude`, `longitude`, `provider_id`, `subscription_date`, `months`, `login`, `password`, `device_count`, `viewing_program`, `paid`, `provider_cost`, `earned`, `created_at`, `notes`) VALUES
(1, 'Maltas', '26031625', 'Maltas iela 17 - 23', 56.90449680, 24.19417310, 3, '2025-10-26', 12, 'stas.pudov@gmail.com', 'Hos624ben', 1, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-11-17 12:33:05', 'Malenas'),
(2, 'Arnis', '29177527', 'Plavnieku iela 2 - 30', 56.94214860, 24.21012020, 3, '2025-10-09', 13, 'stas.pudov@gmail.com', 'Hos624ben', 2, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-11-17 12:37:16', NULL),
(3, 'Svetlana', '29987940', 'Vangazi Priezu 1-39', NULL, NULL, 2, '2025-09-03', 12, 'vangazi@standigital.lv', 'prista', 2, 'Lampa', 0.00, 0.00, 0.00, '2024-11-17 12:59:00', NULL),
(8, 'Elviras', '26994898', 'Marupe', 56.90488170, 24.04381800, 2, '2025-05-09', 9, 'elviras@foksy.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:07:43', NULL),
(9, 'Elviras_DED', 'Телефон - натальи', 'Elviras 13', 56.94673020, 24.05384610, 2, '2025-01-25', 12, 'elviras@foksy.lv', 'prista', 1, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:08:36', NULL),
(10, 'Игорь', '29262995', 'Purvciems', 56.95700950, 24.17898690, 2, '2025-03-20', 12, 'igorpr@foksy.lv', 'prista', 1, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:09:35', NULL),
(11, 'IPTV', '28396989', 'IPTV', NULL, NULL, 2, '2025-10-06', 12, 'iptv@foksy.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:12:43', NULL),
(12, 'Lielvarde', '26829329', 'Lielvarde', 56.72636930, 24.79589200, 2, '2025-04-13', 12, 'lielvarde@standigital.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:13:40', NULL),
(14, 'Lielvardes iela 107', '26802458', 'Lielvardes 107 - 68', 55.90691900, 27.00509010, 4, '2025-10-06', 8, 'lielvardes@standigital.lv', 'Hos34ben', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:16:32', NULL),
(15, 'Robezu', '27496590', 'Robezu iela', 56.94631110, 24.24762740, 2, '2025-03-29', 12, 'robezu@foksy.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:18:04', NULL),
(17, 'Tel', '29289607', 'Neznaju', NULL, NULL, 2, '2025-09-26', 12, 'tel@foksy.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:20:05', NULL),
(18, 'Valmiera', '26853348', 'Valmiera', 57.53891480, 25.42616880, 2, '2025-07-07', 9, 'valmiera@foksy.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:20:47', NULL),
(19, 'Vejinu', '29232460', 'Vejinu Marupe', 56.89083410, 24.02139010, 2, '2025-09-18', 12, 'vejinu@standigital.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:21:33', NULL),
(21, 'Zalenieki', '29229626', 'Zalenieki, Centra iela 8', NULL, NULL, 2, '2025-06-01', 12, 'zalenieki@standigital.lv', 'prista', 2, 'OttPlayer', 0.00, 0.00, 0.00, '2024-11-17 13:23:30', NULL),
(23, 'Алексей', '27723180', 'Ciekurkalna 2 linija 26, kod 235, 25 kvartira', NULL, NULL, 3, '2025-11-13', 12, 'cekur@standigital.lv', 'prista', 1, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-11-18 10:33:33', NULL),
(25, 'Родители Серёги', '+37129668275', 'Ivana kapi', 56.94376690, 24.14725160, 3, '2025-11-24', 12, 'serad@standigital.lv', 'Hos34beb', 2, 'Ott-play', 0.00, 0.00, 0.00, '2024-11-25 16:54:07', NULL),
(26, 'Liliju a', '29 486 271', 'Liliju 32, Mārupe', 56.90196080, 24.04046010, 3, '2025-11-26', 12, 'liliju@standigital.lv', 'Hos34ben', 2, 'Ott-play', 0.00, 0.00, 0.00, '2024-11-27 15:01:21', NULL),
(27, 'Dzirnavu', '27090085', 'Dzirnavu 13 - 22', 56.95446010, 24.12027020, 3, '2025-11-27', 12, 'seiles@standigital.lv', 'Hos34ben', 1, 'Ott-play', 0.00, 0.00, 0.00, '2024-11-27 16:28:28', NULL),
(28, 'Valerijas', '+37129395129', '14, улица Валерияс Сейлес, Latgales apkaime, Рига, LV-1019, Латвия', 56.93491940, 24.16012530, 3, '2025-11-23', 10, 'Valerijas@standigital.lv', 'Hos34ben', 1, 'Televizio', 0.00, 0.00, 0.00, '2024-11-27 17:44:04', NULL),
(29, 'Igors', '+371 27 083 412', '+371 27 083 412', NULL, NULL, 3, '2025-11-24', 12, 'Igors@standigital.lv', 'Hos34ben', 2, 'Ottplayer', 0.00, 0.00, 0.00, '2024-11-28 12:43:44', NULL),
(30, 'Valentina', '+37128649326', 'Plavnieku 5 - 66', 56.94214860, 24.21012020, 3, '2025-12-02', 12, 'plavnieku5@standigital.lv', 'Hos34ben', 2, 'Ott-play', 50.00, 12.00, 38.00, '2024-12-07 09:10:02', NULL),
(31, 'Katolu', '+37125131918', 'Katolu 7 - 52', 56.94458790, 24.13592930, 3, '2025-12-02', 12, 'katolu7@standigital.lv', 'Hos34ben', 2, 'Ott-play', 50.00, 12.00, 38.00, '2024-12-07 10:38:14', NULL),
(32, 'Серёгин отчим', '+37129996005', 'Centr', 56.95812750, 24.12326700, 3, '2025-12-07', 12, 'babser@standigital.lv', 'Hos34ben', 1, 'Ott-play', 25.00, 11.00, 14.00, '2024-12-09 16:29:39', NULL),
(33, 'Salaspils', '29967564', 'Celtnieku 6, Salaspils', 56.85472120, 24.32877510, 3, '2024-12-14', 12, 'salaspils1@standigital.lv', 'Hos34ben', 2, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-12-14 07:05:57', NULL),
(34, 'Salaspils Родители', '29967564', 'Celtnieku 6 ?', 56.65796900, 23.70643780, 3, '2025-08-21', 9, 'salaspils2@standigital.lv', 'Hos34ben', 2, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-12-14 07:12:59', NULL),
(35, 'Dimants', '29 716 673', 'Dammes iela 2 - 104', 56.96576780, 24.00545670, 4, '2025-06-17', 12, 'Dammes', 'Hos34ben', 2, 'Ott-play', 0.00, 0.00, 0.00, '2024-12-16 16:22:58', NULL),
(36, 'Latgales', '22 338 543', 'Latgales 313 - 10', 56.92432840, 23.53513690, 3, '2024-12-17', 24, 'Latgales@standigital.lv', 'Hos34ben', 1, 'Ott-play', 30.00, 9.49, 20.51, '2024-12-17 19:03:54', NULL),
(37, 'Ирэна', '+37126210369', 'Dzilnas 17 - 45', 55.80472220, 26.51277780, 3, '2024-12-19', 13, 'Dzilnas@standigital.lv', 'Hos34ben', 1, 'Ott-play', 50.00, 19.34, 30.66, '2024-12-19 17:36:12', '+37128264166 второй номер'),
(38, 'Папа', 'Телефон Папы', '22 Baker Street, Irthlingborough', NULL, NULL, 3, '2024-12-25', 24, 'dinavixen@gmail.com', 'Hos34ben', 2, 'Ott-play', 0.00, 0.00, 0.00, '2024-12-25 11:52:17', ''),
(39, 'Vecmilgravis', '28151424', 'Ziemelblazmas 51 - 54', 57.03798320, 24.09691950, 4, '2025-11-23', 13, 'Vecmilgravis', 'Hos34ben', 2, 'Ott-Play FUSS', 70.00, 24.45, 45.55, '2024-12-27 17:40:27', '28 151 445 - второй телефон'),
(40, 'Елгава', '22313585', 'Елгава', 56.65220630, 23.72919990, 3, '2024-12-29', 12, 'jelgv@standigital.lv', 'Hos34ben', 1, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2024-12-29 11:31:47', NULL),
(41, 'Igor', '28280919', 'Grivas iela 21 - 42', 56.91451770, 24.04058910, 4, '2025-01-03', 12, 'Grivas21', 'Hos34ben', 2, 'Ott-play', 0.00, 0.00, 0.00, '2025-01-04 11:09:27', NULL),
(42, 'Денис', '+491797511559', 'Sarmas iela 7 - 113', 57.03877280, 24.10204060, 4, '2025-01-05', 12, 'Sarmas', 'Hos34ben', 1, 'Ott-play', 0.00, 0.00, 0.00, '2025-01-05 13:00:01', NULL),
(43, 'Виталик', '27275141', 'Darzinu 16 linija 1', 56.86138880, 24.26409990, 4, '2025-12-10', 13, 'Viestura21', 'Hos34ben', 3, 'Ott-Play FUSS', 65.00, 33.00, 32.00, '2025-01-05 13:00:53', NULL),
(44, 'Islices', '29779356', 'Islices 14 - 52', 56.52000000, 23.97916670, 2, '2025-10-07', 12, 'islices@standigital.lv', 'Hos34ben', 1, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2025-01-05 13:08:14', NULL),
(46, 'Не знаю', '29583304', 'Zagaru iela 4 - 172', 55.90420860, 26.50990280, 4, '2025-01-21', 12, 'Squoll', 'Hos34ben', 1, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2025-01-21 17:31:35', NULL),
(47, 'Эрик', '+37126097874', '23B, Gaigalas iela, Болдерая, Bolderājas apkaime, Рига, LV-1016, Латвия', 57.02855650, 24.05055690, 4, '2025-01-25', 12, 'Gaigalas', 'Hos34ben', 2, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-01-25 11:11:30', NULL),
(48, 'Бабуля в кенге', '+371 29 677 043', 'Salacas iela 21', 56.92761320, 24.17202110, 3, '2025-02-06', 12, 'kenga@standigital.lv', 'Hos34ben', 1, 'Ottplay', 0.00, 0.00, 0.00, '2025-02-06 16:27:09', NULL),
(49, 'Jēkabpils', '29 983 293', 'Jēkabpils', 56.49586380, 25.86877470, 4, '2025-02-05', 12, 'Jekabpils@standigital.lv', 'Hos34ben', 1, 'Ottplay', 0.00, 0.00, 0.00, '2025-02-06 16:28:57', NULL),
(50, 'Jaroslav', '+37120014800', 'Vaidelotes iela 24 - 13', 56.96613280, 24.05199090, 3, '2025-02-15', 12, 'vaidelotes24@standigital.lv', 'Hos34ben', 2, 'Ott+play foss', 0.00, 0.00, 0.00, '2025-02-15 13:26:49', NULL),
(51, 'Robert', '28363686', 'Poruka prospekts 8', 56.96250030, 23.74908500, 4, '2025-02-20', 12, 'poruka@standigital.lv', 'Hos34ben', 2, 'Ott-Play FUSS', 0.00, 0.00, 0.00, '2025-02-22 08:58:53', NULL),
(52, 'Nīcgales 4', '+37129284671', 'Nīcgales 4 - 192', 56.41215430, 26.05759370, 3, '2025-02-08', 12, 'nicgales4@standigital.lv', 'Hos34ben', 2, 'Ott play', 0.00, 0.00, 0.00, '2025-02-24 07:18:24', NULL),
(53, 'Oleksandra', '+371 28794369', 'Varaviksnes iela 10', 56.62280580, 23.72183910, 4, '2025-03-02', 12, 'varaviksnes@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-02 14:12:41', NULL),
(54, 'Aleksandr', '29277623', 'Paula Lejina 3 - 62', 56.94722110, 24.01745360, 4, '2025-03-04', 12, 'plejina3@standigital.lv - plejina', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-04 17:27:00', NULL),
(55, 'Jelena', '29247797', 'Riekstu iela 7 - 12', 56.96752140, 24.06196940, 3, '2025-03-04', 12, 'riekstu@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-04 17:27:43', NULL),
(56, 'Tinuzu', '27188017', 'Tinuzu iela 14 - 34', 56.93423380, 24.20843500, 4, '2025-03-09', 24, 'Tinuzu', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-09 11:48:04', NULL),
(57, 'Daniels', '29287267', 'Druvas iela 1d', 56.92312620, 24.04824430, 4, '2025-03-20', 12, 'druvas', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-20 17:18:33', NULL),
(58, 'Daniels', '29287267', 'Druvas 1d', 56.92312620, 24.04824430, 4, '2025-03-29', 12, 'Druvas', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-29 08:14:21', NULL),
(59, 'Aleksandr', '22176633', 'Dombrovska 85', 57.03823400, 24.09300510, 3, '2025-03-26', 12, 'dombrovska', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-03-29 08:19:41', NULL),
(60, 'Лариса', '29815006', 'Rupnicas 14 - 1', 57.03026550, 24.11604820, 4, '2025-04-02', 12, 'Rupnicas14', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-04-02 15:24:09', NULL),
(61, 'Jasmuizas', '28883444', 'Jasmuizas 11 - 140', 56.93686580, 24.20972710, 4, '2025-04-04', 12, 'Jasmuizas11', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-04-05 08:20:09', NULL),
(62, 'R', '26994898', 'Selu iela 22-5', 57.54547840, 25.40454810, 4, '2025-04-09', 12, 'elviras@foksy.lv', 'prista', 1, 'ottplayer', 0.00, 0.00, 0.00, '2025-04-09 17:24:05', NULL),
(63, 'Jevgenijs', '29450859', 'Rostokas iela 4 - 25', 56.94951360, 24.00799020, 3, '2025-04-12', 12, 'rostokas@standigital.lv', 'Hos34ben (prista -ottplayer)', 2, 'Ott-play fuss / Ottplayer', 0.00, 0.00, 0.00, '2025-04-12 10:30:31', NULL),
(64, 'Ivan', '22025721', 'Instituta 14, Ulbroka', 56.94308940, 24.28212470, 4, '2025-04-18', 12, 'instituta', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-04-18 17:24:42', NULL),
(65, 'Vasilenko Henrihs', '29944499', 'Emila Darzina 4-k3 - 16, Jurmala', NULL, NULL, 3, '2025-04-25', 12, 'darzina4@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-04-26 09:45:59', NULL),
(66, 'Babka', '26374572', 'Berzlapju 11, Garupe', 57.12097490, 24.23575290, 4, '2025-04-27', 10, 'garupe@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-04-27 10:29:16', NULL),
(67, 'Zhenhsina', '20123438', 'Artilerijas 53a - 29', 56.95906210, 24.13933550, 4, '2025-05-02', 12, 'artilerijas53@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-05-04 05:02:06', NULL),
(68, 'Элвира', '+37129746821', 'Skolas iela 30 - 42, Jurmala', 56.95460120, 23.60662320, 3, '2025-05-12', 12, 'skolas30@standigital.lv', 'Hos34ben', 1, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-05-12 16:23:13', NULL),
(69, 'Украинец', '+1(208) 695-8886', 'Tomsona iela 9, Riga', 56.96428360, 24.12455410, 4, '2025-05-15', 12, 'Tomsona9@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-05-16 16:47:11', NULL),
(70, 'Irbenes iela 5a', '29756963', 'Bauskas 12, Eleja', 56.41487850, 23.68822850, 3, '2025-05-17', 12, 'bauskas12@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-05-17 13:42:42', NULL),
(71, 'Без понятия', '29940866', 'Gaismas iela 6 - 8, Stuniši', 56.85555520, 24.05268770, 4, '2025-05-17', 12, 'Gaismas6@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-05-17 13:46:25', NULL),
(72, '29284671', '29 284 671', 'Anslava Eglisa 10', 57.00057930, 24.26858720, 4, '2025-05-31', 12, 'Anslava', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-05-31 12:53:48', NULL),
(73, 'Sergej', '+371 27 195 517', 'Kooperativas iela 9', NULL, NULL, 4, '2025-05-26', 12, 'Kooperativa9@standigital.lv', 'Hos34ben', 1, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-06-02 10:24:59', '27 507 447'),
(74, 'Lena', '29756963', 'Irbenes iela 5a', 56.91283780, 24.08974950, 3, '2025-06-03', 12, 'Irbenes5a@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-06-03 16:54:08', NULL),
(75, 'Lena', '29756963', 'Irbenes iela 5a', 56.91283780, 24.08974950, 4, '2025-06-03', 12, 'Irbenes5a@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-06-03 16:54:36', NULL),
(76, 'Darja', '28388566', '63, улица Дзелзавас, Пурвциемс, Teikas apkaime, Рига, LV-1084, Латвия', 56.95643340, 24.19538800, 4, '2025-06-03', 12, 'Dzelzavas63@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss + Televizio', 0.00, 0.00, 0.00, '2025-06-03 16:57:43', ''),
(77, 'Dmitrij', '26461922', 'Strelnieku iela 5, Sauriesi', 56.92053600, 24.36181920, 4, '2025-06-04', 12, 'Strelnieku5@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-06-04 16:30:58', NULL),
(78, 'Neznaju', '28 231 075', 'Salaspils iela 12 / 4', 56.93044640, 24.17109800, 3, '2025-06-11', 12, 'salaspils12', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-06-18 15:56:12', NULL),
(79, 'Neznaju', '28 231 075', 'Salaspils iela 12 / 4', 56.93044640, 24.17109800, 3, '2025-06-11', 12, 'salaspils12', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-06-18 15:56:12', NULL),
(80, 'Vecdaugava', '27788109', 'Airu 54', 57.05496730, 24.10257860, 4, '2025-06-25', 12, 'Airu', 'Hos34ben', 2, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-06-25 15:45:11', NULL),
(81, 'Lena', '29 748 869', 'Rinuzu 11- 72', 57.03777050, 24.08990450, 3, '2025-07-03', 12, 'Rinuzu11@standigital.lv', 'Hos34ben', 2, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-07-03 17:00:33', NULL),
(82, 'Aleks надо добавить ещё 2 месяца', '20604848 / 25550111', 'Lokomotives 86 - 15', 56.91040960, 24.19880290, 3, '2025-08-03', 10, 'lokomotives86@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-03 16:41:39', NULL),
(83, 'Lena', '26845805', 'Slokas iela 18', 56.94701410, 24.07370110, 3, '2025-08-04', 12, 'slokas18@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-04 17:23:34', NULL),
(84, 'Sergej', '28208695', 'Lokomotives iela 84 - 52', 56.91040960, 24.19880290, 3, '2025-08-04', 12, 'lokomotives84@standigital.lv', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-04 17:24:34', NULL),
(85, 'Pasha', '20123438', 'Limbažu iela 11', 57.00058140, 24.12550760, 4, '2025-08-06', 12, 'Limbazu11', 'Hos34ben', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-06 17:01:49', NULL),
(86, 'Dmitrij', '29704551', 'Gregora iela 8 - 59', 56.94428300, 24.05824730, 3, '2025-08-07', 12, 'Gregora8', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-07 16:10:30', NULL),
(87, 'Lena', '29756963', 'Irbenes iela 5a', 56.91283780, 24.08974950, 3, '2025-08-10', 12, 'Irbenes5abox@standigital.lv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-08-10 12:31:51', NULL),
(88, 'Edvard', '20888071', 'Salnas iela 21 - 110', 56.94237660, 24.22402010, 4, '2025-08-17', 12, 'Salnas21', 'Totzhe', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-08-17 10:15:18', NULL),
(89, 'Jelgava', '29143183', 'Jelgava jelgava', 56.65679480, 23.79633050, 3, '2025-08-19', 12, 'jelgava1@standigital.lv', 'TotZhe', 2, 'ottplayer', 0.00, 0.00, 0.00, '2025-08-19 15:25:38', NULL),
(90, 'Baldones', '28280919', 'Baldones iela 24', 57.37637310, 21.56928650, 4, '2025-08-21', 12, 'Baldone', 'Tozhesamoe', 1, 'Media / Ott-player', 0.00, 0.00, 0.00, '2025-08-21 17:27:11', NULL),
(91, 'Натка', '+371 26 906 969', 'Marupes iela 3 (Priedaine)', 56.97445160, 23.89098110, 4, '2025-08-27', 12, 'Natkalv', 'VseTotZHe', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-08-27 16:45:19', NULL),
(92, 'Владимир', '+37129446990', 'Plineciems', NULL, NULL, 4, '2025-09-02', 12, 'Pilenciems', 'Hos34ben', 1, 'Ss iptv', 0.00, 0.00, 0.00, '2025-09-02 17:02:34', NULL),
(93, 'Жан', '26333174', 'Purvciema iela 57 - 66', 56.95535630, 24.18679820, 3, '2025-09-09', 12, 'purvciema57@standigital.lv', 'kgjdast3', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-09-09 17:23:13', NULL),
(94, 'Латыш M.Z.I sia', '29236661', 'Lutrinu iela 2f', 56.92429150, 24.06220960, 4, '2025-09-09', 12, 'lutrinu2f@standigital.lv', 'totsa', 2, 'SS IPTV + Televizo', 0.00, 0.00, 0.00, '2025-09-09 17:24:31', NULL),
(95, 'Klavu 14', '29409195', 'Klavu iela 14', 56.64376700, 23.70939750, 4, '2025-09-09', 15, 'klavu@standigital.lv', 'Hos34ben', 2, 'SS IPTV', 65.00, 33.00, 32.00, '2025-09-09 17:25:36', NULL),
(96, 'Юрий', '+371 29 575 919', 'Kurzemes prospekts 122 - 42', 56.95912910, 24.03521810, 4, '2025-09-12', 12, 'kprospekt122@standigital.lv', 'Hdhdh', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-09-12 18:13:00', NULL),
(97, 'Tiraine Tiraine', '29289607', 'Tiraine', 56.88540750, 24.06577610, 2, '2025-09-13', 12, 'tel4567@standigital.lv', 'prista', 2, 'Ott player', 0.00, 0.00, 0.00, '2025-09-13 13:27:59', NULL),
(98, 'Tiraine Tiraine', '29 289 607', 'Tiraine', 56.88540750, 24.06577610, 2, '2025-09-13', 12, 'imantas@standigital.lv', 'prista', 2, 'Ottplayer', 0.00, 0.00, 0.00, '2025-09-13 13:28:40', NULL),
(99, 'Теща', 'Телефон тещи', 'Адрес тещи', NULL, NULL, 4, '2025-09-13', 12, 'мой собственный счёт', 'тоже самое', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-09-13 13:30:18', NULL),
(100, 'Olga', '29756963', 'Irbenes iela 5a', 56.91283780, 24.08974950, 4, '2025-09-14', 12, 'ciganka@standigital.lv', 'Hos34ben', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-09-14 10:25:21', NULL),
(101, 'Iptv playlist', '25653243', 'Ebenja', NULL, NULL, 4, '2025-09-14', 12, 'iptvstan@standigital.lv', 'Hos34bwn', 1, 'Smart IPTV 23:9f:85:5e:3d:bc', 0.00, 0.00, 0.00, '2025-09-14 10:34:13', NULL),
(102, 'Василий', '+37129231460', 'Melidas 1 - 20', 57.03259730, 24.11218250, 2, '2025-09-17', 8, 'Mdjdj', 'Djd', 1, 'Ott plY fuss', 0.00, 0.00, 0.00, '2025-09-17 15:39:42', NULL),
(103, 'Igors', '29477045', '5, улица Дзегужу, Дзирциемс, Imantas apkaime, Рига, LV-1007, Латвия', 56.95944120, 24.06374290, 4, '2025-09-22', 12, 'dzeguzu5', 'gakslgkal', 2, 'Televizo', 0.00, 0.00, 0.00, '2025-09-22 16:15:19', NULL),
(104, 'Janis', '+371 25 279 083', 'Gaismas iela 6 - 94 (Kekava)', 56.82869680, 24.23110730, 3, '2025-09-23', 12, 'gaismas6kek@standigital.lv', 'hos-€-', 1, 'Ott Play Fuss', 0.00, 0.00, 0.00, '2025-09-25 05:29:46', NULL),
(105, 'Nepomnju', '29 242 285', 'Lašu iela 5 - 68 (Jurmala)', 57.00106110, 23.92649120, 4, '2025-09-27', 12, 'lashu5', 'totzhe', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-09-27 15:49:37', NULL),
(106, 'Nepomnju', '29 242 285', 'Jana plieksana iela 100', 56.96158410, 23.81535620, 4, '2025-09-27', 12, 'plesu5', 'totzhe', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-09-27 15:50:24', NULL),
(107, 'Vladislav', '+37125279815', 'Daugavpils', 55.87122670, 26.51593370, 2, '2025-10-01', 12, 'Edem', 'Ddddd', 2, 'Bez ponatoja', 0.00, 0.00, 0.00, '2025-09-29 08:39:14', NULL),
(108, 'Olga', '+37129756963', 'Irbenēs iela 5a', 56.91283780, 24.08974950, 4, '2025-09-29', 12, 'Irbenes5ac', 'Dhhdja', 2, 'Televizo', 0.00, 0.00, 0.00, '2025-09-29 16:25:48', NULL),
(109, 'Jelgava', '27893977', 'Jelgava', 56.65220630, 23.72919990, 4, '2025-09-30', 12, 'jelgava', 'fasfa', 2, 'Televizo', 0.00, 0.00, 0.00, '2025-10-02 15:23:04', NULL),
(110, 'Galina', '26818831', 'Dzenu iela 8 - 51', 56.94019520, 24.21112520, 3, '2025-10-06', 12, 'dzenu8@std', 'Hsafa', 2, 'Televizo', 0.00, 0.00, 0.00, '2025-10-06 16:47:40', NULL),
(111, 'Sergejs', '29 812 970', 'Eksporta iela 16 - 63', 56.95750290, 24.09787360, 4, '2025-10-11', 12, 'eksporta16', 'PVruKYbgLYr3Rn', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-10-11 08:52:09', NULL),
(112, 'Elena', '+371 26 028 084', 'Rudens iela 12 - 157', 56.93808710, 24.19899620, 3, '2025-10-12', 12, 'rudens12@standigital.lv', 'hldkdk', 2, 'Ss iptv, ottplayer', 0.00, 0.00, 0.00, '2025-10-12 13:39:02', NULL),
(113, 'Vladimir', '+37129448281', 'Vienibas prospekts 20 k-1, Jurmala', 56.98000510, 23.85753530, 4, '2025-10-14', 12, 'vienibas201', 'Hdhdh', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-10-14 14:13:33', NULL),
(114, 'Bezponatija', '+37125206669', 'Mežrozīšu iela 30 - 51', 56.94475800, 24.25499550, 5, '2025-10-21', 12, 'Мой аккаунт', 'Тотже', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-10-21 16:59:55', NULL),
(115, 'Mark', '29 250 857', 'Ieriku iela 60 - 169', 56.96555470, 24.16952910, 4, '2025-10-22', 12, 'ieriku60', '5125dsta', 2, 'Navigator OTT / OTT PLAY FUSS / OTTPLAYER', 0.00, 0.00, 0.00, '2025-10-22 16:53:13', NULL),
(116, 'Татьяна', '26764224 / 29 996 836', 'Zentenes iela 5 - 23', 56.95869900, 24.00790360, 5, '2025-10-23', 12, 'Мой', 'Dhejie', 2, 'Ott play FoSS', 0.00, 0.00, 0.00, '2025-10-23 14:02:39', NULL),
(117, 'Лена (Тётя)', '22063200', 'Hapsalas iela', 56.99992760, 24.12194620, 4, '2025-10-26', 12, 'kraskalv', 'Hos34ben', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-10-26 07:53:11', NULL),
(118, 'Федя и Оксана', 'Их телефон', 'Их адрес', NULL, NULL, 5, '2025-10-25', 12, 'stas.pudov@gmail.com', 'gsaga', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-10-26 07:53:45', NULL),
(119, 'Алёна', '+37129178259', 'Kooperativa 4a - 16', 56.82573920, 24.07962710, 4, '2025-10-26', 12, 'kooperativa4a', 'Hfhheheh', 2, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-10-26 12:36:16', NULL),
(120, 'Liana', '+371 29 151 264', 'Ruses 13 - 42', 56.94696740, 24.00610490, 5, '2025-10-27', 12, 'stas.pudov@gmail.com', 'Fhdhfj', 2, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-10-27 15:56:59', NULL),
(121, 'Georg', '+37129912434', 'Latgales iela 137', 56.93769250, 24.15174490, 5, '2025-10-28', 12, 'Moj', 'Thhfhfh', 1, 'Ott play FoSS', 0.00, 0.00, 0.00, '2025-10-28 15:09:30', NULL),
(122, 'Aleksandrs', '+371 28 249 637', 'Mežkalnu iela 20 - Līcī', NULL, NULL, 5, '2025-10-29', 12, 'Мой', 'Мой', 5, 'Ott player Fuss', 0.00, 0.00, 0.00, '2025-10-30 12:21:36', NULL),
(123, 'Juris', '29755130', 'Cidoniju iela 51', 56.86629040, 24.26578870, 4, '2025-10-31', 12, 'Moj', 'fsafsa', 1, 'Ott player fuss', 0.00, 0.00, 0.00, '2025-11-01 12:21:24', NULL),
(124, 'Марина (Добавить 2 месяца)', '+37125910606', 'Nometņu iela 38', 56.94022190, 24.07472200, 4, '2025-11-03', 10, 'Мой', 'Fhsusj', 1, 'Ott play fuss', 0.00, 0.00, 0.00, '2025-11-03 17:19:39', NULL),
(125, '?????', '25953938', '54, бульвар Анниньмуйжас, Imanta-4, Иманта, Imantas apkaime, Рига, LV-1069, Латвия', 56.95764860, 24.00823180, 5, '2025-11-07', 12, 'Moj', 'daga231sa', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-11-07 18:25:13', NULL),
(126, '????', '27720485', 'Mezrozisu iela 43', 57.03027240, 24.04971510, 5, '2025-11-08', 12, 'Moj', 'gajsgjas2', 1, 'OTTPLAYER', 0.00, 0.00, 0.00, '2025-11-08 09:20:31', NULL),
(127, 'Мама Лены', '29147562', 'Ленин Адрес 20 кв', NULL, NULL, 5, '2025-11-09', 12, 'Moj', 'gasga2', 1, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-11-09 10:42:14', NULL),
(128, 'Андрей', '26327444', 'Latgales iela 425 - 55', 56.89467430, 24.21608030, 5, '2025-11-09', 12, 'Moj', 'Gsajgalk35', 1, 'Navigator OTT', 0.00, 0.00, 0.00, '2025-11-09 10:43:12', NULL),
(129, 'Neznaju', '25953938', '54, бульвар Анниньмуйжас, Imanta-4, Иманта, Imantas apkaime, Рига, LV-1069, Латвия', 56.95764860, 24.00823180, 4, '2025-11-10', 12, 'pristavkap', 'gasgklasklgkla', 1, 'Navigator OTT', 0.00, 0.00, 0.00, '2025-11-11 17:01:40', NULL),
(130, 'Alla', '29 220 007', 'Istras iela 22', 56.94278480, 24.18782850, 4, '2025-11-13', 12, 'istras22', 'Dhdhhdj', 1, 'Ott play foss', 0.00, 0.00, 0.00, '2025-11-13 17:29:10', NULL),
(131, 'Georg', '28 312 288', 'Carnikava, Maza Kapu 9', NULL, NULL, 3, '2025-11-15', 12, 'mazakapu9@standigital.lv', 'gasgsa', 1, 'Navigator OTT', 0.00, 0.00, 0.00, '2025-11-15 10:22:59', NULL),
(132, 'Dace Lazda', '27061502', 'Talsu Šoseja 31 k-1 dz 21', 56.96268580, 23.60362390, 4, '2025-11-18', 12, 'talsusoseja31', 'af2141', 1, 'SS IPTV', 0.00, 0.00, 0.00, '2025-11-18 10:40:39', NULL),
(133, 'Timur', '24 820 081', 'Prospekts Viestura 14', 57.01727130, 24.13163990, 3, '2025-11-19', 12, 'dumbraja29@standigital.lv', 'gsag325', 2, 'Ott play FUSS', 0.00, 0.00, 0.00, '2025-11-19 16:54:38', NULL),
(134, 'Bez Ponatija', '20 218 689', 'Dumbraja iela 29 - 122', 56.95960230, 24.01562720, 4, '2025-11-19', 12, 'dumbraja29', 'sagjagjk123', 1, 'Ott Play Fuss', 0.00, 0.00, 0.00, '2025-11-19 16:55:30', NULL),
(135, 'Didzis', '26 768 402', '1, Pampāļu iela, Petriņciems, Биерини, Āgenskalna apkaime, Рига, LV-1058, Латвия', 56.91580910, 24.05555160, 5, '2025-11-24', 12, 'Moj', 'Dhehhe', 2, 'Smart IPTV', 0.00, 0.00, 0.00, '2025-11-24 14:23:58', ''),
(136, 'Neznaju', '26867122', 'Melidas iela 3 - 46', 57.03259730, 24.11218250, 5, '2025-11-29', 12, 'moj', 'gfsag325', 2, 'Ott-play fuss', 0.00, 0.00, 0.00, '2025-11-29 18:45:44', NULL),
(137, 'Andrej', '26145363', 'Gravas iela 17  - 33', 56.90473810, 24.22838490, 4, '2025-11-30', 12, 'gravas171', 'gjajgasj', 2, 'Ottplayer', 70.00, 30.00, 40.00, '2025-11-30 13:35:26', NULL),
(138, 'Edgars', '26621172', '17, Gravas iela, Шкиротава, Latgales apkaime, Рига, LV-1057, Латвия', 56.90515090, 24.22958880, 4, '2025-11-30', 12, 'gravas17', 'gajgjkaskj', 2, 'Televizo / Pot Player', 60.00, 30.00, 30.00, '2025-11-30 13:36:20', NULL),
(139, 'Valentina', '26131985', 'Zeltenu 56 - 73 (168)', NULL, NULL, 5, '2025-12-02', 12, 'moj', 'gsagsag', 1, 'Navigator OTT', 50.00, 20.00, 30.00, '2025-12-02 17:21:34', NULL),
(140, 'Vladimir', '26715037 / 22077688', 'Brivibas gatve 451 - 2', 56.96968990, 24.15516990, 5, '2025-12-06', 12, 'Moj', 'totzhe', 1, 'Navigator OTT', 50.00, 20.00, 30.00, '2025-12-06 09:21:50', NULL),
(141, 'Olga', '27588447', 'Duntes iela 28', 56.98809090, 24.13389080, 5, '2025-12-07', 12, 'moj', 'agsja', 1, 'Ott-play fuss', 50.00, 20.00, 30.00, '2025-12-07 10:46:43', NULL),
(142, 'Valentin', '29254296', 'Pavasara iela 25, Balozi', 56.86898730, 24.13486190, 4, '2025-12-07', 12, 'pavasara25', 'hgkahka', 1, 'Ott-play fuss', 70.00, 30.00, 40.00, '2025-12-07 10:47:32', NULL),
(143, 'Roman', '29558107', 'Daugavpils', 55.87122670, 26.51593370, 4, '2025-12-07', 12, 'irinaurbane', 'fsafas', 1, 'Clouddy', 45.00, 30.00, 15.00, '2025-12-07 19:14:57', NULL),
(144, 'Latish', '20392262', 'Asaru prospekts 17', 56.95941750, 23.68822790, 5, '2025-12-10', 12, 'moj', 'asgfagka', 4, 'Ottplayer (moj account) (2 aktivnie sessiji)', 65.00, 20.00, 45.00, '2025-12-10 17:49:05', NULL),
(145, 'Didzis', '26 768 402', '1, Pampāļu iela, Petriņciems, Биерини, Āgenskalna apkaime, Рига, LV-1058, Латвия', 56.91580910, 24.05555160, 5, '2025-12-11', 12, 'moj ( pampalu2)', 'Hgsaga', 1, 'ottplayer (foksy.lv)', 50.00, 20.00, 30.00, '2025-12-11 18:23:18', NULL),
(146, 'Mareks', '26859722', 'Jaunaudzes iela 7', 57.01729940, 24.21827970, 5, '2025-12-11', 12, 'moj', 'gajgasjgj', 1, 'ottplayer (foksy.lv)', 50.00, 19.99, 30.01, '2025-12-11 18:24:55', NULL),
(148, 'Imants Trilops', '29237718', '90, Prūšu iela, Кенгарагс, Latgales apkaime, Рига, LV-1057, Латвия', 56.90596530, 24.20188200, 3, '2025-12-15', 12, 'prusu90@standigital.lv', 'gfsagasg', 2, 'Ottplayer', 50.00, 10.21, 39.79, '2025-12-15 18:15:22', ''),
(149, 'Neznaju', '25241099', 'Ikškeles iela 5', NULL, NULL, 5, '2025-12-15', 12, 'moj(prusu90)', 'gasgsagas', 1, 'Televizo', 50.00, 20.00, 30.00, '2025-12-16 11:12:50', 'Квартира 4,\r\nКод 4-0232'),
(150, 'Georg', '28 312 288', 'Buru iela 5, Jurmala', 56.97711220, 23.86584390, 3, '2025-12-19', 12, 'buru5@standigital.lv', 'fsafas5215', 1, 'Ott-play fuss', 60.00, 12.00, 48.00, '2025-12-19 17:37:29', 'Использован плейлист из m3u.in'),
(151, 'Oleg', '27891318', 'Ilukstes iela 103 k-1', 56.95295150, 24.19664900, 4, '2025-12-09', 12, 'Ilukstes103', 'fsafasfa', 2, 'Ott play FUSS', 70.00, 30.00, 40.00, '2025-12-22 18:09:37', ''),
(152, 'Незнаю', '+37126915039', 'Ezermalas iela 13 k-6 85', NULL, NULL, 5, '2025-12-23', 12, 'Moj', 'Bvffff66', 1, 'Ott play foss', 50.00, 18.50, 31.50, '2025-12-23 16:17:50', ''),
(153, 'Дмитрий', '28 285 215', 'Rasas iela 11 salaspils pagast', NULL, NULL, 5, '2025-12-24', 12, 'moj', 'gsagsa', 1, 'Ott-play fuss', 60.00, 18.95, 41.05, '2025-12-24 09:40:27', ''),
(154, 'Дмитрий', '28285215', 'Augusta Deglava iela 106', 56.94837620, 24.18955310, 5, '2025-12-24', 12, 'Moj', 'gasgag', 1, 'Ott-play fuss', 50.00, 18.95, 31.05, '2025-12-24 09:41:13', ''),
(155, 'Luiza', '29854345', 'Vecmilgravja 1.linija 37', 57.03121240, 24.10155420, 5, '2025-12-25', 12, 'Мой', 'gsat53', 1, '', 50.00, 18.00, 32.00, '2025-12-25 10:13:09', '36 квартира, код 690'),
(156, 'Luiza', '29854345', '9, Rūpnīcas iela, Вецмилгравис, Vecmīlgrāvja apkaime, Рига, LV-1015, Латвия', 57.03046650, 24.11562340, 5, '2025-12-25', 12, 'moj', 'gadga5', 1, '', 50.00, 18.00, 32.00, '2025-12-25 10:13:46', 'Rupnicas iela 9 - 4'),
(157, 'Ekaterina', '23203255', '15, улица Стабу, Centra rajons, Центр, Centra apkaime, Рига, LV-1010, Латвия', 56.95873570, 24.12508110, 5, '2025-12-27', 12, 'moj', 'gsagsaga', 1, 'Ott-play fuss', 50.00, 18.00, 32.00, '2025-12-27 10:19:54', 'Stabu iela 15 - 125 Код двери 368A'),
(158, 'Serb', '29521416', 'Ebelmuizas iela 22', NULL, NULL, 5, '2025-12-27', 12, 'moj', 'gasg326sa', 1, 'Ott-play fuss', 50.00, 18.00, 32.00, '2025-12-27 10:20:58', 'Ebelmuizas iela 22 - 66 , Kod - 66*7409'),
(159, 'Леонид', '26 272 686', 'Ganību dambis 12', NULL, NULL, 5, '2025-12-28', 12, 'moj', 'Dbejjei2728', 1, 'Televizo', 50.00, 15.00, 35.00, '2025-12-28 11:25:42', 'Ganību dambis 12 - 2'),
(160, 'Nikita', '25561820', 'Jukuma Vacieša iela 8', NULL, NULL, 5, '2025-12-29', 12, 'moj', 'fsa535', 1, '', 50.00, 18.00, 32.00, '2025-12-29 18:12:18', 'Jukuma Vacieša iela 8 - 11'),
(161, 'Neznaju', '25478322', 'Kurzemes prospekts 164', NULL, NULL, 5, '2025-12-29', 12, 'Moj', 'gsaga51', 1, 'Ott-play fuss', 50.00, 18.00, 32.00, '2025-12-29 18:13:18', 'Kurzemes prospekts 164 - 59\r\nLatgales iela 285 k-2 11'),
(162, 'Neznaju', '25478322', 'Latgales iela 285', NULL, NULL, 5, '2025-12-29', 12, 'moj', 'fsagfasgt233', 1, 'Ott-play fuss', 40.00, 18.00, 22.00, '2025-12-29 18:14:09', 'Latgales iela 285 k-2 11'),
(163, 'Neznaju', '27507447', 'Vecmilgravja iela 6', NULL, NULL, 4, '2025-12-01', 3, 'm3u.dev', 'gsaga5315', 1, 'Televizo', 0.00, 0.00, 0.00, '2025-12-29 18:24:54', 'Vecmilgravja iela 6 - 86 Надо создать новый аккаунт для него'),
(164, 'Ekaterina', '26745177', 'Staiceles iela 11', NULL, NULL, 5, '2025-12-30', 12, 'moj', 'gaqg213', 1, 'Televizo', 50.00, 18.00, 32.00, '2025-12-30 18:02:19', 'Staiceles iela 11 - 37 37*0468'),
(165, 'Julija', '25969613', 'Dizozolu 27', NULL, NULL, 5, '2025-12-30', 12, 'moj', 'fagaju@4', 1, 'Navigator OTT', 50.00, 18.00, 32.00, '2025-12-30 18:03:09', 'Dizozolu 27 -18');

-- --------------------------------------------------------

--
-- Структура таблицы `tv_events`
--

CREATE TABLE `tv_events` (
  `id` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `months` int(11) DEFAULT NULL,
  `metadata` text DEFAULT NULL,
  `user` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_events`
--

INSERT INTO `tv_events` (`id`, `type`, `client_id`, `provider_id`, `amount`, `months`, `metadata`, `user`, `created_at`) VALUES
(1, 'subscription_extended', 95, NULL, NULL, 12, '{\"paid\":65,\"provider_cost\":33}', 'Squoll', '2025-12-13 18:29:32'),
(2, 'payment_recorded', 95, NULL, 32.00, NULL, '{\"paid\":65,\"provider_cost\":33}', 'Squoll', '2025-12-13 18:29:32'),
(3, 'payment_updated', 145, 5, 1.00, NULL, '{\"paid\":1,\"provider_cost\":0,\"old_paid\":\"50.00\",\"new_paid\":51}', 'Squoll', '2025-12-14 06:16:35'),
(4, 'payment_updated', 145, 5, 10.00, NULL, '{\"paid\":10,\"provider_cost\":0,\"old_paid\":\"51.00\",\"new_paid\":61}', 'Squoll', '2025-12-14 06:16:46'),
(5, 'payment_updated', 145, 5, -11.00, NULL, '{\"paid\":-11,\"provider_cost\":0,\"old_paid\":\"61.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 06:16:54'),
(6, 'payment_updated', 146, 5, 0.01, NULL, '{\"paid\":0,\"provider_cost\":-0.010000000000001563,\"old_paid\":\"50.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 06:17:08'),
(7, 'payment_updated', 143, 4, 15.00, NULL, '{\"paid\":45,\"provider_cost\":30,\"old_paid\":\"0.00\",\"new_paid\":45}', 'Squoll', '2025-12-14 06:20:04'),
(8, 'payment_updated', 140, 5, 30.00, NULL, '{\"paid\":50,\"provider_cost\":20,\"old_paid\":\"0.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 11:11:26'),
(9, 'payment_updated', 30, 3, 38.00, NULL, '{\"paid\":50,\"provider_cost\":12,\"old_paid\":\"0.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 11:11:53'),
(10, 'payment_updated', 31, 3, 38.00, NULL, '{\"paid\":50,\"provider_cost\":12,\"old_paid\":\"0.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 11:12:06'),
(11, 'payment_updated', 139, 5, 30.00, NULL, '{\"paid\":50,\"provider_cost\":20,\"old_paid\":\"0.00\",\"new_paid\":50}', 'Squoll', '2025-12-14 11:12:15'),
(12, 'payment_updated', 137, 4, 40.00, NULL, '{\"paid\":70,\"provider_cost\":30,\"old_paid\":\"0.00\",\"new_paid\":70}', 'Squoll', '2025-12-14 11:12:33'),
(13, 'payment_updated', 138, 4, 30.00, NULL, '{\"paid\":60,\"provider_cost\":30,\"old_paid\":\"0.00\",\"new_paid\":60}', 'Squoll', '2025-12-14 11:12:48'),
(14, 'client_created', 147, 3, NULL, 12, '{\"phone\":\"tetst\",\"address\":\"test\"}', 'Squoll', '2025-12-14 12:55:09'),
(15, 'subscription_extended', 37, NULL, NULL, 1, '{\"paid\":25,\"provider_cost\":9.34}', 'Squoll', '2025-12-15 18:12:36'),
(16, 'payment_recorded', 37, NULL, 15.66, NULL, '{\"paid\":25,\"provider_cost\":9.34}', 'Squoll', '2025-12-15 18:12:36'),
(17, 'client_created', 148, 3, NULL, 12, '{\"phone\":\"29237718\",\"address\":\"Pru\\u0161u iela 90\"}', 'Squoll', '2025-12-15 18:15:22'),
(18, 'payment_recorded', 148, 3, 39.79, NULL, '{\"paid\":50,\"provider_cost\":10.21}', 'Squoll', '2025-12-15 18:15:22'),
(19, 'subscription_extended', 37, NULL, NULL, 1, '{\"paid\":25,\"provider_cost\":10}', 'Squoll', '2025-12-16 06:04:50'),
(20, 'payment_recorded', 37, NULL, 15.00, NULL, '{\"paid\":25,\"provider_cost\":10}', 'Squoll', '2025-12-16 06:04:50'),
(21, 'client_created', 149, 5, NULL, 12, '{\"phone\":\"25241099\",\"address\":\"Ik\\u0161keles iela 5\"}', 'Squoll', '2025-12-16 11:12:50'),
(22, 'payment_recorded', 149, 5, 30.00, NULL, '{\"paid\":50,\"provider_cost\":20}', 'Squoll', '2025-12-16 11:12:50'),
(23, 'subscription_extended', 38, NULL, NULL, 12, '{\"paid\":0,\"provider_cost\":0}', 'Squoll', '2025-12-17 16:38:31'),
(24, 'client_created', 150, 3, NULL, 12, '{\"phone\":\"28 312 288\",\"address\":\"Buru iela 5, Jurmala\"}', 'Squoll', '2025-12-19 17:37:29'),
(25, 'payment_recorded', 150, 3, 48.00, NULL, '{\"paid\":60,\"provider_cost\":12}', 'Squoll', '2025-12-19 17:37:29'),
(26, 'subscription_extended', 36, NULL, NULL, 12, '{\"paid\":30,\"provider_cost\":9.49}', 'Squoll', '2025-12-21 06:04:17'),
(27, 'payment_recorded', 36, NULL, 20.51, NULL, '{\"paid\":30,\"provider_cost\":9.49}', 'Squoll', '2025-12-21 06:04:17'),
(28, 'subscription_extended', 39, NULL, NULL, 12, '{\"paid\":70,\"provider_cost\":24.45}', 'Squoll', '2025-12-21 09:26:31'),
(29, 'payment_recorded', 39, NULL, 45.55, NULL, '{\"paid\":70,\"provider_cost\":24.45}', 'Squoll', '2025-12-21 09:26:31'),
(30, 'client_created', 151, 4, NULL, 12, '{\"phone\":\"27891318\",\"address\":\"Ilukstes iela 103 k-1\"}', 'Squoll', '2025-12-22 18:09:37'),
(31, 'payment_recorded', 151, 4, 40.00, NULL, '{\"paid\":70,\"provider_cost\":30}', 'Squoll', '2025-12-22 18:09:37'),
(32, 'client_created', 152, 5, NULL, 12, '{\"phone\":\"+37126915039\",\"address\":\"Ezermalas iela 13 k-6 85\"}', 'Squoll', '2025-12-23 16:17:50'),
(33, 'payment_recorded', 152, 5, 31.50, NULL, '{\"paid\":50,\"provider_cost\":18.5}', 'Squoll', '2025-12-23 16:17:50'),
(34, 'client_created', 153, 5, NULL, 12, '{\"phone\":\"28 285 215\",\"address\":\"Rasas iela 11 salaspils pagast\"}', 'Squoll', '2025-12-24 09:40:27'),
(35, 'payment_recorded', 153, 5, 41.05, NULL, '{\"paid\":60,\"provider_cost\":18.95}', 'Squoll', '2025-12-24 09:40:27'),
(36, 'client_created', 154, 5, NULL, 12, '{\"phone\":\"28285215\",\"address\":\"Augusta Deglava iela 106\"}', 'Squoll', '2025-12-24 09:41:13'),
(37, 'payment_recorded', 154, 5, 31.05, NULL, '{\"paid\":50,\"provider_cost\":18.95}', 'Squoll', '2025-12-24 09:41:13'),
(38, 'client_created', 155, 5, NULL, 12, '{\"phone\":\"29854345\",\"address\":\"Vecmilgravja 1.linija 37\"}', 'Squoll', '2025-12-25 10:13:09'),
(39, 'payment_recorded', 155, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-25 10:13:09'),
(40, 'client_created', 156, 5, NULL, 12, '{\"phone\":\"29854345\",\"address\":\"Rupnicas iela 9\"}', 'Squoll', '2025-12-25 10:13:46'),
(41, 'payment_recorded', 156, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-25 10:13:46'),
(42, 'client_created', 157, 5, NULL, 12, '{\"phone\":\"23203255\",\"address\":\"Stabu iela 15\"}', 'Squoll', '2025-12-27 10:19:54'),
(43, 'payment_recorded', 157, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-27 10:19:54'),
(44, 'client_created', 158, 5, NULL, 12, '{\"phone\":\"29521416\",\"address\":\"Ebelmuizas iela 22\"}', 'Squoll', '2025-12-27 10:20:58'),
(45, 'payment_recorded', 158, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-27 10:20:58'),
(46, 'client_created', 159, 5, NULL, 12, '{\"phone\":\"26 272 686\",\"address\":\"Gan\\u012bbu dambis 12\"}', 'Squoll', '2025-12-28 11:25:42'),
(47, 'payment_recorded', 159, 5, 35.00, NULL, '{\"paid\":50,\"provider_cost\":15}', 'Squoll', '2025-12-28 11:25:42'),
(48, 'client_created', 160, 5, NULL, 12, '{\"phone\":\"25561820\",\"address\":\"Jukuma Vacie\\u0161a iela 8\"}', 'Squoll', '2025-12-29 18:12:18'),
(49, 'payment_recorded', 160, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-29 18:12:18'),
(50, 'client_created', 161, 5, NULL, 12, '{\"phone\":\"25478322\",\"address\":\"Kurzemes prospekts 164\"}', 'Squoll', '2025-12-29 18:13:18'),
(51, 'payment_recorded', 161, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-29 18:13:18'),
(52, 'client_created', 162, 5, NULL, 12, '{\"phone\":\"25478322\",\"address\":\"Latgales iela 285\"}', 'Squoll', '2025-12-29 18:14:09'),
(53, 'payment_recorded', 162, 5, 22.00, NULL, '{\"paid\":40,\"provider_cost\":18}', 'Squoll', '2025-12-29 18:14:09'),
(54, 'client_created', 163, 4, NULL, 3, '{\"phone\":\"27507447\",\"address\":\"Vecmilgravja iela 6\"}', 'Squoll', '2025-12-29 18:24:54'),
(55, 'client_created', 164, 5, NULL, 12, '{\"phone\":\"26745177\",\"address\":\"Staiceles iela 11\"}', 'Squoll', '2025-12-30 18:02:19'),
(56, 'payment_recorded', 164, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-30 18:02:19'),
(57, 'client_created', 165, 5, NULL, 12, '{\"phone\":\"25969613\",\"address\":\"Dizozolu 27\"}', 'Squoll', '2025-12-30 18:03:09'),
(58, 'payment_recorded', 165, 5, 32.00, NULL, '{\"paid\":50,\"provider_cost\":18}', 'Squoll', '2025-12-30 18:03:09');

-- --------------------------------------------------------

--
-- Структура таблицы `tv_payments`
--

CREATE TABLE `tv_payments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `paid` decimal(10,2) NOT NULL,
  `provider_cost` decimal(10,2) NOT NULL,
  `earned` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_payments`
--

INSERT INTO `tv_payments` (`id`, `client_id`, `year`, `paid`, `provider_cost`, `earned`, `created_at`) VALUES
(1, 95, 2025, 65.00, 33.00, 32.00, '2025-12-13 18:29:32'),
(2, 145, 2025, 1.00, 0.00, 1.00, '2025-12-14 06:16:35'),
(3, 145, 2025, 10.00, 0.00, 10.00, '2025-12-14 06:16:46'),
(4, 145, 2025, -11.00, 0.00, -11.00, '2025-12-14 06:16:54'),
(5, 146, 2025, 0.00, -0.01, 0.01, '2025-12-14 06:17:08'),
(6, 143, 2025, 45.00, 30.00, 15.00, '2025-12-14 06:20:04'),
(7, 140, 2025, 50.00, 20.00, 30.00, '2025-12-14 11:11:26'),
(8, 30, 2025, 50.00, 12.00, 38.00, '2025-12-14 11:11:53'),
(9, 31, 2025, 50.00, 12.00, 38.00, '2025-12-14 11:12:06'),
(10, 139, 2025, 50.00, 20.00, 30.00, '2025-12-14 11:12:15'),
(11, 137, 2025, 70.00, 30.00, 40.00, '2025-12-14 11:12:33'),
(12, 138, 2025, 60.00, 30.00, 30.00, '2025-12-14 11:12:48'),
(13, 37, 2025, 25.00, 9.34, 15.66, '2025-12-15 18:12:36'),
(14, 148, 2025, 50.00, 10.21, 39.79, '2025-12-15 00:00:00'),
(15, 37, 2025, 25.00, 10.00, 15.00, '2025-12-16 06:04:50'),
(16, 149, 2025, 50.00, 20.00, 30.00, '2025-12-15 00:00:00'),
(17, 38, 2025, 0.00, 0.00, 0.00, '2025-12-17 16:38:31'),
(18, 150, 2025, 60.00, 12.00, 48.00, '2025-12-19 00:00:00'),
(19, 36, 2025, 30.00, 9.49, 20.51, '2025-12-21 06:04:17'),
(20, 39, 2025, 70.00, 24.45, 45.55, '2025-12-21 09:26:31'),
(21, 151, 2025, 70.00, 30.00, 40.00, '2025-12-09 00:00:00'),
(22, 152, 2025, 50.00, 18.50, 31.50, '2025-12-23 00:00:00'),
(23, 153, 2025, 60.00, 18.95, 41.05, '2025-12-24 00:00:00'),
(24, 154, 2025, 50.00, 18.95, 31.05, '2025-12-24 00:00:00'),
(25, 155, 2025, 50.00, 18.00, 32.00, '2025-12-25 00:00:00'),
(26, 156, 2025, 50.00, 18.00, 32.00, '2025-12-25 00:00:00'),
(27, 157, 2025, 50.00, 18.00, 32.00, '2025-12-27 00:00:00'),
(28, 158, 2025, 50.00, 18.00, 32.00, '2025-12-27 00:00:00'),
(29, 159, 2025, 50.00, 15.00, 35.00, '2025-12-28 00:00:00'),
(30, 160, 2025, 50.00, 18.00, 32.00, '2025-12-29 00:00:00'),
(31, 161, 2025, 50.00, 18.00, 32.00, '2025-12-29 00:00:00'),
(32, 162, 2025, 40.00, 18.00, 22.00, '2025-12-29 00:00:00'),
(33, 164, 2025, 50.00, 18.00, 32.00, '2025-12-30 00:00:00'),
(34, 165, 2025, 50.00, 18.00, 32.00, '2025-12-30 00:00:00');

-- --------------------------------------------------------

--
-- Структура таблицы `tv_providers`
--

CREATE TABLE `tv_providers` (
  `id` int(11) NOT NULL,
  `operator` varchar(255) NOT NULL,
  `website` varchar(255) DEFAULT NULL,
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `referral_link` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `tv_providers`
--

INSERT INTO `tv_providers` (`id`, `operator`, `website`, `login`, `password`, `referral_link`, `balance`) VALUES
(2, 'EDEM.TV', 'https://i-edem.tv/', 'stas.pudov@gmail.com', 'hos34ben', 'https://i-edem.tv/welcome/register/078bc9e2c9503b1b', 13.00),
(3, 'ANTIFRIZ.TV', 'https://antifriz.tv/', 'stas.pudov@gmail.com', 'Hos624ben', 'https://antifriz.tv/?ref=3J4jfzS', 104.00),
(4, 'Sharavoz Tv', 'www.sh365.org', 'Squoll', 'Hos34ben', 'https://ztempz.xyz/jWuRi7OfsJGIE2Qp5RRYFltRVRI8icYI07NiJG0nj0A=', 12.00),
(5, 'Tv.Team', 'https://tv.team', 'stas.pudov@gmail.com', 'Ho$624ben@', 'https://new.tv.team/398317', 1.00);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `last_login`, `login_attempts`, `locked_until`, `password_changed_at`) VALUES
(2, 'Squoll', '$2y$10$Gaq6H5AJEtBpjnM8iRuCye1O4OXbGfPPCWHWXKRMluh.QcJqZcRKq', '2024-11-03 06:52:06', NULL, 0, NULL, NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `blocked_ips`
--
ALTER TABLE `blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `ip_address_2` (`ip_address`),
  ADD KEY `blocked_until` (`blocked_until`);

--
-- Индексы таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Индексы таблицы `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `severity` (`severity`);

--
-- Индексы таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`);

--
-- Индексы таблицы `tv_events`
--
ALTER TABLE `tv_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `type` (`type`),
  ADD KEY `created_at` (`created_at`);

--
-- Индексы таблицы `tv_payments`
--
ALTER TABLE `tv_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `year` (`year`);

--
-- Индексы таблицы `tv_providers`
--
ALTER TABLE `tv_providers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `blocked_ips`
--
ALTER TABLE `blocked_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT для таблицы `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT для таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=166;

--
-- AUTO_INCREMENT для таблицы `tv_events`
--
ALTER TABLE `tv_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT для таблицы `tv_payments`
--
ALTER TABLE `tv_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT для таблицы `tv_providers`
--
ALTER TABLE `tv_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `tv_clients`
--
ALTER TABLE `tv_clients`
  ADD CONSTRAINT `tv_clients_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `tv_providers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
