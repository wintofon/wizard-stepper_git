-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-04-2025 a las 19:13:17
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
(1, 'Maderas', NULL, NULL),
(2, 'Metales no ferrosos', NULL, NULL);

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
(1, 1, 'Pino', NULL, NULL),
(2, 2, 'Aluminio 6061', NULL, NULL);

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
(2, 1, '22', 'Wood Router – UpCut'),
(3, 1, '3M', 'Fresa uso general 2F'),
(4, 2, '7725', 'High-Perf 4F'),
(5, 2, '7755', 'High-Perf 4F'),
(6, 2, '6205', 'High-Perf 1F'),
(42, 1, '21M', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `strategies`
--

CREATE TABLE `strategies` (
  `strategy_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Milling','Drilling') NOT NULL DEFAULT 'Milling',
  `parent_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `strategies`
--

INSERT INTO `strategies` (`strategy_id`, `name`, `type`, `parent_id`, `image`) VALUES
(1, 'Desbaste rápido', 'Milling', NULL, NULL),
(2, 'Acabado fino', 'Milling', NULL, NULL),
(3, 'V-Carve', 'Milling', NULL, NULL);

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
  `ae_slot_mm` float DEFAULT NULL
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
  `ae_slot_mm` float DEFAULT NULL
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
  `ae_slot_mm` float DEFAULT NULL
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
  `ae_slot_mm` float DEFAULT NULL
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
(1, 1, '90101', 'Wood Router - Up Cut', 2, 3.000, 6.000, NULL, 13.000, 13.000, 30.00, 20.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(2, 1, '90107', 'Wood Router - Up Cut', 2, 4.000, 6.000, NULL, 16.000, 16.000, 30.00, 20.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(3, 1, '90109', 'Wood Router - Up Cut', 2, 5.000, 6.000, NULL, 19.000, 19.000, 30.00, 20.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(4, 1, '90113', 'Wood Router - Up Cut', 2, 6.000, 6.000, NULL, 25.000, 25.000, 30.00, 20.00, 0.00, 0.000, 'EM-WOOD', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(5, 3, '48671', 'Fresa uso general 2F', 2, 1.000, 3.000, NULL, 4.000, 4.000, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(6, 3, '48672', 'Fresa uso general 2F', 2, 1.500, 3.000, NULL, 4.500, 4.500, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL),
(7, 3, '48673', 'Fresa uso general 2F', 2, 2.000, 3.000, NULL, 6.300, 6.300, 30.00, 20.00, 0.00, 0.000, 'EM-GEN', 'EEUU', NULL, 'Sin recubrir', NULL, NULL, NULL);

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
(1, 'EM-WOOD', 'Wood Router', NULL, NULL),
(2, 'EM-GEN', 'General Purpose', NULL, NULL);

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
(1, 'Bolas recirculantes', 1, 3000, 18000, 5000, NULL);

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
  ADD KEY `fk_strat_parent` (`parent_id`);

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
-- AUTO_INCREMENT de la tabla `materialcategories`
--
ALTER TABLE `materialcategories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `materials`
--
ALTER TABLE `materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de la tabla `strategies`
--
ALTER TABLE `strategies`
  MODIFY `strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `tool_material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `toolstrategy`
--
ALTER TABLE `toolstrategy`
  MODIFY `tool_strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
  MODIFY `tool_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  ADD CONSTRAINT `fk_strat_parent` FOREIGN KEY (`parent_id`) REFERENCES `strategies` (`strategy_id`) ON DELETE SET NULL;

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
