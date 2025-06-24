<?php
class ConfigModel {
    public static function getKc11($pdo, $material) {
        $stmt = $pdo->prepare("SELECT spec_energy FROM materials WHERE material_id = ?");
        $stmt->execute([$material]);
        return (float)($stmt->fetchColumn() ?: 31.44);
    }

    public static function getCoefSeg($pdo, $trans_id) {
        $stmt = $pdo->prepare("SELECT coef_security FROM transmissions WHERE id = ?");
        $stmt->execute([$trans_id]);
        return (float)$stmt->fetchColumn();
    }
}
?>
