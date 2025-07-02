<?php
// admin/strategy_delete.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$id = $_GET['id'] ?? null;
if ($id) {
  $pdo->prepare("DELETE FROM strategies WHERE strategy_id = ?")
      ->execute([$id]);
}
header('Location: strategies.php');
exit;
