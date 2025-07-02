<?php
// admin/strategy_save.php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

$name = trim($_POST['name']);
$mtid = $_POST['machining_type_id'] ?: null;

// Manejo de imagen
$image = $_POST['old_image'] ?? '';
if (!empty($_FILES['image']['name'])) {
  $dest = 'uploads/strategies/'.basename($_FILES['image']['name']);
  move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/'.$dest);
  $image = $dest;
}

if (!empty($_POST['strategy_id'])) {
  // UPDATE
  $stmt = $pdo->prepare("
    UPDATE strategies
       SET name = ?, machining_type_id = ?, image = ?
     WHERE strategy_id = ?
  ");
  $stmt->execute([$name,$mtid,$image,$_POST['strategy_id']]);
} else {
  // INSERT
  $stmt = $pdo->prepare("
    INSERT INTO strategies (name,machining_type_id,image)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$name,$mtid,$image]);
}

header('Location: strategies.php');
exit;
