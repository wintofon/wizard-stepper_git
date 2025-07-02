/* =====================================================================
   CNC CALCULADOR ▸ ESTRUCTURA NORMALIZADA  (30-04-2025)
     Marca ▸ Serie ▸ Herramienta
   Cuatro tablas de herramientas: tools_sgs | tools_maykestag | tools_schneider | tools_generico
===================================================================== */

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
START TRANSACTION;
SET NAMES utf8mb4;

/* ---------- 1. MARCAS Y SERIES ---------- */
CREATE TABLE IF NOT EXISTS brands (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO brands (id,name) VALUES
(1,'Kyocera SGS'),(2,'Maykestag'),(3,'Schneider Fresas'),(4,'Genérico');

CREATE TABLE IF NOT EXISTS series (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  brand_id INT NOT NULL,
  code     VARCHAR(40) NOT NULL,
  notes    TEXT,
  UNIQUE (brand_id, code),
  CONSTRAINT fk_series_brand
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ---------- 2. TABLAS DE HERRAMIENTAS (una por marca) ---------- */
-- ¤ Plantilla
CREATE TABLE IF NOT EXISTS _tools_template (
  tool_id            INT(11) NOT NULL,
  series_id          INT NOT NULL,
  tool_code          VARCHAR(50) DEFAULT NULL,
  name               VARCHAR(100) DEFAULT NULL,
  flute_count        INT(11) DEFAULT NULL,
  diameter_mm        DECIMAL(7,3) DEFAULT NULL,
  shank_diameter_mm  DECIMAL(7,3) DEFAULT NULL,
  flute_length_mm    DECIMAL(7,3) DEFAULT NULL,
  cut_length_mm      DECIMAL(7,3) DEFAULT NULL,
  full_length_mm     DECIMAL(7,3) DEFAULT NULL,
  rack_angle         DECIMAL(6,2) DEFAULT NULL,
  helix              DECIMAL(6,2) DEFAULT NULL,
  conical_angle      DECIMAL(6,2) NOT NULL DEFAULT 0,
  radius             DECIMAL(7,3) NOT NULL DEFAULT 0,
  tool_type          VARCHAR(50) DEFAULT NULL,
  made_in            VARCHAR(30) DEFAULT NULL,
  material           VARCHAR(50) DEFAULT NULL,
  coated             VARCHAR(11) NOT NULL DEFAULT 'No',
  notes              TEXT DEFAULT NULL,
  image              VARCHAR(255) DEFAULT NULL,
  image_dimensions   VARCHAR(25)  DEFAULT NULL,
  PRIMARY KEY (tool_id),
  KEY tool_type (tool_type),
  CONSTRAINT fk_tool_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ¤ Clones reales
CREATE TABLE IF NOT EXISTS tools_sgs        LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_maykestag  LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_schneider  LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_generico   LIKE _tools_template;

DROP TABLE _tools_template;

/* ---------- 3. CATEGORÍAS Y MATERIALES ---------- */
CREATE TABLE IF NOT EXISTS materialcategories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  parent_id   INT DEFAULT NULL,
  image       VARCHAR(255),
  CONSTRAINT fk_matcat_parent FOREIGN KEY (parent_id) REFERENCES materialcategories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO materialcategories (category_id,name,parent_id,image) VALUES
(1,'Maderas',NULL,'categories/images (1).jpeg'),
(2,'Metales no ferrosos',NULL,'metal.png'),
(3,'Plásticos',NULL,'categories/images (2).jpeg'),
(4,'Maderas blandas',1,'softwood.png'),
(5,'Maderas duras',1,'hardwood.png'),
(22,'Metales',NULL,'');

CREATE TABLE IF NOT EXISTS materials (
  material_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name        VARCHAR(100) NOT NULL,
  spec_energy FLOAT,
  image       VARCHAR(255),
  CONSTRAINT fk_mat_cat FOREIGN KEY (category_id) REFERENCES materialcategories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO materials (material_id,category_id,name,spec_energy,image) VALUES
(1,4,'Pino',15,'pino.jpg'),
(2,4,'MDF',20,'mdf.jpg'),
(3,5,'Guatambú',25,'guatambu.jpg'),
(4,NULL,'Aluminio 6061-T6',10.5,'al6061.jpg'),
(5,3,'Acrílico (PMMA)',18,'materials/images (1).jpeg');

/* ---------- 4. ESTRATEGIAS ---------- */
CREATE TABLE IF NOT EXISTS strategies (
  strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  type        ENUM('Milling','Drilling') NOT NULL DEFAULT 'Milling',
  parent_id   INT DEFAULT NULL,
  image       VARCHAR(255),
  CONSTRAINT fk_strat_parent FOREIGN KEY (parent_id) REFERENCES strategies(strategy_id) ON DELETE SET NULL
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO strategies (strategy_id,name,type,image) VALUES
(1,'Desbaste rápido','Milling','rough.png'),
(2,'Acabado fino','Milling','finish.png'),
(3,'V-Carve','Milling','vcarve.png');

/* ---------- 5. TOOL ↔ MATERIAL ---------- */
CREATE TABLE IF NOT EXISTS toolmaterial (
  tool_material_id INT AUTO_INCREMENT PRIMARY KEY,
  tool_table       ENUM('tools_sgs','tools_maykestag','tools_schneider','tools_generico') NOT NULL,
  tool_id          INT NOT NULL,
  material_id      INT NOT NULL,
  vc_m_min         FLOAT,
  fz_min_mm        FLOAT,
  fz_max_mm        FLOAT,
  ap_slot_mm       FLOAT,
  ae_slot_mm       FLOAT,
  KEY idx_tool (tool_table,tool_id),
  CONSTRAINT fk_tm_material FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ---------- 6. TOOL ↔ STRATEGY ---------- */
CREATE TABLE IF NOT EXISTS toolstrategy (
  tool_strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  tool_table       ENUM('tools_sgs','tools_maykestag','tools_schneider','tools_generico') NOT NULL,
  tool_id          INT NOT NULL,
  strategy_id      INT NOT NULL,
  KEY idx_ts (tool_table,tool_id),
  CONSTRAINT fk_ts_strategy FOREIGN KEY (strategy_id) REFERENCES strategies(strategy_id) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ---------- 7. TIPOS DE HERRAMIENTA ---------- */
CREATE TABLE IF NOT EXISTS tooltypes (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  code    VARCHAR(50) NOT NULL UNIQUE,
  name    VARCHAR(100) NOT NULL,
  description TEXT,
  icon    VARCHAR(255)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO tooltypes (type_id,code,name,description,icon) VALUES
(1,'EM-STRAIGHT','Fresa recta',NULL,NULL),
(2,'EM-UPCUT','UpCut',NULL,NULL),
(3,'EM-DOWNCUT','DownCut','Hélice izquierda, compacta viruta','downcut.svg'),
(4,'BALLNOSE','Ballnose',NULL,NULL),
(5,'V-BIT-90','V-Bit 90°','Grabado y biselados','vbit.svg'),
(15,'EM-SPEEDCUT','Speed Cut',NULL,NULL),
(16,'EM-WOOD','Wood Router',NULL,NULL),
(17,'EM-GEN','General Purpose',NULL,NULL);

/* ---------- 8. TRANSMISIONES ---------- */
CREATE TABLE IF NOT EXISTS transmissions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  coef_security FLOAT NOT NULL DEFAULT 1,
  rpm_min      INT NOT NULL DEFAULT 3000,
  rpm_max      INT NOT NULL DEFAULT 18000,
  feed_max     INT NOT NULL DEFAULT 5000,
  image        VARCHAR(255)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO transmissions (id,name,coef_security,rpm_min,rpm_max,feed_max,image) VALUES
(1,'Bolas recirculantes',0.01,4000,24000,5000,'transmissions/bolas_recirculantes.jpeg'),
(2,'Cremallera helicoidal',1,4000,24000,5000,'helicoidal.png'),
(3,'Cremallera recta',0.9,4000,24000,5000,'recta.png'),
(4,'Correas',0.8,4000,24000,5000,'correas.png'),
(5,'Cadena',0.6,4000,24000,8000,'transmissions/cadena.jpeg');

/* ---------- 9. USUARIOS ---------- */
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO users (user_id,username,password_hash) VALUES
(1,'admin','admin');   -- ¡reemplazá por hash seguro!

COMMIT;
