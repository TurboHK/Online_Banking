-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2024-12-22 13:34:10
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `gbcdb`
--
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, FILE, INDEX, ALTER, CREATE TEMPORARY TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EVENT, TRIGGER ON *.* TO `gbcdb_user`@`%` IDENTIFIED BY PASSWORD '*3AD7894DAAC94D80CAE4248D839B88F05DA36EBA';
CREATE DATABASE IF NOT EXISTS `gbcdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gbcdb`;

-- --------------------------------------------------------

--
-- 資料表結構 `account`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 12:13:44
--

CREATE TABLE `account` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `Account_type` enum('time_deposit','card','foreign_exchange') NOT NULL,
  `local_currency_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `account`:
--   `user_id`
--       `users` -> `id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `applycredit`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 11:00:39
--

CREATE TABLE `applycredit` (
  `apply_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` varchar(255) NOT NULL,
  `status` enum('waiting','refuse','success') NOT NULL DEFAULT 'waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `applycredit`:
--   `user_id`
--       `users` -> `id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `cards`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 10:52:35
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `card_type` enum('credit','debit') NOT NULL,
  `card_number` bigint(16) DEFAULT NULL,
  `cardholder_id` int(11) NOT NULL,
  `type` varchar(10) NOT NULL,
  `blocked` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `cards`:
--   `cardholder_id`
--       `users` -> `id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `creditcards`
--
-- 建立時間： 2024-12-22 10:48:27
--

CREATE TABLE `creditcards` (
  `id` int(11) NOT NULL,
  `creditcard_id` bigint(16) NOT NULL,
  `application_id` int(11) NOT NULL,
  `quota` decimal(10,2) NOT NULL DEFAULT 50000.00,
  `remaining_quota` decimal(10,2) NOT NULL DEFAULT 50000.00,
  `repayment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `creditcards`:
--   `id`
--       `cards` -> `id`
--   `id`
--       `cards` -> `id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `debitcards`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 12:13:44
--

CREATE TABLE `debitcards` (
  `id` int(11) NOT NULL,
  `debitcard_id` bigint(16) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `debitcards`:
--   `id`
--       `cards` -> `id`
--

--
-- 觸發器 `debitcards`
--
DELIMITER $$
CREATE TRIGGER `after_registration_create_exchange_amount` AFTER INSERT ON `debitcards` FOR EACH ROW BEGIN
    -- Insert initial exchange transaction records for all supported currencies
    INSERT INTO exchange_transactions (card_id, currency_type, amount)
    VALUES 
    (NEW.id, 'USD', 0.00),
    (NEW.id, 'EUR', 0.00),
    (NEW.id, 'JPY', 0.00),
    (NEW.id, 'CNY', 0.00);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_debitcard_number` AFTER INSERT ON `debitcards` FOR EACH ROW BEGIN
    UPDATE cards
    SET card_number = NEW.debitcard_id
    WHERE id = NEW.id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 資料表結構 `exchange_rates`
--
-- 建立時間： 2024-12-22 10:48:27
--

CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL,
  `sell_currency` char(3) NOT NULL,
  `buy_currency` char(3) NOT NULL,
  `exchange_rate` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `exchange_rates`:
--

-- --------------------------------------------------------

--
-- 資料表結構 `exchange_transactions`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 10:52:35
--

CREATE TABLE `exchange_transactions` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `currency_type` enum('USD','EUR','JPY','CNY','HKD','GBP') NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `exchange_transactions`:
--

-- --------------------------------------------------------

--
-- 資料表結構 `local_currency_time_deposits`
--
-- 建立時間： 2024-12-22 10:48:27
--

CREATE TABLE `local_currency_time_deposits` (
  `id` int(11) NOT NULL,
  `interest_rate` decimal(10,5) DEFAULT NULL,
  `maturity` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `local_currency_time_deposits`:
--

-- --------------------------------------------------------

--
-- 資料表結構 `transactions`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 12:13:44
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('exchange_t','swipe_t','transfer','local_currency_cash_deposit','local_currency_cash_withdrawal','time_deposit') NOT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  `card_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `transactions`:
--   `card_id`
--       `cards` -> `id`
--

--
-- 觸發器 `transactions`
--
DELIMITER $$
CREATE TRIGGER `after_swipe_transaction_creditcard` AFTER INSERT ON `transactions` FOR EACH ROW BEGIN
    -- 检查交易类型是否为 'swipe_t'
    IF NEW.transaction_type = 'swipe_t' THEN
        -- 检查卡是否为信用卡并获取剩余额度
        IF EXISTS (SELECT 1 FROM creditcards WHERE id = NEW.card_id) THEN
            -- 检查剩余额度是否足够
            IF (SELECT remaining_quota FROM creditcards WHERE id = NEW.card_id) >= NEW.amount THEN
                -- 更新信用卡额度
                UPDATE creditcards
                SET remaining_quota = remaining_quota - NEW.amount
                WHERE id = NEW.card_id;
            ELSE
                -- 如果额度不足，抛出错误
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Insufficient credit card quota for swipe transaction';
            END IF;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_time_deposit_insert` AFTER INSERT ON `transactions` FOR EACH ROW BEGIN
    IF NEW.transaction_type = 'time_deposit' THEN
        UPDATE account
        SET local_currency_balance = local_currency_balance + NEW.amount
        WHERE user_id = (SELECT cardholder_id FROM cards WHERE id = NEW.card_id)
        AND Account_type = 'time_deposit';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- 資料表結構 `transfer`
--
-- 建立時間： 2024-12-22 10:48:27
--

CREATE TABLE `transfer` (
  `id` int(11) NOT NULL,
  `payer_id` int(11) NOT NULL,
  `payee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `transfer`:
--   `payer_id`
--       `users` -> `id`
--   `payee_id`
--       `users` -> `id`
--

-- --------------------------------------------------------

--
-- 資料表結構 `users`
--
-- 建立時間： 2024-12-22 10:48:27
-- 最後更新： 2024-12-22 10:52:58
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `phone` int(11) NOT NULL,
  `address` varchar(256) NOT NULL,
  `picture` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 資料表的關聯 `users`:
--

--
-- 觸發器 `users`
--
DELIMITER $$
CREATE TRIGGER `after_registration_create_accounts` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    -- Insert local currency account (type: card)
    INSERT INTO Account (user_id, Account_type, local_currency_balance)
    VALUES (NEW.id, 'card', 0.00);

    -- Create a savings card for local currency accounts
    INSERT INTO cards (card_type, cardholder_id, type, blocked)
    VALUES ('debit', NEW.id, 'debit', 0);

    -- Inserting card-linked data in the debitcards table
    INSERT INTO debitcards (id, balance)
    VALUES (LAST_INSERT_ID(), 0.00);

    -- Insert foreign currency account (type: foreign_exchange)
    INSERT INTO Account (user_id, Account_type, local_currency_balance)
    VALUES (NEW.id, 'foreign_exchange', 0.00);

    -- Insert time deposit account (type: time_deposit)
    INSERT INTO Account (user_id, Account_type, local_currency_balance)
    VALUES (NEW.id, 'time_deposit', 0.00);
END
$$
DELIMITER ;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `applycredit`
--
ALTER TABLE `applycredit`
  ADD PRIMARY KEY (`apply_id`),
  ADD KEY `user_id` (`user_id`);

--
-- 資料表索引 `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cardholder_id` (`cardholder_id`);

--
-- 資料表索引 `creditcards`
--
ALTER TABLE `creditcards`
  ADD PRIMARY KEY (`creditcard_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `creditcards_to_cards_cascade` (`id`);

--
-- 資料表索引 `debitcards`
--
ALTER TABLE `debitcards`
  ADD PRIMARY KEY (`debitcard_id`),
  ADD KEY `debitcards_to_cards_cascade` (`id`);

--
-- 資料表索引 `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sell_currency` (`sell_currency`,`buy_currency`);

--
-- 資料表索引 `exchange_transactions`
--
ALTER TABLE `exchange_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- 資料表索引 `local_currency_time_deposits`
--
ALTER TABLE `local_currency_time_deposits`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`);

--
-- 資料表索引 `transfer`
--
ALTER TABLE `transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payer_id` (`payer_id`),
  ADD KEY `payee_id` (`payee_id`);

--
-- 資料表索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `account`
--
ALTER TABLE `account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `applycredit`
--
ALTER TABLE `applycredit`
  MODIFY `apply_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `creditcards`
--
ALTER TABLE `creditcards`
  MODIFY `creditcard_id` bigint(16) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `debitcards`
--
ALTER TABLE `debitcards`
  MODIFY `debitcard_id` bigint(16) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `exchange_transactions`
--
ALTER TABLE `exchange_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `transfer`
--
ALTER TABLE `transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `applycredit`
--
ALTER TABLE `applycredit`
  ADD CONSTRAINT `applycredit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- 資料表的限制式 `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`cardholder_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `creditcards`
--
ALTER TABLE `creditcards`
  ADD CONSTRAINT `creditcards_ibfk_2` FOREIGN KEY (`id`) REFERENCES `cards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `creditcards_to_cards_cascade` FOREIGN KEY (`id`) REFERENCES `cards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `debitcards`
--
ALTER TABLE `debitcards`
  ADD CONSTRAINT `debitcards_to_cards_cascade` FOREIGN KEY (`id`) REFERENCES `cards` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 資料表的限制式 `transfer`
--
ALTER TABLE `transfer`
  ADD CONSTRAINT `transfer_ibfk_1` FOREIGN KEY (`payer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `transfer_ibfk_2` FOREIGN KEY (`payee_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
