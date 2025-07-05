<?php
/**
 * Modelo SeriesModel: operaciones sobre series y sus herramientas.
 */
declare(strict_types=1);

class SeriesModel
{
    /**
     * Actualiza masivamente una columna permitida en todas las
     * herramientas de la serie indicada.
     *
     * @param PDO    $pdo       Conexión a base de datos
     * @param string $column    Columna a modificar
     * @param mixed  $value     Nuevo valor
     * @param int    $seriesId  ID de la serie
     * @return bool             Éxito de la operación
     */
    public static function bulkColumn(PDO $pdo, string $column, $value, int $seriesId): bool
    {
        $allowed = ['made_in','material','tool_type','coated'];
        if (!in_array($column, $allowed, true)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT brand_id FROM series WHERE id = ?');
        $stmt->execute([$seriesId]);
        $brandId = $stmt->fetchColumn();
        if (!$brandId) {
            return false;
        }
        $table = brandTable((int)$brandId);
        $sql = "UPDATE {$table} SET {$column} = :val WHERE series_id = :sid";
        $u = $pdo->prepare($sql);
        return $u->execute([':val' => $value, ':sid' => $seriesId]);
    }
}
