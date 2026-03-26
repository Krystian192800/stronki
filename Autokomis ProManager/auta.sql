-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 02:14 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `auta`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `auta`
--

CREATE TABLE `auta` (
  `id` int(11) NOT NULL,
  `vin` varchar(17) NOT NULL,
  `nr_rejestracyjny` varchar(15) NOT NULL,
  `marka` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `pojemnosc` decimal(3,1) DEFAULT NULL,
  `konie` int(11) DEFAULT NULL,
  `rocznik` int(11) DEFAULT NULL,
  `data_pierwszej_rejestracji` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auta`
--

INSERT INTO `auta` (`id`, `vin`, `nr_rejestracyjny`, `marka`, `model`, `pojemnosc`, `konie`, `rocznik`, `data_pierwszej_rejestracji`) VALUES
(1, 'WBA12345678901234', 'KR 12345', 'BMW', 'Seria 3', 2.0, 190, 2018, '2018-05-15'),
(2, 'WVW98765432109876', 'WA 99887', 'Volkswagen', 'Golf VIII', 1.5, 150, 2020, '2020-02-10'),
(3, 'VF332165498732145', 'PO 55443', 'Peugeot', '3008', 1.6, 180, 2019, '2019-11-20'),
(4, 'TMB55667788990011', 'DW 11223', 'Skoda', 'Octavia', 2.0, 200, 2021, '2021-08-01');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `auta`
--
ALTER TABLE `auta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vin` (`vin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auta`
--
ALTER TABLE `auta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
