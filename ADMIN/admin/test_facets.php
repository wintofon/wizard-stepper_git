<?php
require_once '../includes/db.php';
echo json_encode($pdo->query("SELECT DISTINCT diameter_mm FROM tools_sgs LIMIT 5")->fetchAll(PDO::FETCH_COLUMN));
