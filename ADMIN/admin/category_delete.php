<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: categories.php');
    exit;
}

$category_id = $_GET['id'];

// Eliminar la categoría
$pdo->prepare("DELETE FROM MaterialCategories WHERE category_id = ?")->execute([$category_id]);

header('Location: categories.php');
exit;
