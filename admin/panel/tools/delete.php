<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

/* ── validar parámetros ─────────────────── */
$tbl = $_GET['tbl'] ?? '';
$id  = $_GET['id']  ?? '';

if (!$tbl || !$id) {
    header('Location: ../dashboard.php'); exit;
}

/* ── nombres auxiliares ─────────────────── */
$mtbl = 'toolsmaterial_' . substr($tbl, 6);   // sgs, maykestag, …

/* ── eliminar relaciones ────────────────── */
$pdo->prepare("DELETE FROM toolstrategy WHERE tool_table = ? AND tool_id = ?")
    ->execute([$tbl, $id]);

$pdo->prepare("DELETE FROM $mtbl WHERE tool_id = ?")->execute([$id]);

/* ── eliminar herramienta ───────────────── */
$pdo->prepare("DELETE FROM $tbl WHERE tool_id = ?")->execute([$id]);

header('Location: ../dashboard.php');
exit;
