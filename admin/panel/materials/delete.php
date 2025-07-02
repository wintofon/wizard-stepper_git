<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$material_id = $_GET['id'];

$pdo->prepare("DELETE FROM materials WHERE material_id = ?")
    ->execute([$material_id]);

header('Location: index.php');
exit;
