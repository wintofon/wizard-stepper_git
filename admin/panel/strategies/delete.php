<?php
// admin/strategy_delete.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$id = $_GET['id'] ?? null;
if ($id) {
  $pdo->prepare("DELETE FROM strategies WHERE strategy_id = ?")
      ->execute([$id]);
}
header('Location: index.php');
exit;
