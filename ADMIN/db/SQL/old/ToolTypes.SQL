-- Tabla de tipos de herramientas (plana, bola, V-bit, etc.)
CREATE TABLE ToolTypes (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(255)
);
