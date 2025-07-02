-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-07-2025 a las 05:07:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cnc_calculador`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `brands`
--

INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Kyocera SGS'),
(2, 'Maykestag'),
(5, 'Schneider');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `machining_types`
--

CREATE TABLE `machining_types` (
  `machining_type_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `machining_types`
--

INSERT INTO `machining_types` (`machining_type_id`, `code`, `name`, `description`) VALUES
(1, 'MILLING', 'Fresado', 'Tipo de mecanizado fresado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materialcategories`
--

CREATE TABLE `materialcategories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materialcategories`
--

INSERT INTO `materialcategories` (`category_id`, `name`, `parent_id`, `image`) VALUES
(1, 'Maderas Naturales', NULL, NULL),
(2, 'Metales no ferrosos', NULL, NULL),
(3, 'Plasticos', NULL, ''),
(4, 'Maderas No Naturales', NULL, ''),
(5, 'Metales ferrosos', NULL, NULL),
(6, 'Fibras/Compuestos', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `kc11` float DEFAULT NULL,
  `mc` float DEFAULT NULL,
  `angle_ramp` tinyint(4) NOT NULL DEFAULT 15,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materials`
--

INSERT INTO `materials` (`material_id`, `category_id`, `name`, `kc11`, `mc`, `angle_ramp`, `image`) VALUES
(3, 1, 'Genérico Madera Dura', 90, 0.25, 14, 'materials/164496.png'),
(4, 1, 'Genérico Madera Media', 50, 0.25, 15, NULL),
(5, 1, 'Genérico Madera Blanda', 40, 0.25, 15, NULL),
(6, 3, 'Genérico Plástico', 95, 0.22, 15, NULL),
(7, 4, 'Genérico Contrachapado / Terciado', 1200, 0.18, 5, NULL),
(8, 1, 'Genérico Fibrofácil (MDF)', 40, 0.2, 10, NULL),
(9, 1, 'Álamo', 45, 0.25, 15, NULL),
(10, 1, 'Marupá', 45, 0.25, 15, NULL),
(11, 1, 'Pino (Elliottis, Taeda)', 45, 0.25, 15, NULL),
(12, 1, 'Fresno', 50, 0.25, 15, NULL),
(13, 1, 'Lenga', 50, 0.25, 15, NULL),
(14, 1, 'Eucalipto', 60, 0.25, 15, NULL),
(15, 1, 'Pino Paraná', 70, 0.25, 14, NULL),
(16, 1, 'Cedro Australiano', 70, 0.25, 14, NULL),
(17, 1, 'Paraíso', 70, 0.25, 14, NULL),
(18, 1, 'Cedro Nacional', 70, 0.25, 14, NULL),
(19, 1, 'Petiribí', 90, 0.25, 15, NULL),
(20, 1, 'Cancharana', 90, 0.25, 15, NULL),
(21, 1, 'Guatambú', 105, 0.25, 14, NULL),
(22, 1, 'Guayubira', 105, 0.25, 14, NULL),
(23, 1, 'Incienso', 105, 0.25, 14, NULL),
(24, 1, 'Lapacho', 115, 0.25, 14, NULL),
(25, 4, 'Multilaminado Pino', 50, 0.22, 15, NULL),
(26, 4, 'Multilaminado Eucalipto', 60, 0.22, 15, NULL),
(27, 4, 'Multilaminado Guatambú', 70, 0.22, 14, NULL),
(28, 4, 'MDF Pino', 50, 0.2, 10, NULL),
(29, 4, 'MDF Eucalipto', 60, 0.22, 10, NULL),
(30, 4, 'MDF Guillermina (económico)', 55, 0.22, 10, NULL),
(31, 4, 'Aglomerado estándar', 70, 0.25, 14, NULL),
(32, 4, 'OSB', 65, 0.23, 15, NULL),
(33, 4, 'Melamina base MDF', 60, 0.2, 10, NULL),
(34, 4, 'Melamina base aglomerado', 70, 0.22, 14, NULL),
(35, 4, 'Enchapado base MDF', 60, 0.22, 10, NULL),
(36, 4, 'Enchapado base aglomerado', 70, 0.22, 15, NULL),
(37, 3, 'Acrílico (PMMA)', 95, 0.22, 15, NULL),
(38, 3, 'Acrílico alto impacto', 105, 0.22, 15, NULL),
(39, 3, 'Polifan (PVC espumado)', 20, 0.2, 15, NULL),
(40, 3, 'Polietileno HDPE', 40, 0.2, 15, NULL),
(41, 3, 'Polipropileno', 40, 0.2, 15, NULL),
(42, 3, 'Nylon', 55, 0.2, 15, NULL),
(43, 3, 'Bakelita', 125, 0.25, 14, NULL),
(44, 6, 'Fibra de vidrio (tipo G10)', 1000, 0.3, 5, NULL),
(45, 6, 'Fibra de carbono', 1250, 0.3, 5, NULL),
(46, 6, 'Resina epoxi cargada', 600, 0.25, 8, NULL),
(47, 6, 'PCB (circuitos electrónicos)', 400, 0.25, 11, NULL),
(48, 2, 'Aluminio 1050 (blando)', 300, 0.2, 10, NULL),
(49, 2, 'Aluminio 6061', 400, 0.18, 11, NULL),
(50, 2, 'Aluminio 7075 (duro aeronáutico)', 500, 0.18, 10, NULL),
(51, 2, 'Latón', 750, 0.2, 8, NULL),
(52, 5, 'Acero al carbono (SAE 1010, 1020)', 1750, 0.25, 8, NULL),
(53, 5, 'Acero inoxidable (AISI 304 o 316)', 2500, 0.25, 6, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `series`
--

CREATE TABLE `series` (
  `id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `series`
--

INSERT INTO `series` (`id`, `brand_id`, `code`, `notes`) VALUES
(1, 1, '21', 'Wood Router – UpCut'),
(2, 1, '22', 'Wood Router – DownCut'),
(3, 1, '21M', 'Wood Router – UpCut'),
(4, 1, '22M', 'Wood Router – DownCut'),
(48, 2, '6205', 'Fresa monoflute MDI para aluminio y plásticos'),
(49, 2, '6395', 'Fresa monofluta mini MDI para aluminio y plásticos'),
(50, 2, '8495', 'Fresa monofluta MDI para aluminio y plásticos, derecho con hélice derecha'),
(51, 2, '6215', 'Fresa monoflute MDI para aluminio y plásticos, derecho con hélice izquierda'),
(52, 2, '7976', 'Fresa de contorno MDI para plásticos, corte frontal'),
(53, 2, '7986', 'Fresa de contorno MDI para plásticos, dos filos'),
(54, 2, '7250', 'Speedcut-Aluminium, long series, neck, 3-flutes, unequal spiral angles'),
(55, 2, '7950', 'Speedcut-Aluminium HPC, long series, neck, 3-flutes, unequal spiral angles'),
(56, 2, '7220', 'Speedcut-Aluminium XL extra largo, cuello libre, 3 filos, ángulos de hélice desiguales'),
(58, 2, '7230', 'Speedcut-Aluminium con radio en el cuello, cabeza larga, 3 filos'),
(59, 2, '7050', 'Speedcut-Aluminium XL extra largo con radio en el cuello, 3 filos, hélice desigual'),
(60, 2, '7290', 'Speedcut-Aluminium ballnose, long series, neck, 2 filos, ángulos desiguales'),
(61, 2, '7260', 'Speedcut-Aluminium ballnose XXL, cuello libre, 2 filos, hélice desigual'),
(62, 2, '7055', 'Schruppfräser 35° Vario Speedcut-Aluminium, extra long XL, cuello libre, 3 filos, superficies pulidas'),
(63, 2, '8915', 'Fresa monofluta corta MDI para aluminio, serie corta, 2 filos'),
(64, 2, '8025', 'VHM-Schaftfräser lang, Zweischneider'),
(65, 2, '9245', 'VHM-Miniaturfräser mit verstärktem Schaft, Serie kurz, Zweischneider'),
(66, 2, '7525', 'VHM-Radiusfräser kurz, Zweischneider'),
(67, 2, '6925', 'VHM-Radiusfräser für Aluminium kurz, Zweischneider'),
(68, 2, '7535', 'VHM‐Radiusfräser lang, Zweischneider'),
(69, 2, '7725', 'VHM-Entgrater 60° (Deburring mill 60°)'),
(70, 2, '7755', 'VHM-Entgrater 90° Speedcut-Aluminium, 4 filos'),
(71, 1, '23', 'Tapered Square End (Fractional series)'),
(72, 1, '24', 'Tapered Ball End (fractional series)'),
(73, 1, '1MCR', 'Ti-Namite-A (AlTiN), 4-flute square end, no corner radius'),
(74, 1, '1XLM-A', 'Ti-Namite-A (AlTiN), 4-flute square end, RE = 0.25 mm'),
(75, 1, '1MCR-R', 'Ti-Namite-A (AlTiN), 4-flute square end, RE = 0.50 mm'),
(76, 1, '16M-A', 'Ti-Namite-A (AlTiN), 4-flute square end, RE = 1.00 mm'),
(77, 1, '1MB', '4-flute ball end, short series, RE = DC/2'),
(78, 1, '1XLMB', '4-flute ball end, extended series, RE = DC/2'),
(79, 1, '3M', '2-Flute Square End, short series'),
(80, 1, '3XLM', '2-Flute Square End, long series'),
(81, 1, '3MB', '2-Flute Ball End, short series (RE = DC/2)'),
(82, 1, '3XLMB', '2-Flute Ball End, long series (RE = DC/2)'),
(83, 2, '6257', 'VHM Mini end mill, 2 flutes, HSC, ALUNITE'),
(85, 5, '7860', 'Fresa 2 dientes punta 60°'),
(86, 5, '7890', 'Fresa 2 dientes punta 90°'),
(87, 5, '6670', 'Presa tipo tambor, 2 filos, corte recto'),
(88, 5, '8000', 'Fresa 1/4R, 2 filos, corte recto'),
(89, 5, 'GA', 'V-bit punta, 1 filo, corte recto'),
(90, 5, 'GF', 'V-bit esférica, 1 filo, corte recto'),
(91, 5, '6650', 'Fresa de un diente de compresión'),
(92, 5, '6660', 'Fresa de dos dientes de compresión'),
(93, 2, '6690', 'Fresa de un diente helicoidal, serie extra larga, Austria'),
(95, 1, '3EL', '2 Flute Square End – Serie Extra-Larga'),
(96, 1, '3ELB', '2 Flute Ball End – Serie Extra-Larga');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `strategies`
--

CREATE TABLE `strategies` (
  `strategy_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `machining_type_id` int(11) DEFAULT NULL,
  `type` enum('Milling','Drilling') NOT NULL DEFAULT 'Milling',
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `strategies`
--

INSERT INTO `strategies` (`strategy_id`, `name`, `machining_type_id`, `type`, `parent_id`, `image`) VALUES
(1, 'Perfilado', 1, 'Milling', NULL, ''),
(2, 'Ranurado / Corte/ Slot', 1, 'Milling', NULL, ''),
(3, 'Grabado en V / 2.5D', 1, 'Milling', NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toolsmaterial_generico`
--

CREATE TABLE `toolsmaterial_generico` (
  `tool_material_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `vc_m_min` float DEFAULT NULL,
  `fz_min_mm` float DEFAULT NULL,
  `fz_max_mm` float DEFAULT NULL,
  `ap_slot_mm` float DEFAULT NULL,
  `ae_slot_mm` float DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 0 CHECK (`rating` between 0 and 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toolsmaterial_maykestag`
--

CREATE TABLE `toolsmaterial_maykestag` (
  `tool_material_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `vc_m_min` float DEFAULT NULL,
  `fz_min_mm` float DEFAULT NULL,
  `fz_max_mm` float DEFAULT NULL,
  `ap_slot_mm` float DEFAULT NULL,
  `ae_slot_mm` float DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 0 CHECK (`rating` between 0 and 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toolsmaterial_schneider`
--

CREATE TABLE `toolsmaterial_schneider` (
  `tool_material_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `vc_m_min` float DEFAULT NULL,
  `fz_min_mm` float DEFAULT NULL,
  `fz_max_mm` float DEFAULT NULL,
  `ap_slot_mm` float DEFAULT NULL,
  `ae_slot_mm` float DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 0 CHECK (`rating` between 0 and 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toolsmaterial_sgs`
--

CREATE TABLE `toolsmaterial_sgs` (
  `tool_material_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `vc_m_min` float DEFAULT NULL,
  `fz_min_mm` float DEFAULT NULL,
  `fz_max_mm` float DEFAULT NULL,
  `ap_slot_mm` float DEFAULT NULL,
  `ae_slot_mm` float DEFAULT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 0 CHECK (`rating` between 0 and 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `toolstrategy`
--

CREATE TABLE `toolstrategy` (
  `tool_strategy_id` int(11) NOT NULL,
  `tool_table` enum('tools_sgs','tools_maykestag','tools_schneider','tools_generico') NOT NULL,
  `tool_id` int(11) NOT NULL,
  `strategy_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `toolstrategy`
--

INSERT INTO `toolstrategy` (`tool_strategy_id`, `tool_table`, `tool_id`, `strategy_id`) VALUES
(52, 'tools_sgs', 7, 3),
(53, 'tools_sgs', 7, 1),
(54, 'tools_sgs', 7, 2),
(169, 'tools_sgs', 22, 1),
(170, 'tools_sgs', 22, 2),
(171, 'tools_sgs', 22, 3),
(172, 'tools_sgs', 23, 1),
(173, 'tools_sgs', 23, 2),
(174, 'tools_sgs', 23, 3),
(175, 'tools_sgs', 24, 1),
(176, 'tools_sgs', 24, 2),
(177, 'tools_sgs', 24, 3),
(178, 'tools_sgs', 25, 1),
(179, 'tools_sgs', 25, 2),
(180, 'tools_sgs', 25, 3),
(181, 'tools_sgs', 26, 1),
(182, 'tools_sgs', 26, 2),
(183, 'tools_sgs', 26, 3),
(184, 'tools_sgs', 27, 1),
(185, 'tools_sgs', 27, 2),
(186, 'tools_sgs', 27, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools_generico`
--

CREATE TABLE `tools_generico` (
  `tool_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `tool_code` varchar(50) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `flute_count` tinyint(4) DEFAULT NULL,
  `diameter_mm` decimal(7,3) DEFAULT NULL,
  `shank_diameter_mm` decimal(7,3) DEFAULT NULL,
  `flute_length_mm` decimal(7,3) DEFAULT NULL,
  `cut_length_mm` decimal(7,3) DEFAULT NULL,
  `full_length_mm` decimal(7,3) DEFAULT NULL,
  `rack_angle` decimal(6,2) DEFAULT NULL,
  `helix` decimal(6,2) DEFAULT NULL,
  `conical_angle` decimal(6,2) DEFAULT 0.00,
  `radius` decimal(7,3) DEFAULT 0.000,
  `tool_type` varchar(50) DEFAULT NULL,
  `made_in` varchar(30) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `coated` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_dimensions` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools_maykestag`
--

CREATE TABLE `tools_maykestag` (
  `tool_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `tool_code` varchar(50) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `flute_count` tinyint(4) DEFAULT NULL,
  `diameter_mm` decimal(7,3) DEFAULT NULL,
  `shank_diameter_mm` decimal(7,3) DEFAULT NULL,
  `flute_length_mm` decimal(7,3) DEFAULT NULL,
  `cut_length_mm` decimal(7,3) DEFAULT NULL,
  `full_length_mm` decimal(7,3) DEFAULT NULL,
  `rack_angle` decimal(6,2) DEFAULT NULL,
  `helix` decimal(6,2) DEFAULT NULL,
  `conical_angle` decimal(6,2) DEFAULT 0.00,
  `radius` decimal(7,3) DEFAULT 0.000,
  `tool_type` varchar(50) DEFAULT NULL,
  `made_in` varchar(30) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `coated` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_dimensions` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tools_maykestag`
--

INSERT INTO `tools_maykestag` (`tool_id`, `series_id`, `tool_code`, `name`, `flute_count`, `diameter_mm`, `shank_diameter_mm`, `flute_length_mm`, `cut_length_mm`, `full_length_mm`, `rack_angle`, `helix`, `conical_angle`, `radius`, `tool_type`, `made_in`, `material`, `coated`, `notes`, `image`, `image_dimensions`) VALUES
(1, 4, '7725004001', 'Fresa High-Perf', 4, 4.000, 4.000, NULL, 2.000, 2.000, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'Austria', NULL, 'Sin recubrir', NULL, NULL, NULL),
(2, 4, '7725006001', 'Fresa High-Perf', 4, 6.000, 6.000, NULL, 3.000, 3.000, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'Austria', NULL, 'Sin recubrir', NULL, NULL, NULL),
(3, 4, '7725008001', 'Fresa High-Perf', 4, 8.000, 8.000, NULL, 4.000, 4.000, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'Austria', NULL, 'Sin recubrir', NULL, NULL, NULL),
(4, 48, '062050020100', NULL, 1, 2.000, 2.000, 10.000, 10.000, 38.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050020100.png', 'assets/img/tools/maykesta'),
(5, 48, '062050025100', NULL, 1, 2.500, 2.500, 10.000, 10.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050025100.png', 'assets/img/tools/maykesta'),
(6, 48, '062050030100', NULL, 1, 3.000, 3.000, 12.000, 12.000, 45.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050030100.png', 'assets/img/tools/maykesta'),
(7, 48, '062050040100', NULL, 1, 4.000, 4.000, 15.000, 15.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050040100.png', 'assets/img/tools/maykesta'),
(8, 48, '062050050100', NULL, 1, 5.000, 5.000, 18.000, 18.000, 60.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050050100.png', 'assets/img/tools/maykesta'),
(9, 48, '062050060100', NULL, 1, 6.000, 6.000, 25.000, 25.000, 75.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050060100.png', 'assets/img/tools/maykesta'),
(10, 48, '062050080100', NULL, 1, 8.000, 8.000, 30.000, 30.000, 80.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050080100.png', 'assets/img/tools/maykesta'),
(11, 48, '062050100100', NULL, 1, 10.000, 10.000, 30.000, 30.000, 100.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050100100.png', 'assets/img/tools/maykesta'),
(12, 48, '062050120100', NULL, 1, 12.000, 12.000, 30.000, 30.000, 120.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062050120100.png', 'assets/img/tools/maykesta'),
(13, 49, '0639500100100', NULL, 1, 1.000, 6.000, 5.000, 5.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500100100.png', 'assets/img/tools/maykesta'),
(14, 49, '0639500150100', NULL, 1, 1.500, 6.000, 7.000, 7.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500150100.png', 'assets/img/tools/maykesta'),
(15, 49, '0639500200100', NULL, 1, 2.000, 6.000, 7.000, 7.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500200100.png', 'assets/img/tools/maykesta'),
(16, 49, '0639500250100', NULL, 1, 2.500, 6.000, 8.000, 8.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500250100.png', 'assets/img/tools/maykesta'),
(17, 49, '0639500300100', NULL, 1, 3.000, 6.000, 8.000, 8.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500300100.png', 'assets/img/tools/maykesta'),
(18, 49, '0639500350100', NULL, 1, 3.500, 6.000, 10.000, 10.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500350100.png', 'assets/img/tools/maykesta'),
(19, 49, '0639500400100', NULL, 1, 4.000, 6.000, 10.000, 10.000, 40.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500400100.png', 'assets/img/tools/maykesta'),
(20, 49, '0639500450100', NULL, 1, 4.500, 6.000, 12.000, 12.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500450100.png', 'assets/img/tools/maykesta'),
(21, 49, '0639500500100', NULL, 1, 5.000, 6.000, 12.000, 12.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500500100.png', 'assets/img/tools/maykesta'),
(22, 49, '0639500550100', NULL, 1, 5.500, 6.000, 14.000, 14.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500550100.png', 'assets/img/tools/maykesta'),
(23, 49, '0639500600100', NULL, 1, 6.000, 6.000, 14.000, 14.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0639500600100.png', 'assets/img/tools/maykesta'),
(24, 50, '064950020100', NULL, 1, 2.000, 2.000, 10.000, 10.000, 38.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950020100.png', 'assets/img/tools/maykesta'),
(25, 50, '064950025100', NULL, 1, 2.500, 2.500, 12.000, 12.000, 38.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950025100.png', 'assets/img/tools/maykesta'),
(26, 50, '064950030100', NULL, 1, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950030100.png', 'assets/img/tools/maykesta'),
(27, 50, '064950040100', NULL, 1, 4.000, 4.000, 15.000, 15.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950040100.png', 'assets/img/tools/maykesta'),
(28, 50, '064950050100', NULL, 1, 5.000, 5.000, 15.000, 15.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950050100.png', 'assets/img/tools/maykesta'),
(29, 50, '064950060100', NULL, 1, 6.000, 6.000, 18.000, 18.000, 50.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950060100.png', 'assets/img/tools/maykesta'),
(30, 50, '064950080100', NULL, 1, 8.000, 8.000, 25.000, 25.000, 75.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950080100.png', 'assets/img/tools/maykesta'),
(31, 50, '064950100100', NULL, 1, 10.000, 10.000, 30.000, 30.000, 80.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950100100.png', 'assets/img/tools/maykesta'),
(32, 50, '064950120100', NULL, 1, 12.000, 12.000, 30.000, 30.000, 73.000, 0.00, 30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/064950120100.png', 'assets/img/tools/maykesta'),
(33, 51, '062150020100', NULL, 1, 2.000, 2.000, 10.000, 10.000, 38.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150020100.png', 'assets/img/tools/maykesta'),
(34, 51, '062150025100', NULL, 1, 2.500, 2.500, 12.000, 12.000, 38.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150025100.png', 'assets/img/tools/maykesta'),
(35, 51, '062150030100', NULL, 1, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150030100.png', 'assets/img/tools/maykesta'),
(36, 51, '062150040100', NULL, 1, 4.000, 4.000, 15.000, 15.000, 50.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150040100.png', 'assets/img/tools/maykesta'),
(37, 51, '062150050100', NULL, 1, 5.000, 5.000, 15.000, 15.000, 50.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150050100.png', 'assets/img/tools/maykesta'),
(38, 51, '062150060100', NULL, 1, 6.000, 6.000, 25.000, 25.000, 75.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150060100.png', 'assets/img/tools/maykesta'),
(39, 51, '062150080100', NULL, 1, 8.000, 8.000, 30.000, 30.000, 82.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150080100.png', 'assets/img/tools/maykesta'),
(40, 51, '062150100100', NULL, 1, 10.000, 10.000, 30.000, 30.000, 100.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150100100.png', 'assets/img/tools/maykesta'),
(41, 51, '062150120100', NULL, 1, 12.000, 12.000, 30.000, 30.000, 73.000, 0.00, -30.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062150120100.png', 'assets/img/tools/maykesta'),
(42, 52, '0797600600100', NULL, 1, 6.000, 6.000, 18.000, 18.000, 50.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0797600600100.png', 'assets/img/tools/maykesta'),
(43, 52, '0797600800100', NULL, 1, 8.000, 8.000, 25.000, 25.000, 63.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0797600800100.png', 'assets/img/tools/maykesta'),
(44, 52, '0797601000100', NULL, 1, 10.000, 10.000, 30.000, 30.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0797601000100.png', 'assets/img/tools/maykesta'),
(45, 52, '0797601200100', NULL, 1, 12.000, 12.000, 30.000, 30.000, 73.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0797601200100.png', 'assets/img/tools/maykesta'),
(46, 52, '0797601400100', NULL, 1, 14.000, 14.000, 30.000, 30.000, 75.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0797601400100.png', 'assets/img/tools/maykesta'),
(47, 53, '079860020100', NULL, 2, 2.000, 2.000, 10.000, 10.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860020100.png', 'assets/img/tools/maykesta'),
(48, 53, '079860030100', NULL, 2, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860030100.png', 'assets/img/tools/maykesta'),
(49, 53, '079860040100', NULL, 2, 4.000, 4.000, 15.000, 15.000, 50.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860040100.png', 'assets/img/tools/maykesta'),
(50, 53, '079860050100', NULL, 2, 5.000, 5.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860050100.png', 'assets/img/tools/maykesta'),
(51, 53, '079860060100', NULL, 2, 6.000, 6.000, 25.000, 25.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860060100.png', 'assets/img/tools/maykesta'),
(52, 53, '079860080100', NULL, 2, 8.000, 8.000, 25.000, 25.000, 63.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860080100.png', 'assets/img/tools/maykesta'),
(53, 53, '079860100100', NULL, 2, 10.000, 10.000, 30.000, 30.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/079860100100.png', 'assets/img/tools/maykesta'),
(54, 54, '0725000300100', NULL, 3, 3.000, 6.000, 14.000, 14.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725000300100.png', 'assets/img/tools/maykesta'),
(55, 55, '0795000300100', NULL, 3, 3.000, 6.000, 14.000, 14.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795000300100.png', 'assets/img/tools/maykesta'),
(56, 54, '0725000400100', NULL, 3, 4.000, 6.000, 11.000, 11.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725000400100.png', 'assets/img/tools/maykesta'),
(57, 55, '0795000400100', NULL, 3, 4.000, 6.000, 11.000, 11.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795000400100.png', 'assets/img/tools/maykesta'),
(58, 54, '0725000500100', NULL, 3, 5.000, 6.000, 13.000, 13.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725000500100.png', 'assets/img/tools/maykesta'),
(59, 55, '0795000500100', NULL, 3, 5.000, 6.000, 13.000, 13.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795000500100.png', 'assets/img/tools/maykesta'),
(60, 54, '0725000600100', NULL, 3, 6.000, 6.000, 15.000, 15.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725000600100.png', 'assets/img/tools/maykesta'),
(61, 55, '0795000600100', NULL, 3, 6.000, 6.000, 15.000, 15.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795000600100.png', 'assets/img/tools/maykesta'),
(62, 54, '0725000800100', NULL, 3, 8.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725000800100.png', 'assets/img/tools/maykesta'),
(63, 55, '0795000800100', NULL, 3, 8.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795000800100.png', 'assets/img/tools/maykesta'),
(64, 54, '0725001000100', NULL, 3, 10.000, 10.000, 22.000, 22.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725001000100.png', 'assets/img/tools/maykesta'),
(65, 55, '0795001000100', NULL, 3, 10.000, 10.000, 22.000, 22.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795001000100.png', 'assets/img/tools/maykesta'),
(66, 54, '0725001200100', NULL, 3, 12.000, 12.000, 26.000, 26.000, 83.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725001200100.png', 'assets/img/tools/maykesta'),
(67, 55, '0795001200100', NULL, 3, 12.000, 12.000, 26.000, 26.000, 83.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795001200100.png', 'assets/img/tools/maykesta'),
(68, 54, '0725001600100', NULL, 3, 16.000, 16.000, 32.000, 32.000, 92.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725001600100.png', 'assets/img/tools/maykesta'),
(69, 55, '0795001600100', NULL, 3, 16.000, 16.000, 32.000, 32.000, 92.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795001600100.png', 'assets/img/tools/maykesta'),
(70, 54, '0725002000100', NULL, 3, 20.000, 20.000, 38.000, 38.000, 104.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725002000100.png', 'assets/img/tools/maykesta'),
(71, 55, '0795002000100', NULL, 3, 20.000, 20.000, 38.000, 38.000, 104.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795002000100.png', 'assets/img/tools/maykesta'),
(72, 54, '0725002500100', NULL, 3, 25.000, 25.000, 45.000, 45.000, 120.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0725002500100.png', 'assets/img/tools/maykesta'),
(73, 55, '0795002500100', NULL, 3, 25.000, 25.000, 45.000, 45.000, 120.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0795002500100.png', 'assets/img/tools/maykesta'),
(74, 56, '0722000300100', NULL, 3, 3.000, 6.000, 12.000, 12.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000300100.png', 'assets/img/tools/maykesta'),
(75, 56, '0722000400100', NULL, 3, 4.000, 6.000, 11.000, 11.000, 61.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000400100.png', 'assets/img/tools/maykesta'),
(76, 56, '0722000500100', NULL, 3, 5.000, 6.000, 13.000, 13.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000500100.png', 'assets/img/tools/maykesta'),
(77, 56, '0722000600100', NULL, 3, 6.000, 6.000, 19.000, 19.000, 57.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000600100.png', 'assets/img/tools/maykesta'),
(78, 56, '0722000800100', NULL, 3, 8.000, 8.000, 23.000, 23.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000800100.png', 'assets/img/tools/maykesta'),
(79, 56, '0722001000100', NULL, 3, 10.000, 10.500, 26.000, 26.000, 72.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001000100.png', 'assets/img/tools/maykesta'),
(80, 56, '0722001200100', NULL, 3, 12.000, 12.900, 32.000, 32.000, 95.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001200100.png', 'assets/img/tools/maykesta'),
(81, 56, '0722001600100', NULL, 3, 16.000, 19.000, 32.000, 32.000, 124.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001600100.png', 'assets/img/tools/maykesta'),
(82, 56, '0722002000100', NULL, 3, 20.000, 19.500, 32.000, 32.000, 124.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722002000100.png', 'assets/img/tools/maykesta'),
(83, 56, '0722000300100', NULL, 3, 3.000, 6.000, 8.000, 8.000, 60.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000300100.png', 'assets/img/tools/maykesta'),
(84, 56, '0722000400100', NULL, 3, 4.000, 6.000, 15.000, 15.000, 71.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000400100.png', 'assets/img/tools/maykesta'),
(85, 56, '0722000500100', NULL, 3, 5.000, 6.000, 13.000, 13.000, 76.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000500100.png', 'assets/img/tools/maykesta'),
(86, 56, '0722000600100', NULL, 3, 6.000, 6.000, 19.000, 19.000, 82.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000600100.png', 'assets/img/tools/maykesta'),
(87, 56, '0722000800100', NULL, 3, 8.000, 8.000, 23.000, 23.000, 109.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722000800100.png', 'assets/img/tools/maykesta'),
(88, 56, '0722001000100', NULL, 3, 10.000, 10.000, 26.000, 26.000, 125.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001000100.png', 'assets/img/tools/maykesta'),
(89, 56, '0722001200100', NULL, 3, 12.000, 12.000, 32.000, 32.000, 150.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001200100.png', 'assets/img/tools/maykesta'),
(90, 56, '0722001600100', NULL, 3, 16.000, 16.000, 38.000, 38.000, 180.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722001600100.png', 'assets/img/tools/maykesta'),
(91, 56, '0722002000100', NULL, 3, 20.000, 20.000, 38.000, 38.000, 200.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0722002000100.png', 'assets/img/tools/maykesta'),
(109, 59, '0720500300100', NULL, 3, 3.000, 6.000, 8.000, 14.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500300100.png', 'assets/img/tools/maykesta'),
(110, 59, '0720500400100', NULL, 3, 4.000, 6.000, 11.000, 16.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500400100.png', 'assets/img/tools/maykesta'),
(111, 59, '0720500500100', NULL, 3, 5.000, 6.000, 13.000, 18.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500500100.png', 'assets/img/tools/maykesta'),
(112, 59, '0720500510100', NULL, 3, 5.000, 6.000, 13.000, 18.000, 57.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500510100.png', 'assets/img/tools/maykesta'),
(113, 59, '0720500600100', NULL, 3, 6.000, 6.000, 13.000, 21.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500600100.png', 'assets/img/tools/maykesta'),
(114, 59, '0720500610100', NULL, 3, 6.000, 6.000, 13.000, 21.000, 57.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500610100.png', 'assets/img/tools/maykesta'),
(115, 59, '0720500800100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500800100.png', 'assets/img/tools/maykesta'),
(116, 59, '0720500810100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500810100.png', 'assets/img/tools/maykesta'),
(117, 59, '0720500820100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720500820100.png', 'assets/img/tools/maykesta'),
(118, 59, '0720501000100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501000100.png', 'assets/img/tools/maykesta'),
(119, 59, '0720501010100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501010100.png', 'assets/img/tools/maykesta'),
(120, 59, '0720501020100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501020100.png', 'assets/img/tools/maykesta'),
(121, 59, '0720501210100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501210100.png', 'assets/img/tools/maykesta'),
(122, 59, '0720501220100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501220100.png', 'assets/img/tools/maykesta'),
(123, 59, '0720501230100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 3.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501230100.png', 'assets/img/tools/maykesta'),
(124, 59, '0720501610100', NULL, 3, 16.000, 16.000, 32.000, 44.000, 92.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501610100.png', 'assets/img/tools/maykesta'),
(125, 59, '0720501620100', NULL, 3, 16.000, 16.000, 32.000, 44.000, 92.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720501620100.png', 'assets/img/tools/maykesta'),
(126, 59, '0720502020100', NULL, 3, 20.000, 20.000, 38.000, 71.000, 124.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0720502020100.png', 'assets/img/tools/maykesta'),
(127, 58, '0723000305100', NULL, 3, 3.000, 6.000, 8.000, 14.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000305100.png', 'assets/img/tools/maykesta'),
(128, 58, '0723000405100', NULL, 3, 4.000, 6.000, 11.000, 16.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000405100.png', 'assets/img/tools/maykesta'),
(129, 58, '0723000505100', NULL, 3, 5.000, 6.000, 13.000, 18.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000505100.png', 'assets/img/tools/maykesta'),
(130, 58, '0723000510100', NULL, 3, 5.000, 6.000, 13.000, 18.000, 57.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000510100.png', 'assets/img/tools/maykesta'),
(131, 58, '0723000605100', NULL, 3, 6.000, 6.000, 13.000, 21.000, 57.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000605100.png', 'assets/img/tools/maykesta'),
(132, 58, '0723000610100', NULL, 3, 6.000, 6.000, 13.000, 21.000, 57.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000610100.png', 'assets/img/tools/maykesta'),
(133, 58, '0723000805100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000805100.png', 'assets/img/tools/maykesta'),
(134, 58, '0723000810100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000810100.png', 'assets/img/tools/maykesta'),
(135, 58, '0723000820100', NULL, 3, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723000820100.png', 'assets/img/tools/maykesta'),
(136, 58, '0723001005100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001005100.png', 'assets/img/tools/maykesta'),
(137, 58, '0723001010100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001010100.png', 'assets/img/tools/maykesta'),
(138, 58, '0723001020100', NULL, 3, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001020100.png', 'assets/img/tools/maykesta'),
(139, 58, '0723001210100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001210100.png', 'assets/img/tools/maykesta'),
(140, 58, '0723001220100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001220100.png', 'assets/img/tools/maykesta'),
(141, 58, '0723001230100', NULL, 3, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 3.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001230100.png', 'assets/img/tools/maykesta'),
(142, 58, '0723001610100', NULL, 3, 16.000, 16.000, 32.000, 44.000, 92.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001610100.png', 'assets/img/tools/maykesta'),
(143, 58, '0723001620100', NULL, 3, 16.000, 16.000, 32.000, 44.000, 92.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0723001620100.png', 'assets/img/tools/maykesta'),
(153, 61, '0726000300100', NULL, 2, 3.000, 6.000, 8.000, 23.000, 57.000, 0.00, 0.00, 0.00, 1.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726000300100.png', 'assets/img/tools/maykesta'),
(154, 61, '0726000400100', NULL, 2, 4.000, 6.000, 11.000, 27.000, 57.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726000400100.png', 'assets/img/tools/maykesta'),
(155, 61, '0726000500100', NULL, 2, 5.000, 6.000, 13.000, 30.000, 57.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726000500100.png', 'assets/img/tools/maykesta'),
(156, 61, '0726000600100', NULL, 2, 6.000, 6.000, 13.000, 31.000, 57.000, 0.00, 0.00, 0.00, 3.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726000600100.png', 'assets/img/tools/maykesta'),
(157, 61, '0726000800100', NULL, 2, 8.000, 8.000, 19.000, 34.000, 63.000, 0.00, 0.00, 0.00, 4.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726000800100.png', 'assets/img/tools/maykesta'),
(158, 61, '0726001000100', NULL, 2, 10.000, 10.000, 22.000, 38.000, 72.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726001000100.png', 'assets/img/tools/maykesta'),
(159, 61, '0726001200100', NULL, 2, 12.000, 12.000, 26.000, 45.000, 83.000, 0.00, 0.00, 0.00, 6.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0726001200100.png', 'assets/img/tools/maykesta'),
(160, 60, '0729000300100', NULL, 2, 3.000, 6.000, 8.000, 14.000, 57.000, 0.00, 0.00, 0.00, 1.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729000300100.png', 'assets/img/tools/maykesta'),
(161, 60, '0729000400100', NULL, 2, 4.000, 6.000, 11.000, 16.000, 57.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729000400100.png', 'assets/img/tools/maykesta'),
(162, 60, '0729000500100', NULL, 2, 5.000, 6.000, 13.000, 18.000, 57.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729000500100.png', 'assets/img/tools/maykesta'),
(163, 60, '0729000600100', NULL, 2, 6.000, 6.000, 13.000, 21.000, 57.000, 0.00, 0.00, 0.00, 3.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729000600100.png', 'assets/img/tools/maykesta'),
(164, 60, '0729000800100', NULL, 2, 8.000, 8.000, 19.000, 27.000, 63.000, 0.00, 0.00, 0.00, 4.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729000800100.png', 'assets/img/tools/maykesta'),
(165, 60, '0729001000100', NULL, 2, 10.000, 10.000, 22.000, 32.000, 72.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729001000100.png', 'assets/img/tools/maykesta'),
(166, 60, '0729001200100', NULL, 2, 12.000, 12.000, 26.000, 38.000, 83.000, 0.00, 0.00, 0.00, 6.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729001200100.png', 'assets/img/tools/maykesta'),
(167, 60, '0729001600100', NULL, 2, 16.000, 16.000, 32.000, 44.000, 92.000, 0.00, 0.00, 0.00, 8.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729001600100.png', 'assets/img/tools/maykesta'),
(168, 60, '0729002000100', NULL, 2, 20.000, 20.000, 38.000, 71.000, 124.000, 0.00, 0.00, 0.00, 10.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0729002000100.png', 'assets/img/tools/maykesta'),
(169, 62, '0705500600100', NULL, 3, 6.000, 5.000, 13.000, 26.000, 62.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705500600100.png', 'assets/img/tools/maykesta'),
(170, 62, '0705500800100', NULL, 3, 8.000, 7.500, 15.000, 34.000, 70.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705500800100.png', 'assets/img/tools/maykesta'),
(171, 62, '0705501000100', NULL, 3, 10.000, 9.500, 22.000, 40.000, 80.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705501000100.png', 'assets/img/tools/maykesta'),
(172, 62, '0705501200100', NULL, 3, 12.000, 11.500, 26.000, 50.000, 90.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705501200100.png', 'assets/img/tools/maykesta'),
(173, 62, '0705501600100', NULL, 3, 16.000, 15.000, 32.000, 57.000, 105.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705501600100.png', 'assets/img/tools/maykesta'),
(174, 62, '0705502000100', NULL, 3, 20.000, 19.500, 38.000, 71.000, 124.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0705502000100.png', 'assets/img/tools/maykesta'),
(175, 63, '0891500100100', NULL, 2, 1.000, 1.000, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500100100.png', 'assets/img/tools/maykesta'),
(176, 63, '0891500150100', NULL, 2, 1.500, 1.500, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500150100.png', 'assets/img/tools/maykesta'),
(177, 63, '0891500200100', NULL, 2, 2.000, 2.000, 8.000, 8.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500200100.png', 'assets/img/tools/maykesta'),
(178, 63, '0891500250100', NULL, 2, 2.500, 2.500, 8.000, 8.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500250100.png', 'assets/img/tools/maykesta'),
(179, 63, '0891500300100', NULL, 2, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500300100.png', 'assets/img/tools/maykesta'),
(180, 63, '0891500400100', NULL, 2, 4.000, 4.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500400100.png', 'assets/img/tools/maykesta'),
(181, 63, '0891500500100', NULL, 2, 5.000, 5.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500500100.png', 'assets/img/tools/maykesta'),
(182, 63, '0891500600100', NULL, 2, 6.000, 6.000, 15.000, 15.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500600100.png', 'assets/img/tools/maykesta'),
(183, 63, '0891500800100', NULL, 2, 8.000, 8.000, 20.000, 20.000, 50.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891500800100.png', 'assets/img/tools/maykesta'),
(184, 63, '0891501000100', NULL, 2, 10.000, 10.000, 22.000, 22.000, 50.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891501000100.png', 'assets/img/tools/maykesta'),
(185, 63, '0891501200100', NULL, 2, 12.000, 12.000, 26.000, 26.000, 73.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891501200100.png', 'assets/img/tools/maykesta'),
(186, 63, '0891501400100', NULL, 2, 14.000, 14.000, 26.000, 26.000, 73.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891501400100.png', 'assets/img/tools/maykesta'),
(187, 63, '0891501600100', NULL, 2, 16.000, 16.000, 32.000, 32.000, 92.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891501600100.png', 'assets/img/tools/maykesta'),
(188, 63, '0891501800100', NULL, 2, 18.000, 18.000, 32.000, 32.000, 92.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891501800100.png', 'assets/img/tools/maykesta'),
(189, 63, '0891502000100', NULL, 2, 20.000, 20.000, 38.000, 38.000, 104.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0891502000100.png', 'assets/img/tools/maykesta'),
(190, 64, '062500030100', NULL, 2, 3.000, 3.000, 25.000, 25.000, 60.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500030100.png', 'assets/img/tools/maykesta'),
(191, 64, '062500040100', NULL, 2, 4.000, 4.000, 25.000, 25.000, 60.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500040100.png', 'assets/img/tools/maykesta'),
(192, 64, '062500050100', NULL, 2, 5.000, 5.000, 25.000, 25.000, 60.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500050100.png', 'assets/img/tools/maykesta'),
(193, 64, '062500060100', NULL, 2, 6.000, 6.000, 25.000, 25.000, 60.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500060100.png', 'assets/img/tools/maykesta'),
(194, 64, '062500080100', NULL, 2, 8.000, 8.000, 35.000, 35.000, 80.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500080100.png', 'assets/img/tools/maykesta'),
(195, 64, '062500100100', NULL, 2, 10.000, 10.000, 40.000, 40.000, 100.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500100100.png', 'assets/img/tools/maykesta'),
(196, 64, '062500120100', NULL, 2, 12.000, 12.000, 45.000, 45.000, 125.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/062500120100.png', 'assets/img/tools/maykesta'),
(197, 65, '0624500400100', NULL, 2, 0.400, 3.000, 1.500, 1.500, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624500400100.png', 'assets/img/tools/maykesta'),
(198, 65, '0624500500100', NULL, 2, 0.500, 3.000, 2.000, 2.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624500500100.png', 'assets/img/tools/maykesta'),
(199, 65, '0624500800100', NULL, 2, 0.800, 3.000, 3.000, 3.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624500800100.png', 'assets/img/tools/maykesta'),
(200, 65, '0624500900100', NULL, 2, 0.900, 3.000, 3.000, 3.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624500900100.png', 'assets/img/tools/maykesta'),
(201, 65, '0624501000100', NULL, 2, 1.000, 3.000, 3.000, 3.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624501000100.png', 'assets/img/tools/maykesta'),
(202, 65, '0624501500100', NULL, 2, 1.500, 3.000, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624501500100.png', 'assets/img/tools/maykesta'),
(203, 65, '0624502000100', NULL, 2, 2.000, 3.000, 6.000, 6.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624502000100.png', 'assets/img/tools/maykesta'),
(204, 65, '0624502500100', NULL, 2, 2.500, 3.000, 7.000, 7.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624502500100.png', 'assets/img/tools/maykesta'),
(205, 65, '0624503000100', NULL, 2, 3.000, 3.000, 8.000, 8.000, 38.000, 0.00, 0.00, 0.00, 0.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0624503000100.png', 'assets/img/tools/maykesta'),
(206, 66, '0752500010100', NULL, 2, 1.000, 1.000, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500010100.png', 'assets/img/tools/maykesta'),
(207, 66, '0752500150100', NULL, 2, 1.500, 1.500, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.750, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500150100.png', 'assets/img/tools/maykesta'),
(208, 66, '0752500200100', NULL, 2, 2.000, 2.000, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500200100.png', 'assets/img/tools/maykesta'),
(209, 66, '0752500250100', NULL, 2, 2.500, 2.500, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 1.250, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500250100.png', 'assets/img/tools/maykesta'),
(210, 66, '0752500300100', NULL, 2, 3.000, 3.000, 12.000, 12.000, 40.000, 0.00, 0.00, 0.00, 1.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500300100.png', 'assets/img/tools/maykesta'),
(211, 66, '0752500350100', NULL, 2, 3.500, 3.500, 12.000, 12.000, 40.000, 0.00, 0.00, 0.00, 1.750, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500350100.png', 'assets/img/tools/maykesta'),
(212, 66, '0752500500100', NULL, 2, 5.000, 5.000, 14.000, 14.000, 50.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500500100.png', 'assets/img/tools/maykesta'),
(213, 66, '0752500800100', NULL, 2, 8.000, 8.000, 20.000, 20.000, 70.000, 0.00, 0.00, 0.00, 4.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752500800100.png', 'assets/img/tools/maykesta'),
(214, 66, '0752501000100', NULL, 2, 10.000, 10.000, 25.000, 25.000, 80.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752501000100.png', 'assets/img/tools/maykesta'),
(215, 66, '0752501200100', NULL, 2, 12.000, 12.000, 30.000, 30.000, 90.000, 0.00, 0.00, 0.00, 6.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752501200100.png', 'assets/img/tools/maykesta'),
(216, 66, '0752501600100', NULL, 2, 16.000, 16.000, 32.000, 32.000, 104.000, 0.00, 0.00, 0.00, 8.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752501600100.png', 'assets/img/tools/maykesta'),
(217, 66, '0752502000100', NULL, 2, 20.000, 20.000, 35.000, 35.000, 120.000, 0.00, 0.00, 0.00, 10.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0752502000100.png', 'assets/img/tools/maykesta'),
(218, 67, '0692500100100', NULL, 2, 1.000, 1.000, 5.000, 5.000, 38.000, 0.00, 0.00, 0.00, 0.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500100100.png', 'assets/img/tools/maykesta'),
(219, 67, '0692500200100', NULL, 2, 2.000, 2.000, 8.000, 8.000, 38.000, 0.00, 0.00, 0.00, 1.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500200100.png', 'assets/img/tools/maykesta'),
(220, 67, '0692500300100', NULL, 2, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 1.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500300100.png', 'assets/img/tools/maykesta'),
(221, 67, '0692500400100', NULL, 2, 4.000, 4.000, 14.000, 14.000, 40.000, 0.00, 0.00, 0.00, 2.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500400100.png', 'assets/img/tools/maykesta'),
(222, 67, '0692500500100', NULL, 2, 5.000, 5.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500500100.png', 'assets/img/tools/maykesta'),
(223, 67, '0692500800100', NULL, 2, 8.000, 8.000, 20.000, 20.000, 50.000, 0.00, 0.00, 0.00, 4.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692500800100.png', 'assets/img/tools/maykesta'),
(224, 67, '0692501000100', NULL, 2, 10.000, 10.000, 25.000, 25.000, 63.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692501000100.png', 'assets/img/tools/maykesta'),
(225, 67, '0692501200100', NULL, 2, 12.000, 12.000, 22.000, 22.000, 73.000, 0.00, 0.00, 0.00, 8.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0692501200100.png', 'assets/img/tools/maykesta'),
(226, 68, '0753500300100', NULL, 2, 3.000, 3.000, 60.000, 25.000, 25.000, 0.00, 0.00, 0.00, 1.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753500300100.png', 'assets/img/tools/maykesta'),
(227, 68, '0753500400100', NULL, 2, 4.000, 4.000, 60.000, 30.000, 30.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753500400100.png', 'assets/img/tools/maykesta'),
(228, 68, '0753500500100', NULL, 2, 5.000, 5.000, 70.000, 30.000, 30.000, 0.00, 0.00, 0.00, 2.500, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753500500100.png', 'assets/img/tools/maykesta'),
(229, 68, '0753500600100', NULL, 2, 6.000, 6.000, 100.000, 45.000, 45.000, 0.00, 0.00, 0.00, 4.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753500600100.png', 'assets/img/tools/maykesta'),
(230, 68, '0753500800100', NULL, 2, 8.000, 8.000, 100.000, 45.000, 45.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753500800100.png', 'assets/img/tools/maykesta'),
(231, 68, '0753501000100', NULL, 2, 10.000, 10.000, 100.000, 45.000, 45.000, 0.00, 0.00, 0.00, 5.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753501000100.png', 'assets/img/tools/maykesta'),
(232, 68, '0753501200100', NULL, 2, 12.000, 12.000, 100.000, 65.000, 65.000, 0.00, 0.00, 0.00, 8.000, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0753501200100.png', 'assets/img/tools/maykesta'),
(233, 69, '0772500400100', NULL, 4, 4.000, 4.000, NULL, NULL, 54.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772500400100.png', 'assets/img/tools/maykesta'),
(234, 69, '0772500600100', NULL, 4, 6.000, 6.000, NULL, NULL, 57.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772500600100.png', 'assets/img/tools/maykesta'),
(235, 69, '0772500800100', NULL, 4, 8.000, 8.000, NULL, NULL, 63.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772500800100.png', 'assets/img/tools/maykesta'),
(236, 69, '0772501000100', NULL, 4, 10.000, 10.000, NULL, NULL, 72.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772501000100.png', 'assets/img/tools/maykesta'),
(237, 69, '0772501200100', NULL, 4, 12.000, 12.000, NULL, NULL, 81.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772501200100.png', 'assets/img/tools/maykesta'),
(238, 69, '0772501600100', NULL, 4, 16.000, 16.000, NULL, NULL, 92.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0772501600100.png', 'assets/img/tools/maykesta'),
(239, 70, '0775500400100', NULL, 4, 4.000, 4.000, NULL, NULL, 54.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775500400100.png', 'assets/img/tools/maykesta'),
(240, 70, '0775500600100', NULL, 4, 6.000, 6.000, NULL, NULL, 57.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775500600100.png', 'assets/img/tools/maykesta'),
(241, 70, '0775500800100', NULL, 4, 8.000, 8.000, NULL, NULL, 63.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775500800100.png', 'assets/img/tools/maykesta'),
(242, 70, '0775501000100', NULL, 4, 10.000, 10.000, NULL, NULL, 72.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775501000100.png', 'assets/img/tools/maykesta'),
(243, 70, '0775501200100', NULL, 4, 12.000, 12.000, NULL, NULL, 83.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775501200100.png', 'assets/img/tools/maykesta'),
(244, 70, '0775501600100', NULL, 4, 16.000, 16.000, NULL, NULL, 92.000, 0.00, 0.00, 0.00, NULL, NULL, 'Austria', NULL, NULL, NULL, 'assets/img/tools/maykestag/0775501600100.png', 'assets/img/tools/maykesta'),
(259, 83, '0625700040100', NULL, 2, 0.400, 3.000, 0.350, 6.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(260, 83, '0625700050100', NULL, 2, 0.500, 3.000, 0.450, 7.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(261, 83, '0625700060100', NULL, 2, 0.600, 3.000, 0.550, 8.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(262, 83, '0625700070100', NULL, 2, 0.700, 3.000, 0.650, 9.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(263, 83, '0625700080100', NULL, 2, 0.800, 3.000, 0.750, 12.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(264, 83, '0625700090100', NULL, 2, 0.900, 3.000, 0.850, 12.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(265, 83, '0625700100100', NULL, 2, 1.000, 3.000, 0.950, 15.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(266, 83, '0625700120100', NULL, 2, 1.200, 3.000, 1.150, 15.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(267, 83, '0625700140100', NULL, 2, 1.400, 3.000, 1.350, 21.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(268, 83, '0625700150100', NULL, 2, 1.500, 3.000, 1.450, 23.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(269, 83, '0625700160100', NULL, 2, 1.600, 3.000, 1.550, 24.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(270, 83, '0625700180100', NULL, 2, 1.800, 3.000, 1.750, 27.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(271, 83, '0625700200100', NULL, 2, 2.000, 3.000, 1.950, 30.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(272, 83, '0625700250100', NULL, 2, 2.500, 3.000, 2.450, 37.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(273, 83, '0625700300100', NULL, 2, 3.000, 3.000, 2.950, 40.000, 50.000, 15.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'Austria', NULL, 'AluNite', NULL, NULL, NULL),
(274, 93, '66902001001', NULL, 1, 1.000, 3.000, 38.000, 38.000, 38.000, 90.00, 30.00, 0.00, 0.000, 'EM-HELICAL', 'Austria', NULL, 'AlTiN', NULL, NULL, NULL),
(275, 93, '66902002001', NULL, 1, 2.000, 3.000, 38.000, 38.000, 38.000, 90.00, 30.00, 0.00, 0.000, 'EM-HELICAL', 'Austria', NULL, 'AlTiN', NULL, NULL, NULL),
(276, 93, '66902003001', NULL, 1, 3.000, 3.000, 40.000, 40.000, 40.000, 90.00, 30.00, 0.00, 0.000, 'EM-HELICAL', 'Austria', NULL, 'AlTiN', NULL, NULL, NULL),
(277, 93, '66902004011', NULL, 1, 4.000, 4.000, 76.000, 76.000, 76.000, 90.00, 30.00, 0.00, 0.000, 'EM-HELICAL', 'Austria', NULL, 'AlTiN', NULL, NULL, NULL),
(278, 93, '66902006001', NULL, 1, 6.000, 4.000, 95.000, 95.000, 95.000, 90.00, 30.00, 0.00, 0.000, 'EM-HELICAL', 'Austria', NULL, 'AlTiN', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools_schneider`
--

CREATE TABLE `tools_schneider` (
  `tool_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `tool_code` varchar(50) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `flute_count` tinyint(4) DEFAULT NULL,
  `diameter_mm` decimal(7,3) DEFAULT NULL,
  `shank_diameter_mm` decimal(7,3) DEFAULT NULL,
  `flute_length_mm` decimal(7,3) DEFAULT NULL,
  `cut_length_mm` decimal(7,3) DEFAULT NULL,
  `full_length_mm` decimal(7,3) DEFAULT NULL,
  `rack_angle` decimal(6,2) DEFAULT NULL,
  `helix` decimal(6,2) DEFAULT NULL,
  `conical_angle` decimal(6,2) DEFAULT 0.00,
  `radius` decimal(7,3) DEFAULT 0.000,
  `tool_type` varchar(50) DEFAULT NULL,
  `made_in` varchar(30) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `coated` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_dimensions` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tools_schneider`
--

INSERT INTO `tools_schneider` (`tool_id`, `series_id`, `tool_code`, `name`, `flute_count`, `diameter_mm`, `shank_diameter_mm`, `flute_length_mm`, `cut_length_mm`, `full_length_mm`, `rack_angle`, `helix`, `conical_angle`, `radius`, `tool_type`, `made_in`, `material`, `coated`, `notes`, `image`, `image_dimensions`) VALUES
(1, 85, '786022', NULL, 2, 2.000, 6.000, NULL, NULL, 45.000, 60.00, 0.00, 0.00, 0.000, 'EM-CHAMFER', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(2, 85, '786032', NULL, 2, 3.000, 6.000, NULL, NULL, 45.000, 60.00, 0.00, 0.00, 0.000, 'EM-CHAMFER', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(3, 86, '789022', NULL, 2, 2.000, 6.000, NULL, NULL, 45.000, 90.00, 0.00, 0.00, 0.000, 'EM-CHAMFER', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(4, 86, '789032', NULL, 2, 3.000, 6.000, NULL, NULL, 45.000, 90.00, 0.00, 0.00, 0.000, 'EM-CHAMFER', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(6, 88, '8000126300', NULL, 2, 12.000, 6.000, NULL, NULL, 63.000, 90.00, 0.00, 0.00, 0.250, 'EM-FILLET', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(7, 89, 'GA31500', NULL, 1, 3.000, 3.000, NULL, NULL, 36.000, 15.00, 0.00, 0.00, 0.000, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(8, 89, 'GA32500', NULL, 1, 3.000, 3.000, NULL, NULL, 36.000, 25.00, 0.00, 0.00, 0.000, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(9, 89, 'GA34500', NULL, 1, 3.000, 3.000, NULL, NULL, 36.000, 45.00, 0.00, 0.00, 0.000, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(10, 90, 'GA31500', NULL, 1, 3.000, 3.000, NULL, NULL, 36.000, 15.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(11, 90, 'GA32500', NULL, 1, 3.000, 3.000, NULL, NULL, 36.000, 25.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(12, 90, 'GA40500', NULL, 1, 3.000, 3.000, NULL, NULL, 40.000, 15.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(13, 90, 'GA42500', NULL, 1, 4.000, 4.000, NULL, NULL, 42.500, 25.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(14, 90, 'GA45000', NULL, 1, 4.000, 4.000, NULL, NULL, 45.000, 45.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(15, 90, 'GA61500', NULL, 1, 6.000, 6.000, NULL, NULL, 61.500, 15.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(16, 90, 'GA62500', NULL, 1, 6.000, 6.000, NULL, NULL, 62.500, 25.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(17, 90, 'GA64500', NULL, 1, 6.000, 6.000, NULL, NULL, 64.500, 45.00, 0.00, 0.00, 1.500, 'EM-VBIT', 'Argentina', NULL, 'AlTiN', NULL, NULL, NULL),
(18, 91, '665006', NULL, 1, 6.000, 6.000, 28.000, 28.000, 46.000, 0.00, 0.00, 0.00, 0.000, 'EM-COMPRESSION', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(19, 91, '665008', NULL, 1, 8.000, 6.000, 28.000, 28.000, 52.000, 0.00, 0.00, 0.00, 0.000, 'EM-COMPRESSION', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(20, 92, '666004', NULL, 2, 4.000, 4.000, 18.000, 18.000, 46.000, 0.00, 0.00, 0.00, 0.000, 'EM-COMPRESSION', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(21, 92, '666006', NULL, 2, 6.000, 6.000, 18.000, 18.000, 46.000, 0.00, 0.00, 0.00, 0.000, 'EM-COMPRESSION', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(22, 92, '666008', NULL, 2, 8.000, 6.000, 28.000, 28.000, 52.000, 0.00, 0.00, 0.00, 0.000, 'EM-COMPRESSION', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(23, 87, '66800332', NULL, 2, 3.000, 3.000, 50.000, 50.000, 80.000, 90.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(24, 87, '66800340', NULL, 2, 3.000, 4.000, 40.000, 40.000, 70.000, 90.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(25, 87, '66800355', NULL, 2, 4.000, 4.000, 55.000, 55.000, 80.000, 90.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(26, 87, '66800370', NULL, 2, 6.000, 6.000, 75.000, 75.000, 100.000, 90.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'Argentina', NULL, NULL, NULL, NULL, NULL),
(27, 87, '66800310', NULL, 2, 6.000, 6.000, 100.000, 100.000, 120.000, 90.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'Argentina', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tools_sgs`
--

CREATE TABLE `tools_sgs` (
  `tool_id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `tool_code` varchar(50) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `flute_count` tinyint(4) DEFAULT NULL,
  `diameter_mm` decimal(7,3) DEFAULT NULL,
  `shank_diameter_mm` decimal(7,3) DEFAULT NULL,
  `flute_length_mm` decimal(7,3) DEFAULT NULL,
  `cut_length_mm` decimal(7,3) DEFAULT NULL,
  `full_length_mm` decimal(7,3) DEFAULT NULL,
  `rack_angle` decimal(6,2) DEFAULT NULL,
  `helix` decimal(6,2) DEFAULT NULL,
  `conical_angle` decimal(6,2) DEFAULT 0.00,
  `radius` decimal(7,3) DEFAULT 0.000,
  `tool_type` varchar(50) DEFAULT NULL,
  `made_in` varchar(30) DEFAULT NULL,
  `material` varchar(50) DEFAULT NULL,
  `coated` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_dimensions` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tools_sgs`
--

INSERT INTO `tools_sgs` (`tool_id`, `series_id`, `tool_code`, `name`, `flute_count`, `diameter_mm`, `shank_diameter_mm`, `flute_length_mm`, `cut_length_mm`, `full_length_mm`, `rack_angle`, `helix`, `conical_angle`, `radius`, `tool_type`, `made_in`, `material`, `coated`, `notes`, `image`, `image_dimensions`) VALUES
(29, 5, '91001', 'Wood Router – Down Cut', 2, 3.175, 6.350, 0.000, 12.700, 50.800, 30.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(30, 5, '91005', 'Wood Router – Down Cut', 2, 3.969, 6.350, 0.000, 15.875, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(31, 5, '91009', 'Wood Router – Down Cut', 2, 4.763, 6.350, 0.000, 19.050, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(32, 5, '91013', 'Wood Router – Down Cut', 2, 6.350, 6.350, 0.000, 19.050, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(33, 5, '91017', 'Wood Router – Down Cut', 2, 6.350, 6.350, 0.000, 25.400, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(34, 5, '91021', 'Wood Router – Down Cut', 2, 7.938, 7.938, 0.000, 25.400, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(35, 5, '91025', 'Wood Router – Down Cut', 2, 7.938, 12.700, 0.000, 25.400, 76.200, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(36, 5, '91029', 'Wood Router – Down Cut', 2, 9.525, 9.525, 0.000, 25.400, 63.500, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(37, 5, '91033', 'Wood Router – Down Cut', 2, 9.525, 12.700, 0.000, 31.750, 76.200, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(38, 5, '91037', 'Wood Router – Down Cut', 2, 12.700, 12.700, 0.000, 31.750, 76.200, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(39, 5, '91041', 'Wood Router – Down Cut', 2, 12.700, 12.700, 0.000, 38.100, 88.900, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(40, 5, '91045', 'Wood Router – Down Cut', 2, 12.700, 12.700, 0.000, 50.800, 101.600, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(41, 5, '91049', 'Wood Router – Down Cut', 2, 15.875, 15.875, 0.000, 50.800, 114.300, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(42, 5, '91053', 'Wood Router – Down Cut', 2, 19.050, 19.050, 0.000, 50.800, 114.300, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
(50, 71, '32301', NULL, 3, 3.175, 6.350, 38.100, 38.100, 76.200, 1.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(51, 71, '32303', NULL, 3, 3.175, 6.350, 31.750, 31.750, 76.200, 1.50, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(52, 71, '32305', NULL, 3, 3.175, 6.350, 25.400, 25.400, 76.200, 2.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(53, 71, '32307', NULL, 3, 3.175, 6.350, 19.050, 19.050, 76.200, 3.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(54, 71, '32309', NULL, 3, 3.175, 6.350, 12.700, 12.700, 76.200, 5.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(55, 71, '32311', NULL, 3, 3.175, 6.350, 12.700, 12.700, 76.200, 7.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(56, 71, '32313', NULL, 3, 2.381, 6.350, 19.050, 19.050, 76.200, 10.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(57, 71, '32315', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 1.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(58, 71, '32317', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 1.50, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(59, 71, '32319', NULL, 3, 3.969, 9.525, 44.450, 44.450, 88.900, 2.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(60, 71, '32321', NULL, 3, 3.175, 9.525, 38.100, 38.100, 88.900, 3.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(61, 71, '32323', NULL, 3, 3.175, 9.525, 38.100, 38.100, 88.900, 5.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(62, 71, '32325', NULL, 3, 3.175, 9.525, 25.400, 25.400, 88.900, 7.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(63, 71, '32327', NULL, 3, 2.381, 9.525, 19.050, 19.050, 88.900, 10.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(64, 71, '32329', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 1.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(65, 71, '32331', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 2.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(66, 71, '32333', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 3.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(67, 71, '32335', NULL, 3, 7.938, 12.700, 31.750, 31.750, 101.600, 5.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(68, 71, '32337', NULL, 3, 4.763, 12.700, 31.750, 31.750, 101.600, 7.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(69, 71, '32339', NULL, 3, 3.175, 12.700, 25.400, 25.400, 101.600, 10.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(70, 71, '32341', NULL, 3, 3.175, 12.700, 25.400, 25.400, 101.600, 10.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, '', NULL, NULL, NULL),
(71, 72, '32402', NULL, 3, 3.175, 6.350, 38.100, 38.100, 76.200, 1.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32402.png', 'assets/img/tools/SGS_24_32402_vector.svg'),
(72, 72, '32404', NULL, 3, 3.175, 6.350, 38.100, 38.100, 76.200, 1.50, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32404.png', 'assets/img/tools/SGS_24_32404_vector.svg'),
(73, 72, '32406', NULL, 3, 3.175, 6.350, 31.750, 31.750, 76.200, 2.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32406.png', 'assets/img/tools/SGS_24_32406_vector.svg'),
(74, 72, '32408', NULL, 3, 3.175, 6.350, 25.400, 25.400, 76.200, 3.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32408.png', 'assets/img/tools/SGS_24_32408_vector.svg'),
(75, 72, '32410', NULL, 3, 3.175, 6.350, 19.050, 19.050, 76.200, 5.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32410.png', 'assets/img/tools/SGS_24_32410_vector.svg'),
(76, 72, '32412', NULL, 3, 3.175, 6.350, 12.700, 12.700, 76.200, 7.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32412.png', 'assets/img/tools/SGS_24_32412_vector.svg'),
(77, 72, '32414', NULL, 3, 3.175, 6.350, 12.700, 12.700, 76.200, 10.00, 0.00, 0.00, 0.047, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32414.png', 'assets/img/tools/SGS_24_32414_vector.svg'),
(78, 72, '32416', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 1.00, 0.00, 0.00, 0.093, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32416.png', 'assets/img/tools/SGS_24_32416_vector.svg'),
(79, 72, '32418', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 1.50, 0.00, 0.00, 0.093, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32418.png', 'assets/img/tools/SGS_24_32418_vector.svg'),
(80, 72, '32420', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 2.00, 0.00, 0.00, 0.093, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32420.png', 'assets/img/tools/SGS_24_32420_vector.svg'),
(81, 72, '32422', NULL, 3, 4.763, 9.525, 44.450, 44.450, 88.900, 3.00, 0.00, 0.00, 0.078, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32422.png', 'assets/img/tools/SGS_24_32422_vector.svg'),
(82, 72, '32424', NULL, 3, 3.175, 9.525, 38.100, 38.100, 88.900, 5.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32424.png', 'assets/img/tools/SGS_24_32424_vector.svg'),
(83, 72, '32426', NULL, 3, 3.175, 9.525, 25.400, 25.400, 88.900, 7.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32426.png', 'assets/img/tools/SGS_24_32426_vector.svg'),
(84, 72, '32428', NULL, 3, 2.381, 9.525, 19.050, 19.050, 88.900, 10.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32428.png', 'assets/img/tools/SGS_24_32428_vector.svg'),
(85, 72, '32430', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 1.00, 0.00, 0.00, 0.125, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32430.png', 'assets/img/tools/SGS_24_32430_vector.svg'),
(86, 72, '32432', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 2.00, 0.00, 0.00, 0.125, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32432.png', 'assets/img/tools/SGS_24_32432_vector.svg'),
(87, 72, '32434', NULL, 3, 6.350, 12.700, 50.800, 50.800, 101.600, 3.00, 0.00, 0.00, 0.125, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32434.png', 'assets/img/tools/SGS_24_32434_vector.svg'),
(88, 72, '32436', NULL, 3, 7.938, 12.700, 31.750, 31.750, 101.600, 5.00, 0.00, 0.00, 0.125, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32436.png', 'assets/img/tools/SGS_24_32436_vector.svg'),
(89, 72, '32438', NULL, 3, 4.763, 12.700, 31.750, 31.750, 101.600, 7.00, 0.00, 0.00, 0.093, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32438.png', 'assets/img/tools/SGS_24_32438_vector.svg'),
(90, 72, '32440', NULL, 3, 3.175, 12.700, 25.400, 25.400, 101.600, 10.00, 0.00, 0.00, 0.062, NULL, 'USA', NULL, NULL, NULL, 'assets/img/tools/SGS_24_32440.png', 'assets/img/tools/SGS_24_32440_vector.svg'),
(91, 73, '49178', NULL, 4, 3.000, 3.000, 38.000, 38.000, 50.000, 1.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(92, 73, '49179', NULL, 4, 4.000, 4.000, 38.000, 38.000, 50.000, 1.50, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(93, 73, '49180', NULL, 4, 5.000, 5.000, 38.000, 38.000, 50.000, 2.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(94, 73, '49181', NULL, 4, 6.000, 6.000, 38.000, 38.000, 50.000, 3.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(95, 73, '49182', NULL, 4, 8.000, 8.000, 50.000, 50.000, 75.000, 5.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(96, 73, '49183', NULL, 4, 10.000, 10.000, 50.000, 50.000, 75.000, 7.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(97, 73, '49184', NULL, 4, 12.000, 12.000, 50.000, 50.000, 75.000, 10.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(98, 74, '49412', NULL, 4, 4.000, 4.000, 50.000, 50.000, 75.000, 0.25, 0.00, 0.00, 0.250, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(99, 74, '49389', NULL, 4, 6.000, 6.000, 50.000, 50.000, 75.000, 0.25, 0.00, 0.00, 0.250, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(100, 74, '49391', NULL, 4, 8.000, 8.000, 63.000, 63.000, 100.000, 0.25, 0.00, 0.00, 0.250, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(101, 74, '49404', NULL, 4, 10.000, 10.000, 75.000, 75.000, 150.000, 0.25, 0.00, 0.00, 0.250, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(102, 75, '49414', NULL, 4, 4.000, 4.000, 50.000, 50.000, 75.000, 0.50, 0.00, 0.00, 0.500, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(103, 75, '49412', NULL, 4, 6.000, 6.000, 63.000, 63.000, 100.000, 0.50, 0.00, 0.00, 0.500, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(104, 75, '49417', NULL, 4, 8.000, 8.000, 63.000, 63.000, 100.000, 0.50, 0.00, 0.00, 0.500, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(105, 75, '49414', NULL, 4, 10.000, 10.000, 75.000, 75.000, 150.000, 0.50, 0.00, 0.00, 0.500, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(106, 76, '49414', NULL, 4, 4.000, 4.000, 50.000, 50.000, 75.000, 1.00, 0.00, 0.00, 1.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(107, 76, '49415', NULL, 4, 6.000, 6.000, 63.000, 63.000, 100.000, 1.00, 0.00, 0.00, 1.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(108, 76, '49412', NULL, 4, 8.000, 8.000, 63.000, 63.000, 100.000, 1.00, 0.00, 0.00, 1.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(109, 76, '49414', NULL, 4, 10.000, 10.000, 75.000, 75.000, 150.000, 1.00, 0.00, 0.00, 1.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(110, 77, '48607', NULL, 4, 1.000, 3.000, 4.000, 4.000, 38.000, 0.00, 0.00, 0.00, 0.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(111, 77, '48608', NULL, 4, 1.500, 3.000, 4.500, 4.500, 38.000, 0.00, 0.00, 0.00, 0.750, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(112, 77, '48609', NULL, 4, 2.000, 3.000, 6.300, 6.300, 38.000, 0.00, 0.00, 0.00, 1.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(113, 77, '48610', NULL, 4, 2.500, 3.000, 9.500, 9.500, 38.000, 0.00, 0.00, 0.00, 1.250, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(114, 77, '48611', NULL, 4, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 1.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(115, 77, '48612', NULL, 4, 3.500, 4.000, 12.000, 12.000, 50.000, 0.00, 0.00, 0.00, 1.750, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(116, 77, '48613', NULL, 4, 4.000, 4.000, 14.000, 14.000, 50.000, 0.00, 0.00, 0.00, 2.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(117, 77, '48614', NULL, 4, 4.500, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 2.250, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(118, 77, '48615', NULL, 4, 5.000, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 2.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(119, 77, '48616', NULL, 4, 6.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 3.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(120, 77, '48617', NULL, 4, 7.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 3.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(121, 77, '48618', NULL, 4, 8.000, 8.000, 20.000, 20.000, 63.000, 0.00, 0.00, 0.00, 4.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(122, 77, '48619', NULL, 4, 9.000, 10.000, 22.000, 22.000, 75.000, 0.00, 0.00, 0.00, 4.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(123, 77, '48620', NULL, 4, 10.000, 10.000, 22.000, 22.000, 75.000, 0.00, 0.00, 0.00, 5.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(124, 77, '48621', NULL, 4, 11.000, 12.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 5.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(125, 77, '48622', NULL, 4, 12.000, 12.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(126, 77, '48623', NULL, 4, 14.000, 14.000, 32.000, 32.000, 89.000, 0.00, 0.00, 0.00, 7.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(127, 77, '48624', NULL, 4, 16.000, 16.000, 32.000, 32.000, 89.000, 0.00, 0.00, 0.00, 8.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(128, 77, '48625', NULL, 4, 18.000, 18.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 9.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(129, 77, '48626', NULL, 4, 20.000, 20.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 10.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(130, 77, '48627', NULL, 4, 25.000, 25.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 12.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(131, 78, '49531', NULL, 4, 3.000, 3.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 1.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(132, 78, '49532', NULL, 4, 4.000, 4.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 2.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(133, 78, '49534', NULL, 4, 5.000, 5.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 2.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(134, 78, '49533', NULL, 4, 6.000, 6.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 3.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(135, 78, '49535', NULL, 4, 8.000, 8.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 4.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(136, 78, '49536', NULL, 4, 10.000, 10.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 5.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(137, 78, '49537', NULL, 4, 12.000, 12.000, 50.000, 50.000, 100.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(138, 78, '49538', NULL, 4, 12.000, 12.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(139, 78, '49539', NULL, 4, 14.000, 14.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 7.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(140, 78, '49540', NULL, 4, 16.000, 16.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 8.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(141, 78, '49541', NULL, 4, 18.000, 18.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 9.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(142, 78, '49542', NULL, 4, 20.000, 20.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 10.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(143, 78, '49543', NULL, 4, 25.000, 25.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 12.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(144, 79, '48671', NULL, 2, 1.000, 3.000, 4.000, 4.000, 38.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(145, 79, '48672', NULL, 2, 1.500, 3.000, 4.500, 4.500, 38.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(146, 79, '48673', NULL, 2, 2.000, 3.000, 6.300, 6.300, 38.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(147, 79, '48674', NULL, 2, 2.500, 3.000, 9.500, 9.500, 38.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(148, 79, '48675', NULL, 2, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(149, 79, '48677', NULL, 2, 4.000, 4.000, 10.000, 10.000, 50.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(150, 79, '48678', NULL, 2, 4.500, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(151, 79, '48679', NULL, 2, 5.000, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(152, 79, '48680', NULL, 2, 6.000, 6.000, 19.000, 19.000, 50.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(153, 79, '48681', NULL, 2, 7.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(154, 79, '48682', NULL, 2, 8.000, 8.000, 20.000, 20.000, 63.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(155, 79, '48683', NULL, 2, 10.000, 10.000, 22.000, 22.000, 75.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(156, 79, '48684', NULL, 2, 10.000, 10.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(157, 79, '48685', NULL, 2, 11.000, 12.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(158, 79, '48686', NULL, 2, 12.000, 12.000, 50.000, 50.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(159, 79, '48687', NULL, 2, 14.000, 14.000, 32.000, 32.000, 89.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(160, 79, '48688', NULL, 2, 16.000, 16.000, 32.000, 32.000, 89.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(161, 79, '48689', NULL, 2, 18.000, 18.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(162, 79, '48690', NULL, 2, 20.000, 20.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(163, 79, '48691', NULL, 2, 25.000, 25.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(164, 80, '49453', NULL, 2, 3.500, 4.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(165, 80, '49455', NULL, 2, 5.000, 6.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(166, 80, '49473', NULL, 2, 8.000, 8.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(167, 80, '49486', NULL, 2, 10.000, 10.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(168, 80, '49488', NULL, 2, 12.000, 12.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(169, 80, '49490', NULL, 2, 14.000, 14.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(170, 80, '49492', NULL, 2, 16.000, 16.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(171, 80, '49494', NULL, 2, 18.000, 18.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(172, 80, '49495', NULL, 2, 20.000, 20.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(173, 80, '49497', NULL, 2, 25.000, 25.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 0.000, 'EM-SQUARE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(174, 81, '48735', NULL, 2, 1.000, 3.000, 4.000, 4.000, 38.000, 0.00, 0.00, 0.00, 0.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(175, 81, '48736', NULL, 2, 1.500, 3.000, 4.500, 4.500, 38.000, 0.00, 0.00, 0.00, 0.750, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(176, 81, '48737', NULL, 2, 2.000, 3.000, 6.300, 6.300, 38.000, 0.00, 0.00, 0.00, 1.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(177, 81, '48738', NULL, 2, 2.500, 3.000, 9.500, 9.500, 38.000, 0.00, 0.00, 0.00, 1.250, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(178, 81, '48739', NULL, 2, 3.000, 3.000, 12.000, 12.000, 38.000, 0.00, 0.00, 0.00, 1.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(179, 81, '48740', NULL, 2, 3.500, 4.000, 12.000, 12.000, 50.000, 0.00, 0.00, 0.00, 1.750, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(180, 81, '48741', NULL, 2, 4.000, 4.000, 14.000, 14.000, 50.000, 0.00, 0.00, 0.00, 2.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(181, 81, '48742', NULL, 2, 4.500, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 2.250, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(182, 81, '48743', NULL, 2, 5.000, 6.000, 16.000, 16.000, 50.000, 0.00, 0.00, 0.00, 2.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(183, 81, '48744', NULL, 2, 6.000, 6.000, 19.000, 19.000, 50.000, 0.00, 0.00, 0.00, 3.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(184, 81, '48745', NULL, 2, 7.000, 8.000, 19.000, 19.000, 63.000, 0.00, 0.00, 0.00, 3.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(185, 81, '48746', NULL, 2, 8.000, 8.000, 20.000, 20.000, 63.000, 0.00, 0.00, 0.00, 4.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(186, 81, '48747', NULL, 2, 9.000, 10.000, 22.000, 22.000, 75.000, 0.00, 0.00, 0.00, 4.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(187, 81, '48748', NULL, 2, 10.000, 10.000, 22.000, 22.000, 75.000, 0.00, 0.00, 0.00, 5.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(188, 81, '48749', NULL, 2, 11.000, 12.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 5.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(189, 81, '48750', NULL, 2, 12.000, 12.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(190, 82, '49570', NULL, 2, 3.000, 3.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 1.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(191, 82, '49571', NULL, 2, 4.000, 4.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 2.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(192, 82, '49573', NULL, 2, 5.000, 5.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 2.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(193, 82, '49572', NULL, 2, 6.000, 6.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 3.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(194, 82, '49574', NULL, 2, 8.000, 8.000, 25.000, 25.000, 75.000, 0.00, 0.00, 0.00, 4.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(195, 82, '49575', NULL, 2, 10.000, 10.000, 38.000, 38.000, 100.000, 0.00, 0.00, 0.00, 5.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(196, 82, '49576', NULL, 2, 12.000, 12.000, 50.000, 50.000, 100.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(197, 82, '49577', NULL, 2, 12.000, 12.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 6.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(198, 82, '49578', NULL, 2, 14.000, 14.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 7.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(199, 82, '49579', NULL, 2, 16.000, 16.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 8.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(200, 82, '49580', NULL, 2, 18.000, 18.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 9.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(201, 82, '49581', NULL, 2, 20.000, 20.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 10.000, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(202, 82, '49582', NULL, 2, 25.000, 25.000, 75.000, 75.000, 150.000, 0.00, 0.00, 0.00, 12.500, 'EM-BALLNOSE', 'USA', NULL, 'AlTiN', NULL, NULL, NULL),
(203, 95, '31958', NULL, 2, 3.175, 25.400, 4.763, NULL, 76.200, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(204, 95, '31959', NULL, 2, 4.763, 28.575, 6.350, NULL, 76.200, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(205, 95, '31960', NULL, 2, 6.350, 38.100, 6.350, NULL, 101.600, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(206, 95, '31961', NULL, 2, 7.938, 41.275, 7.938, NULL, 101.600, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(207, 95, '31962', NULL, 2, 9.525, 44.450, 9.525, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(208, 95, '31963', NULL, 2, 11.113, 76.200, 11.113, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(209, 95, '31964', NULL, 2, 12.700, 76.200, 12.700, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(210, 95, '31965', NULL, 2, 15.875, 76.200, 15.875, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(211, 95, '31966', NULL, 2, 19.050, 76.200, 19.050, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(212, 95, '31967', NULL, 2, 25.400, 76.200, 25.400, NULL, 152.400, 90.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(213, 96, '31890', NULL, 2, 3.175, 4.763, 57.150, NULL, 19.050, 90.00, 30.00, 0.00, 1.588, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(214, 96, '31891', NULL, 2, 4.763, 4.763, 63.500, NULL, 19.050, 90.00, 30.00, 0.00, 2.381, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(215, 96, '31892', NULL, 2, 6.350, 6.350, 76.200, NULL, 28.575, 90.00, 30.00, 0.00, 3.175, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(216, 96, '31893', NULL, 2, 7.938, 7.938, 76.200, NULL, 28.575, 90.00, 30.00, 0.00, 3.969, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(217, 96, '31894', NULL, 2, 9.525, 9.525, 76.200, NULL, 28.575, 90.00, 30.00, 0.00, 4.763, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(218, 96, '31895', NULL, 2, 11.113, 11.113, 101.600, NULL, 50.800, 90.00, 30.00, 0.00, 5.556, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(219, 96, '31896', NULL, 2, 12.700, 12.700, 114.300, NULL, 114.300, 90.00, 30.00, 0.00, 6.350, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(220, 96, '31897', NULL, 2, 15.875, 15.875, 127.000, NULL, 57.150, 90.00, 30.00, 0.00, 7.938, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(221, 96, '31898', NULL, 2, 19.050, 19.050, 127.000, NULL, 57.150, 90.00, 30.00, 0.00, 9.525, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(222, 96, '31899', NULL, 2, 25.400, 25.400, 127.000, NULL, 57.150, 90.00, 30.00, 0.00, 12.700, 'EM-BALLNOSE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(234, 1, '90001', NULL, 2, 3.175, 6.350, 12.700, 12.700, 50.800, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(235, 1, '90005', NULL, 2, 3.969, 6.350, 15.875, 15.875, 63.500, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(236, 1, '90009', NULL, 2, 4.763, 6.350, 19.050, 19.050, 63.500, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(237, 1, '90013', NULL, 2, 6.350, 6.350, 19.050, 19.050, 63.500, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(238, 1, '90017', NULL, 2, 6.350, 6.350, 25.400, 25.400, 63.500, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(239, 1, '90021', NULL, 2, 7.938, 7.938, 25.400, 25.400, 63.500, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(240, 1, '90025', NULL, 2, 9.525, 9.525, 31.750, 31.750, 76.200, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(241, 1, '90033', NULL, 2, 12.700, 12.700, 38.100, 38.100, 88.900, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(242, 1, '90041', NULL, 2, 12.700, 12.700, 50.800, 50.800, 101.600, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(243, 1, '90045', NULL, 2, 15.875, 15.875, 50.800, 50.800, 114.300, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(244, 1, '90053', NULL, 2, 19.050, 19.050, 50.800, 50.800, 114.300, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(245, 3, '90101', NULL, 2, 3.000, 6.000, 13.000, 13.000, 50.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(246, 3, '90107', NULL, 2, 4.000, 6.000, 16.000, 16.000, 63.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(247, 3, '90109', NULL, 2, 5.000, 6.000, 19.000, 19.000, 63.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(248, 3, '90113', NULL, 2, 6.000, 6.000, 25.000, 25.000, 63.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(249, 3, '90121', NULL, 2, 8.000, 8.000, 25.000, 25.000, 63.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(250, 3, '90129', NULL, 2, 10.000, 10.000, 31.000, 31.000, 75.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(251, 3, '90137', NULL, 2, 12.000, 12.000, 31.000, 31.000, 75.000, 90.00, 0.00, 0.00, 0.000, 'EM-WOOD', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(252, 2, '91001', NULL, 2, 3.175, 6.350, 12.700, NULL, 50.800, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(253, 2, '91005', NULL, 2, 3.969, 6.350, 15.875, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(254, 2, '91009', NULL, 2, 4.763, 6.350, 19.050, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(255, 2, '91013', NULL, 2, 6.350, 6.350, 19.050, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(256, 2, '91017', NULL, 2, 6.350, 6.350, 25.400, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(257, 2, '91021', NULL, 2, 7.938, 7.938, 25.400, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(258, 2, '91025', NULL, 2, 7.938, 12.700, 25.400, NULL, 76.200, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(259, 2, '91029', NULL, 2, 9.525, 9.525, 25.400, NULL, 63.500, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(260, 2, '91033', NULL, 2, 9.525, 12.700, 31.750, NULL, 76.200, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(261, 2, '91037', NULL, 2, 12.700, 12.700, 31.750, NULL, 76.200, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(262, 2, '91041', NULL, 2, 12.700, 12.700, 38.100, NULL, 88.900, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(263, 2, '91045', NULL, 2, 12.700, 12.700, 50.800, NULL, 101.600, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(264, 2, '91049', NULL, 2, 15.875, 15.875, 50.800, NULL, 114.300, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(265, 2, '91053', NULL, 2, 19.050, 19.050, 50.800, NULL, 114.300, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(266, 4, '91101', NULL, 2, 3.000, 6.000, 13.000, NULL, 50.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(267, 4, '91107', NULL, 2, 4.000, 6.000, 16.000, NULL, 63.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(268, 4, '91109', NULL, 2, 5.000, 6.000, 19.000, NULL, 63.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(269, 4, '91113', NULL, 2, 6.000, 6.000, 25.000, NULL, 63.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(270, 4, '91121', NULL, 2, 8.000, 8.000, 25.000, NULL, 63.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(271, 4, '91129', NULL, 2, 10.000, 10.000, 31.000, NULL, 75.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL),
(272, 4, '91137', NULL, 2, 12.000, 12.000, 31.000, NULL, 75.000, 90.00, 35.00, 0.00, 0.000, 'EM-SQUARE', 'U.S.A', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tooltypes`
--

CREATE TABLE `tooltypes` (
  `type_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tooltypes`
--

INSERT INTO `tooltypes` (`type_id`, `code`, `name`, `description`, `icon`) VALUES
(1, 'EM-BALLNOSE', 'Fresa Punta Esferica', '', NULL),
(2, 'EM-SQUARE', 'Fresa Punta Recta', '', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transmissions`
--

CREATE TABLE `transmissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `coef_security` float NOT NULL DEFAULT 1,
  `rpm_min` int(11) NOT NULL DEFAULT 3000,
  `rpm_max` int(11) NOT NULL DEFAULT 18000,
  `feed_max` int(11) NOT NULL DEFAULT 5000,
  `hp_default` int(3) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transmissions`
--

INSERT INTO `transmissions` (`id`, `name`, `coef_security`, `rpm_min`, `rpm_max`, `feed_max`, `hp_default`, `image`) VALUES
(2, 'Bolas recirculantes', 1, 6000, 24000, 7500, 3, 'assets/img/transmissions/bolas_recirculantes.png'),
(3, 'Cremallera helicoidal', 0.99999, 6000, 24000, 7500, 3, 'assets/img/transmissions/cremallera_helicoidal.png'),
(4, 'Cremallera recta', 0.9, 6000, 24000, 5000, 3, 'assets/img/transmissions/cremallera_recta.png'),
(5, 'Correas', 0.8, 6000, 24000, 3500, 2, 'assets/img/transmissions/correas.png'),
(6, 'Cadena', 0.6, 6000, 24000, 2000, 2, 'assets/img/transmissions/cadena.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`) VALUES
(1, 'admin', 'admin');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `machining_types`
--
ALTER TABLE `machining_types`
  ADD PRIMARY KEY (`machining_type_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `materialcategories`
--
ALTER TABLE `materialcategories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `fk_cat_parent` (`parent_id`);

--
-- Indices de la tabla `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `fk_mat_cat` (`category_id`);

--
-- Indices de la tabla `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_id` (`brand_id`,`code`);

--
-- Indices de la tabla `strategies`
--
ALTER TABLE `strategies`
  ADD PRIMARY KEY (`strategy_id`),
  ADD KEY `fk_strat_parent` (`parent_id`),
  ADD KEY `fk_strategies_machining_type` (`machining_type_id`);

--
-- Indices de la tabla `toolsmaterial_generico`
--
ALTER TABLE `toolsmaterial_generico`
  ADD PRIMARY KEY (`tool_material_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `toolsmaterial_maykestag`
--
ALTER TABLE `toolsmaterial_maykestag`
  ADD PRIMARY KEY (`tool_material_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `toolsmaterial_schneider`
--
ALTER TABLE `toolsmaterial_schneider`
  ADD PRIMARY KEY (`tool_material_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `toolsmaterial_sgs`
--
ALTER TABLE `toolsmaterial_sgs`
  ADD PRIMARY KEY (`tool_material_id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indices de la tabla `toolstrategy`
--
ALTER TABLE `toolstrategy`
  ADD PRIMARY KEY (`tool_strategy_id`),
  ADD KEY `idx_ts` (`tool_table`,`tool_id`),
  ADD KEY `fk_ts_strategy` (`strategy_id`);

--
-- Indices de la tabla `tools_generico`
--
ALTER TABLE `tools_generico`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `tool_type` (`tool_type`),
  ADD KEY `fk_tool_series` (`series_id`);

--
-- Indices de la tabla `tools_maykestag`
--
ALTER TABLE `tools_maykestag`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `tool_type` (`tool_type`),
  ADD KEY `fk_tool_series` (`series_id`);

--
-- Indices de la tabla `tools_schneider`
--
ALTER TABLE `tools_schneider`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `tool_type` (`tool_type`),
  ADD KEY `fk_tool_series` (`series_id`);

--
-- Indices de la tabla `tools_sgs`
--
ALTER TABLE `tools_sgs`
  ADD PRIMARY KEY (`tool_id`),
  ADD KEY `tool_type` (`tool_type`),
  ADD KEY `fk_tool_series` (`series_id`);

--
-- Indices de la tabla `tooltypes`
--
ALTER TABLE `tooltypes`
  ADD PRIMARY KEY (`type_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `transmissions`
--
ALTER TABLE `transmissions`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `machining_types`
--
ALTER TABLE `machining_types`
  MODIFY `machining_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materialcategories`
--
ALTER TABLE `materialcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de la tabla `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de la tabla `strategies`
--
ALTER TABLE `strategies`
  MODIFY `strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `toolsmaterial_generico`
--
ALTER TABLE `toolsmaterial_generico`
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `toolsmaterial_maykestag`
--
ALTER TABLE `toolsmaterial_maykestag`
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `toolsmaterial_schneider`
--
ALTER TABLE `toolsmaterial_schneider`
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `toolsmaterial_sgs`
--
ALTER TABLE `toolsmaterial_sgs`
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT de la tabla `toolstrategy`
--
ALTER TABLE `toolstrategy`
  MODIFY `tool_strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=187;

--
-- AUTO_INCREMENT de la tabla `tools_generico`
--
ALTER TABLE `tools_generico`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tools_maykestag`
--
ALTER TABLE `tools_maykestag`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- AUTO_INCREMENT de la tabla `tools_schneider`
--
ALTER TABLE `tools_schneider`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `tools_sgs`
--
ALTER TABLE `tools_sgs`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT de la tabla `tooltypes`
--
ALTER TABLE `tooltypes`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transmissions`
--
ALTER TABLE `transmissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `materialcategories`
--
ALTER TABLE `materialcategories`
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `materialcategories` (`category_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `fk_mat_cat` FOREIGN KEY (`category_id`) REFERENCES `materialcategories` (`category_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `series`
--
ALTER TABLE `series`
  ADD CONSTRAINT `fk_series_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `strategies`
--
ALTER TABLE `strategies`
  ADD CONSTRAINT `fk_strat_parent` FOREIGN KEY (`parent_id`) REFERENCES `strategies` (`strategy_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_strategies_machining_type` FOREIGN KEY (`machining_type_id`) REFERENCES `machining_types` (`machining_type_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `toolsmaterial_sgs`
--
ALTER TABLE `toolsmaterial_sgs`
  ADD CONSTRAINT `toolsmaterial_sgs_ibfk_1` FOREIGN KEY (`tool_id`) REFERENCES `tools_sgs` (`tool_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `toolsmaterial_sgs_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`material_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `toolstrategy`
--
ALTER TABLE `toolstrategy`
  ADD CONSTRAINT `fk_ts_strategy` FOREIGN KEY (`strategy_id`) REFERENCES `strategies` (`strategy_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
