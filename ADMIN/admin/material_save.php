<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['material_id'] ?? null;
  $name = trim($_POST['name']);
  $category_id = $_POST['category_id'];
  $spec_energy = $_POST['spec_energy'] ?: null;

  $image = null;
  if (!empty($_FILES['image']['name'])) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = 'uploads/materials/' . uniqid('mat_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], '../' . $filename);
    $image = $filename;
  }

  if ($id) {
    // update
    if ($image) {
      $stmt = $pdo->prepare("UPDATE materials SET name=?, category_id=?, spec_energy=?, image=? WHERE material_id = ?");
      $stmt->execute([$name, $category_id, $spec_energy, $image, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE materials SET name=?, category_id=?, spec_energy=? WHERE material_id = ?");
      $stmt->execute([$name, $category_id, $spec_energy, $id]);
    }
  } else {
    // insert
    $stmt = $pdo->prepare("INSERT INTO materials (name, category_id, spec_energy, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $category_id, $spec_energy, $image]);
  }

  header('Location: materials.php');
  exit;
}

?>
