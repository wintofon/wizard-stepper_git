-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-05-2025 a las 20:24:43
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
(2, 'Maykestag');

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
(4, 'Maderas No Naturales', NULL, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materials`
--

CREATE TABLE `materials` (
  `material_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `spec_energy` float DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `materials`
--

INSERT INTO `materials` (`material_id`, `category_id`, `name`, `spec_energy`, `image`) VALUES
(3, 1, 'Genérico Madera Dura', 0, 'materials/164496.png'),
(4, 1, 'Genérico Madera Media', 0, NULL),
(5, 1, 'Genérico Madera Blanda', 0, NULL),
(6, 3, 'Genérico Plástico', 0, NULL),
(7, 4, 'Genérico Contrachapado / Terciado', NULL, NULL),
(8, 1, 'Genérico Fibrofácil (MDF)', NULL, NULL);

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
(4, 1, '22M', 'Wood Router – DownCut');

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

--
-- Volcado de datos para la tabla `toolsmaterial_sgs`
--

INSERT INTO `toolsmaterial_sgs` (`tool_material_id`, `tool_id`, `material_id`, `vc_m_min`, `fz_min_mm`, `fz_max_mm`, `ap_slot_mm`, `ae_slot_mm`, `rating`) VALUES
(182, 22, 8, 200, 0.1, 0.2, 1, 1, 0),
(183, 23, 8, 200, 0.11, 0.21, 1, 1, 0),
(184, 24, 8, 200, 0.12, 0.22, 1, 1, 0),
(185, 25, 8, 200, 0.13, 0.23, 1, 1, 0),
(186, 26, 8, 200, 0.14, 0.24, 1, 1, 0),
(187, 27, 8, 200, 0.15, 0.25, 1, 1, 0);

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
(151, 'tools_sgs', 22, 1),
(152, 'tools_sgs', 22, 2),
(153, 'tools_sgs', 22, 3),
(154, 'tools_sgs', 23, 1),
(155, 'tools_sgs', 23, 2),
(156, 'tools_sgs', 23, 3),
(157, 'tools_sgs', 24, 1),
(158, 'tools_sgs', 24, 2),
(159, 'tools_sgs', 24, 3),
(160, 'tools_sgs', 25, 1),
(161, 'tools_sgs', 25, 2),
(162, 'tools_sgs', 25, 3),
(163, 'tools_sgs', 26, 1),
(164, 'tools_sgs', 26, 2),
(165, 'tools_sgs', 26, 3),
(166, 'tools_sgs', 27, 1),
(167, 'tools_sgs', 27, 2),
(168, 'tools_sgs', 27, 3);

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
(3, 4, '7725008001', 'Fresa High-Perf', 4, 8.000, 8.000, NULL, 4.000, 4.000, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'Austria', NULL, 'Sin recubrir', NULL, NULL, NULL);

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
  `image_dimensions` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tools_sgs`
--

INSERT INTO `tools_sgs` (`tool_id`, `series_id`, `tool_code`, `name`, `flute_count`, `diameter_mm`, `shank_diameter_mm`, `flute_length_mm`, `cut_length_mm`, `full_length_mm`, `rack_angle`, `helix`, `conical_angle`, `radius`, `tool_type`, `made_in`, `material`, `coated`, `notes`, `image`, `image_dimensions`) VALUES
(8, 4, '90001', '', 2, 3.175, 6.350, 0.000, 12.700, 50.800, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(9, 4, '90005', '', 2, 3.969, 6.350, 0.000, 15.875, 63.500, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(10, 4, '90009', '', 2, 4.763, 6.350, 0.000, 19.050, 63.500, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(11, 4, '90013', '', 2, 6.350, 6.350, 0.000, 19.050, 63.500, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(12, 4, '90017', '', 2, 6.350, 6.350, 0.000, 25.400, 63.500, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(13, 4, '90021', '', 2, 7.938, 7.938, 0.000, 25.400, 63.500, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(14, 4, '90025', '', 2, 7.938, 12.700, 0.000, 25.400, 76.200, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(15, 4, '90029', 'j', 2, 9.525, 9.525, NULL, 25.400, 63.500, NULL, 30.00, NULL, NULL, 'EM-SQUARE', 'EEUU', '', 'Sin recubrir', NULL, NULL, NULL),
(16, 4, '90033', '', 2, 9.525, 12.700, 0.000, 31.750, 76.200, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(17, 4, '90037', '', 2, 12.700, 12.700, 0.000, 31.750, 76.200, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(18, 4, '90041', '', 2, 12.700, 12.700, 0.000, 38.100, 88.900, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(19, 4, '90045', '', 2, 12.700, 12.700, 0.000, 50.800, 101.600, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(20, 4, '90049', '', 2, 15.875, 15.875, 0.000, 50.800, 114.300, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(21, 4, '90053', '', 2, 19.050, 19.050, 0.000, 50.800, 114.300, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', 'assets/img/tools/SGS_21M_VECTOR.png', ''),
(22, 1, '90101', '', 2, 3.000, 6.000, 0.000, 13.000, 50.000, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '10', 'Sin recubrir', '', 'aaa', ''),
(23, 1, '90107', '', 2, 4.000, 6.000, 0.000, 16.000, 63.000, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(24, 1, '90109', '', 2, 5.000, 6.000, 0.000, 19.000, 63.000, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(25, 1, '90113', '', 2, 6.000, 6.000, 0.000, 25.000, 63.000, 30.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(26, 1, '90121', '', 2, 8.000, 8.000, 0.000, 25.000, 63.000, 0.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'EEUU', '', 'Sin recubrir', '', '', ''),
(27, 1, '90129', '', 2, 10.000, 10.000, 0.000, 31.000, 75.000, 0.00, 30.00, 0.00, 0.000, 'EM-SQUARE', 'EEUU', '', 'Sin recubrir', '', '', ''),
(28, 3, '90137', '', 2, 12.000, 12.000, 0.000, 31.000, 75.000, 0.00, 30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(29, 5, '91001', 'Wood Router – Down Cut', 2, 3.175, 6.350, 0.000, 12.700, 50.800, NULL, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', NULL, 'assets/img/tools/SGS_22M_VECTOR.png', NULL),
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
(43, 4, '91101', '', 2, 3.000, 6.000, 0.000, 13.000, 50.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(44, 4, '91107', '', 2, 4.000, 6.000, 0.000, 16.000, 63.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(45, 4, '91109', '', 2, 5.000, 6.000, 0.000, 19.000, 63.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(46, 4, '91113', '', 2, 6.000, 6.000, 0.000, 25.000, 63.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(47, 4, '91121', '', 2, 8.000, 8.000, 0.000, 25.000, 63.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(48, 4, '91129', '', 2, 10.000, 10.000, 0.000, 31.000, 75.000, 0.00, -30.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', '', 'Sin recubrir', '', '', ''),
(49, 4, '91137', 'a', 2, 12.000, 12.000, NULL, 31.000, 75.000, NULL, -30.00, NULL, NULL, 'EM-SQUARE', 'EEUU', '', 'Sin recubrir', NULL, NULL, NULL);

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
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `transmissions`
--

INSERT INTO `transmissions` (`id`, `name`, `coef_security`, `rpm_min`, `rpm_max`, `feed_max`, `image`) VALUES
(1, 'Bolas recirculantes', 1, 3000, 18000, 5000, 'transmissions/bolas_recirculantes.jpg');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `machining_types`
--
ALTER TABLE `machining_types`
  MODIFY `machining_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `materialcategories`
--
ALTER TABLE `materialcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

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
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT de la tabla `toolstrategy`
--
ALTER TABLE `toolstrategy`
  MODIFY `tool_strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT de la tabla `tools_generico`
--
ALTER TABLE `tools_generico`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tools_maykestag`
--
ALTER TABLE `tools_maykestag`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tools_schneider`
--
ALTER TABLE `tools_schneider`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tools_sgs`
--
ALTER TABLE `tools_sgs`
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `tooltypes`
--
ALTER TABLE `tooltypes`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transmissions`
--
ALTER TABLE `transmissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
