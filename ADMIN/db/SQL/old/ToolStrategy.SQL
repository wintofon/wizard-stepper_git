-- Tabla que une herramientas con estrategias que pueden realizar
CREATE TABLE ToolStrategy (
    tool_strategy_id INT AUTO_INCREMENT PRIMARY KEY,
    tool_id INT NOT NULL,
    strategy_id INT NOT NULL,
    FOREIGN KEY (tool_id) REFERENCES Tools(tool_id) ON DELETE CASCADE,
    FOREIGN KEY (strategy_id) REFERENCES Strategies(strategy_id) ON DELETE CASCADE
);
