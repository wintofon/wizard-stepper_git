<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
  header("Location: transmissions.php");
  exit;
}

$stmt = $pdo->prepare("DELETE FROM transmissions WHERE id = ?");
$stmt->execute([$id]);

header("Location: transmissions.php");
exit;
