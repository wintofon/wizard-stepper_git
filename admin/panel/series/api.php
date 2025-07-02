<?php
/** Devuelve [{id, code, notes}] para una brand_id GET */
header('Content-Type: application/json');

require_once '../../includes/db.php';

$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;
if (!$brandId) { echo json_encode([]); exit; }

$q = $pdo->prepare("SELECT id, code, notes FROM series WHERE brand_id = ? ORDER BY code");
$q->execute([$brandId]);
echo json_encode($q->fetchAll(PDO::FETCH_ASSOC));
