<?php
// ✅ [REFACTORED] Cambiado de ubicación a /main/admin/panel/materials – actualizado paths
require_once '../../../../admin/includes/db.php';
require_once '../../../../admin/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['material_id'] ?? null;
  $name = trim($_POST['name']);
  $category_id = $_POST['category_id'];
  $kc11       = $_POST['kc11'] ?: null;
  $mc         = $_POST['mc'] ?: null;
  $angle_ramp = $_POST['angle_ramp'] ?: null;

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'uploads/materials/' . uniqid('mat_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], '../../../../admin/' . $filename);
    $image = $filename;
  }

  if ($id) {
    // update
    if ($image) {
      $stmt = $pdo->prepare("UPDATE materials SET name=?, category_id=?, kc11=?, mc=?, angle_ramp=?, image=? WHERE material_id = ?");
      $stmt->execute([$name, $category_id, $kc11, $mc, $angle_ramp, $image, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE materials SET name=?, category_id=?, kc11=?, mc=?, angle_ramp=? WHERE material_id = ?");
      $stmt->execute([$name, $category_id, $kc11, $mc, $angle_ramp, $id]);
    }
  } else {
    // insert
    $stmt = $pdo->prepare("INSERT INTO materials (name, category_id, kc11, mc, angle_ramp, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category_id, $kc11, $mc, $angle_ramp, $image]);
  }

  header('Location: materials.php');
  exit;
}

?>
