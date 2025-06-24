<?php
/**
 * Modelo ToolModel – Interacción con tablas de herramientas y sus parámetros de corte.
 * - getTool(PDO, tabla, tool_id): obtiene datos básicos de una herramienta.
 * - getMaterialData(PDO, tablaMaterial, tool_id, material_id): obtiene datos de corte recomendados para la combinación herramienta-material.
 */
declare(strict_types=1);

class ToolModel
{
    /**
     * Retorna datos básicos de una herramienta (por ID) en la tabla dada.
     * La tabla corresponde a tools_sgs, tools_maykestag, etc según marca.
     *
     * @param PDO    $pdo
     * @param string $tbl      Nombre de la tabla de herramientas (ej: "tools_sgs")
     * @param int    $tool_id  ID de la herramienta
     * @return array|null Arreglo asociativo de datos de la herramienta, o null si no existe.
     */
    public static function getTool(PDO $pdo, string $tbl, int $tool_id): ?array
    {
        // Construir consulta uniendo con series (serie) y brands (marca)
        $sql = "
            SELECT
              t.tool_id,
              t.series_id,
              t.tool_code,
              t.name,
              t.flute_count,
              t.diameter_mm,
              t.shank_diameter_mm,
              t.flute_length_mm,
              t.cut_length_mm,
              t.full_length_mm,
              t.rack_angle,
              t.helix,
              t.tool_type,
              t.made_in,
              t.material,
              t.coated,
              t.notes,
              t.image,
              t.image_dimensions,
              t.tool_type as tool_type_code,
              s.code      AS serie,
              b.name      AS brand
            FROM {$tbl} AS t
            JOIN series AS s   ON t.series_id = s.id
            JOIN brands AS b   ON s.brand_id  = b.id
            WHERE t.tool_id = ?
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tool_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Retorna datos de corte recomendados para una herramienta y material específicos,
     * desde la tabla de intersección toolsmaterial_<brand>.
     *
     * @param PDO    $pdo
     * @param string $matTbl      Nombre de la tabla de parámetros (ej: "toolsmaterial_sgs")
     * @param int    $tool_id     ID de la herramienta
     * @param int    $material_id ID del material
     * @return array|null Arreglo con vc_m_min, fz_min_mm, fz_max_mm, ap_slot_mm, ae_slot_mm; null si no hay registro.
     */
    public static function getMaterialData(PDO $pdo, string $matTbl, int $tool_id, int $material_id): ?array
    {
        $sql = "
            SELECT
              vc_m_min,
              fz_min_mm,
              fz_max_mm,
              ap_slot_mm,
              ae_slot_mm,
              rating
            FROM {$matTbl}
            WHERE tool_id = ? AND material_id = ?
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tool_id, $material_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
