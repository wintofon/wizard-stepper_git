<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
try {
    $pdo = db();
    echo "✔ Conexión correcta: " . DB_USER . "@" . DB_HOST;
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
