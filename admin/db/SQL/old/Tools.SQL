-- Tabla principal de fresas
CREATE TABLE Tools (
    tool_id INT AUTO_INCREMENT PRIMARY KEY,
    series VARCHAR(50),
    tool_code VARCHAR(50),
    name VARCHAR(100),
    diameter_mm FLOAT,
    flute_count INT,
    d1_mm FLOAT,
    l2_mm FLOAT,
    overall_length_mm FLOAT,
    shank_diameter_mm FLOAT,
    tool_type VARCHAR(50),
    material VARCHAR(50),
    notes TEXT,
    image VARCHAR(255),
    image_dimensions VARCHAR(255),
    FOREIGN KEY (tool_type) REFERENCES ToolTypes(code) ON DELETE SET NULL
);
