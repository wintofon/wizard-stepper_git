-- Tabla para estrategias (fresado, taladrado y subtipos)
CREATE TABLE Strategies (
    strategy_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Milling','Drilling') DEFAULT 'Milling',
    parent_id INT NULL,
    image VARCHAR(255),
    FOREIGN KEY (parent_id) REFERENCES Strategies(strategy_id) ON DELETE SET NULL
);
