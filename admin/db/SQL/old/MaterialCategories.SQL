-- Tabla para categorías de materiales (nivel raíz y subniveles)
CREATE TABLE MaterialCategories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT NULL,
    image VARCHAR(255),
    FOREIGN KEY (parent_id) REFERENCES MaterialCategories(category_id) ON DELETE SET NULL
);
