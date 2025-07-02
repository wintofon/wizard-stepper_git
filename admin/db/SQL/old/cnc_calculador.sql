/* ===============================================================
   CNC CALCULADOR – ESQUEMA + DATOS DE MUESTRA COMPLETOS
   Fecha: 2025-04-30
=============================================================== */

/* ---------- 0. BASE DE DATOS ---------- */
CREATE DATABASE IF NOT EXISTS cnc_calculador
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cnc_calculador;

/* ---------- 1. MARCAS y SERIES ---------- */
CREATE TABLE IF NOT EXISTS brands (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS series (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  brand_id INT NOT NULL,
  code     VARCHAR(40) NOT NULL,
  notes    TEXT,
  UNIQUE (brand_id, code),
  CONSTRAINT fk_series_brand
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------- 2. PLANTILLA DE HERRAMIENTAS ---------- */
CREATE TABLE _tools_template (
  tool_id           INT AUTO_INCREMENT PRIMARY KEY,
  series_id         INT NOT NULL,
  tool_code         VARCHAR(50) NOT NULL,
  name              VARCHAR(120),
  flute_count       TINYINT,
  diameter_mm       DECIMAL(7,3),
  shank_diameter_mm DECIMAL(7,3),
  cut_length_mm     DECIMAL(7,3),
  full_length_mm    DECIMAL(7,3),
  rack_angle        DECIMAL(6,2),
  helix             DECIMAL(6,2),
  conical_angle     DECIMAL(6,2) DEFAULT 0,
  radius            DECIMAL(7,3) DEFAULT 0,
  tool_type         VARCHAR(50),
  made_in           VARCHAR(30),
  material          VARCHAR(50),
  coated            VARCHAR(20),
  notes             TEXT,
  image             VARCHAR(255),
  image_dimensions  VARCHAR(25),
  KEY tool_type (tool_type),
  CONSTRAINT fk_tool_series
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* 4 tablas por marca */
CREATE TABLE tools_sgs        LIKE _tools_template;
CREATE TABLE tools_maykestag  LIKE _tools_template;
CREATE TABLE tools_schneider  LIKE _tools_template;
CREATE TABLE tools_generico   LIKE _tools_template;
DROP TABLE _tools_template;

/* ---------- 3. MATERIALES y CATEGORÍAS ---------- */
CREATE TABLE IF NOT EXISTS materialcategories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  parent_id   INT,
  image       VARCHAR(255),
  CONSTRAINT fk_matcat_parent
    FOREIGN KEY (parent_id) REFERENCES materialcategories(category_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materials (
  material_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  name        VARCHAR(100) NOT NULL,
  spec_energy FLOAT,
  image       VARCHAR(255),
  CONSTRAINT fk_material_cat
    FOREIGN KEY (category_id) REFERENCES materialcategories(category_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------- 4. ESTRATEGIAS ---------- */
CREATE TABLE IF NOT EXISTS strategies (
  strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  type        ENUM('Milling','Drilling') DEFAULT 'Milling',
  parent_id   INT,
  image       VARCHAR(255),
  CONSTRAINT fk_strategy_parent
    FOREIGN KEY (parent_id) REFERENCES strategies(strategy_id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------- 5. TOOLMATERIAL por MARCA ---------- */
CREATE TABLE toolmaterial_sgs        LIKE materials;
ALTER TABLE toolmaterial_sgs
  DROP PRIMARY KEY,
  ADD tool_material_id INT AUTO_INCREMENT PRIMARY KEY,
  ADD tool_id INT NOT NULL,
  ADD vc_m_min FLOAT, ADD fz_min_mm FLOAT, ADD fz_max_mm FLOAT,
  ADD ap_slot_mm FLOAT, ADD ae_slot_mm FLOAT,
  ADD CONSTRAINT fk_tms_tool FOREIGN KEY (tool_id)
      REFERENCES tools_sgs(tool_id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_tms_mat  FOREIGN KEY (material_id)
      REFERENCES materials(material_id) ON DELETE CASCADE;

CREATE TABLE toolmaterial_maykestag  LIKE toolmaterial_sgs;
ALTER TABLE toolmaterial_maykestag
  DROP FOREIGN KEY fk_tms_tool,
  ADD CONSTRAINT fk_tmm_tool FOREIGN KEY (tool_id)
      REFERENCES tools_maykestag(tool_id) ON DELETE CASCADE;

CREATE TABLE toolmaterial_schneider  LIKE toolmaterial_sgs;
ALTER TABLE toolmaterial_schneider
  DROP FOREIGN KEY fk_tms_tool,
  ADD CONSTRAINT fk_tmsc_tool FOREIGN KEY (tool_id)
      REFERENCES tools_schneider(tool_id) ON DELETE CASCADE;

CREATE TABLE toolmaterial_generico   LIKE toolmaterial_sgs;
ALTER TABLE toolmaterial_generico
  DROP FOREIGN KEY fk_tms_tool,
  ADD CONSTRAINT fk_tmg_tool FOREIGN KEY (tool_id)
      REFERENCES tools_generico(tool_id) ON DELETE CASCADE;

/* ---------- 6. TOOLSTRATEGY (única) ---------- */
CREATE TABLE IF NOT EXISTS toolstrategy (
  tool_strategy_id INT AUTO_INCREMENT PRIMARY KEY,
  tool_table       VARCHAR(40)  NOT NULL,
  tool_id          INT          NOT NULL,
  strategy_id      INT          NOT NULL,
  KEY idx_tt (tool_table, tool_id),
  CONSTRAINT fk_ts_strategy
    FOREIGN KEY (strategy_id) REFERENCES strategies(strategy_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------- 7. TIPOS, TRANSMISIONES, USUARIOS ---------- */
CREATE TABLE IF NOT EXISTS tooltypes (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  code    VARCHAR(50) NOT NULL UNIQUE,
  name    VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transmissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* =======================================================
   DATOS DEMO – 100 % anidados
======================================================= */
START TRANSACTION;

/* --- Marcas */
INSERT IGNORE INTO brands (id,name) VALUES
 (1,'Kyocera SGS'),(2,'Maykestag'),(3,'Schneider Fresas'),(4,'Genérico');

/* --- Series */
INSERT IGNORE INTO series (brand_id,code,notes) VALUES
 (1,'21','Wood Router UpCut'),(1,'3M','Uso general 2F'),
 (2,'7725','Alto rendimiento 4F'),(2,'6205','1 Filo aluminio'),
 (3,'SF','Fresa espiral 2F'), (4,'GEN','Fresa multiuso');

/* --- Herramientas SGS (2) */
INSERT INTO tools_sgs (series_id,tool_code,name,flute_count,diameter_mm,
 shank_diameter_mm,cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
 ((SELECT id FROM series WHERE brand_id=1 AND code='21'),'90129','WR UpCut Ø10',2,10,10,31,31,30,20,'EM-WOOD','USA','Sin recubrir'),
 ((SELECT id FROM series WHERE brand_id=1 AND code='3M'),'48671','General 2F Ø1',2,1,3,4,4,30,20,'EM-GEN','USA','Sin recubrir');

/* --- Herramientas Maykestag (2) */
INSERT INTO tools_maykestag (series_id,tool_code,name,flute_count,diameter_mm,
 shank_diameter_mm,cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
 ((SELECT id FROM series WHERE code='7725'),'7725008001','HP 4F Ø8',4,8,8,4,4,30,20,'EM-GEN','Austria','Sin recubrir'),
 ((SELECT id FROM series WHERE code='6205'),'6205002001','MonoF Ø2 Alu',1,2,2,10,10,30,20,'EM-GEN','Austria','Sin recubrir');

/* --- Herramientas Schneider (1) */
INSERT INTO tools_schneider (series_id,tool_code,name,flute_count,diameter_mm,
 shank_diameter_mm,cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
 ((SELECT id FROM series WHERE code='SF'),'SF040','Espiral 2F Ø4',2,4,4,12,40,35,30,'EM-GEN','Alemania','TiAlN');

/* --- Herramientas Genérico (1) */
INSERT INTO tools_generico (series_id,tool_code,name,flute_count,diameter_mm,
 shank_diameter_mm,cut_length_mm,full_length_mm,rack_angle,helix,tool_type,made_in,coated)
VALUES
 ((SELECT id FROM series WHERE code='GEN'),'GEN060','Fresa gen Ø6',2,6,6,16,50,30,20,'EM-GEN','-', 'Sin recubrir');

/* --- Categorías y materiales */
INSERT IGNORE INTO materialcategories (category_id,name) VALUES
 (1,'Maderas'),(2,'Metales no ferrosos');
INSERT IGNORE INTO materials (material_id,category_id,name) VALUES
 (1,1,'Pino'),(2,2,'Aluminio 6061');

/* --- Estrategias */
INSERT IGNORE INTO strategies (strategy_id,name) VALUES
 (1,'Desbaste rápido'),(2,'Acabado fino'),(3,'V-Carve');

/* --- ToolMaterial anidados (al menos 1 por tabla) */
INSERT INTO toolmaterial_sgs
(tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
VALUES
 ((SELECT tool_id FROM tools_sgs WHERE tool_code='90129'),1,190,0.05,0.12,6,1.2);

INSERT INTO toolmaterial_maykestag
(tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
VALUES
 ((SELECT tool_id FROM tools_maykestag WHERE tool_code='7725008001'),2,270,0.02,0.04,2,0.5);

INSERT INTO toolmaterial_schneider
(tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
VALUES
 ((SELECT tool_id FROM tools_schneider WHERE tool_code='SF040'),2,220,0.02,0.05,2,0.4);

INSERT INTO toolmaterial_generico
(tool_id,material_id,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
VALUES
 ((SELECT tool_id FROM tools_generico WHERE tool_code='GEN060'),1,150,0.03,0.06,4,0.8);

/* --- ToolStrategy (un par por marca) */
INSERT INTO toolstrategy (tool_table,tool_id,strategy_id) VALUES
 ('tools_sgs',       (SELECT tool_id FROM tools_sgs       WHERE tool_code='90129'),1),
 ('tools_maykestag', (SELECT tool_id FROM tools_maykestag WHERE tool_code='6205002001'),2),
 ('tools_schneider', (SELECT tool_id FROM tools_schneider WHERE tool_code='SF040'),1),
 ('tools_generico',  (SELECT tool_id FROM tools_generico  WHERE tool_code='GEN060'),2);

/* --- Tooltypes, Transmissions, Users de prueba */
INSERT IGNORE INTO tooltypes (code,name) VALUES ('EM-GEN','General Purpose');
INSERT IGNORE INTO transmissions (name) VALUES ('Bolas recirculantes');
INSERT IGNORE INTO users (username,password_hash) VALUES ('admin','admin');

COMMIT;
