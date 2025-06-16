<?php
class ToolModel {
    public static function getTool($pdo, $tbl, $tool_id) {
        $stmt = $pdo->prepare("
            SELECT t.*, s.code AS serie, b.name AS brand
            FROM {$tbl} t
            JOIN series s ON t.series_id = s.id
            JOIN brands b ON s.brand_id = b.id
            WHERE t.tool_id = ?
        ");
        $stmt->execute([$tool_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getMaterialData($pdo, $matTbl, $tool_id, $material) {
        $stmt = $pdo->prepare("
            SELECT vc_m_min, fz_min_mm, fz_max_mm, ap_slot_mm, ae_slot_mm
            FROM {$matTbl}
            WHERE tool_id = ? AND material_id = ?
        ");
        $stmt->execute([$tool_id, $material]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
?>
