<?php
// admin/machining_types_delete.php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

$id = $_GET['id'] ?? null;
if ($id) {
  $pdo->prepare("DELETE FROM machining_types WHERE machining_type_id = ?")
      ->execute([$id]);
}
header('Location: index.php');
exit;
