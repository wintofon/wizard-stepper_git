/* ============================================================
   CNC CALCULADOR – ESQUEMA + DATOS DEMO
   Fecha: 2025-04-30
============================================================ */

/* ---------- 0. Crear y seleccionar la base ---------- */
CREATE DATABASE IF NOT EXISTS cnc_calculador
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE cnc_calculador;

/* ===================================================== */
/* 1. TABLAS JERÁRQUICAS: brands  ▸  series              */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS brands (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS series (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  brand_id INT NOT NULL,
  code     VARCHAR(40) NOT NULL,
  notes    TEXT,
  UNIQUE (brand_id, code),
  CONSTRAINT fk_series_brand
    FOREIGN KEY (brand_id) REFERENCES brands (id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 2. PLANTILLA DE HERRAMIENTAS y tablas por marca       */
/* ===================================================== */
CREATE TABLE _tools_template (
  tool_id            INT AUTO_INCREMENT PRIMARY KEY,
  series_id          INT NOT NULL,
  tool_code          VARCHAR(50) NOT NULL,
  name               VARCHAR(120),
  flute_count        TINYINT,
  diameter_mm        DECIMAL(7,3),
  shank_diameter_mm  DECIMAL(7,3),
  flute_length_mm    DECIMAL(7,3),
  cut_length_mm      DECIMAL(7,3),
  full_length_mm     DECIMAL(7,3),
  rack_angle         DECIMAL(6,2),
  helix              DECIMAL(6,2),
  conical_angle      DECIMAL(6,2) DEFAULT 0,
  radius             DECIMAL(7,3) DEFAULT 0,
  tool_type          VARCHAR(50),
  made_in            VARCHAR(30),
  material           VARCHAR(50),
  coated             VARCHAR(20),
  notes              TEXT,
  image              VARCHAR(255),
  image_dimensions   VARCHAR(25),
  KEY tool_type (tool_type),
  CONSTRAINT fk_tool_series
    FOREIGN KEY (series_id) REFERENCES series(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS tools_sgs       LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_maykestag LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_schneider LIKE _tools_template;
CREATE TABLE IF NOT EXISTS tools_generico  LIKE _tools_template;

DROP TABLE _tools_template;

/* ===================================================== */
/* 3. CATEGORÍAS Y MATERIALES                            */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS materialcategories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  parent_id   INT,
  image       VARCHAR(255),
  CONSTRAINT fk_cat_parent
    FOREIGN KEY (parent_id) REFERENCES materialcategories(category_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS materials (
  material_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name        VARCHAR(100) NOT NULL,
  spec_energy FLOAT,
  image       VARCHAR(255),
  CONSTRAINT fk_mat_cat
    FOREIGN KEY (category_id) REFERENCES materialcategories(category_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 4. ESTRATEGIAS                                        */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS strategies (
  strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  type        ENUM('Milling','Drilling') NOT NULL DEFAULT 'Milling',
  parent_id   INT,
  image       VARCHAR(255),
  CONSTRAINT fk_strat_parent
    FOREIGN KEY (parent_id) REFERENCES strategies(strategy_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 5. RELACIONES TOOL ↔ MATERIAL  y  TOOL ↔ STRATEGY     */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS toolmaterial (
  tool_material_id INT AUTO_INCREMENT PRIMARY KEY,
  tool_table ENUM('tools_sgs','tools_maykestag',
                  'tools_schneider','tools_generico') NOT NULL,
  tool_id    INT NOT NULL,
  material_id INT NOT NULL,
  vc_m_min   FLOAT,
  fz_min_mm  FLOAT,
  fz_max_mm  FLOAT,
  ap_slot_mm FLOAT,
  ae_slot_mm FLOAT,
  KEY idx_tm (tool_table, tool_id),
  CONSTRAINT fk_tm_material
    FOREIGN KEY (material_id) REFERENCES materials(material_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS toolstrategy (
  tool_strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  tool_table  ENUM('tools_sgs','tools_maykestag',
                   'tools_schneider','tools_generico') NOT NULL,
  tool_id     INT NOT NULL,
  strategy_id INT NOT NULL,
  KEY idx_ts (tool_table, tool_id),
  CONSTRAINT fk_ts_strategy
    FOREIGN KEY (strategy_id) REFERENCES strategies(strategy_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 6. TIPOS DE HERRAMIENTA                               */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS tooltypes (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  code    VARCHAR(50) NOT NULL UNIQUE,
  name    VARCHAR(100) NOT NULL,
  description TEXT,
  icon    VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 7. TRANSMISIONES & USUARIOS                           */
/* ===================================================== */
CREATE TABLE IF NOT EXISTS transmissions (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  coef_security FLOAT NOT NULL DEFAULT 1,
  rpm_min      INT NOT NULL DEFAULT 3000,
  rpm_max      INT NOT NULL DEFAULT 18000,
  feed_max     INT NOT NULL DEFAULT 5000,
  image        VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                      COLLATE=utf8mb4_general_ci;

/* ===================================================== */
/* 8. DATOS DE EJEMPLO                                   */
/* ===================================================== */
START TRANSACTION;

/* Marcas */
INSERT IGNORE INTO brands (id,name) VALUES
 (1,'Kyocera SGS'),(2,'Maykestag');

/* Series */
INSERT IGNORE INTO series (brand_id,code,notes) VALUES
 (1,'21','Wood Router – UpCut'),
 (1,'22','Wood Router – UpCut'),
 (1,'3M','Fresa uso general 2F'),
 (2,'7725','High-Perf 4F'), (2,'7755','High-Perf 4F'), (2,'6205','High-Perf 1F');

/* Herramientas SGS – series 21,22,3M (extracto) */
INSERT INTO tools_sgs
 (series_id,tool_code,name,flute_count,diameter_mm,shank_diameter_mm,
  cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
-- serie 21
((SELECT id FROM series WHERE code='21' AND brand_id=1),'90101','Wood Router - Up Cut',2,3 ,6 ,13,13,30,20,'EM-WOOD','EEUU','Sin recubrir'),
((SELECT id FROM series WHERE code='21' AND brand_id=1),'90107','Wood Router - Up Cut',2,4 ,6 ,16,16,30,20,'EM-WOOD','EEUU','Sin recubrir'),
((SELECT id FROM series WHERE code='21' AND brand_id=1),'90109','Wood Router - Up Cut',2,5 ,6 ,19,19,30,20,'EM-WOOD','EEUU','Sin recubrir'),
((SELECT id FROM series WHERE code='21' AND brand_id=1),'90113','Wood Router - Up Cut',2,6 ,6 ,25,25,30,20,'EM-WOOD','EEUU','Sin recubrir'),
-- … (agregá aquí el resto si querés todos) …

-- serie 3M (solo tres para ejemplo rápido)
((SELECT id FROM series WHERE code='3M' AND brand_id=1),'48671','Fresa uso general 2F',2,1  ,3 ,4 ,4 ,30,20,'EM-GEN','EEUU','Sin recubrir'),
((SELECT id FROM series WHERE code='3M' AND brand_id=1),'48672','Fresa uso general 2F',2,1.5,3 ,4.5,4.5,30,20,'EM-GEN','EEUU','Sin recubrir'),
((SELECT id FROM series WHERE code='3M' AND brand_id=1),'48673','Fresa uso general 2F',2,2  ,3 ,6.3,6.3,30,20,'EM-GEN','EEUU','Sin recubrir');

/* Herramientas Maykestag – extracto serie 7725 */
INSERT INTO tools_maykestag
 (series_id,tool_code,name,flute_count,diameter_mm,shank_diameter_mm,
  cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
((SELECT id FROM series WHERE code='7725' AND brand_id=2),'7725004001','Fresa High-Perf',4,4,4,2,2,30,20,'EM-GEN','Austria','Sin recubrir'),
((SELECT id FROM series WHERE code='7725' AND brand_id=2),'7725006001','Fresa High-Perf',4,6,6,3,3,30,20,'EM-GEN','Austria','Sin recubrir'),
((SELECT id FROM series WHERE code='7725' AND brand_id=2),'7725008001','Fresa High-Perf',4,8,8,4,4,30,20,'EM-GEN','Austria','Sin recubrir');

/* Categorías y materiales básicos (solo ejemplos) */
INSERT IGNORE INTO materialcategories (category_id,name) VALUES
 (1,'Maderas'),(2,'Metales no ferrosos');

INSERT IGNORE INTO materials (material_id,category_id,name) VALUES
 (1,1,'Pino'),(2,2,'Aluminio 6061');

/* Tooltypes básicos */
INSERT IGNORE INTO tooltypes (code,name) VALUES
 ('EM-WOOD','Wood Router'),('EM-GEN','General Purpose');

/* Transmisiones demo */
INSERT IGNORE INTO transmissions (name) VALUES ('Bolas recirculantes');

/* Usuario admin (contraseña: admin – ¡cambiá luego!) */
INSERT IGNORE INTO users (username,password_hash) VALUES ('admin','admin');

COMMIT;
