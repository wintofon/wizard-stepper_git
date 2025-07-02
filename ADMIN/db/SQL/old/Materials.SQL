-- Tabla de materiales individuales
CREATE TABLE Materials (
    material_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    spec_energy FLOAT, -- Energía específica de corte
    image VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES MaterialCategories(category_id) ON DELETE SET NULL
);
